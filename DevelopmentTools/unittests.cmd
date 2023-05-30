CD %~dp0
CD ..

SourceCode\vendor\bin\phpunit --testdox -c tests\phpunit.xml tests\UnitTests.php %1 %2 %3 %4 %5
