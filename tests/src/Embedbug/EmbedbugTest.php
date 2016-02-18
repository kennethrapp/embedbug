<?php
use \Embedbug\Embedbug as Embedbug;

class EmbedbugTest extends \PHPUnit_Framework_TestCase
{
	private $Embedbug;
	
	public function setUp()
	{
		$this->Embedbug = new Embedbug();
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