<?php
/**
 * Writes test results as XML file so that test execution can be monitored by continuous integration tools.
 */
class RudimentaryPhpTest_Extension_Listener_XUnitXml implements RudimentaryPhpTest_Listener {
    /**
     * @var DOMDocument DOM of log file
     */
    private $log;
    
    /**
     * @var string Path to the file where the log shall be stored
     */
    private $filename;
    
    /**
     * @var DOMElement Top-level suite
     */
    private $suite;
    
    /**
     * Saves the log to a file
     */
    private function saveLog(){
        $logContent = $this->log->saveXML();
        $writeSucceeded = file_put_contents($this->filename, $logContent);
        if($writeSucceeded===FALSE){
            throw new Exception('Failed to write test log to %s', realpath($this->filename));
        }
    }
    
	public function setUpSuite($path){
	    // Read option to know where to place the log
	    $filename = RudimentaryPhpTest::getOption('XUnitXml.file');
	    $this->filename = $filename;
	    
	    $log = new DOMDocument('1.0', 'UTF-8');
	    // Indent output
	    $log->formatOutput = TRUE;
	    $this->log = $log;
	    
	    $root = $log->createElement('testsuites');
	    $log->appendChild($root);
	    
	    // Try to save log to find out about missing permissions early
	    $this->saveLog();
	    
	    $suite = $this->log->createElement('testsuite');
	    $suite->setAttribute('name', $path);
	    $suite->setAttribute('tests', 0);
	    $suite->setAttribute('assertions', 0);
	    $suite->setAttribute('failures', 0);
	    $suite->setAttribute('errors', 0);
	    $suite->setAttribute('startTime', microtime(true));
	    $this->suite = $suite;
	    
	    $this->log->documentElement->appendChild($suite);
	    $this->saveLog();
	}
	
	public function tearDownSuite($path){
	    $this->writeTime($this->suite);
	    $this->saveLog();
	}
	
	public function setUpClass($className){
	    $class = $this->log->createElement('testsuite');
	    $class->setAttribute('name', $className);
	    $class->setAttribute('tests', 0);
	    $class->setAttribute('assertions', 0);
	    $class->setAttribute('failures', 0);
	    $class->setAttribute('errors', 0);
	    $class->setAttribute('startTime', microtime(true));
	    
	    $this->suite->appendChild($class);
	    $this->saveLog();
	}
	
	public function tearDownClass($className){
	    $class = $this->suite->lastChild;
	    $this->writeTime($class);
	    $this->saveLog();
	}
	
	public function skippedTest($className, $methodName){}
	
	public function setUpTest($className, $methodName){
	    $case = $this->log->createElement('testcase');
	    $case->setAttribute('name', $methodName);
	    $case->setAttribute('class', $className);
	    $case->setAttribute('assertions', 0);
	    $case->setAttribute('startTime', microtime(true));
	    
	    $this->suite->lastChild->appendChild($case);
	    $this->increaseCount('tests');
	    
	    $this->saveLog();
	}
	
	public function assertionSuccess($className, $methodName, $message){
	    $this->increaseCount('assertions');
	    
	    $success = $this->log->createElement('success');
	    $success->appendChild($this->log->createTextNode($message));
	    
	    $class = $this->suite->lastChild;
	    $case = $class->lastChild;
	    $case->appendChild($success);
	    
	    $this->saveLog();
	}
	public function assertionFailure($className, $methodName, $message){
	    $this->increaseCount('assertions');
	    $this->increaseCount('failures');
	    
	    $failure = $this->log->createElement('failure');
	    $failure->appendChild($this->log->createTextNode($message));
	    
	    $class = $this->suite->lastChild;
	    $case = $class->lastChild;
	    $case->appendChild($failure);
	    
	    $this->saveLog();
	}
	public function unexpectedException($className, $methodName, $exception){
	    $this->increaseCount('errors');
	    
	    $error = $this->log->createElement('error');
	    $error->setAttribute('type', get_class($exception));
	    ob_start();
	    echo $exception;
	    $serializedException = ob_get_clean();
	    $error->appendChild($this->log->createTextNode($serializedException));
	    
	    $class = $this->suite->lastChild;
	    $case = $class->lastChild;
	    $case->appendChild($error);
	    
	    $this->saveLog();
	}
	
	public function tearDownTest($className, $methodName, $output){
	    $class = $this->suite->lastChild;
	    $case = $class->lastChild;
	    $this->writeTime($case);
	    
	    // Append captured test output (although in theory test should not create any output)
	    $outputElement = $this->log->createElement('output');
	    $outputElement->appendChild($this->log->createTextNode($output));
	    $case->appendChild($outputElement);
	    
	    $this->saveLog();
	}
	
	/**
	 * Removed the temporary startTime attribute and writes the duration
	 * @param DOMElement $element Element whose startTime attribute shall be dropped in favour of a time attribute
	 */
	private function writeTime(DOMElement $element){
	    $startTime = $element->getAttribute('startTime');
	    $element->removeAttribute('startTime');
	    $element->setAttribute('time', microtime(true)-$startTime);
	}
	
	/**
	 * Increases counter attributes by 1 if they exist on the current test and the suites that contain the test
	 * @param string $counterName Attribute name of the counter
	 */
	private function increaseCount($counterName){
	    $suite = $this->suite;
	    $class = $suite->lastChild;
	    $case = $class->lastChild;
	    
	    foreach(array($suite, $class, $case) as $element){
	        if($element->hasAttribute($counterName)){
    	        $assertionCount = $element->getAttribute($counterName);
    	        $element->setAttribute($counterName, $assertionCount+1);
	        }
	    }
	}
}