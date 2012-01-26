<?php
/**
 * Base for all test classes
 */
abstract class RudimentaryPhpTest_BaseTest implements RudimentaryPhpTest_Assertions {
	/**
	 * @var RudimentaryPhpTest Test executor
	 */
	private $testRunner = NULL;
	
	/**
	 * @var array A List of object that implement additional assertions.
	 * This is construct is neccessary until traits are available in mainstream PHP.
	 */
	private $assertionProviders = array();
	
	/**
	 * @param RudimentaryPhpTest $testRunner Test executor
	 */
	public function setTestRunner(RudimentaryPhpTest $testRunner){
		if($this->testRunner!==NULL){
			throw new Exception('Only to be used internally');
		}
		$this->testRunner = $testRunner;
	}
	
	/**
	 * Registers an assertion provider with the test class.
	 * @param RudimentaryPhpTest_Assertions_Abstract $assertionProvider Implementation for assertions
	 */
	protected function addAssertionProvider(RudimentaryPhpTest_Assertions_Abstract $assertionProvider){
		$this->assertionProviders[] = $assertionProvider;
	}
	
	/**
	 * Invokes an assertion that is not part of the test class but provided by an external class.
	 * @param string $name Assertion name
	 * @param array $arguments Assertion arguments
	 */
	public function __call($name, array $arguments){
		$assertion = NULL;
		// Search all assertion providers for an assertion with the desired name
		foreach($this->assertionProviders as $assertionProvider){
			$assertionProviderReflection = new ReflectionClass($assertionProvider);
			if($assertionProviderReflection->hasMethod($name) && $assertionProviderReflection->getMethod($name)->isPublic()){
				if($assertion!==NULL){
					// More than one assertion provider defines an assertion with the given name
					throw new Exception(sprintf('Assertion %s is ambiguous.', $name));
				}
				$assertion = $assertionProviderReflection->getMethod($name);
			}
		}
		if($assertion===NULL){
			// The assertion was not found
			throw new Exception(sprintf('Method %s is not defined.', $name));
		}
		$assertion->invokeArgs($assertionProvider, $arguments);
	}
	
	/**
	 * Called before a test is run. Typically to start a database transaction.
	 */
	public function setUp(){}
	
	/**
	 * Called after a test is run or after a failed setUp invocation.
	 * Typically to rollback a database transaction.
	 */
	public function tearDown(){}
	
	public function assertTrue($actual, $message){
		if($message===NULL){
			$message = 'Condition has to be true / fulfilled.';
		}
		if($actual){
			// Record success
			$this->testRunner->assertionSucceeded($message);
		} else {
			// Record failure
			$this->testRunner->assertionFailed($message);
		}
	}
	
	public function assertEquals($expected, $actual, $message=NULL){
		if($message===NULL){
			$message = 'Objects are equal in a type-safe check.';
		}
		$areEqual = ($expected===$actual);
		if (!$areEqual) {
			$message .= $this->indent(
				sprintf(PHP_EOL.'expected (%s):%s'.PHP_EOL.'actual (%s):%s', gettype($expected), $this->varDump($expected),
				gettype($actual), $this->varDump($actual))
			);
		}
		$this->assertTrue($areEqual, $message);
	}
	
	/**
	 * @param string $string Text to be indented
	 * @return string Text with a prepended space on each line
	 */
	private function indent($string){
		return preg_replace('/^/um', ' ', $string);
	}
	
	/**
	 * Converts values to string representations
	 * @param mixed $value Anything
	 * @return string Serialized version of the thing
	 */
	private function varDump($value){
		if(is_bool($value)){
			// Make booleans easier to read than standard dump
			$dumped = $value?'TRUE':'FALSE';
		} else {
			$dumped = print_r($value, TRUE);
			// Trim value because some dumps have bogus line breaks attached
			if(!is_string($value)){
				$dumped = preg_replace('/^\\s+|\\s+$/u', '', $dumped);
			}
		}
		// Indent so that strings can't be confused with other log messages
		$dumped = $this->indent($dumped);
		return $dumped;
	}
	
	public function assertEqualsLoose($expected, $actual, $message=NULL){
		if($message===NULL){
			$message = 'Objects are equal in a loose-typed check.';
		}
		$this->assertTrue($expected==$actual, $message);
	}
	
	public function fail($message=NULL){
		if($message===NULL){
			$message = 'This code should never have executed.';
		}
		$this->testRunner->assertionFailed($message);
	}
}
