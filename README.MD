# Embedbug
Embedbug is a page scraper in PHP, designed to extract groups of tags from a url. It's still currently in the beginning stages so if you want to use it go ahead but there are no guarantees. 


## Usage
Embedbug is instantiated with a number of parameters which start a multi_curl process. 

$Embedbug = new Embedbug/Embedbug($url, $settings, $endtag, $endbytes);


$url (string or array) - can be a single url or multiple urls as an array.

$settings (optional array) - curl options for the curl process. To make it easier on your precious typing fingers, the keys for this array can be lowercase, with spaces. If not used, the defaults are

['binarytransfer' => 1, 'buffersize' => 2056, 'ssl verifypeer' => false, 'header' => false]

$endtag (optional string) - an ending html tag which, if found, will abort the curl process on the current read. If not used, the default is "</body>". Note that in some cases (for instance, using "</head>") content beyond the end tag may still be read, depending on where it is found and the size of the buffer.  

$endbytes (optional int) - a number of bytes which, when read, will abort the curl process on the current read. 

$Embedbug->Execute();

Executes the curl process. Must be called before any other methods. 

$Tags = $Embedbug->ExtractTags($url, $tags);

Parses the content for a url and returns any tags, if found, as an array. 

$paths = $Embedbug->ExtractPaths($url, $paths);

Returns content based on an array of xpaths, instead of tag names. 
(for example  $Embedbug->ExtractPaths($url, array("//meta[contains(@property,'og:')]")) returns all open graph meta tags)

## Test Scripts
test.php instantiates the embedbug class and dumps it. (for arstechnica.com)
test-tag.php (params ?url=[url] & tag = [tag]) displays tag(s) for a url (defaults are arstechica.com, <meta>)
test-xpath.php tests an xpath 
