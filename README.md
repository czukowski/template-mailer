Template Mailer
===============

This is Template_Mailer module by Dave Stewart ported to Kohana 3.0.x (and mostly untested yet)

To use it, you'll need Mailer module, such as Banks' (http://github.com/banks/kohana-email), Kohana doesn't include email
helper by default.

To use demo, you'll need to make CSS files from 'media' folder accessible, for example, by copying its contents over to document root.

I also had to change the API a bit, because SwiftMailer wouldn't take email addresses in Name&lt;address@example.com&gt; format.
