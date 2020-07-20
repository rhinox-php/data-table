pushd %~dp0\..\
vendor\bin\phpunit -d memory_limit=-1 %*
popd
