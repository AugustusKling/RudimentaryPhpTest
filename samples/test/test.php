<?php
class SomeOtherTest extends RudimentaryPhpTest_BaseTest {
	public function causeExceptionTest(){
		throw new Exception('Intended fail for test purposes.');
	}
}