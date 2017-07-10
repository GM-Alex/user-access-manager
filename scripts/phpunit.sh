#!/usr/bin/env bash
set -e
PLUGIN_ROOT="$(cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd)"

echo "Execute phpunit"
${PLUGIN_ROOT}/vendor/bin/phpunit --coverage-clover=coverage.clover
wget https://scrutinizer-ci.com/ocular.phar
php ocular.phar code-coverage:upload --format=php-clover coverage.clover