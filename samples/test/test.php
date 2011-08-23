<?php
class SomeOtherTest extends BaseTest {
	public function causeExceptionTest(){
		throw new Exception('Intended fail for test purposes.');
	}
}