<?php
class AssertionProviderAutoloadTest extends RudimentaryPhpTest_BaseTest {
	public function __construct(){
		// Load text related assertions
		$this->addAssertionProvider(
			new RudimentaryPhpTest_Extension_Assertions_Text($this)
		);
	}
	
	public function failingTest(){
		$this->assertStringEndsWith('ande', 'Gelände', 'should fail');
		$this->assertStringStartsWith('Gela', 'Gelände', 'should fail');
	}
	
	public function succeedingTest(){
		$this->assertStringEndsWith('ände', 'Gelände', 'should match');
		$this->assertStringStartsWith('Gelä', 'Gelände', 'should match');
	}
}