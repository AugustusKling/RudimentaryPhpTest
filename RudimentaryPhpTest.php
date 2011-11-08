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
	const SUMMARY_DELMITER_HORIZONTAL = " ";
	
	/**
	 * @var array Nested array to keep counters for passed and failed assertions
	 */
	private $assertions = array();
	
	/**
	 * Test runner must only be instatiated by static function performTestsAndExit
	 */
	private function __construct(){}
	
	/**
	 * Looks for options in command line arguments
	 * @param array $defaults Mapping containing default option values
	 * @return array Mapping of arguments
	 */
	private static function getOptions(array $defaults){
		global $argv;
		// Remove name of executed script from argument list
		array_shift($argv);
		
		foreach($argv as $token){
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
	 * Loads tests, executes tests, prints summary and exits
	 */
	public static function performTestsAndExit(){
		// Default values for command line arguments
		$optionDefaults = array(
			/*
			 * Path to file or directory containing tests.
			 * Every class that is contained and inherits from BaseTest is executed as test.
			 */
			self::OPTION_TESTBASE => NULL,
			/*
			 * Regular expression to filter tests.
			 * The expression is matched against CLASS->METHOD. For example if the test class was called SampleTest and contained a method called someTest, the expression would be matched against SampleTest->someTest. If the matched succeeds the test will be executed.
			 */
			self::OPTION_TESTFILTER => 'Test$',
			/*
			 * Path to initialization code.
			 * The so called bootstrapping code is responsible for setting up a test environment. Usually it would set up a project's class loader and include a base class for tests (that inherits from BaseTest).
			 * The initialization code is executed exactly once before the first test is run.
			 */
			self::OPTION_BOOTSTRAP => NULL
		);
		// Parse command line arguments
		$options = self::getOptions($optionDefaults);
		if($options[self::OPTION_TESTBASE]===NULL){
			throw new Exception(sprintf('Option %s is missing.', self::OPTION_TESTBASE));
		}
		
		// Create test runner instance
		$testRunner = new self();
		
		// Prepare environment for tests
		$testRunner->bootstrap($options[self::OPTION_BOOTSTRAP]);
		
		// Execute tests
		$testRunner->loadTests($options[self::OPTION_TESTBASE]);
		$testRunner->runTests($options[self::OPTION_TESTFILTER]);
		
		// Print table with test results
		$testRunner->printSummary();
		
		// Fail with error code when an assertion failed
		$testRunner->performExit();
	}
	
	/**
	 * Executes the named file
	 * @param string $file Path to initialization code
	 */
	private function bootstrap($file){
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
		// Instantiate test class
		$test = new $className($this);
		// Check all method names for matching the filter
		$allMethods = get_class_methods($test);
		foreach($allMethods as $methodName){
			// See if method is a test by matching it against the filter pattern
			mb_ereg_search_init($className.'->'.$methodName, $testfilter);
			if(mb_ereg_search()){
				echo sprintf(PHP_EOL."\033[1mRunning %s->%s\033[0m".PHP_EOL, $className, $methodName);
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
		$columnHeaders = array(
			'className' => 'Class Name',
			'methodName' => 'Method Name',
			'succeeded' => 'Succeeded',
			'failed' => 'Failed'
		);
		// Determine longest content lengths to get padding right
		$maxLengths = array();
		foreach($columnHeaders as $headerIndex => $header){
			$maxLengths[$headerIndex] = mb_strlen($header);
		};
		foreach($this->assertions as $className => $assertions){
			foreach($assertions as $methodName => $counts){
				$maxLengths['className'] = max(mb_strlen($className), $maxLengths['className']);
				$maxLengths['methodName'] = max(mb_strlen($methodName), $maxLengths['methodName']);
				$maxLengths['succeeded'] = max(mb_strlen($counts['succeeded']), $maxLengths['succeeded']);
				$maxLengths['failed'] = max(mb_strlen($counts['failed']), $maxLengths['failed']);
			}
		}
		
		// Print column headers
		echo sprintf(
			PHP_EOL
			."\033[1m%-${maxLengths['className']}s".self::SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['methodName']}s".self::SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['succeeded']}s".self::SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['failed']}s\033[0m"
			.PHP_EOL,
			$columnHeaders['className'], $columnHeaders['methodName'], $columnHeaders['succeeded'], $columnHeaders['failed']
		);
		
		// Print tests along with assertion counts
		$noColorCode = "\033[0m";
		foreach($this->assertions as $className => $assertions){
			foreach($assertions as $methodName => $counts){
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
				echo sprintf(
					"%-${maxLengths['className']}s".self::SUMMARY_DELMITER_HORIZONTAL
					."%-${maxLengths['methodName']}s".self::SUMMARY_DELMITER_HORIZONTAL
					.$colorCodeSucceeded."%${maxLengths['succeeded']}s".self::SUMMARY_DELMITER_HORIZONTAL
					.$colorCodeFailed."%${maxLengths['failed']}s"
					.PHP_EOL,
					$className, $methodName, $counts['succeeded'], $counts['failed']).$noColorCode;
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
echo 'Usage: php RudimentaryPhpTest.php --testbase=\'samples\' [ --testfilter=\'Test$\' ] [ --bootstrap=\'….php\' ]'.PHP_EOL;
// Run tests
RudimentaryPhpTest::performTestsAndExit();