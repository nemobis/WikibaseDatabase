#! /bin/bash

set -x

originalDirectory=$(pwd)

function runMediaWikiSuite {
	installMediaWiki
	installWikibaseDatabaseAsExtension
}

function installMediaWiki {
	cd ..

	wget https://github.com/wikimedia/mediawiki-core/archive/$MW.tar.gz
    tar -zxf $MW.tar.gz
    mv mediawiki-core-$MW phase3

    cd phase3

    mysql -e 'create database its_a_mw;'
    php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin
}

function installWikibaseDatabaseAsExtension {
	cd extensions

	cp -r $originalDirectory WikibaseDatabase

	cd ..

	echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
	echo 'ini_set("display_errors", 1);' >> LocalSettings.php
	echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
	echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
	echo "putenv( 'MW_INSTALL_PATH=$(pwd)' );" >> LocalSettings.php

	doComposerInstall

	php maintenance/update.php --quick

	cd extensions/WikibaseDatabase
	runPhpUnit
}

function runPhpUnit {
	phpunit --testsuite=WikibaseDatabase$TESTSUITE
}

function doComposerInstall {
	composer install --prefer-source
}

function runPdoSuite {
	# TODO
	doComposerInstall
	runPhpUnit
}

function runStandaloneSuite {
	doComposerInstall
	runPhpUnit
}

originalDirectory=$(pwd)

mysql --user root < tests/createTestDB.sql

if [ "$TESTSUITE" == "MediaWiki" ]
then
	runMediaWikiSuite
elif [ "$TESTSUITE" == "PDO" ]
then
	runPdoSuite
else
	runStandaloneSuite
fi
