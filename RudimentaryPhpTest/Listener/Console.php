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
	 * @var array Nested array to keep counters for passed and failed assertions
	 */
	private $assertions = array();
	
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
	
	public function setUpTest($className, $methodName){
		// Add counter for assertions
		$this->assertions[$className][$methodName] = array(
			'succeeded' => 0,
			'failed' => 0
		);
		
		echo sprintf(PHP_EOL."\033[1mRunning %s->%s\033[0m".PHP_EOL, $className, $methodName);
	}
	
	public function assertionSuccess($className, $methodName, $message){
		$this->assertions[$className][$methodName]['succeeded'] += 1;
	}
	
	public function assertionFailure($className, $methodName, $message){
		$this->assertions[$className][$methodName]['failed'] += 1;
	}
	
	public function unexpectedException($className, $methodName, $exception){
		// Print all exceptions to console that are not expected to happen. Rethrowing breaks stack-trace unfortunately.
		echo $exception;
		echo PHP_EOL;
	}
	
	public function tearDownTest($className, $methodName, $output){
		echo $output;
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
			."\033[1m%-${maxLengths['className']}s".self::SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['methodName']}s".self::SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['succeeded']}s".self::SUMMARY_DELMITER_HORIZONTAL
			."%-${maxLengths['failed']}s\033[0m"
			.PHP_EOL,
			$columnHeaders['className'], $columnHeaders['methodName'], $columnHeaders['succeeded'], $columnHeaders['failed']
		);
		
		// Print tests along with assertion counts
		$noColorCode = "\033[0m";
		foreach($this->assertions as $className => $assertions){
			foreach($assertions as $methodName => $counts){
				$colorCodeSucceeded = $noColorCode;
				$colorCodeFailed = $noColorCode;
				if($counts['succeeded']>0){
					// Print passes in green
					$colorCodeSucceeded = "\033[0;32m";
				}
				if($counts['failed']>0){
					// Print failures in red
					$colorCodeFailed = "\033[0;31m";
				}
				echo sprintf(
					"%-${maxLengths['className']}s".self::SUMMARY_DELMITER_HORIZONTAL
					."%-${maxLengths['methodName']}s".self::SUMMARY_DELMITER_HORIZONTAL
					.$colorCodeSucceeded."%${maxLengths['succeeded']}s".self::SUMMARY_DELMITER_HORIZONTAL
					.$colorCodeFailed."%${maxLengths['failed']}s"
					.PHP_EOL,
					$className, $methodName, $counts['succeeded'], $counts['failed']).$noColorCode;
			}
		}
	}
}