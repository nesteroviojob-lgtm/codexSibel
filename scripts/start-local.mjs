import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
process.chdir(root);
const [major, minor] = process.versions.node.split('.').map(Number);
if (major < 24 || (major === 24 && minor < 18)) {
    console.error('Для запуска нужен Node.js 24.18 или новее: https://nodejs.org/');
    process.exit(1);
}

const lock = fs.readFileSync(path.join(root, 'package-lock.json'), 'utf8');
const stamp = path.join(root, '.runtime/dependencies.lock');
const installed =
    fs.existsSync(path.join(root, 'node_modules/@wp-playground/cli/package.json')) &&
    fs.existsSync(path.join(root, 'node_modules/fflate/package.json')) &&
    fs.existsSync(stamp) &&
    fs.readFileSync(stamp, 'utf8') === lock;
if (!installed) {
    console.log('Устанавливаем зависимости. При первом запуске дождитесь завершения…');
    const command = process.platform === 'win32' ? 'npm.cmd' : 'npm';
    const result = spawnSync(command, ['ci', '--no-audit', '--no-fund'], {
        cwd: root,
        stdio: 'inherit',
        shell: process.platform === 'win32',
    });
    if (result.error || result.status !== 0) {
        console.error(
            'Не удалось установить зависимости. Проверьте подключение к интернету и повторите запуск.',
        );
        if (result.error) console.error(result.error.message);
        process.exit(1);
    }
    fs.mkdirSync(path.dirname(stamp), { recursive: true });
    fs.writeFileSync(stamp, lock);
}
process.env.SIBEL_OPEN_BROWSER = '1';
try {
    await import('./preview.mjs');
} catch (error) {
    console.error('Не удалось запустить локальный сайт:', error.message);
    if (error.code === 'EADDRINUSE') {
        console.error('Порт 9400 уже занят. Закройте другое окно запуска сайта и повторите.');
    }
    process.exitCode = 1;
}
