"""Prepara dump do phpMyAdmin/HostGator para importar no Coolify."""

from pathlib import Path
import re
import sys

DEFAULT_SRC = Path(r"C:\Users\alefe\Downloads\bonusp15_sge-alefe.sql")
DEFAULT_DST = Path(__file__).resolve().parents[1] / "docker" / "database" / "import" / "backup.sql"

OLD_HOSTS = [
    "https://alefecarvalhosilva1773515183000.1431034.meusitehostgator.com.br",
    "http://alefecarvalhosilva1773515183000.1431034.meusitehostgator.com.br",
    "https://www.esportelocal.com",
    "http://www.esportelocal.com",
    "http://esportelocal.com",
]

CANONICAL = "https://esportelocal.com"
MEDIA_PREFIX_ABS = f"{CANONICAL}/backend/public/api/v1/public/media/"
MEDIA_PREFIX_REL = "/backend/public/api/v1/public/media/"


def rewrite_urls(sql: str) -> tuple[str, dict[str, int]]:
    counts: dict[str, int] = {}
    for host in OLD_HOSTS:
        n = sql.count(host)
        if n:
            sql = sql.replace(host, CANONICAL)
            counts[host] = n
    media = sql.count(MEDIA_PREFIX_ABS)
    if media:
        sql = sql.replace(MEDIA_PREFIX_ABS, MEDIA_PREFIX_REL)
        counts["media_abs_to_rel"] = media
    return sql, counts


def add_drop_tables(sql: str) -> str:
    pattern = re.compile(r"^CREATE TABLE `(?P<name>[^`]+)`", re.M)

    def repl(match: re.Match[str]) -> str:
        name = match.group("name")
        return f"DROP TABLE IF EXISTS `{name}`;\nCREATE TABLE `{name}`"

    return pattern.sub(repl, sql)


def wrap_fk_checks(sql: str) -> str:
    start = (
        "SET FOREIGN_KEY_CHECKS=0;\n"
        "SET UNIQUE_CHECKS=0;\n"
        "SET sql_notes=0;\n"
    )
    if "SET FOREIGN_KEY_CHECKS=0" not in sql:
        marker = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";"
        if marker in sql:
            sql = sql.replace(marker, start + marker, 1)
        else:
            sql = start + sql

    if "SET FOREIGN_KEY_CHECKS=1" not in sql:
        sql = sql.replace(
            "COMMIT;",
            "SET UNIQUE_CHECKS=1;\nSET FOREIGN_KEY_CHECKS=1;\nCOMMIT;",
            1,
        )
    return sql


def main() -> int:
    src = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_SRC
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else DEFAULT_DST

    if not src.is_file():
        print(f"Arquivo não encontrado: {src}", file=sys.stderr)
        return 1

    sql = src.read_text(encoding="utf-8", errors="replace")
    sql, url_counts = rewrite_urls(sql)
    sql = add_drop_tables(sql)
    sql = wrap_fk_checks(sql)

    dst.parent.mkdir(parents=True, exist_ok=True)
    dst.write_text(sql, encoding="utf-8", newline="\n")

    drops = sql.count("DROP TABLE IF EXISTS")
    print(f"Origem: {src}")
    print(f"Destino: {dst}")
    print(f"Tamanho: {dst.stat().st_size} bytes")
    print(f"DROP TABLE: {drops}")
    print("Reescritas de URL:")
    if url_counts:
        for key, count in url_counts.items():
            print(f"  {count} x {key}")
    else:
        print("  nenhuma (hosts já estavam em https://esportelocal.com)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
