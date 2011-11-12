<?php
/**
 * Implementation of PHPUnit's assertions.
 *
 * Maybe compatible with PHPUnit or not since documentation does not specify all cases.
 * Note that this implementation is not well tested.
 */
class RudimentaryPhpTest_Extension_Assertions_PHPUnit extends RudimentaryPhpTest_Assertions_Abstract {
	/**
	 * @var RudimentaryPhpTest_Extension_Assertions_Text Assertions for strings
	 */
	private $providerText;
	
	public function __construct(RudimentaryPhpTest_BaseTest $test){
		parent::__construct($test);
		
		$this->providerText = new RudimentaryPhpTest_Extension_Assertions_Text($test);
	}
	
	public function assertArrayHasKey($key, array $array, $message = ''){
		$this->assertTrue(array_key_exists($key, $array), $message);
	}
	
	public function assertClassHasAttribute($attributeName, $className, $message = ''){
		$class = new ReflectionClass($className);
		$this->assertTrue($class->hasProperty($attributeName), $message);
	}
	
	public function assertClassHasStaticAttribute($attributeName, $className, $message = ''){
		$class = new ReflectionClass($className);
		if(!$class->hasProperty($attributeName)){
			$this->fail($message);
		}
		$property = $class->getProperty($attributeName);
		$this->assertTrue($property->isStatic(), $message);
	}
	
	public function assertContains($needle, $haystack, $message = ''){
		if(is_array($haystack) || $haystack instanceof Iterator){
			$found = FALSE;
			foreach($haystack as $candidate){
				if($candidate===$needle){
					$found = TRUE;
					break;
				}
			}
		} else if(is_string($haystack)) {
			$found = mb_strpos($haystack, $needle)!==FALSE;
		} else {
			throw new Exception('Incompatible type');
		}
		$this->assertTrue($found, $message);
	}
	
	public function assertContainsOnly($type, $haystack, $isNativeType = NULL, $message = ''){
		$onlyType = TRUE;
		if(count($haystack)===0){
			$onlyType = TRUE;
		} else {
			foreach($haystack as $candidate){
				if(is_object($candidate)){
					if($type!==get_class($candidate)){
						$onlyType = FALSE;
						break;
					}
				} else {
					if($type!==gettype($candidate)){
						$onlyType = FALSE;
						break;
					}
				}
			}
		}
		$this->assertTrue($onlyType, $message);
	}
	
	public function assertCount($expectedCount, $haystack, $message = ''){
		$this->assertTrue($expectedCount===count($haystack), $message);
	}
	
	public function assertEmpty($actual, $message = ''){
		$this->assertTrue(empty($actual), $message);
	}
	
	public function assertEqualXMLStructure(DOMElement $expectedElement, DOMElement $actualElement, $checkAttributes = FALSE, $message = ''){
		if($checkAttributes===FALSE){
			// Remove attributes
			foreach(array($expectedElement, $actualElement) as $element){
				$xpath = new DOMXPath($element->ownerDocument);
				$elementsWithAttributes = $xpath->query('//*[@*]', $element);
				foreach($elementsWithAttributes as $elementWithAttributes){
					foreach($elementWithAttributes->attributes as $attribute){
						$elementWithAttributes->removeAttribute($attribute->name);
					}
				}
			}
		}
		
		$expected = $expectedElement->ownerDocument->saveXML($expectedElement);
		$actual = $actualElement->ownerDocument->saveXML($actualElement);
		
		$contentPattern = '>[^]+<';
		$replacement = '><';
		$expected = mb_ereg_replace($contentPattern, $replacement, $expected, 'm');
		$actual = mb_ereg_replace($contentPattern, $replacement, $actual, 'm');
		
		$this->assertTrue($expected===$actual, $message);
	}
	
	public function assertEquals($expected, $actual, $message = '', $allowedError = 0){
		if($allowedError===0){
			// Contradictory to default implementation this method uses a loose-type check
			$this->assertTrue($expected==$actual, $message);
		} else {
			$this->assertTrue(abs($expected->$actual)<$allowedError, $message);
		}
	}
	
	public function assertFalse($condition, $message = ''){
		$this->assertTrue(!$condition, $message);
	}
	
	public function assertFileEquals($expected, $actual, $message = ''){
		$this->assertTrue(
			file_get_contents($expected)===file_get_contents($actual),
			$message
		);
	}
	
