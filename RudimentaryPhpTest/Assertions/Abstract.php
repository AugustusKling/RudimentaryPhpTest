<?php
/**
 * Base class for assertion providers in order to make tests assertions available to assertion providers
 */
abstract class RudimentaryPhpTest_Assertions_Abstract implements RudimentaryPhpTest_Assertions {
	/**
	 * @var RudimentaryPhpTest_BaseTest Running test
	 */
	private $test;
	
	/**
	 * @param RudimentaryPhpTest_BaseTest $test Running test
	 */
	public function __construct(RudimentaryPhpTest_BaseTest $test){
		$this->test = $test;
	}
	
	public function assertTrue($actual, $message){
		$this->test->assertTrue($actual, $message);
	}
	
	public function assertEquals($expected, $actual, $message=NULL){
		$this->test->assertEquals($expected, $actual, $message);
	}
	
	public function assertEqualsLoose($expected, $actual, $message=NULL){
		$this->test->assertEqualsLoose($expected, $actual, $message);
	}
	
	public function fail($message=NULL){
		$this->test->fail($message);
	}
}