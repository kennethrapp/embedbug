<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
</head>

<body>
<pre>
<?php

require_once(realpath("../src/EmbedBug/EmbedBug.php"));

/* 	test pulling a series of urls by scraping the frontpage of
	hacker news and processing the outbound links... */

$HNews = "https://news.ycombinator.com/news";

$EmbedBug = new EmbedBug\EmbedBug(
	
	$HNews,
	
	array(
        'binarytransfer' => 1,
        'buffersize'     => 2056,
        'ssl verifypeer' => false,
        'header'		 => false
	), '</html>', 1024768
);

$EmbedBug->execute();

$HNLinks = $EmbedBug->ExtractTags($HNews, array('a'));


//echo print_r($HNLinks,true);

/* collect the hrefs for out bound links and pass back to EmbedBug. */
$HNOutboundLinks = array();


if(count($HNLinks) && array_key_exists('a', $HNLinks)){ 

	foreach($HNLinks['a'] as $link){

		if(array_key_exists('href', $link)){ 

			$href = $link['href'];
				
			// make sure the links are prefaced with http and validate as urls.
			if((stripos($href, 'http') === 0) && (filter_var($href, FILTER_VALIDATE_URL))){
				
				$url = parse_url($href);

				// remove links inside ycombinator
				if($url['host'] !== 'ycombinator.com'){ 
					$HNOutboundLinks[] = $href;
				}
			}
		}
	}

	//echo print_r($HNOutboundLinks, true);

	// this should be a Refeed method 

	$EmbedBug = new EmbedBug\EmbedBug(
		
		$HNOutboundLinks,
		
		array(
	        'binarytransfer' => 1,
	        'buffersize'     => 2056,
	        'ssl verifypeer' => false,
	        'header'		 => false
		), '</html>', 1024768
	);

	$EmbedBug->execute();

	/* NEXT TO DO

	clarify the array keys. */

	/*test pulling curl info from all urls (passes)
	echo "all info from all urls: ".print_r($EmbedBug->GetInfo(), true);*/

	/* test pulling curl info from a single string url (passes)
	echo "all info from a single url".print_r($EmbedBug->GetInfo($HNOutboundLinks[0]), true); */

	/* test with single string url, and a single key (passes - note: returns a string);
	echo "single url and a single key (content type):".print_r($EmbedBug->GetInfo($HNOutboundLinks[0], 'content type'), true);*/

	/* test with a string url and multiple keys (passes, although doesn't return the url, also the
		keys are numeric, although this may prove difficult to fix. );
	echo "single url and multiple keys (url, content type, http code, total time): ".print_r($EmbedBug->GetInfo($HNOutboundLinks[0], array('url', 'content type', 'http code', 'total time')), true);
*/
	/* test with multiple urls and a single key (passes) 
    echo "multuple urls with a single key: (content type):".print_r($EmbedBug->GetInfo($HNOutboundLinks, 'content type'), true);*/


    /* extract meta tags from the urls. (ok - the array is indexed by site name, then numerically for each tag)*/

    $Feed = $EmbedBug->ExtractFeed();

    echo print_r($Feed);
}
?>
</pre>
</body>
</html>