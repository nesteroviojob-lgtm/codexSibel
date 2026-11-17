import json
from pathlib import Path
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / 'wordpress/wp-content/themes/sibel'
manifest = json.loads((ROOT / 'scripts/figma-assets.json').read_text())
missing = [item['filename'] for item in manifest['assets'] if not (THEME / 'assets/images' / item['filename']).is_file() or not (THEME / 'assets/images' / item['filename']).stat().st_size]
if missing:
    sys.exit(f"Cannot package an incomplete site: {len(missing)} original Figma assets are missing. Run npm run assets first.")
for weight in ['Regular', 'Medium', 'SemiBold', 'Bold']:
    font = THEME / 'assets/fonts' / f'VelaSans-{weight}.woff2'
    if not font.is_file() or not font.stat().st_size:
        sys.exit(f'Cannot package the theme: Vela Sans {weight} is missing.')
(ROOT / 'dist').mkdir(exist_ok=True)
with zipfile.ZipFile(ROOT / 'dist/sibel-theme.zip', 'w', compression=zipfile.ZIP_DEFLATED) as archive:
    for file in sorted(THEME.rglob('*')):
        if file.is_file():
            archive.write(file, file.relative_to(THEME.parent))
print(ROOT / 'dist/sibel-theme.zip')
