<?php namespace EmbedBug;

require_once("uagent.php"); // randon user agent generator by Luka Pušić

class EmbedBug{

	/* there should be a url router which handles specific urls. Also the images are
	incredibly large sometimes so there should be options to thumbnail locally or not
	download at all. */

	private $Content;
	private $Curl;
	private $handle;
	private $terminate_string;
	private $terminate_length;
	private $urls;
	private $doc;
	private $xpath;

	function __construct($urls, $settings=null, $terminate_string="</head>", $terminate_length=1024){
		
		$this->urls = $urls;
		$this->Content = array();
		$this->Curl = array();

		$this->handle = curl_multi_init();

		$this->terminate_string = $terminate_string;
		$this->terminate_length = $terminate_length;

		/* for each url, initialize a cUrl object and add it to the array. */

		if(is_array($urls) && count($urls)){ 

	    	foreach($urls as $url){

	    		if(filter_var($url, FILTER_VALIDATE_URL) && !array_key_exists($url, $this->Curl)){ 
	        		$this->Curl[$url] = $this->addHandle($url, $settings, $this->handle);	
	        	}
	    	}
	    
	    }else if(filter_var($urls, FILTER_VALIDATE_URL) && !array_key_exists($urls, $this->Curl)){ 
	        $this->Curl[$urls] = $this->addHandle($urls, $settings, $this->handle);	
	    }

	    	libxml_use_internal_errors(true);
		    libxml_clear_errors();

		    $this->doc = new \DOMDocument();
		     
    }

    // return whether the length terminator has been reached
    
    function TerminateAtLength($len){
    	return (is_int($this->terminate_length) && ($len >= $this->terminate_length));    	
    }

    // return whether the string terminator has been reached

    function TerminateAtString(&$content){
    	return (is_string($this->terminate_string) && (stripos($content, $this->terminate_string) !== false));
    }

    /* set content. Content is grouped by url into 'headers'
    ( an array of headers for each url), and 'content' ( a string 
    	of content from each url) */

	function SetContent($url, $key, $val){
		
		if(!array_key_exists($url, $this->Content)){
			$this->Content[$url] = array();
		}

		if(!array_key_exists($key, $this->Content[$url])){ 
			$this->Content[$url][$key] = array();
		}

		
		$this->Content[$url][$key][] = $val;
		
	}

	/* retrieve a content group */

	function GetContent($url=null, $key=null){

		if(isset($url)){
			if(array_key_exists($url, $this->Content)){
				if(isset($key, $this->Content[$url][$key])){
					return $this->Content[$url][$key];
				}
				return $this->Content[$url];
			}
		}

		return $this->Content;
	}


