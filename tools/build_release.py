#!/usr/bin/env python3
"""Build a portable WordPress ZIP using only the explicit release manifest."""

import argparse
import hashlib
from pathlib import Path, PurePosixPath
import re
import sys
import zipfile


SLUG = "dayquanta-date-difference-for-forminator"
ROOT = Path(__file__).resolve().parent.parent


def get_version(expected=None):
    plugin = (ROOT / (SLUG + ".php")).read_text(encoding="utf-8-sig")
    readme = (ROOT / "readme.txt").read_text(encoding="utf-8-sig")
    matches = [
        re.search(r"^\s*\*\s*Version:\s*(\S+)", plugin, re.MULTILINE),
        re.search(r"define\(\s*'DMMF_VERSION'\s*,\s*'([^']+)'", plugin),
        re.search(r"^Stable tag:\s*(\S+)", readme, re.MULTILINE),
    ]
    if not all(matches):
        raise ValueError("Missing plugin header version, DMMF_VERSION, or stable tag.")
    versions = [match.group(1) for match in matches]
    if len(set(versions)) != 1:
        raise ValueError("Version mismatch: " + ", ".join(versions))
    version = versions[0]
    if not re.fullmatch(r"\d+\.\d+\.\d+(?:[-.][A-Za-z0-9]+)*", version):
        raise ValueError("Invalid release version: " + version)
    if expected is not None and expected != version:
        raise ValueError("Requested version " + expected + " differs from source " + version)
    return version


def release_files():
    names = []
    manifest = ROOT / "tools" / "release-files.txt"
    for line in manifest.read_text(encoding="utf-8").splitlines():
        name = line.strip()
        if not name or name.startswith("#"):
            continue
        path = PurePosixPath(name)
        if ("\\" in name or path.is_absolute() or path.as_posix() != name
                or any(part.startswith(".") for part in path.parts)
                or ":" in name or name in names):
            raise ValueError("Unsafe or duplicate manifest entry: " + name)
        allowed_root = name in {SLUG + ".php", "uninstall.php", "readme.txt", "LICENSE"}
        allowed_source = name.startswith("src/") and name.endswith(".php")
        allowed_asset = name.startswith("assets/") and path.suffix in {".js", ".css"}
        if not (allowed_root or allowed_source or allowed_asset):
            raise ValueError("Unexpected production-file type: " + name)
        if any(part in {"Development", "fixtures"} or part.startswith("Poc")
               or part.startswith("poc-") for part in path.parts):
            raise ValueError("Development file in manifest: " + name)
        source = ROOT.joinpath(*path.parts)
        if not source.is_file() or ROOT not in source.resolve().parents:
            raise ValueError("Missing file or file outside the repository: " + name)
        names.append(name)
    required = {SLUG + ".php", "uninstall.php", "readme.txt", "LICENSE"}
    if not required.issubset(names):
        raise ValueError("The release manifest is missing a required root file.")
    runtime = {p.relative_to(ROOT).as_posix() for directory in ("src", "assets")
               for p in (ROOT / directory).rglob("*")
               if p.is_file() and p.suffix in {".php", ".js", ".css"}}
    unlisted = runtime - set(names)
    if unlisted:
        raise ValueError("Runtime files missing from manifest: " + ", ".join(sorted(unlisted)))
    return sorted(names)


def build(version, names, output_dir):
    output_dir.mkdir(parents=True, exist_ok=True)
    archive_path = output_dir / (SLUG + "-" + version + ".zip")
    # Stable ZIP metadata and file order make repeat builds reproducible.
    with zipfile.ZipFile(archive_path, "w", compression=zipfile.ZIP_DEFLATED,
                         compresslevel=9) as archive:
        for name in names:
            entry = zipfile.ZipInfo(SLUG + "/" + name, date_time=(1980, 1, 1, 0, 0, 0))
            entry.create_system = 3
            entry.external_attr = 0o100644 << 16
            entry.compress_type = zipfile.ZIP_DEFLATED
            archive.writestr(entry, (ROOT / name).read_bytes(), compresslevel=9)
    with zipfile.ZipFile(archive_path) as archive:
        if archive.testzip() is not None:
            raise ValueError("The generated ZIP failed its integrity check.")
        if archive.namelist() != [SLUG + "/" + name for name in names]:
            raise ValueError("The generated ZIP differs from the release manifest.")
    digest = hashlib.sha256(archive_path.read_bytes()).hexdigest()
    checksum = archive_path.with_suffix(".zip.sha256")
    checksum.write_bytes((digest + "  " + archive_path.name + "\n").encode("ascii"))
    print("Created: " + str(archive_path))
    print("Files: " + str(len(names)) + " | SHA-256: " + digest)
    return archive_path


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--version", help="Expected version; must match source metadata")
    parser.add_argument("--output-dir", type=Path, default=ROOT / "dist")
    args = parser.parse_args()
    try:
        build(get_version(args.version), release_files(), args.output_dir.resolve())
    except (OSError, ValueError, zipfile.BadZipFile) as error:
        print("Build failed: " + str(error), file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
