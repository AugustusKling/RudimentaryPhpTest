<?php
/**
 * Defines methods that are invoked during execution of the tests.
 * Intended use is for being informed about test progress and results. Not to be used to influence the tests.
 */
interface RudimentaryPhpTest_Listener {
	/**
	 * Called when loading of a file results in output without even attempting to use the loaded code.
	 * @param string $path Path to file which caused the output
	 * @param string $output Captured output
	 */
	public function suspiciousOutput($path, $output);
	
	/**
	 * Called before the first test is executed
	 * @param string $path Absolute path to testbase
	 */
	public function setUpSuite($path);
	
	/**
	 * Called after the last test was executed
	 * @param string $path Absolute path to testbase
	 */
	public function tearDownSuite($path);
	
	/**
	 * Called before the first test of a test class is executed
	 * @param string $className Name of the test class
	 */
	public function setUpClass($className);
	
	/**
	 * Called after the last test of a test class is executed
	 * @param string $className Name of the test class
	 */
	public function tearDownClass($className);
	
	/**
	 * Called whenever a test is skipped due to not matching the filter
	 * @param string $className Name of the test class
	 * @param string $methodName Name of the test method
	 */
	public function skippedTest($className, $methodName);
	
	/**
	 * Called before a test is executed
	 * @param string $className Name of the test class
	 * @param string $methodName Name of the test method
	 * @param string $file Path to the file that defines the test
	 * @param integer $line Line number where the test is defined
	 */
	public function setUpTest($className, $methodName, $file, $line);
	
	/**
	 * Called after setUp returned either successful or not
	 * @param string $className Name of the test class
	 * @param string $methodName Name of the test method
	 * @param string $file Path to the file that defines the test
	 * @param integer $line Line number where the test is defined
	 * @param string $output Output captured whilst setUp was running
	 */
	public function setUpTestDone($className, $methodName, $file, $line, $output);
	
	/**
	 * Called when a assertion within a test succeeds or the expected exception gets caught
	 * @param string $className Name of the test class
	 * @param string $methodName Name of the test method
	 * @param string $file Path to the file in which the assertion was called
	 * @param integer $line Line number where the assertion was called
	 * @param string $message Explanation of assertion purpose
	 */
	public function assertionSuccess($className, $methodName, $file, $line, $message);
	
	/**
	 * Called when a assertion within a test fails or an unexpected exception gets caught
	 * @param string $className Name of the test class
	 * @param string $methodName Name of the test method
	 * @param string $file Path to the file in which the assertion was called
	 * @param integer $line Line number where the assertion was called
	 * @param string $message Explanation of assertion purpose
	 */
	public function assertionFailure($className, $methodName, $file, $line, $message);
	
	/**
	 * Called when an unexpected exception gets caught
	 * @param string $className Name of the test class
	 * @param string $methodName Name of the test method
	 * @param string $message Explanation of assertion purpose
	 */
	public function unexpectedException($className, $methodName, $exception);
	
	/**
	 * Called after a test is executed
	 * @param string Name of the test class
	 * @param string Name of the test method
	 * @param string The output created whilst the test was running
	 */
	public function tearDownTest($className, $methodName, $output);
	
	/**
	 * Called after tearDown returned either successful or not
	 * @param string $className Name of the test class
	 * @param string $methodName Name of the test method
	 * @param string $file Path to the file that defines the test
	 * @param integer $line Line number where the test is defined
	 * @param string $output Output captured whilst tearDown was running
	 */
	public function tearDownTestDone($className, $methodName, $output);
}