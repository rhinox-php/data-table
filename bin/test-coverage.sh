#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DATE=`date +%Y-%m-%d_%H%M.%S`
pushd $DIR/../
# @todo use paratest
XDEBUG_MODE=coverage vendor/bin/phpunit --order-by=random --coverage-xml .test-output/run_$DATE/coverage-xml --coverage-html .test-output/run_$DATE/coverage --log-junit .test-output/run_$DATE/report.xml -d memory_limit=-1 $@
vendor/bin/psalm --threads=$(getconf _NPROCESSORS_ONLN) --output-format=xml > .test-output/run_$DATE/psalm.xml
vendor/bin/phpmd classes xml phpmd.xml > .test-output/run_$DATE/phpmd.xml
rm -rf .test-output/current
cp -rp .test-output/run_$DATE .test-output/current
popd
