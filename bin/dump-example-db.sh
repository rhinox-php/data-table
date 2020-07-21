#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
mysqldump --add-drop-database --skip-comments --databases rhino_data_table_examples > $DIR/../examples/includes/database.sql
