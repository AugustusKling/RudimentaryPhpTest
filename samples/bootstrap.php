<?php
echo 'Now initializing test environment from bootstrapping code.'.PHP_EOL;

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

// Another example would be to override logging to the console
require_once('RudimentaryPhpTest/Listener/Spreader.php');
// Override a default option. Defaults set in bootstrapping code can still be overridden by command line arguments.
RudimentaryPhpTest::overrideDefaultOption(RudimentaryPhpTest::OPTION_LISTENER,
	// Attach a spreader to supply multiple listeners as constructor arguments
	new RudimentaryPhpTest_Listener_Spreader(
		// Log to console (and append desired loggers here)
		new RudimentaryPhpTest_Listener_Console()
	)
);