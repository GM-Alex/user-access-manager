#!/usr/bin/env bash
set -e
PLUGIN_ROOT="$(cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd)"

PLUGIN="user-access-manager"
PLUGIN_BUILDS_PATH="${PLUGIN_ROOT}/builds/${PLUGIN}"

if [[ -d ${PLUGIN_BUILDS_PATH} ]]; then
    rm -R -f ${PLUGIN_BUILDS_PATH}
fi

mkdir -p ${PLUGIN_BUILDS_PATH}
GIT_IGNORE_FILE=$(cat ${PLUGIN_ROOT}/.gitignore)
EXCLUDES=${GIT_IGNORE_FILE//[[:cntrl:]]/,}

if [[ ${EXCLUDES} != '' ]]; then
    EXCLUDES="${EXCLUDES},"
fi

"${PLUGIN_ROOT}/scripts/version.sh"
"${PLUGIN_ROOT}/vendor/bin/wp" i18n make-pot "${PLUGIN_ROOT}" "${PLUGIN_ROOT}/languages/${PLUGIN}.pot" \
    --slug="${PLUGIN}" --exclude=vendor,tests,builds,scripts,tmp --allow-root

EXCLUDES="${EXCLUDES}.gitkeep,README.md,.github,builds,phpunit.xml.dist,infection.json.dist,tests,scripts"
eval "rsync -av ${PLUGIN_ROOT}/* ${PLUGIN_BUILDS_PATH} --exclude={${EXCLUDES}}"

composer --working-dir="${PLUGIN_BUILDS_PATH}" install --no-dev