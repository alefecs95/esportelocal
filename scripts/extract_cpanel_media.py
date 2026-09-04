"""Extrai mídias do backup cPanel (esportelocal.com.zip) para o volume Laravel."""

from __future__ import annotations

import shutil
import zipfile
from pathlib import Path
import sys

DEFAULT_SRC = Path(r"C:\Users\alefe\Downloads\esportelocal.com.zip")
REPO = Path(__file__).resolve().parents[1]
PUBLIC_PREFIX = "esportelocal.com/backend/storage/app/public/"
TERMO_SRC = "esportelocal.com/termo_adesao_jefs.html"


def extract_media(src: Path, dest_public: Path) -> tuple[int, int]:
    dest_public.mkdir(parents=True, exist_ok=True)
    copied = 0
    bytes_copied = 0

    with zipfile.ZipFile(src) as z:
        for info in z.infolist():
            name = info.filename.replace("\\", "/")
            if info.is_dir() or not name.startswith(PUBLIC_PREFIX):
                continue
            rel = name[len(PUBLIC_PREFIX) :]
            if not rel or rel.endswith(".gitignore"):
                continue
            target = dest_public / rel
            target.parent.mkdir(parents=True, exist_ok=True)
            with z.open(info) as reader, open(target, "wb") as writer:
                shutil.copyfileobj(reader, writer)
            copied += 1
            bytes_copied += info.file_size
    return copied, bytes_copied


def extract_termo(src: Path, dest: Path) -> bool:
    with zipfile.ZipFile(src) as z:
        try:
            dest.parent.mkdir(parents=True, exist_ok=True)
            dest.write_bytes(z.read(TERMO_SRC))
            return True
        except KeyError:
            return False


def zip_public_tree(public_dir: Path, zip_path: Path) -> None:
    zip_path.parent.mkdir(parents=True, exist_ok=True)
    if zip_path.exists():
        zip_path.unlink()
    with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED) as out:
        for path in public_dir.rglob("*"):
            if path.is_file() and path.name != ".gitignore":
                out.write(path, path.relative_to(public_dir).as_posix())


def main() -> int:
    src = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_SRC
    if not src.is_file():
        print(f"ZIP não encontrado: {src}", file=sys.stderr)
        return 1

    dest_public = REPO / "backend" / "storage" / "app" / "public"
    dest_zip = REPO / "docker" / "database" / "import" / "storage-public.zip"
    dest_termo = REPO / "frontend" / "public" / "termo_adesao_jefs.html"

    print(f"Lendo {src} ({src.stat().st_size} bytes)")
    files, nbytes = extract_media(src, dest_public)
    print(f"Mídias extraídas: {files} arquivos, {nbytes} bytes -> {dest_public}")

    zip_public_tree(dest_public, dest_zip)
    print(f"ZIP para o Coolify: {dest_zip} ({dest_zip.stat().st_size} bytes)")

    if extract_termo(src, dest_termo):
        print(f"Termo copiado: {dest_termo}")
    else:
        print("termo_adesao_jefs.html não encontrado no ZIP")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
