pushd %~dp0\..\
vendor\bin\paratest --functional --processes=%NUMBER_OF_PROCESSORS% %*
popd