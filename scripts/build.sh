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

npm install
${PLUGIN_ROOT}/node_modules/grunt-cli/bin/grunt

EXCLUDES="${EXCLUDES}.gitkeep,README.md,.travis.yml,builds,phpunit.xml.dist,humbug.json.dist,tests,scripts,package.json,Gruntfile.js"
eval "rsync -av ${PLUGIN_ROOT}/* ${PLUGIN_BUILDS_PATH} --exclude={${EXCLUDES}}"

composer --working-dir="${PLUGIN_BUILDS_PATH}" install --no-dev