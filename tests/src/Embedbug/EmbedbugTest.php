<?php
use \Embedbug\Embedbug as Embedbug;
class EmbedbugTest extends \PHPUnit_Framework_TestCase
{
	private $Embedbug;
	private $Localhost="localhost:8000";
	
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

	public function testCurlWorksWithLocalHost()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->Localhost."/index.html");
		$response = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->assertEquals($http_status, 200);

	}
}