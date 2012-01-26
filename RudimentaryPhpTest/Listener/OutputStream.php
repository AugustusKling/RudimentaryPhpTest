<?php
/**
 * Writes test progress to a stream. Additionally writes a summary after all tests are done.
 */
class RudimentaryPhpTest_Listener_OutputStream implements RudimentaryPhpTest_Listener {
	/**
	 * @var resource Recipient of the output
	 */
	private $stream = NULL;
	
	/**
	 * @var string Delimiter for test summary
	 */
	protected $SUMMARY_DELMITER_HORIZONTAL = ' ';
	
	/**
	 * @var string Control character for normal font
	 */
	protected $FONT_INFORMATIVE_NORMAL = '';
	
	/**
	 * @var string Control character for bold font
	 */
	protected $FONT_INFORMATIVE_BOLD = '';
	
	/**
	 * @var string Control character for redish font
	 */
	protected $FONT_FAILURE_NORMAL = '';
	
	/**
	 * @var string Control character for greenish font
	 */
	protected $FONT_SUCCESS_NORMAL = '';
		
	/**
	 * @var array Nested array to keep counters for passed and failed assertions
	 */
	private $assertions = array();
	
	/**
	 * @var ReflectionMethod Method that is currently under test
	 */
	private $currentTest = NULL;
	
	/**
	 * @param resource $stream Recipient of the output
	 */
	public function __construct($stream){
	    $this->stream = $stream;
	}
	
	/**
	 * Writes a text to the output stream
	 * @param string $text Text to emit
	 */
	private function write($text){
		if(fwrite($this->stream, $text)===FALSE){
			throw new Exception('Writing output to stream failed');
		}
	}
	
	public function suspiciousOutput($path, $output){
		$this->write(sprintf(PHP_EOL.$this->FONT_FAILURE_NORMAL.'Suspicious output whilst loading %s:'.$this->FONT_INFORMATIVE_NORMAL.PHP_EOL, $path));
		$this->write($output.PHP_EOL);
	}
	
	public function setUpSuite($path){
	}
	
	public function tearDownSuite($path){
		// Print table with test results
		$this->printSummary();
	}
	
	public function setUpClass($className){
	}
	
	public function tearDownClass($className){
	}
	
	public function skippedTest($className, $methodName){
	}
	
	public function setUpTest($className, $methodName, $file, $line){
		// Add counter for assertions
		$this->assertions[$className][$methodName] = array(
			'succeeded' => 0,
			'failed' => 0
		);
		
		// Keep track of the method under test since locations of assertive calls are not neccessarily within the method under test
		$this->currentTest = new ReflectionMethod($className, $methodName);
		
		$this->write(sprintf(PHP_EOL.$this->FONT_INFORMATIVE_BOLD.'Running %s->%s'.$this->FONT_INFORMATIVE_NORMAL.PHP_EOL, $className, $methodName));
	}
	
	public function setUpTestDone($className, $methodName, $file, $line, $output){
		if($output!==''){
			$this->write($output.PHP_EOL);
		}
	}
	
	/**
	 * Increase the counters for succeeded and failed assertions
	 * @param string $counterName Either succeeded or failed
	 */
	private function increaseCounter($counterName){
		$className = $this->currentTest->getDeclaringClass()->getName();
		$methodName = $this->currentTest->getName();
		$this->assertions[$className][$methodName][$counterName] += 1;
	}
	
	public function assertionSuccess($className, $methodName, $file, $line, $message){
		$this->write(sprintf($this->FONT_SUCCESS_NORMAL.'Assertion succeeded at line %d: %s'.$this->FONT_INFORMATIVE_NORMAL.PHP_EOL, $line, $message));
		$this->increaseCounter('succeeded');
	}
	
	public function assertionFailure($className, $methodName, $file, $line, $message){
		$this->write(sprintf($this->FONT_FAILURE_NORMAL.'Assertion failed at line %d: %s'.$this->FONT_INFORMATIVE_NORMAL.PHP_EOL, $line, $message));
		$this->increaseCounter('failed');
	}
	
	public function unexpectedException($className, $methodName, $exception){
		// Print all exceptions to console that are not expected to happen. Rethrowing breaks stack-trace unfortunately.
		$this->write($exception);
		$this->write(PHP_EOL);
	}
	
	public function tearDownTest($className, $methodName, $output){
		// Print a tests output if any was created
		if($output!==''){
			$this->write($output.PHP_EOL);
		}
	}
	
	public function tearDownTestDone($className, $methodName, $output){
		$this->currentTest = NULL;
		if($output!==''){
			$this->write($output.PHP_EOL);
		}
	}
	
	/**
	 * Prints a summary of passed and failed assertions
	 */
	private function printSummary(){
		$columnHeaders = array(
			'className' => 'Class Name',
			'methodName' => 'Method Name',
			'succeeded' => 'Succeeded',
			'failed' => 'Failed'
		);
		// Determine longest content lengths to get padding right
		$maxLengths = array();
		foreach($columnHeaders as $headerIndex => $header){
			$maxLengths[$headerIndex] = mb_strlen($header);
		};
		foreach($this->assertions as $className => $assertions){
			foreach($assertions as $methodName => $counts){
				$maxLengths['className'] = max(mb_strlen($className), $maxLengths['className']);
				$maxLengths['methodName'] = max(mb_strlen($methodName), $maxLengths['methodName']);
				$maxLengths['succeeded'] = max(mb_strlen($counts['succeeded']), $maxLengths['succeeded']);
				$maxLengths['failed'] = max(mb_strlen($counts['failed']), $maxLengths['failed']);
			}
		}
		
		// Print column headers
		$this->write(sprintf(
			PHP_EOL
			.$this->FONT_INFORMATIVE_BOLD."%-${maxLengths['className']}s".$this->SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['methodName']}s".$this->SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['succeeded']}s".$this->SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['failed']}s".$this->FONT_INFORMATIVE_NORMAL
			.PHP_EOL,
			$columnHeaders['className'], $columnHeaders['methodName'], $columnHeaders['succeeded'], $columnHeaders['failed']
		));
		
		// Print tests along with assertion counts
		foreach($this->assertions as $className => $assertions){
			foreach($assertions as $methodName => $counts){
				$colorCodeSucceeded = $this->FONT_INFORMATIVE_NORMAL;
				$colorCodeFailed = $this->FONT_INFORMATIVE_NORMAL;
				if($counts['succeeded']>0){
					// Print passes in green
					$colorCodeSucceeded = $this->FONT_SUCCESS_NORMAL;
				}
				if($counts['failed']>0){
					// Print failures in red
					$colorCodeFailed = $this->FONT_FAILURE_NORMAL;
				}
				$this->write(sprintf(
					"%-${maxLengths['className']}s".$this->SUMMARY_DELMITER_HORIZONTAL
					."%-${maxLengths['methodName']}s".$this->SUMMARY_DELMITER_HORIZONTAL
					.$colorCodeSucceeded."%${maxLengths['succeeded']}s".$this->SUMMARY_DELMITER_HORIZONTAL
					.$colorCodeFailed."%${maxLengths['failed']}s"
					.PHP_EOL,
					$className, $methodName, $counts['succeeded'], $counts['failed']).$this->FONT_INFORMATIVE_NORMAL);
			}
		}
	}
}