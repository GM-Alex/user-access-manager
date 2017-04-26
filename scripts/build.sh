#!/usr/bin/env bash
set -e
PLUGIN_ROOT="$(cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd)"
composer install
echo "Execute php codesniffer"
${PLUGIN_ROOT}/vendor/bin/phpcs -p --standard=PSR2 ${PLUGIN_ROOT}/src ${PLUGIN_ROOT}/tests

echo "Execute phpunit"
${PLUGIN_ROOT}/vendor/bin/phpunit --coverage-clover=coverage.clover
wget https://scrutinizer-ci.com/ocular.phar
php ocular.phar code-coverage:upload --format=php-clover coverage.clover

echo "Execute humbug"
${PLUGIN_ROOT}/vendor/bin/humbug
HUMBUG_REPORT_FILE=$(cat ${PLUGIN_ROOT}/humbuglog.json)
REGEX='.*"total":[ ]*([0-9]+).*"kills":[ ]*([0-9]+).*'

if [[ ! ${HUMBUG_REPORT_FILE} =~ ${REGEX} ]] || [[ ${BASH_REMATCH[1]} != ${BASH_REMATCH[2]} ]]; then
    echo "Humbug mutation tests failed"
    exit 1
fi

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