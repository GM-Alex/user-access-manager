#!/usr/bin/env bash
set -e

# Takes the stable tag of the readme over from the plugin header, the only place the version is maintained.

PLUGIN_ROOT="$(cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd)"
PLUGIN_MAIN_FILE="${PLUGIN_ROOT}/user-access-manager.php"
README_FILE="${PLUGIN_ROOT}/readme.txt"
VERSION_REGEX='.*Version:[ ]*([0-9]+\.[0-9]+\.[0-9]+-?[A-Za-z]*).*'

if [[ $(cat "${PLUGIN_MAIN_FILE}") =~ ${VERSION_REGEX} ]]; then
    VERSION=${BASH_REMATCH[1]}
else
    echo "Unable to identify current plugin version" 1>&2
    exit 1
fi

sed -i.bak -E "s/^Stable tag:.*$/Stable tag: ${VERSION}/" "${README_FILE}"
rm -f "${README_FILE}.bak"

echo "Stable tag set to ${VERSION}."
