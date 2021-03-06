#EmbedBug

EmbedBug is a web scraper/site profiler in php. Designed to return xpath, tag and text content queries in a common array format to make parsing and json APIs easy. 

    include_once("EmbedBug.php") 

or include it with your [Composer](https://getcomposer.org) project's composer.json:
   
    "require":{
        "kennethrapp/embedbug:"dev-master"
    }

and call it:
    require_once("vendor/autoload.php");
    $EmbedBug = new Embedbug\Embedbug();

Now pass it some urls (either one or multiple) as an array:

    $EmbedBug->SetURLs(array("http://arstechnica.com"));
	


Methods to handle queries pair a url with either an array of xpaths, an array of tag names, or a text value. 
Each of these methods will return an array containing the url, an md5 signature (of the url and each of the paths or tags, 
representing the query in total), the returned http code and content type, and a 'data' array containing the actual result of the query.
    
    array(
        [url] => http://arstechnica.com
        [hash] => 23c07747b6f003e06597865f8e3628a2
        [http-code] => 200
        [content-type] => text/html; charset=UTF-8
        [data] => Array ( ... )
    )

The data array will contain an index for each returned tag, with the tag name as the index, and all attributes (including the text content) as keys.

**retrieve tags by name**

    $EmbedBug->GetTags( string $url, array $tags); 

*(note $tags is a simple array of tag names)*
	
```php
   $result = $EmbedBug->GetTags("http://arstechnica.com", array("meta"));
   var_dump($result);
```
    
    Array
    (
    [url] => http://arstechnica.com
    [hash] => 203291d26e9b01e08e40956308150364
    [http-code] => 200
    [content-type] => text/html; charset=UTF-8
    [data] => Array
        (
            [meta] => Array
                (
                    [0] => Array
                        (
                            [tag] => meta
                            [name] => application-name
                            [content] => Ars Technica
                        )

                    [1] => Array
                        (
                            [tag] => meta
                            [name] => msapplication-starturl
                            [content] => http://arstechnica.com/
                        )

                    [2] => Array
                        (
                            [tag] => meta
                            [name] => msapplication-tooltip
                            [content] => Ars Technica: Serving the technologist for 1.2 decades
                        )
                     (... etc etc etc)
              )
        )
    )
	
**retrieve tags by xpath**

    $EmbedBug->GetXPaths( string $url, array $paths); 

*(note $paths is an array with key=query name, value=associated xpath)*

```php
   $result = $EmbedBug->GetXPaths("http://arstechnica.com", array("open-graph"=>"//meta[contains(@property, 'og:')]"));
 var_dump($result);
```

    Array
    (
    [url] => http://arstechnica.com
    [hash] => cb1d01f1025ed5272482e44a1fe6d8d2
    [http-code] => 200
    [content-type] => text/html; charset=UTF-8
    [data] => Array
        (
            [open-graph] => Array
                (
                    [0] => Array
                        (
                            [tag] => meta
                            [property] => og:site_name
                            [content] => Ars Technica
                        )

                    [1] => Array
                        (
                            [tag] => meta
                            [property] => og:title
                            [content] => Ars Technica
                        )

                    [2] => Array
                        (
                            [tag] => meta
                            [property] => og:type
                            [content] => website
                        )
                    (etc..)
            )))

**retrieve text content b**

    $EmbedBug->GetText( string $url, array $tags, string $text); 

*(note $tags is a simple array of tag names or *, $text will apply to all of the tags)*

```php
   $result = $EmbedBug->GetText("http://arstechnica.com", array("*"), "NSA");
    var_dump($result);
 
	 Array
	(
		[url] => http://arstechnica.com
		[hash] => 6b51a141c197fce198ed1a9a4129324f
		[http-code] => 200
		[content-type] => text/html; charset=UTF-8
		[data] => Array
			(
				[*] => Array
					(
						[0] => Array
							(
								[tag] => a
								[class] => heading
								[href] => http://arstechnica.com/tech-policy/2014/07/snowden-nsa-employees-routinely-pass-around-intercepted-nude-photos/
							)

						[1] => Array
							(
								[tag] => a
								[href] => http://arstechnica.com/tech-policy/2014/07/snowden-nsa-employees-routinely-pass-around-intercepted-nude-photos/
							)

						[2] => Array
							(
								[tag] => a
								[href] => http://arstechnica.com/security/2014/07/google-project-zero-hopes-to-find-zero-day-vulnerabilities-before-the-nsa/
							)

					)

			)

	)
	

**retrieve site profile**

    $EmbedBug->GetProfile( string $url); 

*this will retrieve an array containing any open graph, twitter card tags, the title and meta description if it exists, and
a number of other useful tags (including robots and rel tags) as shown here (keys will map to the xpaths as shown):

"robots" => "//meta[contains(@name,'robots')",
"title" => "//title[string-length(text()) > 0]",
"refresh" => "//meta[contains(http-equiv,'refresh']",
"author" => "//meta[contains(@name,'author')",
"keywords" => "//meta[contains(@name, 'keywords')]",
"description" => "//meta[contains(@name, 'description')]",
"facebook" => "//meta[contains(@property, 'og:')]",
"twitter" => "//meta[contains(@property, 'twitter:')]",
"google" => "//meta[contains(@property, 'itemprop')]",
"copyright" => "//*[contains(@rel,'copyright')",
"license" => "//*[contains(@rel,'license')",
"alternate" => "//*[contains(@rel,'alternate')",
"rel-author" => "//*[contains(@rel,'author')",
"rel-publisher" => "//*[contains(@rel,'publisher')",
"next" => "//*[contains(@rel,'next')",
"prev" => "//*[contains(@rel,'prev')"

Social media tags will be grouped under 'facebook', 'twitter' and 'google', and meta description under 'description'*

```php
   $result = $EmbedBug->GetProfile("http://arstechnica.com");
   var_dump($result);
	
	Array
	(
		[url] => http://arstechnica.com
		[hash] => 23c07747b6f003e06597865f8e3628a2
		[http-code] => 200
		[content-type] => text/html; charset=UTF-8
		[data] => Array
			(
				[title] => Array
					(
						[0] => Array
							(
								[tag] => title
							)

					)

				[description] => Array
					(
						[0] => Array
							(
								[tag] => meta
								[name] => description
								[content] => Serving the Technologist for more than a decade. IT news, reviews, and analysis.
							)

					)

				[facebook] => Array
					(
						[0] => Array
							(
								[tag] => meta
								[property] => og:site_name
								[content] => Ars Technica
							)

						[1] => Array
							(
								[tag] => meta
								[property] => og:title
								[content] => Ars Technica
							)

						[2] => Array
							(
								[tag] => meta
								[property] => og:type
								[content] => website
							)

						[3] => Array
							(
								[tag] => meta
								[property] => og:url
								[content] => http://arstechnica.com/
							)

						[4] => Array
							(
								[tag] => meta
								[property] => og:image
								[content] => http://cdn.arstechnica.net/wp-content/themes/arstechnica/assets/images/ars-logo-open-grey.png
							)

						[5] => Array
							(
								[tag] => meta
								[property] => og:description
								[content] => Serving the Technologist for more than a decade. IT news, reviews, and analysis.
							)
					)
			)
	)

** get debug data **


** get errors **
	
	
**caching queries**

EmbedBug can cache queries in PHP's serialize format to temp files in a provided path. If the Cache() method is chained with a time 
as the argument (in seconds) to one of the above methods, the temp file will be created and will be accessed prior to calling cUrl
for the matching url and queries until the cache file expires and is deleted. 

	$EmbedBug->SetCachePath(realpath("path/to/cache"));
	$EmbedBug->Cache(int $time)->*any of the above queries*

**integration testing?**

I've provided integration tests designed to be run in PHP's local server using phpunit, as opposed to making requests to remote URLS all the time. This is not the right way to do it but the right way would probably mean redesigning everything to fully mock Curl, DomDocument and DomXML and I really don't want to do that, so the "next best thing" to me is to run tests on a known data set.If you want to, you'll need to start the server and have the docroot point to tests/localhost like so:

php -S localhost:8000 -t "tests\localhost"	(from within the tests directory)

then you can run the test suite from another terminal. 

In the test folder you can find old tests which cover each of the examples here

**known issues/to fix or ignore**

- doesn't do anything with cookies, POST or RESTful APIs, only GET requests
- doesn't route through proxies or anything either
- doesn't respect robots.txt
- doesn't appear to follow redirects
- SetURLs will still load cUrl handles even when the end result calls a cached file.  