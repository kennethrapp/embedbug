<?php namespace Embedbug;

require_once("Embedbug.php");

class Feed{

	private $data;
	private $parsed;
	private $EmbedBug;
	private $url;
	private $BannableUrls;
	private $BannableText;

	function Activate($url, $curl_options = array(
			'binarytransfer' => 1,
			'buffersize'     => 2056,
			'ssl verifypeer' => false,
			'header'		 => false
		), $end_tag = "</head>", $end_size = 1024868){

		if(is_array($url)){ 
			$this->url = $url;
		}
		else{
			$this->url=array($url);
		}

		$this->EmbedBug = new EmbedBug($url, $curl_options, $end_tag, $end_size);
		$this->EmbedBug->execute();

	}

	function slugify($text){ 
	  // replace non letter or digits by -
	  $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

	  // trim
	  $text = trim($text, '-');

	  // transliterate
	  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

	  // lowercase
	  $text = strtolower($text);

	  // remove unwanted characters
	  $text = preg_replace('~[^-\w]+~', '', $text);

	  if (empty($text)){
	    return null;
	  }

	  return $text;
	}

	function ExtractFeed($urls, $tags=array('meta', 'title', 'link')){

		$this->Activate($urls);

		$Feed = array();
		
		if($EmbedBug = $this->EmbedBug){ 
		
			if(($AllTags = $this->EmbedBug->ExtractTags($this->url, $tags))){

				foreach($this->url as $url){ 

					if($url_parsed = $this->validate_url($url) && ((int)$this->GetInfo($url, 'http code') === 200) ){

						// provide defaults for the header (must have link, title, site name and type
						// and an index for Curl info
						$Feed[$url] = array(
							"link" => $url,
							'host' => $url_parsed['host'],
							'info' => $this->GetInfo($url)
						);
						
						// if there's still no title, see if the title tag exists
						if(isset($AllTags[$url]['title']) && count($AllTags[$url]['title'])){
							if(array_key_exists('textcontent', $AllTags[$url]['title'][0])){ 
								$Feed[$url]['title'] = $AllTags[$url]['title'][0]['textcontent'];
							}			
						}

						
						if(count($AllTags[$url]['meta'])){ 

							foreach($AllTags[$url]['meta'] as $Meta){

								if(isset($Meta['name'], $Meta['content'])){

									// slurp everthing from parse.ly
									if(($Meta['name'] == 'parsely-page') && ($content = json_decode(trim($Meta['content']), 1))){
										foreach($content as $key=>$val){
											$Feed[$url][$key] = $val;
										}
									}
				    				
					    			if(isset($Meta['content']) && ($content = trim($Meta['content']))){ 
										switch(strtolower($Meta['name'])){
											case "description" : $Feed[$url]['description'] = $content; break;
											case "title"       : $Feed[$url]['title']       = $content; break;
											case "author"      : $Feed[$url]['author']      = $content; break;
											case "keywords"    : $Feed[$url]['keywords']    = explode(",", $content); break;
											case "copyright"   : $Feed[$url]['copyright']   = $content; break;
											case "robots"      : $Feed[$url]['robots']      = $content; break;
											
										}
									}
								
								}// end meta name exists


								// now edit keywords if they exist

								if(isset($Feed[$url]['keywords']) && count($Feed[$url]['keywords'])){

									// remove null and empty elements
									$Feed[$url]['keywords'] = array_filter($Feed[$url]['keywords'], 'strlen');
									
									// this should remove duplicate elements case-insensitively
									$Feed[$url]['keywords'] = array_intersect_key(
										$Feed[$url]['keywords'],
										array_unique(
											array_map(array($this, 'slugify'), $Feed[$url]['keywords'])
										)
									);			
								}


								if(isset($Meta['content'], $Meta['property'])){
					
									$content = trim($Meta['content']);
									
									switch(strtolower($Meta['property'])){
										// integrate facebook tags
										case "og:image"		    : $Feed[$url]['image_url']   = $content; break;
										case "og:title"		    : $Feed[$url]['title'] 	     = $content; break;
										case "og:url"  		    : $Feed[$url]['link'] 	     = $content; break;
										case "og:site_name"     : $Feed[$url]['site_name']   = $content; break;
										case "og:type"          : $Feed[$url]['type']        = $content; break;
										case "og:description"   : $Feed[$url]['description'] = $content; break;
										case "og:video"         : $Feed[$url]['video'] = array('src'=>$content); break; 
										case "og:video:width"   : $Feed[$url]['video']['width'] = $content; break;
										case "og:video:height"  : $Feed[$url]['video']['height'] = $content; break;
										case "og:video:type"    : $Feed[$url]['video']['type']   = $content; break;
										case "og:video:duration": $Feed[$url]['video']['duration'] = $content; break;
										
										// integrate twitter tags
										case "twitter:creator" 		: $Feed[$url]['twitter']     = $content; break;
										case "twitter:url" 			: $Feed[$url]['link']   	 = $content; break;
										case "twitter:title" 		: $Feed[$url]['title']   	 = $content; break;
										case "twitter:description" 	: $Feed[$url]['description'] = $content; break;
										case "twitter:image"        : $Feed[$url]['image_url']   = $content; break;
										case "twitter:image:width"  : $Feed[$url]['image_w']     = $content; break;
										case "twitter:image:height" : $Feed[$url]['image_h']     = $content; break;
										case "twitter:card"  		: $Feed[$url]['type']        = $content; break;

										// author tags
										case "article:author"		  : $Feed[$url]['author']         = $content; break;
										case "article:published_time" : $Feed[$url]['published_time'] = $content; break;
										case "article:modified_time"  : $Feed[$url]['modified_time']  = $content; break;
									}
								}
							}
						}

						if(count($AllTags[$url]['link'])){ 
							
							foreach($AllTags[$url]['link'] as $Link){

								if(array_key_exists('rel', $Link) && !empty($Link['rel'])){
									
									if(isset($Link['href']) && ($content = trim($Link['href']))){ 

										switch(strtolower($Link['rel'])){
											case "prev"	    : $Feed[$url]['prev']      = array('title'=>isset($Link['title'])?$Link['title']:$Link['href'], 'href'=>$Link['href']); break;
											case "next"	    : $Feed[$url]['next']      = array('title'=>isset($Link['title'])?$Link['title']:$Link['href'], 'href'=>$Link['href']); break;
											case "author"   : $Feed[$url]['author']    = $content;
											case "license"  : $Feed[$url]['license']   = $content;
											case "alternate": $Feed[$url]['alternate'] = $content;
										}
										
										// get oembed
										if(strtolower($Link['rel']) === "alternate"){
											if(array_key_exists('type', $Link) && !empty($Link['type']) && ($Link['type'] === "application/json+oembed")){
												$Feed[$url]['oembed'] = $content;
											}
										}
									}
								}
							}
						}
					} 
				}
			}
		}



		// one final dupe-check - remove duplicate links and titles. 
		// ignore if we only took the feed of one url 

		if(count($Feed) > 1){ 

			$cache=array('link'=>array(), 'title'=>array());

			foreach($Feed as $key=>$val){
				
				if(in_array($Feed[$key]['link'], $cache['link'])){
					unset($Feed[$key]);
				}
			
				else{
					$cache['link'][] = $Feed[$key]['link'];
				}

				if(isset($Feed[$key]['title'])){
					if(in_array($Feed[$key]['title'], $cache['title'])){
						unset($Feed[$key]);
					}
					else{
						$cache['title'][]=$Feed[$key]['title'];
					}
				}


			}
		}
		
		unset($cache);

		return $Feed;
	}

