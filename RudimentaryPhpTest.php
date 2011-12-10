<?php
// Ensure PHP shows errors
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', '1');
// Ensure a sensible encoding is used
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

// Prepare to load own dependencies
spl_autoload_register(array('RudimentaryPhpTest', 'autoloadClassOfRudimentaryPhpTest'));

/**
 * Parses command line arguments and runs tests when instantiated
 */
class RudimentaryPhpTest {
	const OPTION_TESTBASE = 'testbase';
	const OPTION_TESTFILTER = 'testfilter';
	const OPTION_BOOTSTRAP = 'bootstrap';
	
	/**
	 * @var array Default values for command line arguments
	 */
	private $optionDefaults = array(
		/*
		 * Path to file or directory containing tests when given as string. User defined bootstrap code can also provide an iterable that contains multiple paths.
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
		self::OPTION_BOOTSTRAP => NULL
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
	 * @var RudimentaryPhpTest_Bootstrap Default or user defined bootstrap code
	 */
	private $bootstrapImplementation;
	
	/**
	 * Test runner must only be instatiated by static function performTestsAndExit
	 */
	private function __construct(){
		// Parse command line arguments to check if a bootstrap file was provided
		$this->parseOptions();
		
		// Prepare environment for tests, load project defaults
		$bootstrap = $this->bootstrap();
		$this->bootstrapImplementation = $bootstrap;
		foreach($bootstrap->overrideDefaultOptions() as $option => $value) {
		    $this->overrideDefaultOption($option, $value);
		}
		
		// Parse command line arguments again because bootstrap code could have overridden options
		$this->parseOptions();
		
		$this->listener = $bootstrap->getListener();
	}
	
	/**
	 * Looks for options in command line arguments and updates defaults
	 */
	private function parseOptions(){
		global $argv;
		
		foreach($argv as $index => $token){
			// Ignore name of executed script in argument list
			if($index===0){
				continue;
			}
			
			// Split up each option into name and value
			if(mb_ereg('--(.*)=(.*)', $token, $argument)!==FALSE){
				// Override default value
				$this->optionDefaults[$argument[1]] = $argument[2];
			} else {
				throw new Exception(sprintf('Could not parse command line argument %s', $token));
			}
		}
	}
	
	/**
	 * Overrides an option's default value.
	 * @param string $option Any of the OPTION_* constants in this class or a user-defined name
	 * @param string $value Value to use if option is not provided as command line argument.
	 * @throws Exception
	 */
	private function overrideDefaultOption($option, $value){
	    if($option===self::OPTION_BOOTSTRAP){
	        throw new Exception('Overriding the bootstrapping option has no effect.');
	    }
	    $this->optionDefaults[$option] = $value;
	}
	
	/**
	 * Reads an option after bootstrapping
	 * @param string $option Name of the option
	 * @return mixed Option value that will be used during tests
	 */
	public function getOption($option){
	    if(!array_key_exists($option, $this->optionDefaults)){
	        throw new Exception(sprintf('Option %s does not exist.', $option));
	    }
	    return $this->optionDefaults[$option];
	}
	
	/**
	 * Loads tests, executes tests, prints summary and exits
	 */
	public static function performTestsAndExit(){
		$testRunner = new self();
		
		// Fail with error code when an assertion failed
        register_shutdown_function(array($testRunner, 'performExit'));
		
		$testbaseOption = $testRunner->getOption(self::OPTION_TESTBASE);
		if($testbaseOption===NULL){
			throw new Exception(sprintf('Option %s is missing.', self::OPTION_TESTBASE));
		}
		
		// Allow user defined bootstrap classes to initialize things
		$testRunner->bootstrapImplementation->setUp();
		
		if(is_string($testbaseOption)){
			$testbaseOption = array($testbaseOption);
		}
		foreach($testbaseOption as $testbase){
			$testbase = realpath($testbase);
			// Load tests
			$testRunner->loadTests($testbase);
			// Execute tests
			$testRunner->listener->setUpSuite($testbase);
			$testRunner->runTests($testbase, $testRunner->getOption(self::OPTION_TESTFILTER));
			$testRunner->listener->tearDownSuite($testbase);
		}
		
		$testRunner->bootstrapImplementation->tearDown();
	}
	
	/**
	 * Executes the named file
	 * @return RudimentaryPhpTest_Bootstrap Bootstrap implementation
	 */
	private function bootstrap(){
	    $file = $this->getOption(self::OPTION_BOOTSTRAP);
		if($file===NULL){
			// Run tests with default initialization code since user choose not to give initialization code
			return new RudimentaryPhpTest_Bootstrap($this);
		}
		if(!file_exists($file)){
			throw new Exception('Could not read bootstrap file');
		}
		// Load user defined bootstrap class
		require_once($file);
		$allClasses = get_declared_classes();
		foreach($allClasses as $className){
		    $classReflection = new ReflectionClass($className);
			if($classReflection->isSubclassOf('RudimentaryPhpTest_Bootstrap') && $classReflection->isInstantiable()){
				return $classReflection->newInstance($this);
			}
		}
		throw new Exception('User defined bootstrapping requested but no implementation provided');
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
	 * @param string $testbase Path within which tests should be executed
	 * @param string $testfilter Multi-byte regular expression that is matched against CLASS->METHOD to filter tests.
	 */
	private function runTests($testbase, $testfilter){
		// Check all loaded classes for containing tests
		$allClasses = get_declared_classes();
		foreach($allClasses as $className){
		    $classReflection = new ReflectionClass($className);
			if($classReflection->isSubclassOf('RudimentaryPhpTest_BaseTest') && $classReflection->isInstantiable() && $this->containsClass($testbase, $classReflection)){
				// Assume all classes that inherit from RudimentaryPhpTest_BaseTest contain tests
				$this->listener->setUpClass($className);
				$this->runTestsOfClass($className, $testfilter);
				$this->listener->tearDownClass($className);
			}
		}
	}
	
	/**
	 * Checks if a file or folder contains a class definition
	 * @param string $testbase File or folder that might contain class
	 * @param ReflectionClass $classReflection
	 * @return boolean TRUE if class definition is contained
	 */
	private function containsClass($testbase, ReflectionClass $classReflection){
		return mb_substr($classReflection->getFileName(), 0, mb_strlen($testbase))===$testbase;
	}
	
	/**
	 * Runs tests within a test class
	 * @param string $className Name of test class
	 * @param string $testfilter Multi-byte regular expression that is matched against CLASS->METHOD to filter tests.
	 */
	private function runTestsOfClass($className, $testfilter){
		// Instantiate test class
		$test = new $className();
		$test->setTestRunner($this);
		// Check all method names for matching the filter
		$allMethods = get_class_methods($test);
		foreach($allMethods as $methodName){
			// See if method is a test by matching it against the filter pattern
			mb_ereg_search_init($className.'->'.$methodName, $testfilter);
			if(mb_ereg_search()){
				$method = new ReflectionMethod($test, $methodName);
				$this->listener->setUpTest($className, $methodName, $method->getFileName(), $method->getStartLine());
				$test->setUp();
				
				// Check method comment for @expect annotations
				$expectedException = $this->parseExpectedException($method);
				
				$testOutput = NULL;
				try {
					// Invoke test
					ob_start();
					$testCallLine = __LINE__ + 1;
					$test->$methodName();
					$testOutput = ob_get_clean();
					
					if($expectedException!==NULL){
						// Expected exception was not caught
						$caughtAllExpectedExceptions = FALSE;
						$this->assertionFailedInternal(__CLASS__, __METHOD__, __FILE__, $testCallLine,
    						sprintf('Expected exception %s was not thrown', $exceptionName));
					}
				} catch(Exception $e){
					$testOutput = ob_get_clean();
					// Catch everything to prevent simple failures in tests from breaking test run
					if($expectedException===NULL || !($e instanceof $expectedException)){
						// Record the unexpected exception as failure
						$this->assertionFailedInternal($className, $methodName, $e->getFile(), $e->getLine(), 'Unexpected exception caught.');
						$this->listener->unexpectedException($className, $methodName, $e);
					} else {
						// Record catch
						$this->assertionSucceededInternal($className, $methodName, $e->getFile(), $e->getLine(), 'Caught expected exception.');
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
	 * Find the call in user-supplied tests that invoked methods of the base test.
	 * This is used to get the line of code that invoked an assertion such as assertions.
	 * @return array Location of code that called an assertive method. Contains keys: class, method, file and index.
	 */
	private function getCaller(){
		$trace = debug_backtrace();
		$caller = NULL;
		foreach($trace as $traceElement){
			// Ignore additional stack entries due to reflection use and own virtual methods
			if(($traceElement['class']==='ReflectionMethod' && $traceElement['function']==='invokeArgs') || ($traceElement['class']==='RudimentaryPhpTest_BaseTest' && $traceElement['function']==='__call')){
				continue;
			}
			
			// Consider everything as an assertive call that is called fail or starts with assert
			$isAssertive = $traceElement['function']==='fail' || mb_ereg_match('^assert.', $traceElement['function']);
			// Add first non-assertive call after an assertive call was found to allow for building user defined assertions that rely on provided assertions
			if($caller!==NULL && !$isAssertive){
				$caller['method'] = $traceElement['function'];
				return $caller;
			}
			if(!array_key_exists('object', $traceElement)){
				continue;
			}
			$reflection = new ReflectionClass($traceElement['object']);
			// Look for assertive calls only within test code, not within code under test
			if($reflection->isSubclassOf('RudimentaryPhpTest_BaseTest') && $isAssertive){
				$caller = array(
					'class' => $reflection->getName(),
					'file' => $traceElement['file'],
					'line' => $traceElement['line']
				);
			}
		}
		throw new Exception('Could not determine caller.');
	}
	
	/**
	 * Records a passed assertion
	 * @param string $message Explanation of assertion purpose
	 */
	public function assertionSucceeded($message){
		$caller = $this->getCaller();
		$this->assertionSucceededInternal($caller['class'], $caller['method'], $caller['file'], $caller['line'], $message);
	}
	/**
	 * Records a passed assertion
	 * @param string $className Name of test class
	 * @param string $methodName Name of test method
	 * @param string $file Path to the file in which the assertion was called
	 * @param integer $line Line number where the assertion was called
	 * @param string $message Explanation of assertion purpose
	 */
	private function assertionSucceededInternal($className, $methodName, $file, $line, $message){
		// Capture test output and disable buffering so that listers' output can not be recorded as test output
		$testOutput = ob_get_clean();
		$this->listener->assertionSuccess($className, $methodName, $file, $line, $message);
		// Re-enable capturing of test output
		ob_start();
		// Add earlier test output to new buffer
		echo $testOutput;
	}
	
	/**
	 * Records a failed assertion
	 * @param string $message Explanation of assertion purpose
	 */
	public function assertionFailed($message){
		$caller = $this->getCaller();
		$this->assertionFailedInternal($caller['class'], $caller['method'], $caller['file'], $caller['line'], $message);
	}
	
	/**
	 * Records a failed assertion
	 * @param string $className Name of test class
	 * @param string $methodName Name of test method
	 * @param string $file Path to the file in which the assertion was called
	 * @param integer $line Line number where the assertion was called
	 * @param string $message Explanation of assertion purpose
	 */
	private function assertionFailedInternal($className, $methodName, $file, $line, $message){
		$this->atLeastOneAssertionFailed = TRUE;
		// Capture test output and disable buffering so that listers' output can not be recorded as test output
		$testOutput = ob_get_clean();
		$this->listener->assertionFailure($className, $methodName, $file, $line, $message);
		// Re-enable capturing of test output
		ob_start();
		// Add earlier test output to new buffer
		echo $testOutput;
	}
	
	/**
	 * Parse annotations from method comment since PHP does not offer annotation support.
	 * Annotate an expected exception by @expect followed by the exception's class name.
	 * @param ReflectionMethod $method Method whose comment to parse
	 * @return string|NULL Exception class name of exception expected to be caught
	 */
	private function parseExpectedException(ReflectionMethod $method){
		$documentation = $method->getDocComment();
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
			return $expectedException;
		}
		
		// No @expect annotation was found
		return NULL;
	}
	
	/**
	 * Kills the test runner script to be able to set an exit code
	 */
	public function performExit(){
		// Print those errors which are fatal for PHP (stack trace is not available)
		$error = error_get_last();
		if($error!==NULL){
			switch($error['type']){
				case E_ERROR:
				case E_PARSE:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
					echo PHP_EOL.sprintf('Fatal error for PHP (type %d) in %s at line %d: %s', $error['type'], $error['file'], $error['line'], $error['message']);
			}
		}
		
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
	
	/**
	 * Tries to load files belonging to RudimentaryPhpTest and aborts the script run if it fails to do so
	 * @param string $className Name of class that shall be defined
	 */
	public static function autoloadClassOfRudimentaryPhpTest($className){
		$start = mb_substr($className, 0, mb_strlen(__CLASS__.'_'));
		if($start===__CLASS__.'_'){
			// One of the classes of RudimentaryPhpTest should be loaded
			$relativePath = mb_ereg_replace('_', DIRECTORY_SEPARATOR, $className).'.php';
			require(dirname(__FILE__).DIRECTORY_SEPARATOR.$relativePath);
		}
	}
}

// Print usage information
echo 'Usage: php RudimentaryPhpTest.php --testbase=\'samples/test\' [ --testfilter=\'Test$\' ] [ --bootstrap=\'….php\' ]'.PHP_EOL;
// Run tests
RudimentaryPhpTest::performTestsAndExit();