"""Prepare the development WordPress runtime from the official GitHub mirror."""
from pathlib import Path
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[1]
runtime = ROOT / '.runtime'
runtime.mkdir(exist_ok=True)
normalized = runtime / 'wordpress.zip'
if not normalized.exists():
    source = runtime / 'wordpress-source.zip'
    if not source.exists():
        subprocess.run(['curl', '--fail', '--location', '--silent', '--show-error', '--max-time', '90', 'https://codeload.github.com/WordPress/WordPress/zip/refs/tags/6.9', '--output', str(source)], check=True)
    with zipfile.ZipFile(source) as original, zipfile.ZipFile(normalized, 'w', compression=zipfile.ZIP_DEFLATED) as output:
        for info in original.infolist():
            relative = info.filename.split('/', 1)[1] if '/' in info.filename else ''
            if relative:
                output.writestr('wordpress/' + relative, original.read(info.filename))
print(normalized)
