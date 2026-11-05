import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { loadNodeRuntime, createNodeFsMountHandler } from '@php-wasm/node';
import { bootWordPressAndRequestHandler } from '@wp-playground/wordpress';

export const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export async function createSibelRuntime({
    siteUrl = 'http://127.0.0.1:9400',
    stateDir = '.runtime/site',
} = {}) {
    const zip = path.join(root, '.runtime/wordpress.zip');
    if (!fs.existsSync(zip)) {
        const prepared = spawnSync('python', [path.join(root, 'scripts/prepare-runtime.py')], {
            stdio: 'inherit',
        });
        if (prepared.status) throw new Error('Could not prepare the local WordPress runtime.');
    }
    const state = path.resolve(root, stateDir);
    fs.mkdirSync(state, { recursive: true });
    let pid = 1;
    const handler = await bootWordPressAndRequestHandler({
        siteUrl,
        phpVersion: '8.3',
        createPhpRuntime: () => loadNodeRuntime('8.3', { emscriptenOptions: { processId: pid++ } }),
        maxPhpInstances: 1,
        documentRoot: '/wordpress',
        cookieStore: false,
        wordPressZip: fs.existsSync(path.join(state, 'wp-load.php'))
            ? undefined
            : new File([fs.readFileSync(zip)], 'wordpress.zip'),
        wordpressInstallMode: fs.existsSync(path.join(state, 'wp-load.php'))
            ? 'install-from-existing-files-if-needed'
            : 'download-and-install',
        sqliteIntegrationPluginZip: new File(
            [
                fs.readFileSync(
                    path.join(root, 'node_modules/@wp-playground/cli/sqlite-database-integration.zip'),
                ),
            ],
            'sqlite.zip',
        ),
        constants: {
            WP_DEBUG: true,
            WP_DEBUG_DISPLAY: false,
            WP_DEBUG_LOG: true,
            DISABLE_WP_CRON: true,
            WP_ENVIRONMENT_TYPE: 'local',
            AUTOMATIC_UPDATER_DISABLED: true,
            WP_HTTP_BLOCK_EXTERNAL: true,
        },
        hooks: {
            beforeWordPressFiles: async (php) => {
                if (!php.fileExists('/wordpress')) php.mkdir('/wordpress');
                await php.mount('/wordpress', createNodeFsMountHandler(state));
            },
            beforeDatabaseSetup: async (php) => {
                const target = '/wordpress/wp-content/themes/sibel';
                if (!php.fileExists(target)) php.mkdir(target);
                await php.mount(
                    target,
                    createNodeFsMountHandler(path.join(root, 'wordpress/wp-content/themes/sibel')),
                );
            },
        },
    });
    const php = await handler.getPrimaryPhp();
    const credentialsFile = path.join(path.dirname(state), path.basename(state) + '-credentials.json');
    const credentials = fs.existsSync(credentialsFile)
        ? JSON.parse(fs.readFileSync(credentialsFile, 'utf8'))
        : { username: 'admin', password: randomBytes(24).toString('base64url') };
    fs.writeFileSync(credentialsFile, JSON.stringify(credentials, null, 2) + '\n', { mode: 0o600 });
    await php.run({ code: `<?php require '/wordpress/wp-load.php'; switch_theme('sibel');` });
    const setup = await php.run({
        code: `<?php
        require '/wordpress/wp-load.php';
        sibel_seed();
        do_action('admin_init');
        wp_set_password(${JSON.stringify(credentials.password)}, 1);
        update_option('permalink_structure', '/%postname%/');
        update_option('timezone_string', 'Asia/Tomsk');
        flush_rewrite_rules();
        echo 'SIBEL_READY';
    `,
    });
    if (!setup.text.includes('SIBEL_READY') || setup.exitCode)
        throw new Error(setup.errors || setup.text || 'WordPress setup failed');
    return {
        handler,
        php,
        credentials,
        state,
        credentialsFile,
        async close() {
            php.exit();
        },
    };
}
