<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Welcome to the Site, {{name}}!</title>
	<link href="/some/link/to/external.css" rel="stylesheet" type="text/css" />
	<meta name="from" content="The Team<team@<?php echo preg_replace('/^\w+\./', '', $_SERVER['HTTP_HOST']); ?>>" />
	<style type="text/css">
	
		*{
			font-size:12px;
			line-height:17px;
			font-family:Arial, Helvetica, sans-serif;
			}
			
		body{
			padding:30px;
			font-family:Arial, Helvetica, sans-serif;
			}
			
		h1{
			font-size:36px;
			line-height:40px;
			font-weight:normal;
			font-family:"Palatino Linotype", "Palatino Linotype Bold", Georgia, serif;
			margin:10px 0px 10px 0px;
			}
			
		p{
			margin:0px 0px 10px 0px;
			}
			
		li{
			list-style-type:square;
			}
			
		strong{
			font-weight:bold;
			}
			
		a, a:link{
			color:green;
			}
			
		#message{
			font-style:italic;
			}
			
		.name{
			font-family:"Palatino Linotype", "Palatino Linotype Bold", Georgia, serif;
			font-size:36px;
			color:red;
			}
			
		.info{
			font-size:0.8em;
			color:#CCC;
			}
			
	</style>
</head>

<body>
<h1>Hello <span class="name">{{name}}</span></h1>
<p id="message"><strong>Welcome to the site!</strong> {{message}}</p>
<p>You may also like to check out these links:</p>
<ul>
	<li><a href="http://www.keyframesandcode.com">Keyframes and Code</a></li>
	<li><a href="http://www.google.com">Google</a></li>
	<li><a href="http://www.bbc.co.uk">BBC</a></li>
</ul>
<p>All the best,<br />The Ace Site team</p>
<p class="info">Contact us on 020 1234 5678</p>
</body>
</html>