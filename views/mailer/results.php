<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Template Mailer Demo</title>
	<link rel="stylesheet" type="text/css" href="<?php echo URL::site('css/mailer/styles.css')?>" />
</head>

<body>

	<div id="content">
	
		<h1>Template Mailer Demo</h1>
		<h2>Results</h2>
		
		<?php if($mailer->success): ?>
		
		<p>The mail was sent OK!</p>
		
		<?php else: ?>
		
		<p>There was a problem sending the mail. The status message was as follows:</p>
		<pre><?php echo HTML::chars($mailer->status);?></pre>
		
		<?php endif; ?>
		
		<p>The Template Mailer log is as follows:</p>
		<pre><?php print_r($mailer->log); ?></pre>
		
	</div>

</body>
</html>