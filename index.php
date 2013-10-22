<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js" ></script>
<script src="mustache.js"></script>
<style type="text/css">
body{
line-height:150%;
font-family:arial,monospace;
font-size:10pt;
}

a{
	text-decoration:none
}

.type{
	font-weight:bold;
	margin-right:0.5em;
}

.block{
	display:block;
}

.header{
	font-size:12pt;
	border-bottom:1px solid #ddd;
	padding-bottom:0.2em;
}

.hentry{
	padding-top:0.2em;
	padding-bottom:1em;
	
}

.sitename{
	font-size:8pt;
	color:#505050;
}

.footer{
	font-size:8pt;
}

.entry-content{
  display:block;
}
.container {
    padding-top	: 3em;
    display		: block;
    padding-left: 2em;
    padding-right: 4em;
}
</style>
</head>

<body>

<form>
	<input type="text" size="50" name="url" value="https://news.ycombinator.com/news">
	<input type="submit">
</form>
<script type="text/javascript">
/*
var template = $('#template-item').html();
			var html = Mustache.to_html(template, data);
			console.log(html);
			//$(".container").append(html);
*/
$(window).on('load', function(){
	$.ajax({
		url: "json.php",
		success: function(data){
			console.log(data);
			
			for(url in data){
				var template = $("#template-item").html();
				var rendered = Mustache.to_html(template, data[url]);
				$(".container").append(rendered);	
			}
		}
	});
});
</script>

<div class="container"></div>

</body>

<script id="template-item" type="text/template">
<article class="block hentry">

<header class="block header">
<span class="type">{{type}}</span>  <a class="outlink" href="{{link}}">{{title}}</a> <span class="sitename">({{site_name}})</a> 
</header>

<div class="entry-content">
{{ description }}
</div>

<footer class="block footer">
{{ author }} - (c){{ copyright }}
</footer>

</article>

</script>


</html>