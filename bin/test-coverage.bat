pushd %~dp0\..\
vendor\bin\paratest --processes=%NUMBER_OF_PROCESSORS% --coverage-html .test-output/coverage %*
popd
