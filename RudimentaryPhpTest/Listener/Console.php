<?php
/**
 * Prints test progress to the console. Additionally prints a summary after all tests are done.
 */
class RudimentaryPhpTest_Listener_Console implements RudimentaryPhpTest_Listener {
	/**
	 * @var string Delimiter for test summary
	 */
	const SUMMARY_DELMITER_HORIZONTAL = " ";
	
	/**
	 * @var string Control character for normal font
	 */
	private $FONT_INFORMATIVE_NORMAL = "\033[0m";
	
	/**
	 * @var string Control character for bold font
	 */
	private $FONT_INFORMATIVE_BOLD = "\033[1m";
	
	/**
	 * @var string Control character for redish font
	 */
	private $FONT_FAILURE_NORMAL = "\033[0;31m";
	
	/**
	 * @var string Control character for greenish font
	 */
	private $FONT_SUCCESS_NORMAL = "\033[0;32m";
		
	/**
	 * @var array Nested array to keep counters for passed and failed assertions
	 */
	private $assertions = array();
	
	/**
	 * @var ReflectionMethod Method that is currently under test
	 */
	private $currentTest = NULL;
	
	/**
	 * @param boolean $noColor Set TRUE to surpress colorized output
	 */
	public function __construct($noColor){
	    if($noColor){
	        $this->FONT_FAILURE_NORMAL = '';
	        $this->FONT_INFORMATIVE_BOLD = '';
	        $this->FONT_INFORMATIVE_NORMAL = '';
	        $this->FONT_SUCCESS_NORMAL = '';
	    }
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
		
		echo sprintf(PHP_EOL.$this->FONT_INFORMATIVE_BOLD.'Running %s->%s'.$this->FONT_INFORMATIVE_NORMAL.PHP_EOL, $className, $methodName);
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
		echo sprintf($this->FONT_SUCCESS_NORMAL.'Assertion succeeded at line %d: %s'.$this->FONT_INFORMATIVE_NORMAL.PHP_EOL, $line, $message);
		$this->increaseCounter('succeeded');
	}
	
	public function assertionFailure($className, $methodName, $file, $line, $message){
		echo sprintf($this->FONT_FAILURE_NORMAL.'Assertion failed at line %d: %s'.$this->FONT_INFORMATIVE_NORMAL.PHP_EOL, $line, $message);
		$this->increaseCounter('failed');
	}
	
	public function unexpectedException($className, $methodName, $exception){
		// Print all exceptions to console that are not expected to happen. Rethrowing breaks stack-trace unfortunately.
		echo $exception;
		echo PHP_EOL;
	}
	
	public function tearDownTest($className, $methodName, $output){
		$this->currentTest = NULL;
		// Print a tests output if any was created
		if($output!==''){
			echo $output.PHP_EOL;
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
		echo sprintf(
			PHP_EOL
			.$this->FONT_INFORMATIVE_BOLD."%-${maxLengths['className']}s".self::SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['methodName']}s".self::SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['succeeded']}s".self::SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['failed']}s".$this->FONT_INFORMATIVE_NORMAL
			.PHP_EOL,
			$columnHeaders['className'], $columnHeaders['methodName'], $columnHeaders['succeeded'], $columnHeaders['failed']
		);
		
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
				echo sprintf(
					"%-${maxLengths['className']}s".self::SUMMARY_DELMITER_HORIZONTAL
					."%-${maxLengths['methodName']}s".self::SUMMARY_DELMITER_HORIZONTAL
					.$colorCodeSucceeded."%${maxLengths['succeeded']}s".self::SUMMARY_DELMITER_HORIZONTAL
					.$colorCodeFailed."%${maxLengths['failed']}s"
					.PHP_EOL,
					$className, $methodName, $counts['succeeded'], $counts['failed']).$this->FONT_INFORMATIVE_NORMAL;
			}
		}
	}
}