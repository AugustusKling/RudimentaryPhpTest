<?php
/**
 * Marker interface for assertion providers.
 * User defined assertion interfaces must extend this interface to be recognized.
 */
interface RudimentaryPhpTest_Assertions {
	/**
	 * Asserts that a condition holds
	 */
	public function assertTrue($actual, $message);
	
	/**
	 * Type-safe comparison of 2 objects
	 * @param mixed $expected Known object
	 * @param mixed $actual Object as ocurring in test
	 * @param string $message Description of the assertions meaning
	 */
	public function assertEquals($expected, $actual, $message=NULL);
	
	/**
	 * Comparison of 2 objects without type checking.
	 * @param mixed $expected Known object
	 * @param mixed $actual Object as ocurring in test
	 * @param string $message Description of the assertions meaning
	 */
	public function assertEqualsLoose($expected, $actual, $message=NULL);
	
	/**
	 * Records a fail
	 * @param string $message Reason why a place should not have been reached
	 */
	public function fail($message=NULL);
}