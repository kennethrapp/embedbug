<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
</head>
<style type="text/css">

</style>
<body>

<form>
	<input type="text" size="50" name="url" value="https://news.ycombinator.com/news">
	<input type="submit">
</form>

<?php
$time = microtime(true); // Gets microseconds

require_once(realpath("src/Embedbug/Embedbug.php"));

$filter = array('url'=>array('filter'=>FILTER_VALIDATE_URL));

$url = filter_input_array(INPUT_GET, $filter);

if(!$url){
	$url = array('url'=>"https://news.ycombinator.com/news");
}
$url_parsed = parse_url($url['url']);

?><h1><?php echo htmlspecialchars($url_parsed['host']); ?></h1><?php 

$EmbedBug = new EmbedBug\EmbedBug(
	
	$url['url'],
	
	array(
        'binarytransfer' => 1,
        'buffersize'     => 2056,
        'ssl verifypeer' => false,
        'header'		 => false
	), '</html>', 1024768
);

$EmbedBug->execute();

$Links = $EmbedBug->ExtractTags($url['url'], array('a'));

/* collect the hrefs for out bound links and pass back to EmbedBug. */

$OutboundLinks = array();



if(count($Links) && array_key_exists('a', $Links)){ 

	foreach($Links['a'] as $link){

		if(array_key_exists('href', $link)){ 

			$href = $link['href'];
				
			// make sure the links are prefaced with http and validate as urls.
			if((stripos($href, 'http') === 0) && (filter_var($href, FILTER_VALIDATE_URL))){
				
				$url = parse_url($href);

				// ignore inbound links
				if(stripos($url['host'], $url_parsed['host']) === FALSE ){ 
					$OutboundLinks[] = $href;
				}
			}
		}
	}

	// this should be a Refeed method 

	$EmbedBug = new EmbedBug\EmbedBug(
		
		$OutboundLinks,
		
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
	echo "all info from a single url".print_r($EmbedBug->GetInfo($OutboundLinks[0]), true); */

	/* test with single string url, and a single key (passes - note: returns a string);
	echo "single url and a single key (content type):".print_r($EmbedBug->GetInfo($OutboundLinks[0], 'content type'), true);*/

	/* test with a string url and multiple keys (passes, although doesn't return the url, also the
		keys are numeric, although this may prove difficult to fix. );
	echo "single url and multiple keys (url, content type, http code, total time): ".print_r($EmbedBug->GetInfo($OutboundLinks[0], array('url', 'content type', 'http code', 'total time')), true);
*/
	/* test with multiple urls and a single key (passes) 
    echo "multuple urls with a single key: (content type):".print_r($EmbedBug->GetInfo($OutboundLinks, 'content type'), true);*/


    /* extract meta tags from the urls. (ok - the array is indexed by site name, then numerically for each tag)*/

    $Feed = $EmbedBug->ExtractFeed(array(
    	"type" => "website"
    ));

    if(count($Feed)){
    	
    	//file_put_contents("feed.json", json_encode($Feed));
    	
    	?><UL><?php
	    foreach($Feed as $Item=>$Values){
		    
		    ?><LI>
			
			<a href="?url=<?php echo htmlspecialchars($Item); ?>"> [+] </a>
			
			<?php
		    /* images are slow and stupid*/
    		if(array_key_exists('image_url', $Values)){
    			?><img src="<?php echo htmlspecialchars($Values['image_url']);?>" style="max-width:100px"><?php
    		} 

    		if(array_key_exists('type', $Values)){ 
    			echo htmlspecialchars($Values['type']).": ";
    		}
		    
		    ?><a href="<?php echo htmlspecialchars($Item); ?>"><?php

    		if(array_key_exists('title', $Values)){
    			echo htmlspecialchars($Values['title']);
    		}
    		else{
    			echo htmlspecialchars($Item);
    		}
	    	
	    	?></a> (<?php
	    		
	    		$u = parse_url($Item);
	    		echo htmlspecialchars($u['host']);
	    	
	    	?>) <?php
	    	
	    	if(array_key_exists('description', $Values)){
	    		
	    		?><div><?php

	    		echo excerpt(htmlspecialchars($Values['description']));
	    		
	    		if(array_key_exists('author', $Values)){
	    			
	    			?><UL><LI>-- by <?php 

	    			echo htmlspecialchars($Values['author']);
	    			
	    			if(array_key_exists('twitter', $Values)){
	    				
	    				?>(<a href="twitter.com/<?php 
	    					
	    				echo substr(htmlspecialchars($Values['twitter']), 1);
	    				
	    				?>"><?php echo htmlspecialchars($Values['twitter']); ?></a> ) 

	    				<?php
	    				if(array_key_exists('copyright', $Values)){
	    					?> Copyright: <?php echo htmlspecialchars($Values['copyright']);
	    				}

						// these seem not to be showing up even when i know they exist
	    				if(array_key_exists('keywords', $Values)){

	    					?><div><small><?php
	    					
	    					if(is_array($Values['keywords'])){

	    						echo htmlspecialchars(implode(",", $Values['keywords']));
	    					
	    					}
	    					else{
	    						
	    						echo htmlspecialchars($Values['keywords']);
	    					
	    					}
	    				}
	    				
	    				?></small></div></LI></UL><?php
	    			}
	    		}
	    		?></div><?php
	    	}
	    	?></LI><?php
	    }
	    ?></UL><?php
    }
}

/* with string $text, limit to at least $limit words (separated by condensed spaces), and then
    limit again by $limit chars chars. Add an ellipsis if necessary. */
function excerpt($text, $limit_words = 50, $limit_chars = 150){
	
	if(is_string($text)){
	
		$text = str_replace("\s+", "\s", $text); // flatten spaces
 		
		// if it (still) has spaces, trim and limit by words
		if(str_word_count($text, 0) >= $limit_words){
			$text = implode(" ", array_slice(explode(" ", $text), 0, $limit_words));
		}
		
		// limit again by characters, so someone can't flood using a single giant book-length word.
		if(strlen($text) >= $limit_chars){	
			$text = substr($text, 0, $limit_chars);
			
			// pop the last word in case we cut it off...
			if ($words = explode(" ", $text)){
				array_pop($words);
				$text = implode(" ", $words);
				$text = $text." (...) ";
			}
		}
	}
	
	return trim($text);
}
?><hr><?php 
echo "Time Elapsed: ".(microtime(true) - $time)."s";

function convert($size)
 {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
 }

echo " / Memory: " . convert(memory_get_usage(true)); // 123 kb
?>

</body>
</html>