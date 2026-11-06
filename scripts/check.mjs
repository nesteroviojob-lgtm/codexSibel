import { readdir, readFile, stat } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime } from '@php-wasm/node';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const theme = path.join(root, 'wordpress/wp-content/themes/sibel');
async function files(dir) {
    const entries = await readdir(dir, { withFileTypes: true });
    return (
        await Promise.all(
            entries.map((e) => (e.isDirectory() ? files(path.join(dir, e.name)) : path.join(dir, e.name))),
        )
    ).flat();
}
const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: 1 } }));
let failures = 0;
try {
    const all = await files(theme);
    for (const file of all) {
        if (!/\.(php|js|css)$/.test(file)) continue;
        const source = await readFile(file, 'utf8');
        if (file.endsWith('.php')) {
            const encoded = Buffer.from(source).toString('base64');
            const result = await php.run({
                code: `<?php try { token_get_all(base64_decode('${encoded}'), TOKEN_PARSE); echo 'OK'; } catch (ParseError $e) { echo $e->getMessage(); exit(1); }`,
            });
            if (result.exitCode || result.text.trim() !== 'OK') {
                console.error(path.relative(root, file), result.text, result.errors);
                failures++;
            }
        } else if (file.endsWith('.js')) {
            const result = spawnSync(process.execPath, ['--check', file], { encoding: 'utf8' });
            if (result.status) {
                console.error(result.stderr);
                failures++;
            }
        }
        if (/\.(php|js|css)$/.test(file) && /https:\/\/www\.figma\.com\/api\/mcp\/asset/.test(source)) {
            console.error('Temporary asset URL in runtime code:', file);
            failures++;
        }
    }
    console.log(
        `${all.filter((f) => f.endsWith('.php')).length} PHP files parsed; JavaScript syntax and runtime asset URLs checked.`,
    );
    const manifest = JSON.parse(await readFile(path.join(root, 'scripts/figma-assets.json'), 'utf8'));
    const missing = [];
    for (const asset of manifest.assets) {
        try {
            if (!(await stat(path.join(theme, 'assets/images', asset.filename))).size)
                missing.push(asset.filename);
        } catch {
            missing.push(asset.filename);
        }
    }
    if (missing.length) {
        console.error(
            `Missing ${missing.length}/${manifest.assets.length} original Figma assets. Run npm run assets in an environment with www.figma.com allowed.`,
        );
        if (!process.argv.includes('--code-only')) failures++;
    }
    for (const weight of ['Regular', 'Medium', 'SemiBold', 'Bold']) {
        try {
            await stat(path.join(theme, `assets/fonts/VelaSans-${weight}.woff2`));
        } catch {
            console.error(`Missing Vela Sans ${weight}`);
            failures++;
        }
    }
} finally {
    php.exit();
}
process.exitCode = failures ? 1 : 0;
