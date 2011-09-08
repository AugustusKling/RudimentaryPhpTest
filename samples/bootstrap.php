<?php
echo 'Now initializing test environment'.PHP_EOL;

// For example, set up an autoloader
function sample_autoloader($className){
	echo sprintf('Loading %s.'.PHP_EOL, $className);
	$classFile = dirname(__FILE__).'/../'.mb_ereg_replace('_', '/', $className).'.php';
	if(file_exists($classFile)){
		require_once $classFile;
		echo sprintf('Loading of %s succeeded.'.PHP_EOL, $className);
	}
}
spl_autoload_register('sample_autoloader', TRUE);