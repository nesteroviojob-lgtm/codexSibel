import fs from 'node:fs';
import path from 'node:path';
import net from 'node:net';
import { createHash } from 'node:crypto';
import { fileURLToPath, pathToFileURL } from 'node:url';

const photoName = 'd9626.png';
const photoHash = '27e610fdc3fea474654fd623e058767caae4759d78075ba0e1b931861ba7d646';

export async function restoreHomepagePhoto(php) {
    const response = await php.run({
        code: `<?php
        require '/wordpress/wp-load.php';
        $settings = get_option('sibel_settings', []);
        if (!is_array($settings)) { echo 'INVALID_SETTINGS'; exit(1); }
        if (get_option('sibel_hero_before_repair', null) === null) {
            add_option('sibel_hero_before_repair', ['value' => $settings['hero_image'] ?? null, 'date' => wp_date('c')], '', false);
        }
        $settings['hero_image'] = 'd9626.png';
        update_option('sibel_settings', $settings);
        echo get_option('sibel_settings')['hero_image'] === 'd9626.png' ? 'PHOTO_REPAIRED' : 'REPAIR_FAILED';
        `,
    });
    if (response.exitCode || response.text.trim() !== 'PHOTO_REPAIRED') {
        throw new Error('Не удалось выбрать фотографию в WordPress. Настройки не сбрасывались.');
    }
}

export function installHomepagePhoto(root) {
    const target = path.join(root, 'wordpress/wp-content/themes/sibel/assets/images', photoName);
    const bundled = path.join(root, photoName);
    const source = fs.existsSync(bundled) ? bundled : target;
    if (!fs.existsSync(source))
        throw new Error('Распакуйте все файлы из архива: рядом должен быть d9626.png.');
    const bytes = fs.readFileSync(source);
    if (createHash('sha256').update(bytes).digest('hex') !== photoHash) {
        throw new Error('Файл фотографии отличается от оригинала. Повторно распакуйте архив исправления.');
    }
    fs.mkdirSync(path.dirname(target), { recursive: true });
    if (fs.existsSync(target) && !fs.readFileSync(target).equals(bytes)) {
        const backup = path.join(root, '.runtime/hero-image-before-repair.bin');
        if (!fs.existsSync(backup)) fs.copyFileSync(target, backup);
    }
    if (path.resolve(source) !== path.resolve(target)) {
        fs.writeFileSync(target + '.part', bytes);
        fs.renameSync(target + '.part', target);
    }
}

async function requireStoppedSite() {
    const running = await new Promise((resolve, reject) => {
        const socket = net.createConnection({ host: '127.0.0.1', port: 9400 });
        socket.setTimeout(1500);
        socket.once('connect', () => {
            socket.destroy();
            resolve(true);
        });
        socket.once('error', (error) => {
            socket.destroy();
            if (error.code === 'ECONNREFUSED') resolve(false);
            else reject(new Error('Не удалось проверить порт 9400: ' + error.code));
        });
        socket.once('timeout', () => {
            socket.destroy();
            reject(new Error('Не удалось проверить порт 9400.'));
        });
    });
    if (running) {
        const error = new Error(
            'Старое окно запуска сайта ещё открыто. Закройте его и повторите исправление.',
        );
        error.code = 'SITE_RUNNING';
        throw error;
    }
}

async function main() {
    const folder = path.dirname(fileURLToPath(import.meta.url));
    const root = fs.existsSync(path.join(folder, 'start-local.cmd')) ? folder : path.resolve(folder, '..');
    for (const name of [
        'start-local.cmd',
        'scripts/runtime.mjs',
        '.runtime/site/wp-load.php',
        'node_modules/@wp-playground/cli/package.json',
    ]) {
        if (!fs.existsSync(path.join(root, name))) {
            throw new Error(
                'Поместите три файла исправления в существующую папку сайта, рядом со start-local. В этой папке должны быть .runtime и node_modules.',
            );
        }
    }
    await requireStoppedSite();
    installHomepagePhoto(root);
    console.log('Фотография установлена. Обновляем выбор фото в WordPress…');
    const { createSibelRuntime } = await import(pathToFileURL(path.join(root, 'scripts/runtime.mjs')).href);
    const runtime = await createSibelRuntime({ siteUrl: 'http://127.0.0.1:9400' });
    try {
        await restoreHomepagePhoto(runtime.php);
    } finally {
        await runtime.close();
    }
    console.log('\nГотово. Фото первого экрана выбрано. Остальные настройки сайта сохранены.');
    console.log('Папка сайта: ' + root);
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
    main().catch((error) => {
        console.error('\n' + error.message);
        process.exitCode = error.code === 'SITE_RUNNING' ? 2 : 1;
    });
}
