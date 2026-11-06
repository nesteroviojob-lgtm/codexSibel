import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { pipeline } from 'node:stream/promises';
import { Readable } from 'node:stream';
import { createWriteStream } from 'node:fs';
import { unzipSync, zipSync } from 'fflate';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const sourceUrl = 'https://codeload.github.com/WordPress/WordPress/zip/refs/tags/6.9';

export function normalizeWordPressZip(source) {
    const files = unzipSync(source);
    const entry = Object.keys(files).find((name) => name.endsWith('/wp-load.php'));
    if (!entry) throw new Error('Архив WordPress не содержит wp-load.php. Повторите загрузку.');
    const prefix = entry.slice(0, -'wp-load.php'.length);
    const normalized = {};
    for (const [name, data] of Object.entries(files)) {
        if (!name.startsWith(prefix)) continue;
        const relative = name.slice(prefix.length);
        if (!relative || relative.endsWith('/')) continue;
        if (relative.split('/').some((part) => part === '..' || part === '.')) {
            throw new Error('Недопустимый путь в архиве WordPress.');
        }
        normalized['wordpress/' + relative] = data;
    }
    return zipSync(normalized, { level: 1 });
}

export async function prepareWordPressRuntime(runtimeDir = path.join(projectRoot, '.runtime')) {
    await fs.mkdir(runtimeDir, { recursive: true });
    const target = path.join(runtimeDir, 'wordpress.zip');
    try {
        if ((await fs.stat(target)).size) return target;
    } catch (error) {
        if (error.code !== 'ENOENT') throw error;
    }
    const source = path.join(runtimeDir, 'wordpress-source.zip');
    try {
        if (!(await fs.stat(source)).size) await fs.unlink(source);
    } catch (error) {
        if (error.code !== 'ENOENT') throw error;
    }
    try {
        await fs.access(source);
    } catch {
        console.log('Первый запуск: скачиваем WordPress. Это может занять несколько минут…');
        const partial = source + '.part';
        try {
            const response = await fetch(sourceUrl, { signal: AbortSignal.timeout(180_000) });
            if (!response.ok || !response.body) {
                throw new Error(`Загрузка WordPress: HTTP ${response.status}. Проверьте доступ к GitHub.`);
            }
            await pipeline(Readable.fromWeb(response.body), createWriteStream(partial));
            await fs.rename(partial, source);
        } catch (error) {
            await fs.rm(partial, { force: true });
            throw error;
        }
    }
    console.log('Подготавливаем WordPress для локального просмотра…');
    const bytes = normalizeWordPressZip(await fs.readFile(source));
    const temporary = target + '.part';
    await fs.writeFile(temporary, bytes);
    await fs.rename(temporary, target);
    return target;
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
    console.log(await prepareWordPressRuntime());
}
