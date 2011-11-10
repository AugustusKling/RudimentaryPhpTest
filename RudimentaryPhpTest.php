<?php
// Ensure PHP shows errors
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', '1');
// Ensure a sensible encoding is used
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

// Load own dependencies
require_once('RudimentaryPhpTest/BaseTest.php');
require_once('RudimentaryPhpTest/Listener.php');
require_once('RudimentaryPhpTest/Listener/Console.php');

/**
 * Parses command line arguments and runs tests when instantiated
 */
class RudimentaryPhpTest {
	const OPTION_TESTBASE = 'testbase';
	const OPTION_TESTFILTER = 'testfilter';
	const OPTION_BOOTSTRAP = 'bootstrap';
	const OPTION_LISTENER = 'listener';
	
	/**
	 * @var array Default values for command line arguments
	 */
	private static $optionDefaults = array(
		/*
		 * Path to file or directory containing tests.
		 * Every class that is contained and inherits from RudimentaryPhpTest_BaseTest is executed as test.
		 */
		self::OPTION_TESTBASE => NULL,
		/*
		 * Regular expression to filter tests.
		 * The expression is matched against CLASS->METHOD. For example if the test class was called SampleTest and contained a method called someTest, the expression would be matched against SampleTest->someTest. If the matched succeeds the test will be executed.
		 */
		self::OPTION_TESTFILTER => 'Test$',
		/*
		 * Path to initialization code.
		 * The so called bootstrapping code is responsible for setting up a test environment. Usually it would set up a project's class loader and include a base class for tests (that inherits from RudimentaryPhpTest_BaseTest).
		 * The initialization code is executed exactly once before the first test is run.
		 */
		self::OPTION_BOOTSTRAP => NULL,
		/*
		 * Instance of RudimentaryPhpTest_Listener to be used instead of the default RudimentaryPhpTest_Listener_Console instance to print to the console.
		 * Set an instance of another implementation to control logging.
		 */
		self::OPTION_LISTENER => NULL
	);
	
	/**
	 * @var RudimentaryPhpTest_Listener Listener to take record of test execution progress. The listener should not be used to influence the tests.
	 */
	private $listener;
	
	/**
	 * @var boolean Flag to record if at least one assertion failed in order to set exit code
	 */
	private $atLeastOneAssertionFailed = FALSE;
	
	/**
	 * Test runner must only be instatiated by static function performTestsAndExit
	 */
	private function __construct(RudimentaryPhpTest_Listener $listener){
		$this->listener = $listener;
	}
	
	/**
	 * Looks for options in command line arguments
	 * @param array $defaults Mapping containing default option values
	 * @return array Mapping of arguments
	 */
	private static function getOptions(array $defaults){
		global $argv;
		
		foreach($argv as $index => $token){
			// Ignore name of executed script in argument list
			if($index===0){
				continue;
			}
			
			// Split up each option into name and value
			if(mb_ereg('--(.*)=(.*)', $token, $argument)!==FALSE){
				// Override default value
				$defaults[$argument[1]] = $argument[2];
			} else {
				throw new Exception(sprintf('Could not parse command line argument %s', $token));
			}
		}
		return $defaults;
	}
	
	/**
	 * Overrides an option's default value.
	 * Intended to be called from a bootstrap file to give sensible defaults. Values set with this
	 * method do never override values that are provided as command line arguments. 
	 * @param string $option Any of the OPTION_* constants in this class
	 * @param string $value Value to use if option is not provided as command line argument.
	 * @throws Exception
	 */
	public static function overrideDefaultOption($option, $value){
	    if($option===self::OPTION_BOOTSTRAP){
	        throw new Exception('Overriding the bootstrapping option has no effect.');
	    }
	    if(!array_key_exists($option, self::$optionDefaults)){
	        throw new Exception('Provided option does not exist');
	    }
	    self::$optionDefaults[$option] = $value;
	}
	
	/**
	 * Loads tests, executes tests, prints summary and exits
	 */
	public static function performTestsAndExit(){
		// Check if a bootstrap file was provided
		$options = self::getOptions(self::$optionDefaults);
		
		// Prepare environment for tests
		self::bootstrap($options[self::OPTION_BOOTSTRAP]);
		
		// Parse command line arguments again because bootstrap code could overridden default options
		$options = self::getOptions(self::$optionDefaults);
		if($options[self::OPTION_TESTBASE]===NULL){
			throw new Exception(sprintf('Option %s is missing.', self::OPTION_TESTBASE));
		}
		
		// Create test runner instance
		$listener = $options[self::OPTION_LISTENER];
		if($listener===NULL){
			$listener = new RudimentaryPhpTest_Listener_Console();
		}
		$testRunner = new self($listener);
		
		// Execute tests
		$testRunner->loadTests($options[self::OPTION_TESTBASE]);
		$testRunner->listener->setUpSuite(realpath($options[self::OPTION_TESTBASE]));
		$testRunner->runTests($options[self::OPTION_TESTFILTER]);
		$testRunner->listener->tearDownSuite(realpath($options[self::OPTION_TESTBASE]));
		
		// Fail with error code when an assertion failed
		$testRunner->performExit();
	}
	
	/**
	 * Executes the named file
	 * @param string $file Path to initialization code
	 */
	private static function bootstrap($file){
		if($file===NULL){
			// Run tests without initialization code since user choose not to give initialization code
			return;
		}
		if(!file_exists($file)){
			throw new Exception('Could not read bootstrap file');
		}
		require_once $file;
	}
	
