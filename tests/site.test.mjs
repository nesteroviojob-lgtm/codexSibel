import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createSibelRuntime } from '../scripts/runtime.mjs';

test('WordPress site and content management', { timeout: 180000 }, async (t) => {
    const stateDir = `.runtime/integration-${process.pid}`;
    const siteUrl = 'http://127.0.0.1:9499';
    const runtime = await createSibelRuntime({ siteUrl, stateDir });
    const run = async (code) => {
        const r = await runtime.php.run({ code: `<?php require '/wordpress/wp-load.php'; ${code}` });
        assert.equal(r.exitCode, 0, r.errors);
        return r.text;
    };
    const request = (url, options = {}) => runtime.handler.request({ url: siteUrl + url, ...options });
    const post = (url, data, cookie = '') =>
        request(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                ...(cookie ? { Cookie: cookie } : {}),
            },
            body: Buffer.from(new URLSearchParams(data).toString()),
        });
    let cookie = '';
    try {
        await t.test('seed is complete and does not duplicate content', async () => {
            const code = `sibel_seed(); echo json_encode(array_map(fn($t) => (int)wp_count_posts($t)->publish, ['sibel_product','sibel_project','sibel_certificate','sibel_partner']));`;
            assert.deepEqual(JSON.parse(await run(code)), [10, 4, 8, 5]);
            assert.deepEqual(JSON.parse(await run(code)), [10, 4, 8, 5]);
        });
        await t.test('all design pages render with local asset references', async () => {
            for (const [url, title] of [
                ['/', 'Производственная электротехническая компания'],
                ['/products/', 'Продукция'],
                ['/products/ktp/', 'Комплектные трансформаторные подстанции'],
                ['/projects/', 'Реализованные проекты'],
                ['/projects/rosneft/', 'Производство и поставка КТП'],
                ['/certificates/', 'Сертификаты'],
                ['/contacts/', 'Контакты'],
            ]) {
                const r = await request(url);
                assert.equal(r.httpStatusCode, 200, url + ': ' + r.errors);
                assert.ok(r.text.includes(title), url);
                assert.match(r.text, /<main[^>]*id="main"/);
                assert.doesNotMatch(r.text, /https:\/\/www\.figma\.com\/api\/mcp\/asset/);
                assert.doesNotMatch(r.text, /Fatal error|Warning:|critical error/);
            }
        });
        await t.test('admin rejects anonymous access and accepts a valid WordPress login', async () => {
            const denied = await request('/wp-admin/admin.php?page=sibel');
            assert.equal(denied.httpStatusCode, 302);
            const login = await post(
                '/wp-login.php',
                {
                    log: runtime.credentials.username,
                    pwd: runtime.credentials.password,
                    'wp-submit': 'Log In',
                    redirect_to: siteUrl + '/wp-admin/',
                    testcookie: '1',
                },
                'wordpress_test_cookie=WP%20Cookie%20check',
            );
            assert.equal(login.httpStatusCode, 302, login.text);
            cookie = (login.headers['set-cookie'] || []).map((c) => c.split(';')[0]).join('; ');
            assert.match(cookie, /wordpress_logged_in_/);
            const dashboard = await request('/wp-admin/admin.php?page=sibel', {
                headers: { Cookie: cookie },
            });
            assert.equal(dashboard.httpStatusCode, 200);
            assert.ok(dashboard.text.includes('Всё под рукой'));
        });
        await t.test('settings form persists edits and updates the public contact page', async () => {
            const form = await request('/wp-admin/admin.php?page=sibel-settings', {
                headers: { Cookie: cookie },
            });
            assert.equal(form.httpStatusCode, 200);
            const nonce = form.text.match(/name="_wpnonce" value="([^"]+)"/)[1];
            const values = JSON.parse(await run("echo json_encode(get_option('sibel_settings'));"));
            const data = { action: 'sibel_save_settings', _wpnonce: nonce };
            for (const [key, value] of Object.entries(values)) data[`settings[${key}]`] = value;
            data['settings[email]'] = 'test@example.org';
            const save = await post('/wp-admin/admin-post.php', data, cookie);
            assert.equal(save.httpStatusCode, 302, save.text);
            const contacts = await request('/contacts/');
            assert.match(contacts.text, /mailto:test@example\.org/);
            assert.equal(await run("echo sibel_setting('email');"), 'test@example.org');
            const bad = await post(
                '/wp-admin/admin-post.php',
                {
                    action: 'sibel_save_settings',
                    _wpnonce: 'invalid',
                    'settings[email]': 'wrong@example.org',
                },
                cookie,
            );
            assert.equal(bad.httpStatusCode, 403);
            assert.equal(await run("echo sibel_setting('email');"), 'test@example.org');
        });
        await t.test('product fields, draft visibility and gallery edits persist', async () => {
            const result = JSON.parse(
                await run(`
                wp_set_current_user(1);
                $_POST = ['sibel_meta_nonce' => wp_create_nonce('sibel_save_meta'), 'sibel_meta' => ['short' => '<b>Тест</b>', 'features' => "Характеристика 1\\nХарактеристика 2", 'document' => 'javascript:alert(1)', 'gallery' => '999999'], 'sibel_clear_gallery' => '1'];
                $id = wp_insert_post(['post_type'=>'sibel_product','post_title'=>'Тестовый товар','post_name'=>'integration-product','post_status'=>'draft']);
                echo json_encode(['id'=>$id,'short'=>get_post_meta($id,'_sibel_short',true),'features'=>get_post_meta($id,'_sibel_features',true),'document'=>get_post_meta($id,'_sibel_document',true),'gallery'=>get_post_meta($id,'_sibel_gallery',true)]);
            `),
            );
            assert.equal(result.short, 'Тест');
            assert.match(result.features, /Характеристика 2/);
            assert.equal(result.document, '');
            assert.equal(result.gallery, '');
            assert.equal((await request('/products/integration-product/')).httpStatusCode, 404);
            await run(`wp_update_post(['ID'=>${result.id},'post_status'=>'publish']);`);
            const published = await request('/products/integration-product/');
            assert.equal(published.httpStatusCode, 200);
            assert.ok(published.text.includes('Тестовый товар'));
            await run(
                `wp_set_current_user(1); $_POST=['sibel_meta_nonce'=>'invalid','sibel_meta'=>['short'=>'Injected']]; wp_update_post(['ID'=>${result.id},'post_title'=>'Тестовый товар']); echo get_post_meta(${result.id},'_sibel_short',true);`,
            ).then((value) => assert.equal(value, 'Тест'));
        });
        await t.test(
            'lead form validates input, protects private data and rate limits submissions',
            async () => {
                const home = await request('/');
                const config = JSON.parse(home.text.match(/var sibelConfig = (\{[^;]+\});/)[1]);
                const valid = {
                    action: 'sibel_lead',
                    nonce: config.nonce,
                    name: 'Проверка формы',
                    company: '<b>Компания клиента</b>',
                    phone: '+7 999 123-45-67',
                    email: 'client@example.org',
                    consent: 'on',
                    product: 'КТП',
                };
                const initial = Number(await run("echo (int)wp_count_posts('sibel_lead')->private;"));
                const invalid = await post('/wp-admin/admin-ajax.php', { ...valid, nonce: 'invalid' });
                assert.equal(invalid.httpStatusCode, 403);
                const noConsent = await post('/wp-admin/admin-ajax.php', { ...valid, consent: '' });
                assert.equal(noConsent.httpStatusCode, 422);
                const badEmail = await post('/wp-admin/admin-ajax.php', { ...valid, email: 'not-an-email' });
                assert.equal(badEmail.httpStatusCode, 422);
                const longCompany = await post('/wp-admin/admin-ajax.php', {
                    ...valid,
                    company: 'Я'.repeat(151),
                });
                assert.equal(longCompany.httpStatusCode, 422);
                const arrayCompany = await post('/wp-admin/admin-ajax.php', {
                    ...valid,
                    'company[]': 'Некорректное поле',
                });
                assert.equal(arrayCompany.httpStatusCode, 400);
                await post('/wp-admin/admin-ajax.php', { ...valid, website: 'spam.example.org' });
                assert.equal(Number(await run("echo (int)wp_count_posts('sibel_lead')->private;")), initial);
                for (let i = 0; i < 5; i++) {
                    const saved = await post('/wp-admin/admin-ajax.php', valid);
                    assert.equal(saved.httpStatusCode, 200, saved.text);
                    assert.equal(JSON.parse(saved.text).success, true);
                }
                assert.equal(
                    Number(await run("echo (int)wp_count_posts('sibel_lead')->private;")),
                    initial + 5,
                );
                assert.equal((await post('/wp-admin/admin-ajax.php', valid)).httpStatusCode, 429);
                const access = JSON.parse(
                    await run(
                        "$id=wp_insert_user(['user_login'=>'integration_reader','user_pass'=>'unused-test-password','role'=>'subscriber']); echo json_encode([user_can($id,'manage_sibel'), user_can($id,'edit_posts')]);",
                    ),
                );
                assert.deepEqual(access, [false, false]);
                const privateId = await run(
                    "$p=get_posts(['post_type'=>'sibel_lead','post_status'=>'private','numberposts'=>1]); echo $p[0]->ID;",
                );
                const publicRequest = await request('/?post_type=sibel_lead&p=' + privateId);
                assert.ok(!publicRequest.text.includes('client@example.org'));
                assert.ok(!publicRequest.text.includes('Компания клиента'));
                assert.equal(
                    await run(`echo get_post_meta(${Number(privateId)}, '_sibel_company', true);`),
                    'Компания клиента',
                );
                const leadEditor = await run(
                    `wp_set_current_user(1); ob_start(); sibel_lead_meta_box(get_post(${Number(privateId)})); echo ob_get_clean();`,
                );
                assert.match(leadEditor, /Компания клиента/);
            },
        );
    } finally {
        await runtime.close();
        fs.rmSync(runtime.state, { recursive: true, force: true });
        fs.rmSync(runtime.credentialsFile, { force: true });
    }
});
