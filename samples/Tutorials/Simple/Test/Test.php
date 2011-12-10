<?php
// Since this tutorial has no autoloader registered, the implementation under test needs to be loader manually
require_once dirname(__FILE__).'/../Implementation/Text.php';

/**
 * Class which is the unit test that exercises (its name does not matter).
 */
class Tutorials_Simple_Text
	// A test class needs to inherit RudimentaryPhpTest_BaseTest so that it is recognized during test executions
	extends RudimentaryPhpTest_BaseTest {
	
	/**
	 * Each public method within a test class is potentially a test.
	 * The method name does not matter but it needs to match the testfilter option if the test
	 * should be executed. The default filter looks for methods ending with Test.
	 * 
	 * This test implementation checks if a wrapped text can be converted to a string.
	 */
	public function conversionToNativeStringTest(){
		// Set up the things you want to test initially
		$implementationUnderTest = new Tutorials_Simple_Implementation_Text("Hello, I'm there to be tested.");
		
		// Check various ways to get the text back from the wrapper. Assertion do just compare known values with results from method calls.
		
		// Ensure the direct call to the conversion method works
		$this->assertEquals("Hello, I'm there to be tested.", $implementationUnderTest->__toString());
		// Ensure PHP calls the conversion method when it “casts”
		$this->assertEquals("Hello, I'm there to be tested.", (string)$implementationUnderTest);
		// Ensure PHP calls the conversion method when it is ask to convert to strings
		$this->assertEquals("Hello, I'm there to be tested.", strval($implementationUnderTest));
	}
	
	public function startsWithTest(){
		$implementationUnderTest = new Tutorials_Simple_Implementation_Text("Hello, I'm there to be tested.");
		
		// Check a case where the implementation should return FALSE liker earlier
		$this->assertEquals(FALSE, $implementationUnderTest->startWith('Something else'));
		// Do another check
		$this->assertEquals(FALSE, $implementationUnderTest->startWith('Other thing'));
		
		// Ensure the implementation returns TRUE when it should and record a message as well (when your configured listener opts to show it)
		$this->assertTrue($implementationUnderTest->startWith('Hello'), 'Hello is considered to be the start of Hello, …');
	}
}
