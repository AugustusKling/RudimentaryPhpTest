<?php
class RudimentaryPhpTest_Extension_Assertions_Text extends RudimentaryPhpTest_Assertions_Abstract {
	/**
	 * Asserts that a string finishes with another string
	 * @param string $suffix Required finish
	 * @param string $string The whole string
	 * @param string $message Description of the assertions meaning
	 */
	public function assertStringEndsWith($suffix, $string, $message = ''){
		$start = mb_strlen($string) - mb_strlen($suffix);
		if($start<0){
			$this->fail($message);
		} else {
			$actual = mb_substr($string, $start, mb_strlen($suffix));
			$this->assertTrue($suffix===$actual, $message);
		}
	}
	
	/**
	 * Asserts that a string starts with another string
	 * @param string $suffix Required start
	 * @param string $string The whole string
	 * @param string $message Description of the assertions meaning
	 */
	public function assertStringStartsWith($prefix, $string, $message = ''){
		if(mb_strlen($prefix)>mb_strlen($string)){
			$this->fail($message);
		} else {
			$actual = mb_substr($string, 0, mb_strlen($prefix));
			$this->assertTrue($actual===$prefix, $message);
		}
	}
}