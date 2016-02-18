<?php
use \Embedbug\Embedbug as Embedbug;

include_once("../../mocks/CurlMock.php");

class EmbedbugTest extends \PHPUnit_Framework_TestCase
{
	private $Embedbug;
	private $MockCurl;
	private $MockDomDocument;
	
	public function setUp()
	{
		$this->Embedbug = new Embedbug(NULL, new \CurlMock());
	}

	public function tearDown()
	{
		unset($this->Embedbug);
	}

	public function testEmbedbugInitialize()
	{
		$this->assertInstanceOf('\Embedbug\Embedbug', $this->Embedbug);
	}
}