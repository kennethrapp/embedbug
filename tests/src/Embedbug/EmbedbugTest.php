<?php
use \Embedbug\Embedbug as Embedbug;
define("PORT",8080);
define("LOCALHOST","http://localhost:".PORT);

if(!isset($_SERVER['HTTP_USER_AGENT']))
{
	$_SERVER['HTTP_USER_AGENT'] = "HACKERS";
}
//http://www.sitepoint.com/be-more-asssertive-getting-to-know-phpunits-assertions/
class EmbedbugTest extends \PHPUnit_Framework_TestCase
{	
	public function testAssertDataPathExists()
	{
		$this->assertTrue(is_readable(DATA_PATH));
	}

	public function testCurlWorksWithLocalHost()
	{
		$f1 = "/index.html";
		$u1 = LOCALHOST.$f1;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $u1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		$c1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->assertEquals($c1, 200);
		curl_close($ch);
	}

	public function testEmbedbugWorksWithLocalHost()
	{   
		$f1 = "/index.html";
		$u1 = LOCALHOST.$f1;
		
		$Embedbug = new Embedbug();	
		$Embedbug->SetURLs(array($u1));
		$Embedbug->Execute();

		$c1 = $Embedbug->GetInfo($u1, "http code");
		$this->assertEquals($c1, 200);
	}
	
	public function testEmbedbugReturnsContentMatchingFile()
	{
		$f1 = "/index.html";
		$u1 = LOCALHOST.$f1;
		$this->assertTrue(is_readable(DATA_PATH.$f1));
		$Embedbug = new Embedbug();

		$Embedbug->SetURLs(array($u1),
		array('binarytransfer' => 0,
			  'returntransfer' => 1,
			  'nobody'         => 0,
			  'buffersize'     => 1024,
			  'ssl verifypeer' => 0,
			  'header' 		   => 0,
			  'port'           => PORT
		));
		
		$Embedbug->Execute();			
		
		//GetContent is an array containing the html response by buffer size 
		//but imploding it with NULL reconstructs it properly
		$r2 = $Embedbug->GetContent($u1,'content');	
		$r2 = implode(NULL, $r2);

		$this->assertStringEqualsFile(DATA_PATH.$f1, $r2);
	}

	public function testEmbedbugWorksWithMultipleURLs()
	{
		$f1 = "/index.html";
		$f2 = "/index2.html";
		$u1 = LOCALHOST.$f1;
		$u2 = LOCALHOST.$f2;
		
		$this->assertFileExists(DATA_PATH.$f1);
		$this->assertFileExists(DATA_PATH.$f2);

		$Embedbug = new Embedbug();
		$Embedbug->SetURLs(array($u1, $u2),
		array('binarytransfer' => 0,
			  'returntransfer' => 1,
			  'nobody'         => 0,
			  'buffersize'     => 1024,
			  'ssl verifypeer' => 0,
			  'header' 		   => 0,
			  'port'           => PORT
		));
		
		$Embedbug->Execute();

		$r1 = $Embedbug->GetContent($u1, 'content');
		$r2 = $Embedbug->GetContent($u2, 'content');
		
		$r1 = implode(NULL, $r1);
		$r2 = implode(NULL, $r2);

		$this->assertStringEqualsFile(DATA_PATH.$f1, $r1);
		$this->assertStringEqualsFile(DATA_PATH.$f2, $r2);
	}

	// test a file that sends text/html headers
	public function testEmbedBugGetsCorrectContentTypeHeaders()
	{
		$f1 = "/content_type_headers.php";
		
		$u1 = LOCALHOST.$f1;
		
		$this->assertFileExists(DATA_PATH.$f1);
		
		$Embedbug = new Embedbug();
		
		$Embedbug->SetURLs(array($u1),
		array('binarytransfer' => 0,
			  'returntransfer' => 1,
			  'nobody'         => 1,
			  'buffersize'     => 1024,
			  'ssl verifypeer' => 0,
			  'header' 		   => 1,
			  'followlocation' => 0,
			  'maxredirs' 	   => 0,
			  'port'           => PORT
		));
		
		$Embedbug->Execute();
		
		$c1 = $Embedbug->GetInfo($u1, 'content type');
		$this->assertEquals($c1, "text/html; charset=UTF-8");
	}

	// test a file that sends plaintext headers
	public function testEmbedBugGetsCorrectPlaintextHeaders()
	{
		$f1 = "/content_type_headers_plaintext.php";
		
		$u1 = LOCALHOST.$f1;
		
		$this->assertFileExists(DATA_PATH.$f1);
		
		$Embedbug = new Embedbug();
		
		$Embedbug->SetURLs(array($u1),
		array('binarytransfer' => 0,
			  'returntransfer' => 1,
			  'nobody'         => 1,
			  'buffersize'     => 1024,
			  'ssl verifypeer' => 0,
			  'header' 		   => 1,
			  'followlocation' => 0,
			  'maxredirs' 	   => 0,
			  'port'           => PORT
		));
		
		$Embedbug->Execute();	
		$c1 = $Embedbug->GetInfo($u1, 'content type');
		$this->assertEquals($c1, "text/plain; charset=UTF-8");
	}

	// return default headers (text/plain) for an empty file
	public function testEmptyFileReturnsPlaintextAndDoesntBreakAnything()
	{
		$f1 = "/empty.txt"; 

		$u1 = LOCALHOST.$f1;
		
		$this->assertFileExists(DATA_PATH.$f1);
		
		$Embedbug = new Embedbug();
		
		$Embedbug->SetURLs(array($u1),
		array('binarytransfer' => 0,
			  'returntransfer' => 1,
			  'nobody'         => 1,
			  'buffersize'     => 1024,
			  'ssl verifypeer' => 0,
			  'header' 		   => 1,
			  'followlocation' => 0,
			  'maxredirs' 	   => 0,
			  'port'           => PORT
		));
		
		$Embedbug->Execute();	
		$c1 = $Embedbug->GetInfo($u1, 'content type');
		$this->assertEquals($c1, "text/plain; charset=UTF-8");
	}

	public function testEmbedBugGetsRedirect()
	{
		// returns a 301 redirect to example.com 
		$f1 = "/redirect_headers.php";
		
		$u1 = LOCALHOST.$f1;
		
		$this->assertFileExists(DATA_PATH.$f1);
		
		$Embedbug = new Embedbug();
		
		$Embedbug->SetURLs(array($u1),
		array('binarytransfer' => 0,
			  'returntransfer' => 1,
			  'nobody'         => 1,
			  'buffersize'     => 1024,
			  'ssl verifypeer' => 0,
			  'header' 		   => 1,
			  'followlocation' => 0,
			  'maxredirs' 	   => 0,
			  'port'           => PORT
		));
		
		$Embedbug->Execute();	
		$c1 = $Embedbug->GetInfo($u1, 'http code');
		$this->assertEquals(301, $c1);

		$r1 = $Embedbug->GetInfo($u1, 'redirect url');
		$this->assertEquals('http://example.com',$r1);
	}


}