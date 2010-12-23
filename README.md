Template Mailer
===============

This is Template_Mailer module by Dave Stewart ported to Kohana 3.0.x (and mostly untested yet)

The files from Banks' Email module (http://github.com/banks/kohana-email) are included for convenience, but if you already have it,
it's no problem, thanks to Kohana Cascading File System.

To use demo, you'll need to make CSS files from 'media' folder accessible, for example, by copying its contents over to document root.

I also had to change the API a bit, because SwiftMailer wouldn't take email addresses in Name&lt;address@example.com&gt; format.
