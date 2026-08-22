"""Small build-time additions for the Griglia documentation site."""

from pathlib import Path
import re


def on_config(config):
    """Expose the latest released package version to the theme overrides."""
    changelog = Path(config.config_file_path).parent / "CHANGELOG.md"
    match = re.search(r"^## \[(\d+\.\d+\.\d+)\]", changelog.read_text(encoding="utf-8"), re.MULTILINE)

    config.extra["griglia_version"] = match.group(1) if match else "unreleased"
    return config
