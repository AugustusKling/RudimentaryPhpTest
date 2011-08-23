<?php
/**
 * Shows how a simple database dependent test class could be implemented.
 *
 * The basic idea is to fill a database with test data (using a dump of a non-test database for example). Then a connection to the database is made once and cached in a static property.
 * Before each test a new transaction is started which is always rolled back after the test completes. Thus the database content is never affected by the test methods. Time is saved that would otherwise have spent rebuilding the test database content.
 */
class DatabaseTest extends BaseTest {
	/**
	 * @var DummyDatabaseConnection Sample database connection
	 */
	protected static $connection = NULL;

	/**
	 * Called before each database dependent test method
	 */
	public function setUp(){
		// Ensure database connection is ready to be used
		self::ensureConnected();
		// Start transaction and assume methods to be tested don't mess with transactions but instead use savepoints
		self::$connection->beginTransaction();
	}
	
	/**
	 * Called after each database dependent test method
	 */
	public function tearDown(){
		// Instruct the database management system to discard all changes that happened during the test method. This keeps the original database content.
		self::$connection->rollBackTransaction();
	}
	
	/**
	 * Looks if a database connection is available. If not, one is created.
	 */
	private static function ensureConnected(){
		if(self::$connection===NULL){
			self::$connection = new DummyDatabaseConnection();
		}
	}
}

/**
 * A sample printer that would be replaced with a real database connection
 */
class DummyDatabaseConnection {
	public function __construct(){
		echo 'Would now: Connect to database'.PHP_EOL;
	}
	
	public function beginTransaction(){
		echo 'Would now: Begin a transaction'.PHP_EOL;
	}
	
	public function rollBackTransaction(){
		echo 'Would now: Roll back a transaction'.PHP_EOL;
	}
	
	public function simulateSomeDataRetrieval(){
		return 'Database said: stuff';
	}
}