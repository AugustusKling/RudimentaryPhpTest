<?php
/**
 * Contrains default bootstrapping code.
 * Override in your bootstrap file to control behavior and supply project defaults. Your class will be initialized automatically after the bootstrap file was loaded.
 */
class RudimentaryPhpTest_Bootstrap {
	/**
	 * @var RudimentaryPhpTest Test runner to have access to options
	 */
	private $testRunner;
	
	/**
	 * @param RudimentaryPhpTest $testRunner Test runner to have access to options
	 */
	public final function __construct(RudimentaryPhpTest $testRunner){
		$this->testRunner = $testRunner;
	}
	
	/**
	 * Override a default option. Defaults set in bootstrapping code can still be overridden by command line arguments.
	 * @return array Mapping of option names to values if options shall be overridden
	 */
	public function overrideDefaultOptions(){
		return array();
	}
	
	/**
	 * Allows to read configuration options
	 * @param string $option Option name
	 * @return mixed Value of a command line option or default if not given
	 */
	public function getOption($option){
		return $this->testRunner->getOption($option);
	}
	
	/**
	 * Controls logging by specifying the log listener
	 * @return RudimentaryPhpTest_Listener_Console
	 */
	public function getListener(){
		return new RudimentaryPhpTest_Listener_Console(FALSE);
	}
	
	/**
	 * Called just before first test is loaded
	 */
	public function setUp(){
	}
	
	/**
	 * Called just before exiting the test run / when all tests are done
	 */
	public function tearDown(){
	}
}