<?php
/**
 * Prints test progress to the standard output stream regardless of output buffers.
 * Additionally prints a summary after all tests are done.
 */
class RudimentaryPhpTest_Listener_Console extends RudimentaryPhpTest_Listener_OutputStream implements RudimentaryPhpTest_Listener {
	/**
	 * @var string Control character for normal font
	 */
	protected $FONT_INFORMATIVE_NORMAL = "\033[0m";
	
	/**
	 * @var string Control character for bold font
	 */
	protected $FONT_INFORMATIVE_BOLD = "\033[1m";
	
	/**
	 * @var string Control character for redish font
	 */
	protected $FONT_FAILURE_NORMAL = "\033[0;31m";
	
	/**
	 * @var string Control character for greenish font
	 */
	protected $FONT_SUCCESS_NORMAL = "\033[0;32m";
	
	/**
	 * @param boolean $noColor Set TRUE to surpress colorized output
	 */
	public function __construct($noColor){
		parent::__construct(STDOUT);
		
	    if($noColor){
	        $this->FONT_FAILURE_NORMAL = '';
	        $this->FONT_INFORMATIVE_BOLD = '';
	        $this->FONT_INFORMATIVE_NORMAL = '';
	        $this->FONT_SUCCESS_NORMAL = '';
	    }
	}
}