<?php
/**
 * Base for all test classes
 */
abstract class RudimentaryPhpTest_BaseTest {
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
	 * Asserts that a condition holds
	 */
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
	
	/**
	 * Type-safe comparison of 2 objects
	 * @param mixed $expected Known object
	 * @param mixed $actual Object as ocurring in test
	 * @param string $message Description of the assertions meaning
	 */
	public function assertEquals($expected, $actual, $message=NULL){
		if($message===NULL){
			$message = 'Objects are equal in a type-safe check.';
		}
		$this->assertTrue($expected===$actual, $message);
	}
	
	/**
	 * Comparison of 2 objects without type checking.
	 * @param mixed $expected Known object
	 * @param mixed $actual Object as ocurring in test
	 * @param string $message Description of the assertions meaning
	 */
	public function assertEqualsLoose($expected, $actual, $message=NULL){
		if($message===NULL){
			$message = 'Objects are equal in a loose-typed check.';
		}
		$this->assertTrue($expected==$actual, $message);
	}
	
	/**
	 * Records a fail
	 * @param string $message Reason why a place should not have been reached
	 */
	public function fail($message=NULL){
		if($message===NULL){
			$message = 'This code should never have executed.';
		}
		$this->testRunner->assertionFailed($message);
	}
}