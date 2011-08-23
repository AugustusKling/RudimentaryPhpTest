<?php
require_once dirname(__FILE__).'/../DatabaseTest.php';

class UseDatabaseTwoTest extends DatabaseTest {
	public function oneTest(){
		$databaseContent = self::$connection->simulateSomeDataRetrieval();
		$this->assertEquals('test stuff', $databaseContent, 'Database sample test failed, and should fail.');
	}
	
	public function twoTest(){}
}