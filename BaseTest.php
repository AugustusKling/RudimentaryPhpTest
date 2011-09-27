<?php
/**
 * Base for all test classes
 */
abstract class BaseTest {
	/**
	 * @var RudimentaryPhpTest Test executor
	 */
	private $testRunner;
	
	/**
	 * @param RudimentaryPhpTest $testRunner Test executor
	 */
	public function __construct(RudimentaryPhpTest $testRunner){
		$this->testRunner = $testRunner;
	}
	
	/**
	 * Called before a test is run. Typically to start a database transaction.
	 */
	public function setUp(){}
	
	/**
	 * Called after a test is run. Typically to rollback a database transaction.
	 */
	public function tearDown(){}
	
	/**
	 * Type-safe comparison of 2 objects
	 * @param mixed $expected Known object
	 * @param mixed $actual Object as ocurring in test
	 * @param string $message Problem description that is printed on failure
	 */
	public function assertEquals($expected, $actual, $message=NULL){
		$className = get_class($this);
		$methodName = $this->getCallerFunction();
		if($expected===$actual){
			// Record success
			$this->testRunner->assertionSucceeded($className, $methodName);
		} else {
			// Print cause of problem
			if($message===NULL){
				$message = 'Objects did not equal each other in a type safe check';
			}
			echo sprintf('Failed to assert object 1 equals object 2: %s'.PHP_EOL, $message);
			echo 'Object 1 was:'.PHP_EOL;
			var_dump($expected);
			echo 'Object 2 was:'.PHP_EOL;
			var_dump($actual);
			// Record failure
			$this->testRunner->assertionFailed($className, $methodName);
		}
	}
	
	/**
	 * Comparison of 2 objects without type checking.
	 * @param mixed $expected Known object
	 * @param mixed $actual Object as ocurring in test
	 * @param string $message Problem description that is printed on failure
	 */
	public function assertEqualsLoose($expected, $actual, $message=NULL){
		$className = get_class($this);
		$methodName = $this->getCallerFunction();
		if($expected==$actual){
			$this->testRunner->assertionSucceeded($className, $methodName);
		} else {
			if($message===NULL){
				$message = 'Objects did not equal each other in a check with loose typing';
			}
			echo sprintf('Failed to assert object 1 equals object 2: %s'.PHP_EOL, $message);
			echo 'Object 1 was:'.PHP_EOL;
			var_dump($expected);
			echo 'Object 2 was:'.PHP_EOL;
			var_dump($actual);
			$this->testRunner->assertionFailed($className, $methodName);
		}
	}
	
	/**
	 * Records a fail
	 * @param string $message Reason why a place should not have been reached
	 */
	public function fail($message=NULL){
		$className = get_class($this);
		$methodName = $this->getCallerFunction();
		
		if($message===NULL){
			$message = 'This code should never have executed.';
		}
		echo $message.PHP_EOL;
		
		$this->testRunner->assertionFailed($className, $methodName);
	}
	
	/**
	 * Determines the caller of the method which calls this method.
	 * @return string Method name
	 */
	private function getCallerFunction(){
		$trace = debug_backtrace();
		$lastFunctionName = NULL;
		foreach($trace as $traceElement){
			if(is_subclass_of($traceElement['class'], 'BaseTest')){
				$lastFunctionName = $traceElement['function'];
			} else if($lastFunctionName!==NULL){
				return $lastFunctionName;
			}
		}
		throw new Exception('Could not determine caller function.');
	}
}