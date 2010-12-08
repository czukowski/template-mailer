<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Template Mailer: Demo</title>
	<link rel="stylesheet" type="text/css" href="<?php echo URL::site('css/mailer/styles.css')?>" />
	<link rel="stylesheet" type="text/css" href="<?php echo URL::site('css/mailer/form.css')?>" />
</head>

<body>

	<form id="content" name="form1" method="post" action="<?php echo Route::url('mailer_demo', array('mode' => 'send'))?>">
	
		<h1>Template Mailer Demo</h1>
		<p>The demo on this page demonstrates basic usage - loading, converting and sending an <a href="<?php echo Route::url('mailer_demo', array('mode' => 'template'))?>">example</a> template file.</p>
<p>You can preview the markup generated before sending, and then	send	yourself the example file to see how it renders in your email client.</p>
		<fieldset>
			<input type="hidden" name="preview" value="" />
			<table width="100%" border="0" cellspacing="5">
				<tr>
					<td><label>Your Name: </label></td>
					<td><input type="text" name="name" value="<?php echo @$name; ?>" /><br />
					<span class="error"><?php echo @$errors['name']; ?></span></td>
				</tr>
				<tr>
					<td><label>Your Email: </label></td>
					<td><input type="text" name="email"  value="<?php echo @$email; ?>"/><br />
					<span class="error"><?php echo @$errors['email']; ?></span></td>
				</tr>
				<tr>
					<td><label>Message: </label></td>
					<td><textarea class="right" name="message">Your user id is <?php echo rand(10000, 99999); ?>.</textarea></td>
				</tr>
				<tr>
					<td><label>Template: </label></td>
					<td><select name="template">
						<option value="mailer/email/example">Simple exmaple email</option>
						<option value="mailer/email/acid">EmailStandards.org Acid Test</option>
					</select></td>
				</tr>
			</table>
		</fieldset>
		<div id="buttons">
			<input class="button" type="submit" value="View Template" onclick="previewTemplate()" title="View the original HTML template"/>
			<div>
				<input class="button" type="submit" value="Preview HTML" onclick="previewEmail()" title="View the template, converted" /><br />
				<label style="width:auto; display:inline"><input type="checkbox" name="simulate_email" id="simulate_email"	value="1" /> As email</label>
			</div>
			<div>
				<input class="button" type="submit" value="Send Email" onclick="sendEmail()" title="Send the email, converted, to the email address above" />
				<label style="width:auto; display:inline"><input type="checkbox" name="no_conversion" id="no_conversion" value="1" /> No conversion</label>
			</div>
		</div>
		
	</form>

	<script type="text/javascript">
	
		var form = document.forms[0];
	
		function previewTemplate()
		{
			form.action = '<?php echo Route::url('mailer_demo', array('mode' => 'preview'))?>';
		}
	
		function previewEmail()
		{
			form.action = form.simulate_email.checked ? '<?php echo Route::url('mailer_demo', array('mode' => 'preview'))?>' : '<?php echo Route::url('mailer_demo', array('mode' => 'html'))?>';
		}
	
		function sendEmail()
		{
			form.action = form.no_conversion.checked ? '<?php echo Route::url('mailer_demo', array('mode' => 'send_native'))?>' : '<?php echo Route::url('mailer_demo', array('mode' => 'send'))?>';
		}
	
	</script>
</body>
</html>