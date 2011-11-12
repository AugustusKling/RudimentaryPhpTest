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

// Override the default bootstrap class to control test execution. The name of your class does not matter.
class Boot extends RudimentaryPhpTest_Bootstrap {
	public function overrideDefaultOptions(){
		return array(
			RudimentaryPhpTest::OPTION_TESTBASE => realpath('../samples/tests'),
			'XUnitXml.file' => 'log.xml'
		);
	}
	
	public function getListener(){
		// Attach a spreader to supply multiple listeners as constructor arguments
		return new RudimentaryPhpTest_Listener_Spreader(
			// Log to console (and append desired loggers here)
			new RudimentaryPhpTest_Listener_Console(),
			// Log to XML format as well
			new RudimentaryPhpTest_Extension_Listener_XUnitXml($this->getOption('XUnitXml.file'))
		);
	}
}