	/*
	return info about a transfer. 

	 both $url and $key can be an array, string or null. 

	"url"
	"content type"
	"http code"
	"header size"
	"request size"
	"filetime"
	"ssl verify result"
	"redirect count"
	"total time"
	"namelookup time"
	"connect time"
	"pretransfer time"
	"size upload"
	"size download"
	"speed_download"
	"speed upload"
	"download_content length"
	"upload_content length"
	"starttransfer time"
	"redirect time"
	"certinfo" 

	*/

	
	function GetInfo($url=null, $key=null){

		$curl_const = null;

		if(is_string($key)){
			$curl_const = 'CURLINFO_'.trim(strtoupper(str_replace(' ', '_', $key)));
		}
		else if(is_array($key)){
			$curl_const = array();
			foreach($key as $k){
				$curl_const[] = 'CURLINFO_'.trim(strtoupper(str_replace(' ', '_', $k)));
			}
		}
		
		
		$content = array();
		

		// normalize url list. Match $url to either a string or array passed
		// to the constructor. Otherwise, take everything.

		if(is_string($url) && array_key_exists($url, $this->Curl)){

			if(is_string($curl_const)){ 
				if(!empty($curl_const) && defined($curl_const)){ 
					return curl_getinfo($this->Curl[ $url ], constant($curl_const));
				}
				else{
					return curl_getinfo($this->Curl[$url]);
				}
			}
			
			else if(is_array($curl_const)){
				foreach($curl_const as $c){
					if(!empty($c) && defined($c)){
						$content[] =curl_getinfo($this->Curl[ $url ], constant($c));
					}
				}

				return $content;	
			}
			
			return curl_getinfo($this->Curl[ $url ]); // no constant - return all data
		}

		// url is array, take the intersection

		else if(is_array($url)){
			
			$filtered_urls = array_intersect($url, $this->urls);

			if(count($filtered_urls)){

				foreach($filtered_urls as $filtered_url){

					if(is_string($curl_const)){ 
						if(!empty($curl_const) && defined($curl_const)){ 
							$content[] = curl_getinfo($this->Curl[ $filtered_url ], constant($curl_const));
						}
						else{
							$content[] = curl_getinfo($this->Curl[$filtered_url]);
						}
					}
					
					else if(is_array($curl_const)){
						foreach($curl_const as $c){
							if(!empty($c) && defined($c)){
								$content[] =curl_getinfo($this->Curl[ $url ], constant($c));
							}
						}
					}
				}

				return $content;
			}
		}

		// url was not passed, take everything.

		else if(is_string($this->urls)){ // as a string
			if(!empty($curl_const) && defined($curl_const)){ 
				return curl_getinfo($this->Curl[ $this->urls ], constant($curl_const));
			}
			else{
				return curl_getinfo($this->Curl[ $this->urls ]);
			}
		}
		
		else if(is_array($this->urls)){ // as an array
			foreach($this->urls as $url){
				if(!empty($curl_const) && defined($curl_const)){ 
					$content[] = curl_getinfo($this->Curl[ $url ], constant($curl_const));
				}
				else{
					$content[] = curl_getinfo($this->Curl[ $url ]);
				}
			}
			return $content;
		}

		return null;

	}


	/* set a single curl option */

	 function CurlOpt($ch, $key, $opt){
        
        $curl_const = 'CURLOPT_'.trim(strtoupper(str_replace(' ', '_', $key)));
        
        if(defined($curl_const)){ 
        	curl_setopt($ch, constant($curl_const), $opt);
        }
    }

    /* set multiple curl options */

    function CurlOpts($ch, array $options){
    	foreach($options as $key=>$val){
    		$this->CurlOpt($ch, $key, $val);
    	}
    }

    /* add a curl handle indexed by url */

    function addHandle($url, $settings, $handle){

        $cUrl = curl_init();
 		
 		$ref = $this;
        
        $this->CurlOpts($cUrl, array(
        	'url'			=> $url,
        	'returntransfer'=> 1,
        	'header'		=> 1,
        	'connecttimeout'=> 5,
        	'timeout'		=> 15,
        	'useragent'	    => random_uagent() // select a random user agent by default
        ));

        if(is_array($settings)){ 
            $this->CurlOpts($cUrl, $settings);
        }

        // store the headers 
        $this->CurlOpt($cUrl, 'headerfunction',  function($ch, $header) use($url, $ref){   

        	if($ref->GetInfo($url, 'http code') !== 200) return -1;     
            
            $ref->SetContent($url,'headers', $header);    
      	    
      	    return strlen($header);
        
        });

       	/* store the content. 
       	If the string and length limits are enabled, slightly more content than
       	indicated may be taken, based on the buffersize setting passed to the
       	constructor. */

 		$this->CurlOpt($cUrl, 'writefunction', function($ch, $string) use($url, $ref){

 			// if the code isn't 200, abort.
 			if($ref->GetInfo($url, 'http code') !== 200) return -1;    

          	// if the terminator string exists, end. 
            if($ref->TerminateAtString($string)) return -1;
            
        	// if the size limit has been reached, end.
            if($ref->TerminateAtLength(strlen($string))) return -1;
            
 			$ref->SetContent($url,'content',  ($string)); 

            return strlen($string);
        }); 

         curl_multi_add_handle($handle, $cUrl);

        return $cUrl;
    } 

