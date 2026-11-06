import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import os from 'node:os';
import http from 'node:http';
import { once } from 'node:events';
import { unzipSync } from 'fflate';
import { downloadAsset, validateManifest, createAssetArchives } from '../scripts/download-assets.mjs';

test('download URLs stay within Figma and filenames cannot escape the image folder', () => {
    const valid = { filename: 'abcde.svg', url: 'https://www.figma.com/api/mcp/asset/example/abcde.svg' };
    assert.deepEqual(validateManifest({ assets: [valid] }), [valid]);
    assert.throws(() => validateManifest({ assets: [{ ...valid, filename: '../abcde.svg' }] }));
    assert.throws(() => validateManifest({ assets: [{ ...valid, url: 'https://example.org/abcde.svg' }] }));
    assert.throws(() => validateManifest({ assets: [valid, valid] }));
});

test('downloaded originals are byte-identical, cached, and archived without modification', async (t) => {
    const directory = await fs.mkdtemp(path.join(os.tmpdir(), 'sibel-assets-'));
    const svg = Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="12" height="8"></svg>');
    let requests = 0;
    const server = http.createServer((_request, response) => {
        requests++;
        response.writeHead(200, { 'Content-Type': 'image/svg+xml' });
        response.end(svg);
    });
    server.listen(0, '127.0.0.1');
    await once(server, 'listening');
    t.after(async () => {
        await new Promise((resolve) => server.close(resolve));
        await fs.rm(directory, { recursive: true, force: true });
    });
    const asset = { filename: 'abcde.svg', url: `http://127.0.0.1:${server.address().port}/logo.svg` };
    assert.equal((await downloadAsset(asset, directory)).cached, false);
    assert.deepEqual(await fs.readFile(path.join(directory, asset.filename)), svg);
    assert.equal((await downloadAsset(asset, directory)).cached, true);
    assert.equal(requests, 1);
    const [archive] = await createAssetArchives([asset], directory, directory);
    assert.deepEqual(Buffer.from(unzipSync(await fs.readFile(archive))[asset.filename]), svg);
});

test('HTML and expired links are rejected without leaving image or partial files', async (t) => {
    const directory = await fs.mkdtemp(path.join(os.tmpdir(), 'sibel-assets-errors-'));
    const server = http.createServer((request, response) => {
        response.writeHead(request.url === '/expired' ? 410 : 200, { 'Content-Type': 'text/html' });
        response.end('<html><body>Login required</body></html>');
    });
    server.listen(0, '127.0.0.1');
    await once(server, 'listening');
    t.after(async () => {
        await new Promise((resolve) => server.close(resolve));
        await fs.rm(directory, { recursive: true, force: true });
    });
    const base = `http://127.0.0.1:${server.address().port}`;
    await assert.rejects(
        downloadAsset({ filename: 'abcde.svg', url: base + '/login' }, directory),
        /страница ошибки/,
    );
    await assert.rejects(downloadAsset({ filename: 'f1234.png', url: base + '/expired' }, directory), /410/);
    assert.deepEqual(await fs.readdir(directory), []);
});