	public function assertFileExists($filename, $message = ''){
		$this->assertTrue(file_exists($filename), $message);
	}
	
	public function assertGreaterThan($expected, $actual, $message = ''){
		$this->assertTrue($expected<$actual, $message);
	}
	
	public function assertGreaterThanOrEqual($expected, $actual, $message = ''){
		$this->assertTrue($expected<=$actual, $message);
	}
	
	public function assertInstanceOf($expected, $actual, $message = ''){
		$this->assertTrue($actual instanceof $expected, $message);
	}
	
	public function assertInternalType($expected, $actual, $message = ''){
		$this->assertTrue($expected===gettype($actual), $message);
	}
	
	public function assertLessThan($expected, $actual, $message = ''){
		$this->assertTrue($expected>$actual, $message);
	}
	
	public function assertLessThanOrEqual($expected, $actual, $message = ''){
		$this->assertTrue($actual<=$expected, $message);
	}
	
	public function assertNull($variable, $message = ''){
		$this->assertTrue($variable===NULL, $message);
	}
	
	public function assertObjectHasAttribute($attributeName, $object, $message = ''){
		$reflection = new ReflectionObject($object);
		$this->assertTrue($reflection->hasProperty($attributeName), $message);
	}
	
	public function assertRegExp($pattern, $string, $message = ''){
		$this->assertTrue(mb_ereg_match($pattern, $string), $message);
	}
	
	public function assertStringMatchesFormat($format, $string, $message = ''){
		$this->assertTrue(sprintf($format, $string)===$string, $message);
	}
	
	public function assertStringMatchesFormatFile($formatFile, $string, $message = ''){
		$format = file_get_contents($formatFile);
		$this->assertRegExp($format, $string, $message);
	}
	
	public function assertSame($expected, $actual, $message = ''){
		$this->assertTrue($expected===$actual, $message);
	}
	
	public function assertSelectCount(array $selector, $count, $actual, $message = '', $isHtml = TRUE){
		throw new Exception('Not implemented');
	}
	
	public function assertSelectEquals(array $selector, $content, $count, $actual, $message = '', $isHtml = TRUE){
		throw new Exception('Not implemented');
	}
	
	public function assertSelectRegExp(array $selector, $pattern, $count, $actual, $message = '', $isHtml = TRUE){
		throw new Exception('Not implemented');
	}
	
	public function assertStringEndsWith($suffix, $string, $message = ''){
		$this->providerText->assertStringEndsWith($suffix, $string, $message);
	}
	
	public function assertStringEqualsFile($expectedFile, $actualString, $message = ''){
		$expected = file_get_contents($expectedFile);
		$this->assertTrue($expected===$actualString, $message);
	}
	
	public function assertStringStartsWith($prefix, $string, $message = ''){
		$this->providerText->assertStringStartsWith($prefix, $string, $message);
	}
	
	public function assertTag(array $matcher, $actual, $message = '', $isHtml = TRUE){
		throw new Exception('Not implemented');
	}
	
	public function assertThat($value, PHPUnit_Framework_Constraint $constraint, $message = ''){
		throw new Exception('Not implemented');
	}
	
	public function assertXmlFileEqualsXmlFile($expectedFile, $actualFile, $message = ''){
		$expected = new DomDocument();
		$expected->loadXML(file_get_contents($expectedFile));
		$actual = new DomDocument();
		$actual->loadXML(file_get_contents($actualFile));
		
		$this->assertTrue($expected->saveXML()===$actual->saveXML(), $message);
	}
	
	public function assertXmlStringEqualsXmlFile($expectedFile, $actualXml, $message = ''){
		$expected = new DomDocument();
		$expected->loadXML(file_get_contents($expectedFile));
		$actual = new DomDocument();
		$actual->loadXML($actualXml);
		
		$this->assertTrue($expected->saveXML()===$actual->saveXML(), $message);
	}
	
	public function assertXmlStringEqualsXmlString($expectedXml, $actualXml, $message = ''){
		$expected = new DomDocument();
		$expected->loadXML($expectedXml);
		$actual = new DomDocument();
		$actual->loadXML($actualXml);
		
		$this->assertTrue($expected->saveXML()===$actual->saveXML(), $message);
	}
}