    function __destruct(){
        curl_multi_close($this->handle);
    }

    function Execute(){    

        $flag=null;

        do{
            curl_multi_exec($this->handle, $flag);
        }while($flag > 0);
    }

    /* extract html tags and content from a previously taken url.

    unknown if the option for url as null has actually
    been tested yet. */

    function ExtractTags($url = null, array $tags){

    	$content = array();

    	if($url === null){
    		$url = $this->urls;
    	}

    	if(is_array($url)){
    		
    		foreach($url as $u){
    			$content[$u] = $this->ExtractTags($u, $tags);
    		}

    		return $content;
    	}

    	if($contentArray = $this->GetContent($url, 'content')){ 

    		$content=$this->multi_implode($contentArray, "");

		    $extracted = array();

       		$this->doc->loadHTML($content);
		    $this->xpath = new \DOMXPath($this->doc);    

		    foreach($tags as $tag){

		        $extracted[$tag] = array();

		        $tagset = $this->xpath->query("//$tag");

		        if($tagset->length > 0){

		            foreach($tagset as $tagnode){

		                $tagarray = array();

		                foreach($tagnode->attributes as $attr){
		                    $name = $attr->name;
		                    $val = $attr->value;
		                    $tagarray[$name] = $val;
		                }

		                if(isset($tagnode->textContent)){
		                    $tagarray['textcontent']=$tagnode->textContent;
		                }

		                if(count($tagarray)){ 
		                    $extracted[$tag][]  = $tagarray;
		                }
		            }
		        }

		        // try it with a regex for meta tags. Even though this is 'evil', given how much
		        // the parser seems to fail (more often than the regex) i'm fine with it.
		        if($tag === 'meta'){

		            $tagarray = array();
		            
		            if(preg_match("#<meta([^>]*)>#si", $content, $matches)){
		                
		                //$matches[1] is what stores the contents of the meta tag
		                $matches[1] = str_replace("/", '', $matches[1]);

		                // put each attribute and its value into an array
		                $attrs = preg_split("#(\"\s)#i", trim($matches[1]));

		                if(count($attrs)){ 

		                // we now get the attribute name and attribute value in sperate variables

		                   /* some unknown site is causing an exception because of its metadata
		                   with explode. Later, I should replace explode with strtok */
		                    @list($attr_name, $attr_value) = explode("=", $attr);
		                	
		                	// create our meta array, trimming of any double quotes 
		                	// remaining in the attribute value string.
		                    if(isset($attr_name, $attr_value)){ 
		                        $tagarray[$attr_name] = trim($attr_value, '"');
		                        $extracted[$tag][] = $tagarray;
		                    }
		                }
		            }
		        }
		    }

		   return $extracted;
		}

		return null;
	}

	// multi-dimensional implode. 
	function multi_implode($array, $glue) {
    	
    	$ret = '';

	    foreach ($array as $item) {
	        if (is_array($item)) {
	            $ret .= $this->multi_implode(array_values($item), $glue);
	        } else {
	            $ret .= $item . $glue;
	        }
	    }

	    return $ret;
	}

	/* format the current urls into a "feed", as an array.
	Each index of the array will be a url, which will
	be an array of values for that site. Possible values
	will be

	link
	title
	type
	author
	post_id
	pub_date
	section
	tags[]
	image_url

	However at the very least you may end up with an empty
	array even if the site has metadata - in which case
	there will only be the site url to work with. 

	$defaults is an optional array with default values
	*/