	/**
	 * Loads test classes into namespace
	 * @param string $testbase Path to file or folder with tests
	 */
	private function loadTests($testbase){
		if(!file_exists($testbase)){
			throw new Exception(sprintf('Testbase %s was not existing or could not be read.', $testbase));
		}
		$baseInfo = new SplFileInfo($testbase);
		switch($baseInfo->getType()){
			case 'file':
				// Load a single test class
				require_once($testbase);
				break;
			case 'dir':
				// Walk directory recursively and load all test classes
				$dirIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testbase));
				foreach($dirIterator as $path => $info){
					// Assume all PHP files in the given directory are tests
					if(pathinfo($info->getFileName(), PATHINFO_EXTENSION)==='php'){
						require_once($path);
					}
				}
				break;
			default:
				// Only files and directories are supported currently
				throw new Exception(sprintf('%s is not a valid testbase file or directory containing testbase files', $testbase));
		}
	}
	
	/**
	 * Looks for tests in all loaded classes and executes them
	 * @param string $testfilter Multi-byte regular expression that is matched against CLASS->METHOD to filter tests.
	 */
	private function runTests($testfilter){
		// Check all loaded classes for containing tests
		$allClasses = get_declared_classes();
		foreach($allClasses as $className){
			if(is_subclass_of($className, 'RudimentaryPhpTest_BaseTest')){
				// Assume all classes that inherit from RudimentaryPhpTest_BaseTest contain tests
				$this->listener->setUpClass($className);
				$this->runTestsOfClass($className, $testfilter);
				$this->listener->tearDownClass($className);
			}
		}
	}
	
	/**
	 * Runs tests within a test class
	 * @param string $className Name of test class
	 * @param string $testfilter Multi-byte regular expression that is matched against CLASS->METHOD to filter tests.
	 */
	private function runTestsOfClass($className, $testfilter){
		// Instantiate test class
		$test = new $className($this);
		// Check all method names for matching the filter
		$allMethods = get_class_methods($test);
		foreach($allMethods as $methodName){
			// See if method is a test by matching it against the filter pattern
			mb_ereg_search_init($className.'->'.$methodName, $testfilter);
			if(mb_ereg_search()){
				$this->listener->setUpTest($className, $methodName);
				$test->setUp();
				
				// Check method comment for @expect annotations
				$expectedException = $this->parseExpectedException($test, $methodName);
				
				$testOutput = NULL;
				try {
					// Invoke test
					ob_start();
					$test->$methodName();
					$testOutput = ob_get_clean();
					
					if($expectedException!==NULL){
						// Expected exception was not caught
						$caughtAllExpectedExceptions = FALSE;
						$this->assertionFailed($className, $methodName,
							sprintf('Expected exception %s was not thrown', $exceptionName));
					}
				} catch(Exception $e){
					$testOutput = ob_get_clean();
					// Catch everything to prevent simple failures in tests from breaking test run
					if($expectedException!==get_class($e)){
						// Record the unexpected exception as failure
						$this->assertionFailed($className, $methodName, 'Unexpected exception caught.');
						$this->listener->unexpectedException($className, $methodName, $e);
					} else {
						// Record catch
						$this->assertionSucceeded($className, $methodName, 'Caught expected exception.');
					}
				}
				
				$this->listener->tearDownTest($className, $methodName, $testOutput);
				$test->tearDown();
			} else {
				$this->listener->skippedTest($className, $methodName);
			}
		}
	}
	
	/**
	 * Records a passed assertion
	 * @param string $className Name of test class
	 * @param string $methodName Name of test method
	 * @param string $message Explanation of assertion purpose
	 */
	public function assertionSucceeded($className, $methodName, $message){
		$this->listener->assertionSuccess($className, $methodName, $message);
	}
	
	/**
	 * Records a failed assertion
	 * @param string $className Name of test class
	 * @param string $methodName Name of test method
	 * @param string $message Explanation of assertion purpose
	 */
	public function assertionFailed($className, $methodName, $message){
		$this->atLeastOneAssertionFailed = TRUE;
		$this->listener->assertionFailure($className, $methodName, $message);
	}
	
	/**
	 * Parse annotations from method comment since PHP does not offer annotation support.
	 * Annotate an expected exception by @expect followed by the exception's class name.
	 * @param string $documentation Method comment to parse
	 * @return string|NULL Exception class name of exception expected to be caught
	 */
	private function parseExpectedException($test, $methodName){
		$reflection = new ReflectionMethod($test, $methodName);
		$documentation = $reflection->getDocComment();
		if($documentation===FALSE){
			// There is not method comment so it can't declare an expected exception
			return NULL;
		}
		
		// Look for @expect followed by exception name
		mb_ereg_search_init($documentation, '\\s*\\*\\s*@expect\\s+(\\w+)');
		if(mb_ereg_search()){
			$expectedException = NULL;
			while($exceptionMatch = mb_ereg_search_getregs()){
				if($expectedException!==NULL){
					throw new Exception(sprintf('More than 1 expected exceptions declared for %s->%s.', get_class($test), $methodName));
				}
				// Matched group is exception name
				$expectedException = $exceptionMatch[1];
				mb_ereg_search_regs();
			}
		}
		
		// No @expect annotation was found
		return NULL;
	}
	
	/**
	 * Kills the test runner script to be able to set an exit code
	 */
	private function performExit(){
		// Add line break so console is never messed up
		echo PHP_EOL;
		
		// See if there are any failed assertions
		if($this->atLeastOneAssertionFailed){
			// End with error code
			exit(1);
		}
		// End with success code
		exit(0);
	}
}

// Print usage information
echo 'Usage: php RudimentaryPhpTest.php --testbase=\'samples/test\' [ --testfilter=\'Test$\' ] [ --bootstrap=\'….php\' ]'.PHP_EOL;
// Run tests
RudimentaryPhpTest::performTestsAndExit();