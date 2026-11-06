import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createSibelRuntime } from '../scripts/runtime.mjs';
import { installHomepagePhoto, restoreHomepagePhoto } from '../scripts/repair-home-photo.mjs';

test('bundled photo replaces an invalid file, preserves a backup and rejects a modified download', (t) => {
    const root = fs.mkdtempSync(path.join(os.tmpdir(), 'sibel-photo-fix-'));
    t.after(() => fs.rmSync(root, { recursive: true, force: true }));
    const source = fileURLToPath(
        new URL('../wordpress/wp-content/themes/sibel/assets/images/d9626.png', import.meta.url),
    );
    fs.mkdirSync(path.join(root, '.runtime'));
    fs.copyFileSync(source, path.join(root, 'd9626.png'));
    const target = path.join(root, 'wordpress/wp-content/themes/sibel/assets/images/d9626.png');
    fs.mkdirSync(path.dirname(target), { recursive: true });
    fs.writeFileSync(target, 'old invalid image');
    installHomepagePhoto(root);
    assert.deepEqual(fs.readFileSync(target), fs.readFileSync(source));
    assert.equal(
        fs.readFileSync(path.join(root, '.runtime/hero-image-before-repair.bin'), 'utf8'),
        'old invalid image',
    );
    installHomepagePhoto(root);
    fs.writeFileSync(path.join(root, 'd9626.png'), 'not the original');
    assert.throws(() => installHomepagePhoto(root), /отличается от оригинала/);
    assert.deepEqual(fs.readFileSync(target), fs.readFileSync(source));
});

test(
    'photo repair changes only the selected home image and preserves its original selection on retry',
    { timeout: 120000 },
    async () => {
        const runtime = await createSibelRuntime({
            stateDir: `.runtime/photo-repair-${process.pid}`,
            siteUrl: 'http://127.0.0.1:9496',
        });
        const run = async (code) => {
            const result = await runtime.php.run({ code: `<?php require '/wordpress/wp-load.php'; ${code}` });
            assert.equal(result.exitCode, 0, result.errors);
            return result.text;
        };
        try {
            const before = JSON.parse(
                await run(
                    "$s=get_option('sibel_settings');$s['hero_image']='https://example.org/missing-photo.png';$s['hero_title']='Заголовок заказчика';$s['email']='owner@example.org';update_option('sibel_settings',$s);echo json_encode($s);",
                ),
            );
            await restoreHomepagePhoto(runtime.php);
            const after = JSON.parse(await run("echo json_encode(get_option('sibel_settings'));"));
            assert.deepEqual(after, { ...before, hero_image: 'd9626.png' });
            await restoreHomepagePhoto(runtime.php);
            const backup = JSON.parse(await run("echo json_encode(get_option('sibel_hero_before_repair'));"));
            assert.equal(backup.value, before.hero_image);
            assert.equal(await run("echo get_page_by_path('consent')->post_status;"), 'publish');
        } finally {
            await runtime.close();
            fs.rmSync(runtime.state, { recursive: true, force: true });
            fs.rmSync(runtime.credentialsFile, { force: true });
        }
    },
);
