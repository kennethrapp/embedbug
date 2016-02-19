<?php
use \Embedbug\Embedbug as Embedbug;
define("PORT",8080);
define("LOCALHOST","http://localhost:".PORT);

if(!isset($_SERVER['HTTP_USER_AGENT']))
{
	$_SERVER['HTTP_USER_AGENT'] = "HACKERS";
}


class EmbedbugTest extends \PHPUnit_Framework_TestCase
{
	public function testCurlWorksWithLocalHost()
	{
		$url= LOCALHOST."/index.html";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->assertEquals($http_status, 200);
		curl_close($ch);
	}

	public function testEmbedbugWorksWithLocalHost()
	{   
		$url=LOCALHOST."/index.html";
		$Embedbug = new Embedbug();
		$Embedbug->SetURLs(array($url));
		$Embedbug->Execute();
		$http_status = $Embedbug->GetInfo($url, "http code");
		$this->assertEquals($http_status, 200);
	}
	
	public function testEmbedbugReturnsContent()
	{
		$url= LOCALHOST."/index.html";

		$r1 = file_get_contents(DATA_PATH."/index.html");
		
		$Embedbug = new Embedbug();

		// curl won't read the document body if the buffer size is too large
		// and the document is too small... I don't know how to fix that and
		// it's never happened in production. Setting the buffer size to 
		// 1 character appears to "fix" it. Also, I have to make sure 
		// not to get headers and the port may or may not be necessary.
		$Embedbug->SetURLs(array($url),
		array(
			'binarytransfer' => 0,
			'returntransfer' => 1,
			'nobody'         => 0,
			'buffersize' 	 => 1,
			'ssl verifypeer' => false,
			'header' 		 => false,
			'followlocation' => true,
			'maxredirs' 	 => 1,
			'forbid reuse'   => false,
			'port'           => PORT
		));
		
		$Embedbug->Execute();			
		
		$r2 = $Embedbug->GetContent($url,'content');
		$r2 = implode(NULL, $r2);
		
		$this->assertEquals(md5($r1), md5($r2));
	}

}