	function OutboundLinks($urls){
		
		$this->Activate($urls, array(
	        'binarytransfer' => 1,
	        'buffersize'     => 2056,
	        'ssl verifypeer' => false,
	        'header'		 => false
		), '</body>', 1024768);
		
		$OutboundLinks = array();

		$Links = $this->EmbedBug->ExtractTags($this->url, array('a'));

		foreach($this->url as $url){
			
			if((int)$this->GetInfo($url, 'http code') === 200) { 
				//$Headers = $this->EmbedBug->GetInfo($url, 'headers');
				
				$OutboundLinks[$url] = array();

				if(isset($Links[$url]['a'])){ 
					foreach($Links[$url]['a'] as $link){
						if(isset($link['href'], $link['textcontent'])){
							if($this->validate_url($link['href'])){ 	
								$OutboundLinks[$url][] = $link['href'];
							}							
						}
					}
				}
			}
		}

		return $OutboundLinks; 

	}

	function getDomainWithMX($url) {
	    //parse hostname from URL 
	    //http://www.example.co.uk/index.php => www.example.co.uk
	    
		if($urlParts = $this->validate_url($url)){ 
		    
		    //find first partial name with MX record
		    $hostnameParts = explode(".", $urlParts["host"]);
		    do {
		        
		        $hostname = implode(".", $hostnameParts);
		        
		        if (checkdnsrr($hostname, "MX")){
		        	// remove 'www' prefix if it exists
		        	$hostname = preg_replace('#^www\.(.+\.)#i', '$1', $hostname);
		        	return $hostname;
		        } 
		    
		    } while (array_shift($hostnameParts) !== null);

	    }

	    return false; 
	}

	function validate_url($url){
		if($url === filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED|FILTER_FLAG_SCHEME_REQUIRED) && ($parsed_url = parse_url($url))){	
			if((stripos($parsed_url['scheme'], 'http') !== FALSE) && (strlen($parsed_url['path']) > 1)){
				return $parsed_url;
			}
		}
		return false;
	}

	function GetInfo($url, $key=null){
		return $this->EmbedBug->GetInfo($url, $key);
	}


}
