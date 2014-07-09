<?php namespace Embedbug;

/* 	broken: any attempt at enabling case-insensitive search using php functions.
    also, all of this extra filtering causes the array keys to be needlessly verbose
	ex:  [//meta | META | meta | Meta]
	
	need to be able to test this on local xml content, to verify that whatever i am doing is
	working for case insensitive nodes, not case insensitive text content.
*/
	
//http://codingexplained.com/coding/php/solving-xpath-case-sensitivity-with-php
//https://stackoverflow.com/questions/1625446/problem-with-upper-case-and-lower-case-xpath-functions-in-selenium-ide/1625859#1625859
//https://stackoverflow.com/questions/953845/how-do-i-perform-a-case-insensitive-search-for-a-node-in-php-xpath?lq=1
require_once("uagent.php"); // randon user agent generator by Luka Pušić

class Embedbug{

	private $Content;
	private $Curl;
	private $handle;
	private $terminate_string;
	private $terminate_length;
	private $urls;
	private $doc;
	private $xpath;
	private $save_html;

	function __construct($urls, $settings=null, $terminate_string="</head>", $terminate_length=1024, $preserve_whitespace=false, $save_html=false){
		

		if(is_array($urls)){ 
			$this->urls = $urls;
		}
		else{
			$this->urls=array($urls);
		}

		$this->Content = array();
		$this->Curl = array();

		$this->handle = curl_multi_init();

		$this->terminate_string = $terminate_string;
		$this->terminate_length = $terminate_length;

		/* for each url, initialize a cUrl object and add it to the array. */
    	foreach($this->urls as $url){

    		if(filter_var($url, FILTER_VALIDATE_URL) && !array_key_exists($url, $this->Curl)){ 
        		$this->Curl[$url] = $this->addHandle($url, $settings, $this->handle);	
        	}
    	}
    
    	libxml_use_internal_errors(true);
	    libxml_clear_errors();

	    $this->doc = new \DOMDocument();
		$this->doc->preserveWhiteSpace = $preserve_whitespace;
		
		$this->save_html = $save_html;
		     
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

		if(array_key_exists($url, $this->Content)){
			if(isset($key, $this->Content[$url][$key])){
				return $this->Content[$url][$key];
			}
			return $this->Content[$url];
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

        	if((int)$ref->GetInfo($url, 'http code') !== 200) return -1;     
            
            $ref->SetContent($url,'headers', $header);    
      	    
      	    return strlen($header);
        
        });

       	/* store the content. 
       	If the string and length limits are enabled, slightly more content than
       	indicated may be taken, based on the buffersize setting passed to the
       	constructor. */

 		$this->CurlOpt($cUrl, 'writefunction', function($ch, $string) use($url, $ref){

 			// if the code isn't 200, abort.
 			//if($ref->GetInfo($url, 'http code') !== 200) return -1;    

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
	
	function ExtractPaths($url=null, array $paths){
		
		$content = array();

    	if($url === null){
    		$url = $this->urls;
    	}

    	if(is_array($url)){
    		
    		foreach($url as $u){
    			$content[$u] = $this->ExtractPaths($u, $paths);
    		}

    		return $content;
    	}

    	if($contentArray = $this->GetContent($url, 'content')){ 

    		$content=$this->multi_implode($contentArray, "");

		    $extracted = array();

       		$this->doc->loadHTML($content);
		    $this->xpath = new \DOMXPath($this->doc);
			
	/* THIS DOESN'T WORK */		
			
			$this->xpath->registerNamespace('php', 'http://php.net/xpath');
			$this->xpath->registerPhpFunctions(array('stripos','strtoupper'));			

		    foreach($paths as $path){

		        $extracted[$path] = array();

		        $nodeset = $this->xpath->query("$path");

		        if($nodeset->length > 0){

		            foreach($nodeset as $node){

		                $nodearray = array();

		                foreach($node->attributes as $attr){
		                    $name = $attr->name;
		                    $val = $attr->value;
		                    $nodearray[$name] = $val;
		                }

		                if(isset($node->textContent)){
		                    $nodearray['textcontent']=$node->textContent;
		                }
						
	
		                if(count($nodearray)){ 
		                    $extracted[$path][]  = $nodearray;
		                }
		            }
		        }
		    }

			if($this->save_html === true){
				$extracted['xml'] = $this->doc->saveHTML();
			}

			
		   return $extracted;
		}

		return null;
	}

    /* extract html tags and content from a previously taken url.*/

    function ExtractTags($url = null, array $tags){

		foreach($tags as $key=>$val){
			// poor attempt at case-insensitive node matching by using the common cases.
			// this SHOULD use stripos or something but I can't get it to work. 
			$tags[$key] = "//$val | ".strtoupper($val)." | ".strtolower($val)." | ".ucfirst($val);
		}
		
		return $this->ExtractPaths($url, $tags);
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

}