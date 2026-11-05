"""Download original, unmodified Figma assets. No substitutions or screenshots."""
import concurrent.futures
import json
import pathlib
import subprocess
import sys

ROOT = pathlib.Path(__file__).resolve().parents[1]
TARGET = ROOT / 'wordpress/wp-content/themes/sibel/assets/images'
MANIFEST = json.loads((ROOT / 'scripts/figma-assets.json').read_text())
TARGET.mkdir(parents=True, exist_ok=True)


def download(item):
    destination = TARGET / item['filename']
    if destination.exists() and destination.stat().st_size:
        return item['filename']
    temporary = destination.with_suffix(destination.suffix + '.part')
    result = subprocess.run([
        'curl', '--fail', '--location', '--silent', '--show-error',
        '--connect-timeout', '15', '--max-time', '90',
        '--output', str(temporary), item['url'],
    ], capture_output=True, text=True)
    if result.returncode or not temporary.exists() or not temporary.stat().st_size:
        temporary.unlink(missing_ok=True)
        raise RuntimeError(f"{item['filename']}: {result.stderr.strip() or 'empty response'}")
    temporary.replace(destination)
    return item['filename']


try:
    # Fail fast if the Figma host is still blocked. Do not issue 77 denied requests.
    download(MANIFEST['assets'][0])
    with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
        for result in pool.map(download, MANIFEST['assets'][1:]):
            print(result)
except Exception as error:
    print(str(error), file=sys.stderr)
    print('Original assets are incomplete. Check network access to www.figma.com; expired URLs must be refreshed through the Figma connector.', file=sys.stderr)
    sys.exit(1)
print(f"Downloaded {len(MANIFEST['assets'])} original assets into {TARGET}")
