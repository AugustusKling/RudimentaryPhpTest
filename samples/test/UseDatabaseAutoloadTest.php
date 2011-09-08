<?php
// Note: This test uses autoloading. Specify /samples/bootstrap.php for environment set up to enable a sample autoloader.

class UseDatabaseAutoloadTest extends RudimentaryPhpTest_Extension_DatabaseTest {
	public function oneFailingTest(){
		$databaseContent = self::$connection->simulateSomeDataRetrieval();
		$this->assertEquals('test stuff', $databaseContent, 'Database sample test failed, and should fail.');
	}
	
	public function twoSucceedingTest(){
		$databaseContent = self::$connection->simulateSomeDataRetrieval();
		$this->assertEquals($databaseContent, $databaseContent, 'Sample content mismatched.');
	}
}