	function ExtractFeed($defaults = null){ 
		
		$Feed = array();

		if($AllMeta = $this->ExtractTags(null, array('meta','title'))){

	    	// format into a nice thing. 
	    	// this works, except it needs indices for each url. 
	    	foreach($AllMeta as $key=>$val){

	    		$code = $this->GetInfo($key, 'http code');

	    		if((int)$code !== 200){
	    			continue;
	    		}
	    		
	    		$Parse = array();
	    		$URL = $key;

	    		// if there's still no title, see if the title tag exists
		    	if(isset($AllMeta[$key]['title']) && count($AllMeta[$key]['title'])){

		    		if(array_key_exists('textcontent', $AllMeta[$key]['title'][0])){ 
		    			$Parse['title'] = $AllMeta[$key]['title'][0]['textcontent'];
		    		}			
		    	
		    	}

	    		foreach($AllMeta[$key]['meta'] as $Meta){

		    		if(array_key_exists('name', $Meta)){
		    			
		    			if(array_key_exists('content', $Meta)){ 
		    				
		    				$content = trim($Meta['content']);
		    				
		    				if(is_string($content) && !empty($content)){ 

				    			switch(strtolower($Meta['name'])){
				    				case "description" : $Parse['description'] = $content; break;
				    				case "title"       : $Parse['title']       = $content; break;
				    				case "author"      : $Parse['author']      = $content; break;
				    				case "keywords"    : $Parse['keywords']    = explode(",", $content); break;
				    				case "copyright"   : $Parse['copyright']   = $content; break;
				    			}
				    		}

			    			// replace with parsely JSON if it exists
			    			if($Meta['name'] === 'parsely-page'){
			    				
			    				$json = json_decode($content, true);

			    				foreach($json as $key=>$val){

			    					if(is_string($val) && !empty($val)){ 
			    						$Parse[$key] = $val;
			    					}
			    				}
							}
						}
		    		}
		    		
		    		/* look for open graph and twitter tags. Overwrite the parse array with those. */

		    		if(array_key_exists('property', $Meta)){

		    			$prop = trim($Meta['property']);

		    			if(array_key_exists('content', $Meta)){ 
			    			
			    			$content = trim($Meta['content']);
			    			
			    			if(is_string($content) && !empty($content) && (stripos($prop,'twitter:') !== false)){ 
			    			// twitter tags
			    				
			    				switch($prop){
			    					case "twitter:creator" 		: $Parse['twitter']     = $content; break;
			    					case "twitter:url" 			: $Parse['link']   		= $content; break;
			    					case "twitter:title" 		: $Parse['title']   	= $content; break;
			    					case "twitter:description" 	: $Parse['description'] = $content; break;
			    					case "twitter:image"        : $Parse['image_url']   = $content; break;
			      				}
			    			
			    			}

			    			if(is_string($content) && (stripos($prop,'og:') !== false)){ // an open graph tag
			    				
			    				switch($prop){
			    					case "og:image"		  : $Parse['image_url']   = $content; break;
			    					case "og:title"		  : $Parse['title'] 	  = $content; break;
			    					case "og:url"  		  : $Parse['link'] 		  = $content; break;
			    					case "og:site_name"   : $Parse['site_name']   = $content; break;
			    					case "og:type"        : $Parse['type']        = $content; break;
			    					case "og:description" : $Parse['description'] = $content; break;
			      				}
			    			}

			    			
			    			if(is_string($content) && (stripos($prop,'article:') !== false)){// replace 'article:' content
			    			
			    				switch($prop){
			    					case "article:author"		  : $Parse['author']         = $content; break;
			    					case "article:published_time" : $Parse['published_time'] = $content; break;
			    				}
			    			}
			    		}
		    		}
		    	}// end foreach

		    	// apply defaults to nonexistent or empty fields
		    	if(is_array($defaults)){
					foreach($defaults as $key=>$val){
						if(!array_key_exists($key, $Parse) || empty($Parse[$key])){
							$Parse[$key] = $defaults[$key];
						}
					}
				}
		    	
		    	$Feed[ $URL ] = $Parse;
		    }
    	}

    	return $this->Finalize($Feed, $defaults);
	}

	function Finalize($array){
		foreach($array as $key=>$val){
			if(is_array($val)){
				$array[$key] = $this->Finalize($val);
			}
			else{
				$val = trim( utf8_decode( preg_replace('/[^(\x20-\x7F)]*/', '', $val) ) );
				$array[$key] =  $val;
			}
		}
		
		return $array;
	}
}