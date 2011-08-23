<?php
require_once dirname(__FILE__).'/../DatabaseTest.php';

class UseDatabaseTest extends DatabaseTest {
	public function oneTest(){}
	
	public function twoTest(){
		throw new Exception('Intended fail');
	}
}