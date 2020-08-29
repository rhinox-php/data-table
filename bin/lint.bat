pushd %~dp0\..\
vendor\bin\php-cs-fixer fix --config=bin\lint.config.php --no-ansi
popd
