CD %~dp0
CD ..

:complete
CALL composer validate --strict
CALL composer install --prefer-dist
ECHO outdated:
CALL composer outdated

ECHO Checking code styles...
php SourceCode\vendor\bin\phpcs -sp --standard=ruleset.xml SourceCode

ECHO Testing...
CD DevelopmentTools
CALL UnitTests.cmd

:end
