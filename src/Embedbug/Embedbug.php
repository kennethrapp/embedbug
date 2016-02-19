<?php namespace Embedbug;

class Embedbug{

    private static $Properties;
    private static $Caching;
    private static $CachePath;
    private static $cURLStack;
    private static $cURLResponse;
    private static $cURLHandle;
    private static $xPath;
    private static $Doc;
    private static $Errors;
    public function __set($key, $val)
    {
        self::$Properties[$key] = $val;
    }
    
    public function __get($key)
    {
        try
        {
            if(array_key_exists($key, self::$Properties))
            {
                return self::$Properties[$key];
            }
            else throw new Exception("Property $key not found.");
        }
        catch(\Exception $e)
        {
            die($e);
        }
    }
    
    public function __toString()
    {
        return print_r(self::$cURLResponse,true);
    }
    
    public function SetCachePath($path)
    {
        self::$CachePath = $path;
    }

    public function Cache($time)
    {
        self::$Caching = $time;
        return $this;
    }
    
    public function GenerateCacheKey($url, array $query)
    {
        $cacheKey = md5(trim(strtolower($url)));
        
        foreach($query as $key=>$val)
        {
            $cacheKey = md5($cacheKey.$val);
        }
        
        return $cacheKey;
    }
    
    public function CacheFile($url, array $query, array $content)
    {   
        $key = $this->GenerateCacheKey($url, $query);
        $path = realpath(rtrim(self::$CachePath,"/"))."/"."$key.tmp";
        file_put_contents($path, serialize($content));    
        chmod($path, 0655);
    }
    
    public function GetCachedFile($key)
    {
        $path = realpath(rtrim(self::$CachePath,"/"))."/"."$key.tmp";
        
        $content=null;
        
        if(is_readable($path))
        {
            $content =  unserialize(file_get_contents($path));
            
            if (((int)self::$Caching > 0) && (filemtime($path) < time() - self::$Caching))
            {
                unlink($path);
            }
        }
    
        return $content;
    }
    
    public function __construct()
    {
        self::$cURLStack  = array();
        self::$cURLHandle = curl_multi_init();
        self::$Doc = new \DOMDocument();
        self::$cURLResponse = array();
        self::$CachePath = sys_get_temp_dir();
        self::$Caching = 0;
        self::$Errors = array();

        $this->terminate_length = 10240000;
        $this->terminate_string = "</body>";    
        libxml_use_internal_errors(true);
        libxml_clear_errors(); 
    }
    
    public function SetURLs(array $urls, $cURLSettings = array())
    {
        foreach($urls as $url)
        {
            self::$cURLStack[md5(trim(strtolower($url)))] = $this->AddcURLHandle($url, $cURLSettings, self::$cURLHandle);
        }   
    }
    
    public function GetXPaths($url, array $paths)
    {
        $key = $this->GenerateCacheKey($url, $paths);
            
        if((self::$Caching !== 0) && ($cache = $this->GetCachedFile($key)))
        {
            return $cache;
        }
        
        $this->Execute();
        
        $extracted = array(
                    "url" => $this->GetInfo($url, 'effective url'),
                   "hash" => md5(trim(strtolower($url))),
              "http-code" => $this->GetInfo($url, 'http code'),
           "content-type" => $this->GetInfo($url, 'content type'),
                   "data" => array()
        );
        
        if($contentArray = $this->GetContent($url, 'content'))
        {
            $content = implode(NULL, $contentArray);
            
            self::$Doc->loadHTML('<?xml encoding="UTF-8">' . $content);

            foreach (self::$Doc->childNodes as $item)
            {
                if ($item->nodeType == XML_PI_NODE)
                {
                    self::$Doc->removeChild($item); // remove hack
                    self::$Doc->encoding = 'UTF-8'; // insert proper
                }
            }
    
            self::$xPath = new \DOMXPath(self::$Doc);
            self::$xPath->registerNamespace('php', 'http://php.net/xpath');
            self::$xPath->registerPhpFunctions(array('stripos','strtolower'));  
            
            $nodeset=null;
            
            foreach($paths as $key => $path)
            {
                $path = trim($path);
                
                $nodeset = self::$xPath->query("$path");
                
                $lcpath = strtolower($path);
                
                $extracted['hash'] = md5($extracted['hash'].$path);
        
                if(($nodeset !== FALSE) && ($nodeset->length > 0))
                {                   
                    foreach ($nodeset as $node)
                    {
                      $nodearray = array();
            
                        $nodearray['tag'] = $node->tagName;
                        $nodearray['tag'] = $node->textContent;
                        $nodearray['value'] = $node->nodeValue;

                        foreach($node->attributes as $attr)
                        {   
                            $name = $attr->name;
                            $val = $attr->value;    
                            
                            $nodearray[$name] = $val;
                        }
                        
                        if(count($nodearray))
                        {
                            $extracted['data'][$key][]  = $nodearray;
                        }
                    }
                }
            }
        }
        
        if(self::$Caching !== 0)
        {
            $this->CacheFile($url, $paths, $extracted);
        }
        
        return $extracted;
    }
    
    public function GetTags($url, array $tags)
    {
        $remap_tags = array();
        
        foreach($tags as $tag=>$val)
        {
            // taking into account that HTML is case insensitive, we've imported strtolower
            // so we can make a case insensitive filter against the tag names.
            $remap_tags[$val] = sprintf("//*[php:functionString('strtolower', name()) = '%s' ]", 
                addslashes(strtolower(trim($val))));
        }
        
        return $this->GetXPaths($url, $remap_tags);
    }
    
