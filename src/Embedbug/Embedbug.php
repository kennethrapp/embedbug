<?php namespace Embedbug;

require_once("uagent.php"); // randon user agent generator by Luka Pušić

class Embedbug{

	private $Content;
	private $Curl;
	private $handle;
	private $terminate_string;
	private $terminate_length;
	private $urls;

	function __construct($urls, $settings=null, $terminate_string="</head>", $terminate_length=1024){
		
		$this->urls = $urls;
		$this->Content = array();
		$this->Curl = array();

		$this->handle = curl_multi_init();

		$this->terminate_string = $terminate_string;
		$this->terminate_length = $terminate_length;

		/* for each url, initialize a cUrl object and add it to the array. */

		if(is_array($urls) && count($url)){ 

	    	foreach($urls as $url){

	    		$url = trim($url);

	    		if(filter_var($url, FILTER_VALIDATE_URL) && !array_key_exists($url, $this->Curl)){ 
	        		$this->Curl[$url] = $this->addHandle($url, $settings, $this->handle);	
	        	}
	    	}
	    
	    }else if(isset($urls)){ // if passed as a string
	    	
	    	$url = trim($urls);

	    	if(filter_var($url, FILTER_VALIDATE_URL) && !array_key_exists($url, $this->Curl)){ 
	        	$this->Curl[$url] = $this->addHandle($url, $settings, $this->handle);	
	        }
	    } 
    }

    // return whether the length terminator has been reached
    
    function TerminateAtLength($len){
    	return (is_int($this->terminate_length) && ($len >= $this->terminate_length));    	
    }

    // return whether the string terminator has been reached

    function TerminateAtString(&$content){
    	return (is_string($this->terminate_string) && (stripos($content, $this->terminate_string) !== false));
    }

    /* set content. */

	function SetContent($url, $key, $val){
		
		/* content will be grouped by url (node) into
		   'headers', 'content', and 'info' */

		if(!array_key_exists($url, $this->Content)){
			$this->Content[$url] = array();
		}

		if(!array_key_exists($key, $this->Content[$url])){ 
			$this->Content[$url][$key] = array();
		}

		if(preg_replace('/\s+/','',$val)){ 
			$this->Content[$url][$key][] = trim($val);
		}
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

	$url can be one or more urls passed by the constructor. If null,
	all passed arrays will be used.

	$key can be the plaintext (lowercase, with spaces)
	equivalent of a CURLINFO_ constant. If omitted, this method will
	return the all curlinfo results as an associative array.  

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
		
		$const = 'CURLINFO_'.trim(strtoupper(str_replace(' ', '_', $key)));
		$curl_const = null;
		$content = array();

		if(defined($curl_const)){ 
			
			/* if url was passed as an array, get the settings from those urls */
			if(is_array($url)){
				
				foreach($url as $u){
					$content[] = curl_getinfo($this->Curl[ $u ], $curl_const);
				}
				
				return $content;
			}

			else if(is_string($url)){ // single url passed
				return curl_getinfo($this->Curl[ $url ]);
			}

			/* if url is null, get everything */
			else if(is_null($url)){
				
				if(is_array($this->url)){ // multiple urls were passed to the constructor
					
					foreach($this->url as $u){
						$content[] = curl_getinfo($this->Curl[ $u ], $curl_const);
					}

					return $content;
				}

				else if(is_string($this->url)){ // a single url was passed to the constructor
					return curl_getinfo($this->Curl[ $u ], $curl_const);
				}	
			}
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
            $ref->SetContent($url,'headers', $header);    
      	    return strlen($header);
        });

       	/* store the content. 
       	If the string and length limits are enabled, slightly more content than
       	indicated may be taken, based on the buffersize setting passed to the
       	constructor. */

 		$this->CurlOpt($cUrl, 'writefunction', function($ch, $string) use($url, $ref){    

 			// write or append to the current content
            if($content = $ref->GetContent($url, 'content')){
            	$ref->SetContent($url,'content',  ($content.$string)); 
            }
            else{
            	$ref->SetContent($url,'content', $string); 
            	$content=null;
            }


            // if the terminator string exists, end. 
            if($ref->TerminateAtString($string)){
                return -1;
            }

        	// if the size limit has been reached, end.
            if($ref->TerminateAtLength(strlen($string))){
                return -1;
            }

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

    /* extract html tags and content from a previously taken url. */

    function ExtractTags($url, array $tags){

    	if($contentArray = $this->GetContent($url, 'content')){ 
    		
    		$content=implode(null, $contentArray);

		    $extracted = array();

		    libxml_use_internal_errors(true);
		    libxml_clear_errors();

		    $doc = new \DOMDocument();
		    $doc->loadHTML($content);
		    $xpath = new \DOMXPath($doc);            

		    foreach($tags as $tag){

		        $extracted[$tag] = array();

		         $tagset = $xpath->query("//$tag");

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

		                //die(var_dump($attrs));

		                if(count($attrs)){ 

		                // we now get the attribute name and attribute value in sperate variables

		                   /* some unknown site is causing an exception because of its metadata
		                   with explode. Later, I should replace explode with strtok */
		                    @list($attr_name, $attr_value) = explode("=", $attr);
		                // we create our meta array, trimming of any double quotes remaining in the attribute value string.
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

}