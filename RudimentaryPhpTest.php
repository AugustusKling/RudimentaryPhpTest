<?php
// Ensure PHP shows errors
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', '1');
// Ensure a sensible encoding is used
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

// Load own dependencies
require_once('BaseTest.php');

/**
 * Parses command line arguments and runs tests when instantiated
 */
class RudimentaryPhpTest {
	const OPTION_TESTBASE = 'testbase';
	const OPTION_TESTFILTER = 'testfilter';
	const OPTION_BOOTSTRAP = 'bootstrap';
	
	/**
	 * @var string Delimiter for test summary
	 */
	const SUMMARY_DELMITER_HORIZONTAL = "\t\t";
	
	/**
	 * @var array Nested array to keep counters for passed and failed assertions
	 */
	private $assertions = array();
	
	/**
	 * Test runner must only be instatiated by static function performTestsAndExit
	 */
	private function __construct(){}
	
	/**
	 * Loads tests, executes tests, prints summary and exits
	 */
	public static function performTestsAndExit(){
		// Parse command line arguments
		$optionDefaults = array(
			self::OPTION_TESTBASE.':' => NULL,
			self::OPTION_TESTFILTER.':' => 'Test$',
			self::OPTION_BOOTSTRAP.':' => NULL
		);
		$options = getopt('', array_keys($optionDefaults));
		if(!isset($options[self::OPTION_TESTBASE])){
			throw new Exception(sprintf('Option %s is missing.', self::OPTION_TESTBASE));
		}
		
		// Create test runner instance
		$testRunner = new self();
		
		// Prepare environment for tests
		$testRunner->bootstrap(isset($options[self::OPTION_BOOTSTRAP])?$options[self::OPTION_BOOTSTRAP]:NULL);
		
		// Execute tests
		$testRunner->loadTests($options[self::OPTION_TESTBASE]);
		$testRunner->runTests(isset($options[self::OPTION_TESTFILTER])?$options[self::OPTION_TESTFILTER]:$optionDefaults[self::OPTION_TESTFILTER.':']);
		
		// Print table with test results
		$testRunner->printSummary();
		
		// Fail with error code when an assertion failed
		$testRunner->performExit();
	}
	
