#!/usr/bin/env bash
set -e
PLUGIN_ROOT="$(cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd)"

PLUGIN="user-access-manager"
PLUGIN_BUILDS_PATH="${PLUGIN_ROOT}/builds/${PLUGIN}"

if [[ -d ${PLUGIN_BUILDS_PATH} ]]; then
    rm -R ${PLUGIN_BUILDS_PATH}
fi

mkdir -p ${PLUGIN_BUILDS_PATH}
GIT_IGNORE_FILE=$(cat ${PLUGIN_ROOT}/.gitignore)
EXCLUDES=${GIT_IGNORE_FILE//[[:cntrl:]]/,}

if [[ ${EXCLUDES} != '' ]]; then
    EXCLUDES="${EXCLUDES},"
fi

EXCLUDES="${EXCLUDES}README.md,.travis.yml,composer.json,composer.lock,builds,phpunit.xml.dist,humbug.json.dist,tests,scripts"
eval "rsync -av ${PLUGIN_ROOT}/* ${PLUGIN_BUILDS_PATH} --exclude={${EXCLUDES}}"

git clone https://github.com/grappler/i18n.git "${PLUGIN_ROOT}/tmp/tools"
php "${PLUGIN_ROOT}/tmp/makepot.php" wp-plugin "${PLUGIN_BUILDS_PATH}" "${PLUGIN_BUILDS_PATH}/languages/${PLUGIN}.pot"