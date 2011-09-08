<?php
require_once dirname(__FILE__).'/../../RudimentaryPhpTest/Extension/DatabaseTest.php';

class UseDatabaseTest extends RudimentaryPhpTest_Extension_DatabaseTest {
	public function oneTest(){}
	
	public function twoTest(){
		throw new Exception('Intended fail');
	}
}