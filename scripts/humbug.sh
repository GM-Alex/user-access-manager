#!/usr/bin/env bash
set -e
PLUGIN_ROOT="$(cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd)"

echo "Execute humbug"
${PLUGIN_ROOT}/vendor/bin/humbug
HUMBUG_REPORT_FILE=$(cat ${PLUGIN_ROOT}/humbuglog.json)
REGEX='.*"total":[ ]*([0-9]+).*"kills":[ ]*([0-9]+).*'

if [[ ! ${HUMBUG_REPORT_FILE} =~ ${REGEX} ]] || [[ $((${BASH_REMATCH[1]} - ${BASH_REMATCH[2]})) > 1 ]]; then
    echo "Humbug mutation tests failed"
    exit 1
fi