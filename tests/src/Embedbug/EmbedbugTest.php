<?php
use \Embedbug\Embedbug as Embedbug;

class EmbedbugTest extends \PHPUnit_Framework_TestCase
{
	public function testEmbedbugInitialize()
	{
		$Embedbug = new Embedbug();
		$this->assertInstanceOf('\Embedbug\Embedbug', $Embedbug);
	}
}