pushd %~dp0\..\
vendor\bin\phpunit --coverage-html .test-output/coverage -d memory_limit=-1 %*
popd
