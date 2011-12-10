<?php
/**
 * Class which is the unit under test (its name does not matter).
 * This example implements some text processing functions.
 */
class Tutorials_Simple_Implementation_Text {
		/**
		 * @var string The represented string
		 */
		private $content;
		
		/**
		 * @param string $content The text to represent / wrap
		 */
		public function __construct($content){
			$this->content = $content;
		}
		
		public function __toString(){
			return $this->content;
		}
		
		/**
		 * @param string $start String that is searched for
		 * @return boolean TRUE if the wrapped string begins with the given string
		 */
		public function startWith($start){
			$startWithinWrappedString = mb_substr($this->content, 0, mb_strlen($start));
			return $startWithinWrappedString===$start;
		}
}
