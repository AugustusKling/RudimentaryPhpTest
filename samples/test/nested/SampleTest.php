<?php
/**
 * An example test class. It needs to inherit from RudimentaryPhpTest_BaseTest to be accepted as test.
 */
class SampleTest extends RudimentaryPhpTest_BaseTest {
	public function dummyTest(){
		// Fails and prints message
		$this->assertEquals('dummy', 'dumy', 'test');
		// Fails and prints default message
		$this->assertEquals('dummy', 'dumy');
		// Succeeds. Prints nothing but records success.
		$this->assertEquals('dummy', 'dummy');
	}
	
	public function otherTest(){
		// Succeeds. Prints nothing but records success.
		$this->assertEquals(234, 23+211);
	}
	
	public function typesTest(){
		// Succeeds. Prints nothing but records success.
		$this->assertEqualsLoose(12, '12');
		// Fails and prints default message
		$this->assertEquals(13, '13');
	}
	
	/**
	 * Test comment
	 * @expect InvalidArgumentException
	 */
	public function expectedExceptionTest(){
		throw new InvalidArgumentException();
	}
	
	public function causeFailTest(){
		// Call always records an failed assertion
		$this->fail('Failing by intention');
	}
}