    public function GetText($url, array $tags, $text)
    {
        $remap_tags = array();
        
        foreach($tags as $tag)
        {
            //NTS: test to see if case matters for this. 
            $remap_tags[$tag]= sprintf("//%s[contains(text(), '%s')]", $tag, addslashes(trim($text)));
        }
        
        return $this->GetXPaths($url, $remap_tags);
    }
    
    public function GetProfile($url)
    {
        // enforce content attributes 
        $paths = array(
              "robots" => "//meta[contains(@name,'robots')]",
               "title" => "//title[string-length(text()) > 0]",
             "refresh" => "//meta[contains(http-equiv,'refresh']",
              "author" => "//meta[contains(@name,'author')",
            "keywords" => "//meta[contains(@name, 'keywords')]",
         "description" => "//meta[contains(@name, 'description')]",
            "facebook" => "//meta[contains(@property, 'og:')]",
             "twitter" => "//meta[contains(@property, 'twitter:')]",
              "google" => "//meta[contains(@property, 'itemprop')]",
           "copyright" => "//*[contains(@rel,'copyright')]",
             "license" => "//*[contains(@rel,'license')]",
           "alternate" => "//*[contains(@rel,'alternate')]",
          "rel-author" => "//*[contains(@rel,'author')]",
       "rel-publisher" => "//*[contains(@rel,'publisher')]",
                "next" => "//*[contains(@rel,'next')]",
                "prev" => "//*[contains(@rel,'prev')]"
        );
        
        $profile =  $this->GetXPaths($url, $paths);    
        return $profile;
    }

    
    public function AddcURLHandle($url, array $cURLSettings, $cURLHandle) // can't typehint resources
    {
        $cURL = curl_init();
        
        $ref = $this;
        
        $this->cURLSetOpts($cURL, array(
                       'url' => $url,
            'returntransfer' => 1,
                    'header' => 1,
            'connecttimeout' => 5,
                   'timeout' => 15,
                 'useragent' => $_SERVER['HTTP_USER_AGENT'] // doesn't work in CLI 
        ));
        
        if(count($cURLSettings))
        { 
            $this->cURLSetOpts($cURL, $cURLSettings);
        }
        
        $this->cURLSetOpt($cURL, 'headerfunction',  function($ch, $header) use($url, $ref)
        {   
            $ref->SetContent($url, 'headers', $header);    
            return strlen($header);        
        });
        
        $this->cURLSetOpt($cURL, 'writefunction', function($ch, $string) use($url, $ref)
        {      
            $len = strlen($string);
            $ref->SetContent($url, 'content',  $string);
            /* if either the length of the content exceeds the termination length or
            the terminate string has been found, end the process */
            if(($len >= $ref->terminate_length) || (stripos($string, $ref->terminate_string)))
            {
                return -1;
            }
            
            return $len;
        }); 

        curl_multi_add_handle(self::$cURLHandle, $cURL);
        
        $url = md5(trim(strtolower($url)));
        
        if(!isset(self::$Errors[$url]))
        {
            self::$Errors[$url]=array();
        }
        
        return $cURL;
    }
    
    /* set a single curl option */
    public function cURLSetOpt($ch, $key, $opt)
    {
        $curl_const = 'CURLOPT_'.trim(strtoupper(str_replace(' ', '_', $key)));
        
        if(defined($curl_const))
        { 
            curl_setopt($ch, constant($curl_const), $opt);
        }
    }

    /* set multiple curl options */
    public function cURLSetOpts($ch, array $options)
    {
        foreach($options as $key=>$val)
        {
            $this->cURLSetOpt($ch, $key, $val);
        }
    }
    
    public function SetContent($url, $key, $val)
    {
        $url = md5(trim(strtolower($url)));
        
        if(!array_key_exists($url, self::$cURLResponse))
        {
            self::$cURLResponse[$url] = array();
        }

        if(!array_key_exists($key, self::$cURLResponse[$url]))
        { 
            self::$cURLResponse[$url][$key] = array();
        }
        
        self::$cURLResponse[$url][$key][] = $val;
    }
    
    public function GetContent($url=null, $key=null)
    {
        $url = md5(trim(strtolower($url)));
        
        if(array_key_exists($url, self::$cURLResponse))
        {
            if(isset($key, self::$cURLResponse[$url][$key]))
            {
                return self::$cURLResponse[$url][$key];
            }
            
        }
        
        return null;
    }

    public function GetInfo($url=null, $key=null)
    {
        $curl_const = 'CURLINFO_'.trim(strtoupper(str_replace(' ', '_', $key)));
        
        $url = md5(trim(strtolower($url)));

        if(isset(self::$cURLStack[$url]))
        {
            if(defined($curl_const))
            {
                return curl_getinfo(self::$cURLStack[$url], constant($curl_const));
            }
            else return curl_getinfo(self::$cURLStack[$url]);          
        }

    }

    public function GetDebug($url)
    {  
        $key = md5(trim(strtolower($url)));
        
        if(isset(self::$Errors[$key]))
        {
            $errors = self::$Errors[$key];
        }

        $info = $this->GetInfo($url);

        return array("url"=>$url, "errors"=>$errors, "info"=>$info);
    }
    
     public function Execute()
     {
        $flag = null;
        
        do
        {
            curl_multi_exec(self::$cURLHandle, $flag);
           
        }while($flag > 0);

        foreach(self::$cURLStack as $key => $ch)
        {
            self::$Errors[$key][] = curl_error($ch);
        }
    }
}
