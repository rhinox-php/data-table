#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
pushd $DIR/../
vendor/bin/phpunit -d memory_limit=-1 $@
popd
