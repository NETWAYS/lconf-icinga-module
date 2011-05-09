#!/bin/bash
php ./phing.php install-module
if [ $? == 0 ]; then
	php ./phing.php -f test.xml test
fi
