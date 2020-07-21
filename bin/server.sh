#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PHP_CLI_SERVER_WORKERS=$(getconf _NPROCESSORS_ONLN)
php -S 0.0.0.0:8990 -t $DIR/../ $DIR/router.php
