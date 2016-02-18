<?php
/*
https://phpunit.de/manual/current/en/phpunit-book.html
https://phpunit.de/manual/current/en/appendixes.assertions.html

Apparently, I can't get a log for timing (or other custom things) exporting TAP. Exporting XML means
some kind of insane crap to convert it to html, so exporting as json seems to be the way to go. 

I don't know how to export files as timestamped. the current solution in the batch file works but it's
only accurate to the minute, not second. 

https://phpunit.de/manual/current/en/database.html
https://stackoverflow.com/questions/7911535/how-to-unit-test-curl-call-in-php
*/
define("PROJECT_PATH",realpath(dirname(dirname(__FILE__))."/project"));
include_once(PROJECT_PATH."/src/embedbug/Embedbug.php");