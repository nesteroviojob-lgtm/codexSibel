import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { zipSync } from 'fflate';

const run = promisify(execFile);
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const imageFolder = path.join(root, 'wordpress/wp-content/themes/sibel/assets/images');
const pngSignature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);

export function validateManifest(manifest) {
    if (!Array.isArray(manifest.assets) || !manifest.assets.length)
        throw new Error('Список изображений пуст.');
    const names = new Set();
    for (const asset of manifest.assets) {
        if (!/^[a-z0-9_-]+\.(png|svg)$/i.test(asset.filename) || names.has(asset.filename)) {
            throw new Error('Некорректное или повторяющееся имя изображения.');
        }
        const url = new URL(asset.url);
        if (
            url.protocol !== 'https:' ||
            url.hostname !== 'www.figma.com' ||
            url.port ||
            url.username ||
            url.password ||
            !url.pathname.startsWith('/api/mcp/asset/')
        ) {
            throw new Error('В списке обнаружена ссылка вне Figma.');
        }
        names.add(asset.filename);
    }
    return manifest.assets;
}

export async function validateImage(filename, file) {
    const handle = await fs.open(file, 'r');
    try {
        const { size } = await handle.stat();
        const header = Buffer.alloc(Math.min(size, 1024));
        await handle.read(header, 0, header.length, 0);
        const valid = filename.toLowerCase().endsWith('.png')
            ? header.length >= 24 &&
              header.subarray(0, 8).equals(pngSignature) &&
              header.toString('ascii', 12, 16) === 'IHDR' &&
              header.readUInt32BE(16) > 0 &&
              header.readUInt32BE(20) > 0
            : /^\s*(?:<\?xml[\s\S]*?\?>\s*)?(?:<!--[\s\S]*?-->\s*)*<svg(?:\s|>)/i.test(
                  header.toString('utf8').replace(/^\uFEFF/, ''),
              );
        if (!valid)
            throw new Error(`${filename}: вместо изображения получен пустой файл или страница ошибки.`);
    } finally {
        await handle.close();
    }
}

export async function downloadAsset(asset, directory) {
    if (!/^[a-z0-9_-]+\.(png|svg)$/i.test(asset.filename)) throw new Error('Некорректное имя изображения.');
    await fs.mkdir(directory, { recursive: true });
    const target = path.join(directory, asset.filename);
    try {
        await validateImage(asset.filename, target);
        return { filename: asset.filename, cached: true };
    } catch {
        /* Retry missing or invalid files, keeping the old file until download succeeds. */
    }
    const partial = target + '.part';
    try {
        await run(
            process.platform === 'win32' ? 'curl.exe' : 'curl',
            [
                '--fail',
                '--location',
                '--silent',
                '--show-error',
                '--connect-timeout',
                '15',
                '--max-time',
                '90',
                '--output',
                partial,
                asset.url,
            ],
            { windowsHide: true, maxBuffer: 1024 * 1024 },
        );
        await validateImage(asset.filename, partial);
        await fs.rename(partial, target);
        return { filename: asset.filename, cached: false };
    } catch (error) {
        await fs.rm(partial, { force: true });
        if (error.code === 'ENOENT')
            throw new Error('Не найдена программа curl. Она входит в современные версии Windows.');
        throw new Error(`${asset.filename}: ${error.stderr?.trim() || error.message}`);
    }
}

export async function createAssetArchives(assets, directory, outputDirectory) {
    const groups = [];
    let group = [],
        bytes = 0;
    for (const asset of assets) {
        const { size } = await fs.stat(path.join(directory, asset.filename));
        if (group.length && bytes + size > 20 * 1024 * 1024) {
            groups.push(group);
            group = [];
            bytes = 0;
        }
        group.push(asset);
        bytes += size;
    }
    if (group.length) groups.push(group);
    const archives = [];
    for (const [index, items] of groups.entries()) {
        const files = {};
        for (const asset of items)
            files[asset.filename] = await fs.readFile(path.join(directory, asset.filename));
        const name = groups.length === 1 ? 'SIBEL-FIGMA.zip' : `SIBEL-FIGMA-${index + 1}.zip`;
        const target = path.join(outputDirectory, name);
        await fs.writeFile(target, zipSync(files, { level: 1 }));
        archives.push(target);
    }
    return archives;
}

async function main() {
    const listedAssets = validateManifest(
        JSON.parse(await fs.readFile(path.join(root, 'scripts/figma-assets.json'), 'utf8')),
    );
    // The user confirmed this logo URL opens on their computer. Probe it first.
    const probe = listedAssets.find((asset) => asset.filename === '2e56a.svg') ?? listedAssets[0];
    const assets = [probe, ...listedAssets.filter((asset) => asset !== probe)];
    console.log(`Скачиваем ${assets.length} оригинальных файлов из Figma. Оставьте окно открытым.\n`);
    // Verify one URL first, so expired links or an unavailable host do not cause 76 failed requests.
    await downloadAsset(assets[0], imageFolder);
    let next = 1,
        completed = 1;
    const successful = [assets[0]],
        failed = [];
    console.log(`[1/${assets.length}] ${assets[0].filename}`);
    async function worker() {
        while (next < assets.length) {
            const asset = assets[next++];
            try {
                await downloadAsset(asset, imageFolder);
                successful.push(asset);
                console.log(`[${++completed}/${assets.length}] ${asset.filename}`);
            } catch (error) {
                failed.push(asset.filename);
                console.error(error.message);
            }
        }
    }
    await Promise.all([worker(), worker(), worker()]);
    const archives = await createAssetArchives(successful, imageFolder, root);
    console.log(`\nПолучено ${successful.length} из ${assets.length} файлов.`);
    console.log('Обновите страницу локального сайта клавишей F5: скачанные изображения уже подключены.');
    console.log('\nВ папке сайта созданы архивы. Прикрепите их в чат Codex:');
    for (const archive of archives) console.log(path.basename(archive));
    if (failed.length) {
        console.error(`\nНе скачались: ${failed.join(', ')}. Повторный запуск продолжит загрузку.`);
        process.exitCode = 1;
    }
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
    main().catch((error) => {
        console.error('\nЗагрузка остановлена: ' + error.message);
        console.error('Если ссылка устарела или Figma запросила вход, сообщите об этом в чат.');
        process.exitCode = 1;
    });
}
