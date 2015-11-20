<?php

// The Nette Tester command-line runner can be
// invoked through the command: ../vendor/bin/tester .

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

// create temporary directory
define('TEMP_DIR', __DIR__ . '/tmp');
@mkdir(TEMP_DIR); // @ - directory may already exist

define('MYSQL_DSN', 'mysql:host=127.0.0.1;dbname=test');
define('MYSQL_USER', 'root');
define('MYSQL_PASSWORD', 'pass');

define('SCHEMA_PATH', __DIR__ . '/../sql/schema.sql');

function before(\Closure $function = NULL)
{
	static $val;
	if (!func_num_args()) {
		return ($val ? $val() : NULL);
	}
	$val = $function;
}

function test(\Closure $function)
{
	before();
	$function();
}