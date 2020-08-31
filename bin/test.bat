pushd %~dp0\..\
cls
vendor\bin\phpunit -d memory_limit=-1 %*
popd
