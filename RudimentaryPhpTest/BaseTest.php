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
	 * Called after a test is run. Typically to rollback a database transaction.
	 */
	public function tearDown(){}
	
	public function assertTrue($actual, $message){
		if($message===NULL){
			$message = 'Conditon has to be true / fulfilled.';
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
		$this->assertTrue($expected===$actual, $message);
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