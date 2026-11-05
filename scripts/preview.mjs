import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { createSibelRuntime, root } from './runtime.mjs';

const port = Number(process.env.PORT || 9400);
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
server.listen(port, '127.0.0.1', () => {
    console.log(`Local: ${siteUrl}`);
    console.log(`Admin: ${siteUrl}/wp-admin/admin.php?page=sibel`);
    console.log(`Development login details: ${runtime.credentialsFile}`);
    console.log('This loopback-only preview is for development. Upload only the theme ZIP to your hosting.');
});
for (const signal of ['SIGINT', 'SIGTERM'])
    process.on(signal, () =>
        server.close(async () => {
            await runtime.close();
            process.exit(0);
        }),
    );