	private function bootstrap($file){
		if($file===NULL){
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
		$allClasses = get_declared_classes();
		foreach($allClasses as $className){
			if(is_subclass_of($className, 'BaseTest')){
				// Assume all classes that inherit from BaseTest contain tests
				$this->runTestsOfClass($className, $testfilter);
			}
		}
	}
	
	/**
	 * Runs tests within a test class
	 * @param string $className Name of test class
	 * @param string $testfilter Multi-byte regular expression that is matched against CLASS->METHOD to filter tests.
	 */
	private function runTestsOfClass($className, $testfilter){
		$test = new $className($this);
		$allMethods = get_class_methods($test);
		foreach($allMethods as $methodName){
			// See if method is a test by matching it against filter pattern
			mb_ereg_search_init($className.'->'.$methodName, $testfilter);
			if(mb_ereg_search()){
				echo sprintf(PHP_EOL.'Running %s->%s'.PHP_EOL, $className, $methodName);
				$test->setUp();
				
				// Add counter for assertions
				$this->assertions[$className][$methodName] = array(
					'succeeded' => 0,
					'failed' => 0
				);
				// Check method comment for @expect annotations
				$reflection = new ReflectionMethod($test, $methodName);
				$documentation = $reflection->getDocComment();
				if($documentation===FALSE){
					$expectedExceptions = array();
				} else {
					$expectedExceptions = $this->parseExpectedExceptions($documentation);
				}
				
				try {
					// Invoke test
					$test->$methodName();
				} catch(Exception $e){
					// Catch everything to prevent simple failures in tests from breaking test run
					if(!isset($expectedExceptions[get_class($e)])){
						// Print all exceptions to console that are not expected to happen. Rethrowing breaks stack-trace unfortunately.
						echo $e;
						// Record the unexpected exception as failure
						$this->assertionFailed($className, $methodName);
					} else {
						// Record catch
						$expectedExceptions[get_class($e)] += 1;
					}
				}
				// Check if all expected exceptions did actually happen
				foreach($expectedExceptions as $exceptionName => $countCaught){
					if($countCaught===0){
						// Expected exception was never caught
						echo sprintf('Expected exception %s was not thrown'.PHP_EOL, $exceptionName);
						$this->assertionFailed($className, $methodName);
					}
				}
				
				$test->tearDown();
			}
		}
	}
	
	/**
	 * Records a passed assertion
	 * @param string $className Name of test class
	 * @param string $methodName Name of test method
	 */
	public function assertionSucceeded($className, $methodName){
		$this->assertions[$className][$methodName]['succeeded'] += 1;
	}
	
	/**
	 * Records a failed assertion
	 * @param string $className Name of test class
	 * @param string $methodName Name of test method
	 */
	public function assertionFailed($className, $methodName){
		$this->assertions[$className][$methodName]['failed'] += 1;
	}
	
	/**
	 * Prints a summary of passed and failed assertions
	 */
	private function printSummary(){
		echo PHP_EOL.'Class Name'.self::SUMMARY_DELMITER_HORIZONTAL.'Method Name'.self::SUMMARY_DELMITER_HORIZONTAL.'Succeeded'.self::SUMMARY_DELMITER_HORIZONTAL.'Failed'.PHP_EOL;
		foreach($this->assertions as $className => $assertions){
			foreach($assertions as $methodName => $counts){
				$noColorCode = "\033[0m";
				$colorCodeSucceeded = $noColorCode;
				$colorCodeFailed = $noColorCode;
				if($counts['succeeded']>0){
					// Print passes in green
					$colorCodeSucceeded = "\033[0;32m";
				}
				if($counts['failed']>0){
					// Print failures in red
					$colorCodeFailed = "\033[0;31m";
				}
				echo sprintf('%s'.self::SUMMARY_DELMITER_HORIZONTAL.'%s'.self::SUMMARY_DELMITER_HORIZONTAL.$colorCodeSucceeded.'%d'.self::SUMMARY_DELMITER_HORIZONTAL.$colorCodeFailed.'%d'.PHP_EOL, $className, $methodName, $counts['succeeded'], $counts['failed']).$noColorCode;
			}
		}
	}
	
	/**
	 * Parse annotations from method comment since PHP does not offer annotation support
	 * @param string $documentation Method comment to parse
	 * @return array Mapping of exception name to times caught
	 */
	private function parseExpectedExceptions($documentation){
		// Look for @expect followed by exception name
		mb_ereg_search_init($documentation, '\\s*\\*\\s*@expect\\s+(\\w+)');
		if(mb_ereg_search()){
			$expectedExceptions = array();
			while($exceptionMatch = mb_ereg_search_getregs()){
				// Matched group is exception name
				$expectedExceptions[$exceptionMatch[1]] = 0;
				mb_ereg_search_regs();
			}
			return $expectedExceptions;
		} else {
			return array();
		}
	}
	
	/**
	 * Kills the test runner script to be able to set an exit code
	 */
	private function performExit(){
		// Add line break so console is never messed up
		echo PHP_EOL;
		
		// See if there are any failed assertions
		foreach($this->assertions as $assertions){
			foreach($assertions as $assertion){
				if($assertion['failed']>0){
					// End with error code
					exit(1);
				}
			}
		}
		// End with success code
		exit(0);
	}
}

// Print usage information
echo 'Usage: php -f RudimentaryPhpTest.php -- --testbase=\'samples\' [ --testfilter=\'Test$\' ] [ --bootstrap=\'….php\' ]'.PHP_EOL;
// Run tests
RudimentaryPhpTest::performTestsAndExit();