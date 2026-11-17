import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { createSibelRuntime, root } from './runtime.mjs';

const port = Number(process.env.PORT || 9400);
if (!Number.isInteger(port) || port < 1 || port > 65535) throw new Error('Некорректный номер порта.');
const siteUrl = `http://127.0.0.1:${port}`;
const runtime = await createSibelRuntime({ siteUrl });
let phpQueue = Promise.resolve();
const types = {
    '.css': 'text/css; charset=utf-8',
    '.js': 'application/javascript; charset=utf-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.svg': 'image/svg+xml',
    '.webp': 'image/webp',
    '.gif': 'image/gif',
    '.ico': 'image/x-icon',
    '.woff2': 'font/woff2',
    '.woff': 'font/woff',
    '.ttf': 'font/ttf',
};
const server = http.createServer(async (req, res) => {
    res.setHeader('Cache-Control', 'no-store');
    try {
        const pathname = decodeURIComponent(new URL(req.url, siteUrl).pathname);
        if (pathname.split('/').some((segment) => segment.startsWith('.'))) {
            res.writeHead(403);
            res.end();
            return;
        }
        const ext = path.extname(pathname).toLowerCase();
        if (types[ext]) {
            const themePrefix = '/wp-content/themes/sibel/';
            const base = pathname.startsWith(themePrefix)
                ? path.join(root, 'wordpress/wp-content/themes/sibel')
                : runtime.state;
            const relative = pathname.startsWith(themePrefix)
                ? pathname.slice(themePrefix.length)
                : pathname.slice(1);
            const target = path.resolve(base, relative);
            if (!target.startsWith(base + path.sep)) {
                res.writeHead(403);
                res.end();
                return;
            }
            if (!fs.existsSync(target) || !fs.statSync(target).isFile()) {
                res.writeHead(404);
                res.end();
                return;
            }
            res.writeHead(200, { 'Content-Type': types[ext] });
            fs.createReadStream(target).pipe(res);
            return;
        }
        const chunks = [];
        for await (const chunk of req) chunks.push(chunk);
        const work = phpQueue.then(() =>
            runtime.handler.request({
                url: siteUrl + req.url,
                method: req.method,
                headers: req.headers,
                body: chunks.length ? Buffer.concat(chunks) : undefined,
            }),
        );
        phpQueue = work.catch(() => {});
        const response = await work;
        res.writeHead(response.httpStatusCode, response.headers);
        res.end(Buffer.from(response.bytes));
    } catch (error) {
        console.error(error);
        res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end('Ошибка локального сервера. Подробности в терминале.');
    }
});
server.on('error', async (error) => {
    console.error(
        error.code === 'EADDRINUSE'
            ? `Порт ${port} уже занят. Закройте предыдущее окно запуска сайта и повторите.`
            : `Ошибка локального сервера: ${error.message}`,
    );
    await runtime.close();
    process.exitCode = 1;
});
server.listen(port, '127.0.0.1', () => {
    console.log(`Local: ${siteUrl}`);
    console.log(`Admin: ${siteUrl}/wp-admin/admin.php?page=sibel`);
    console.log(`Development login details: ${runtime.credentialsFile}`);
    console.log('This loopback-only preview is for development. Upload only the theme ZIP to your hosting.');
    if (process.env.SIBEL_OPEN_BROWSER === '1') {
        console.log('\nСайт готов. Для входа в админку:\n');
        console.log(`Логин: ${runtime.credentials.username}`);
        console.log(`Пароль: ${runtime.credentials.password}`);
        console.log('\nНе закрывайте это окно, пока смотрите сайт. Для остановки нажмите Ctrl+C.\n');
        if (process.platform === 'win32') {
            for (const url of [siteUrl, `${siteUrl}/wp-admin/admin.php?page=sibel`]) {
                const child = spawn('cmd.exe', ['/d', '/c', 'start', '', url], {
                    detached: true,
                    stdio: 'ignore',
                    windowsHide: true,
                });
                child.on('error', () => console.log(`Откройте в браузере: ${url}`));
                child.unref();
            }
        }
    }
});
for (const signal of ['SIGINT', 'SIGTERM'])
    process.on(signal, () =>
        server.close(async () => {
            await runtime.close();
            process.exit(0);
        }),
    );
