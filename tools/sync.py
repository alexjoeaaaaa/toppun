#!/usr/bin/env python3
"""Sync VANACANDLES between this PC and the live FTP host.

Usage:
  python tools/sync.py up                 upload local -> remote
  python tools/sync.py down               download remote -> local
  python tools/sync.py up helpers.php     upload one file or folder
  python tools/sync.py down assets/images download one remote path
  python tools/sync.py status             list what would change (upload direction)
"""

from __future__ import annotations

import argparse
import fnmatch
import json
import os
import sys
from ftplib import FTP, FTP_TLS, error_perm
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
CONFIG_PATH = Path(__file__).resolve().parent / "sync.local.json"

# Never overwrite the other environment's database config.
# Never ship credentials, git, or SQL dumps.
EXCLUDE_NAMES = {
    ".git",
    ".gitignore",
    ".ftpquota",
    "sync.local.json",
    "連線命令.txt",
}
EXCLUDE_GLOBS = {
    "*.sql",
    "backup_*.sql",
}
EXCLUDE_RELATIVE = {
    "db.php",
    "doc/db.php",
    "fed5.php",
    "config.local.php",
    "tools/sync.local.json",
}


def load_config() -> dict:
    if not CONFIG_PATH.is_file():
        example = CONFIG_PATH.with_name("sync.local.json.example")
        sys.exit(
            f"缺少 {CONFIG_PATH.name}。\n"
            f"請複製 {example.name} 為 sync.local.json 並填入 FTP 帳密。"
        )
    data = json.loads(CONFIG_PATH.read_text(encoding="utf-8"))
    required = ("host", "user", "password")
    missing = [k for k in required if not data.get(k)]
    if missing:
        sys.exit(f"sync.local.json 缺少欄位: {', '.join(missing)}")
    data.setdefault("port", 21)
    data.setdefault("remote_dir", "/")
    data.setdefault("tls", False)
    data.setdefault("local_dir", str(ROOT))
    return data


def rel_posix(path: Path, base: Path) -> str:
    return path.relative_to(base).as_posix()


def is_excluded(rel: str) -> bool:
    parts = rel.split("/")
    if any(part in EXCLUDE_NAMES for part in parts):
        return True
    if rel in EXCLUDE_RELATIVE:
        return True
    name = parts[-1]
    return any(fnmatch.fnmatch(name, pat) for pat in EXCLUDE_GLOBS)


def connect(cfg: dict):
    cls = FTP_TLS if cfg.get("tls") else FTP
    ftp = cls()
    ftp.connect(cfg["host"], int(cfg["port"]), timeout=30)
    ftp.login(cfg["user"], cfg["password"])
    if isinstance(ftp, FTP_TLS):
        ftp.prot_p()
    ftp.encoding = "utf-8"
    remote = cfg["remote_dir"].replace("\\", "/").rstrip("/") or "/"
    if remote != "/":
        ftp.cwd(remote)
    return ftp


def iter_local_files(local_root: Path, only: str | None):
    start = local_root / only if only else local_root
    if start.is_file():
        rel = rel_posix(start, local_root)
        if not is_excluded(rel):
            yield start, rel
        return
    if not start.is_dir():
        sys.exit(f"本機找不到: {start}")
    for dirpath, dirnames, filenames in os.walk(start):
        dirnames[:] = [d for d in dirnames if d not in EXCLUDE_NAMES]
        current = Path(dirpath)
        for name in filenames:
            path = current / name
            rel = rel_posix(path, local_root)
            if not is_excluded(rel):
                yield path, rel


def ftp_makedirs(ftp: FTP, remote_dir: str) -> None:
    if not remote_dir or remote_dir == ".":
        return
    parts = [p for p in remote_dir.replace("\\", "/").split("/") if p]
    acc = []
    for part in parts:
        acc.append(part)
        path = "/".join(acc)
        try:
            ftp.mkd(path)
        except error_perm:
            pass


def ftp_nlst(ftp: FTP, remote_dir: str) -> list[str]:
    try:
        if remote_dir in ("", ".", "/"):
            return ftp.nlst()
        return ftp.nlst(remote_dir)
    except error_perm:
        return []


def is_remote_dir(ftp: FTP, path: str) -> bool:
    current = ftp.pwd()
    try:
        ftp.cwd(path)
        ftp.cwd(current)
        return True
    except error_perm:
        return False


def iter_remote_files(ftp: FTP, prefix: str = ""):
    names = ftp_nlst(ftp, prefix or ".")
    for raw in names:
        name = raw.replace("\\", "/").rstrip("/").split("/")[-1]
        if name in (".", ".."):
            continue
        rel = f"{prefix}/{name}".strip("/") if prefix else name
        if is_excluded(rel):
            continue
        remote_path = rel
        if is_remote_dir(ftp, remote_path):
            yield from iter_remote_files(ftp, rel)
        else:
            yield rel


def upload(ftp: FTP, local_root: Path, only: str | None, dry_run: bool) -> int:
    count = 0
    for path, rel in iter_local_files(local_root, only):
        print(f"UP   {rel}")
        if not dry_run:
            parent = str(Path(rel).parent).replace("\\", "/")
            if parent not in (".", ""):
                ftp_makedirs(ftp, parent)
            with path.open("rb") as fh:
                ftp.storbinary(f"STOR {rel}", fh)
        count += 1
    return count


def download(ftp: FTP, local_root: Path, only: str | None, dry_run: bool) -> int:
    if only:
        only = only.replace("\\", "/").strip("/")
        if is_remote_dir(ftp, only):
            rels = list(iter_remote_files(ftp, only))
        else:
            rels = [] if is_excluded(only) else [only]
    else:
        rels = list(iter_remote_files(ftp))

    count = 0
    for rel in rels:
        dest = local_root / rel
        print(f"DOWN {rel}")
        if not dry_run:
            dest.parent.mkdir(parents=True, exist_ok=True)
            with dest.open("wb") as fh:
                ftp.retrbinary(f"RETR {rel}", fh.write)
        count += 1
    return count


def main() -> int:
    parser = argparse.ArgumentParser(description="本機 <-> 遠端 FTP 同步")
    parser.add_argument("action", choices=("up", "down", "status"), help="up 上傳 / down 下載 / status 預覽上傳")
    parser.add_argument("path", nargs="?", help="只同步這個相對路徑（檔案或資料夾）")
    parser.add_argument("--dry-run", action="store_true", help="只列出、不傳檔")
    args = parser.parse_args()

    cfg = load_config()
    local_root = Path(cfg["local_dir"]).resolve()
    if not local_root.is_dir():
        sys.exit(f"本機目錄不存在: {local_root}")

    action = args.action
    dry = args.dry_run or action == "status"
    if action == "status":
        action = "up"

    print(f"本機: {local_root}")
    print(f"遠端: {cfg['user']}  {cfg['host']}:{cfg.get('port', 21)} {cfg.get('remote_dir', '/')}")
    print(f"方向: {'上傳 local -> remote' if action == 'up' else '下載 remote -> local'}")
    if dry:
        print("模式: 預覽（不會寫入）")
    print("略過: db.php、SQL 備份、sync 帳密、.git")
    print("---")

    ftp = connect(cfg)
    try:
        if action == "up":
            n = upload(ftp, local_root, args.path, dry)
        else:
            n = download(ftp, local_root, args.path, dry)
    finally:
        try:
            ftp.quit()
        except Exception:
            ftp.close()

    print("---")
    print(f"完成 {n} 個檔案" + ("（預覽）" if dry else ""))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
