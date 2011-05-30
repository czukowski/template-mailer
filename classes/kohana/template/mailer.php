<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package         Template Mailer
 * @author          Dave Stewart
 * @website         kohana.keyframesandcode.com
 * @date            11th November 2010
 * @copyright       (c) Dave Stewart 2010
 * @kohana version  Tested on 3.0.8
 * 
 * Kohana_Template_Mailer sends inline-CSS templated emails
 * 
 * It is specifically designed to read in standard HTML/PHP template files and do the following:
 * 
 *   - Grab email subject from the page <title> tag
 *   - Grab default "from" address from the first metadata tag of teh format <meta name="from" content="email@address.com" />
 *   - Convert linked and global CSS declarations to inline CSS by loading, parsing and converting stylesheets.
 *   
 * The CSS is converted by parsing:
 * 
 *   - All CSS from files linked from <link href="/path/to/file.css" /> tags (href must be /absolute)
 *   - All CSS from the first <style> tag
 *
 * For a complete guide to support of CSS styles across different email clients, see http://www.campaignmonitor.com/css/
 * 
 * Data is parsed in the following way: 
 * 
 *  - On instantiation, all PHP wil be parsed as standard Kohana Views (good for one-time only emails)
 *  - Subsequently, you should use set_data() to populate {{placeholder}} tags in the email HTML (good for one-time and batch emails)
 *   
 * @usage
 * 
 * 		Template_Mailer::factory('path/to/email/template', $php_data)	// set initial PHP variables, and updates template to inline CSS. 
 * 																		// if no data is provided, cached version is used
 * 
 * 			->set_data($template_data_1)			// sets the template data for user 1 
 * 			->send(array($name_1, $email_1))		// emails to user 1
 * 
 * 			->set_data($template_data_2)			// sets the template data for user 2 
 * 			->send(array($name_2, $email_2))		// emails to user 2
 * 
 * 			->set_data($template_data_3)			// sets the template data for user 3 
 * 			->send(array($name_3, $email_3))		// emails to user 3
 * 
 */
class Kohana_Template_Mailer
{
	// ------------------------------------------------------------------------------------------------
	// variables

		// content
			protected $template			= '';			// the original html resulting from the template view being rendered
			protected $title			= '';			// the original html title resulting from the template view being rendered
			protected $html				= '';			// the parsed html that includes all placeholder replacements

		// parameters
			protected $subject			= '';			// Email subject that will be stored between sends
			protected $from				= array();		// email address in the format 'Joe Bloggs <joe@bloggs.com>'
			protected $to				= array();		// email address in the format 'Joe Bloggs <joe@bloggs.com>'

		// status variables
			protected $_success			= FALSE;		// flag to say whether the last-sent mail was successful
			protected $_status			= '';			// the last status message. will hold any errors if mail sending was unsuccessful

		// logging
			protected $_log				= array(); 
			protected $_time			= array(); 

			protected $_cache_prefix	= 'Template_Mailer/';

	// ------------------------------------------------------------------------------------------------
	// instantiation

		/**
		 * Template_Mailer constructor
		 * 
		 * @param string $template_path			A path to the email template view file 
		 * @param array $data [optional]		Optional data to parse into the view file as it is processed for the first time, or TRUE to rebuild the cached template
		 * @return 
		 */
		public function __construct($template_path, $php_data = NULL)
		{
			// includes
				require_once(Kohana::find_file('vendor', 'mailer/CSSToInlineStyles'));
				require_once(Kohana::find_file('vendor', 'mailer/HTML2Text'));

			// log
				$this->_time = microtime(TRUE);
				$this->log('Class loaded');
			
			// default from email
				$this->from				= array('admin@'.preg_replace('/^\w+\./', '', $_SERVER['HTTP_HOST']), 'Admin');
				
			// get cached template
				$this->template			= $this->get_cache($template_path); 
				
			// if there's no cached template, it's now old, or if there's new data, re-create the template and cache it
				if ($this->template == NULL || $php_data != NULL)
				{
					// check if recache was forced
						if ($php_data === TRUE)
						{
							$this->log("Forcing re-cache");
							$php_data = array();
						}

					// log
						$this->log("Creating new template");

					// load view
						$this->template = View::factory($template_path, $php_data)->render();

					// parse css 
						$this->parse_css();

					// cache for future use
						$this->set_cache($template_path);
				}

			// finally, set the from and subject from the template
				$this->parse_params();

			// log
				$this->log('Setup complete');


			// return 
				return $this;
		}


		/**
		 * Chainable factory method to return new Template_Mailer instance
		 * 
		 * @param object $template_path
		 * @param object $data [optional]
		 * @param object $template_path [optional]
		 * @return 
		 */
		public static function factory($template_path = NULL, $data = NULL)
		{
			return new Template_Mailer($template_path, $data);
		}


	// ------------------------------------------------------------------------------------------------
	// public methods: data
	
		/**
		 * Assign data to {placeholder} variables.
		 * 
		 * The following properties will also be set if the $data array contains the following values:
		 * 
		 * "key"				-> sets "property"
		 * --------------------    -----------------------
		 * "name" and "email"	-> sets "to email"
		 * "to"					-> sets "to"   
		 * "from"				-> sets "from"   
		 * "subject"			-> sets "subject"
		 * 
		 * @param array $data				An array or key => value pairs
		 * @param bool $reset [optional]	Set to false to keep adding new data
		 * @return 
		 */
		public function set_data(array $data, $reset = TRUE)
		{
			// reset if asked
				if ($reset)
				{
					$this->reset();
				}

			// automatically set class variables if contained in $data

				// name and email if it exists
					if (isset($data['name']) && isset($data['email']))
					{
						$this->set_to(array($data['email'], $data['name']));
					}

				// to
					if (isset($data['to']))
					{
						$this->set_to($data['to']);
					}

				// from
					if (isset($data['from']))
					{
						$this->set_from($data['from']);
					}

				// subject
					if (isset($data['subject']))
					{
						$this->set_subject($data['subject']);
					}

			// replace placeholders
				$this->html			= $this->parse_variables($this->html, $data);
				$this->subject		= $this->parse_variables($this->title, $data);


			// return
				return $this;
		}

		/**
		 * Set the subject of the email
		 * 
		 * @param string $subject 	The subject of the email
		 * @return 
		 */
		public function set_subject($subject)
		{
			$this->subject = $subject;
			$this->html = preg_replace('%<title>[^<]+?</title>%', '<title>' .$subject. '</title>', $this->html);
			return $this;
		}

		/**
		 * Set the from address of the email
		 * 
		 * @param mixed $email 	The email address of the sender. Can be a string or name/address array		
		 * @return 
		 */
		public function set_from($email)
		{
			$this->from = $this->parse_email($email);
			return $this;
		}

		/**
		 * Set the to address of the email
		 * 
		 * @param mixed $email 	The email address of the sender. Can be a string or name/address array		
		 * @return 
		 */
		public function set_to($email)
		{
			$this->to = $this->parse_email($email);
			return $this;
		}
		
		/**
		 * Reset the email template so variables can be repopulated
		 * 
		 * @return 
		 */
		public function reset()
		{
			$this->html = $this->template;
			return $this;
		}
		
		
	// ------------------------------------------------------------------------------------------------
	// public methods: send email

		/**
		 * Send the mail to a recipient
		 * 
		 * @param mixed $to						The email address of the recipient. Can be a string or name/address array 
		 * @param string $subject [optional]	The subject of the email. If not supplied, uses the last subject set 
		 * @param mixed $from [optional]		The sender of the email. Can be a string or name/address array. If not supplied, uses the last sender set
		 * @return 
		 */
		public function send($to = NULL, $subject = NULL, $from = NULL)
		{
			
			// ----------------------------------------------------------------------------------------------------
			// set variables if supplied
			
				if ($to)
				{
					$this->set_to($to);
				}
				if ($subject)
				{
					$this->set_subject($subject);
				}
				if ($from)
				{
					$this->set_from($from);
				}
				
			// ----------------------------------------------------------------------------------------------------
			// run the mail sending code

				// check from and to are defined
					if ( ! $this->to || ! $this->from || ! $this->subject)
					{
						throw new Kohana_Exception('To, from, and subject fields need to be set');
					}
			
				// log
					$this->log('Sending mail...');
					$log_text = ' subject:"'.HTML::chars($this->subject).'", to:'.HTML::chars($this->email_string($this->to)).', from:'.HTML::chars($this->email_string($this->from));
				
				// set a temporary error handler to trap any connection errors
					set_error_handler(array('Template_Mailer', 'sendmail_error_handler'));
					
				// attempt to send the mail, and log any actions
					try
					{
						email::send($this->to, $this->from, $this->subject, $this->html, TRUE);
						$this->_success	= TRUE; 
						$this->_status	= 'Mail sent OK';
						$this->log('[SENT]' . $log_text);
					}
				// on failure, log the failure message, etc
					catch(Exception $e)
					{
						$this->_success	= FALSE;
						$this->_status	= $e->getMessage();
						$this->log("[ERROR] message:" .$this->_status);
						$this->log('[NOT SENT]' . $log_text);
					}
					
				// restore the Kohana error handler 
					restore_error_handler();

			// ----------------------------------------------------------------------------------------------------
			// return
				return $this;
		}
		
		/**
		 * Preview converted emails on screen 
		 * 
		 * @param object $to [optional]
		 * @param object $subject [optional]
		 * @param object $from [optional]
		 * @return 
		 */
		public function preview($echo = FALSE)
		{
			// grab body html
				$rx_body	= '%(<body\b.+?</body>)%sim';
				preg_match($rx_body, $this->html, $matches);
				
				if ($matches)
				{
					$body		= $matches[1];
					$body		= preg_replace('%^<body%', "\n<div", $body);
					$body		= preg_replace('%body>$%', "div>\n", $body);
				}
				else
				{
					$body = '';
				}
				
			// create visual representation of email
				$html = '<div style="margin:30px; border:1px solid #666; -moz-box-shadow: 0px 5px 15px #ccc; -webkit-box-shadow: 0px 5px 15px #ccc; box-shadow: 0px 5px 15px #ccc;">'."\n";
				$html .= '	<div style="padding:5px; background-color:#DDD; border-bottom:1px solid #666">'."\n";
				$html .= '		<p style="margin:5px; font:12px/15px Arial">Subject : '.$this->subject.'</p>'."\n";
				$html .= '		<p style="margin:5px; font:12px/15px Arial">To : '.HTML::chars(self::create_email($this->to)).'</p>'."\n";
				$html .= '		<p style="margin:5px; font:12px/15px Arial">From : '.HTML::chars(self::create_email($this->from)).'</p>'."\n";
				$html .= '	</div>'."\n";
				$html .= '	<div style="overflow:hidden;">'."\n";
				$html .= $body;
				$html .= '	</div>'."\n";
				$html .= '</div>'."\n";
				
			// log
				$this->_success		= TRUE;
				$this->_status		= 'Preview completed';
			
			// return
				if ($echo)
				{
					echo $html."\n";
					return $this;
				}
				else
				{
					return $html."\n";
				}

		}
		
		
		/**
		 * Creates an email address of the format 'Joe Bloggs<joe@bloggs.com>'
		 * 
		 * @param object $email		Either a string, or array in the format ('name', 'address'), or ('name' => 'address')
		 * @return 
		 */
		public static function create_email($email)
		{
			// vars
				$name 			= NULL;
				$address 		= NULL;
				$rx_address		= '/^([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})$/i';
				$rx_email		= '/^([\w -_]+)<([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})>$/i';
				
			// correctly get the name and address from input
			
				// string
					if (is_string($email))
					{
						$email = trim($email);
						if
						(
							preg_match($rx_email, $email) ||
							preg_match($rx_address, $email)
						)
						{
							$address = $email;
							return $address;
						}
					}
					
				// array
					else if (is_array($email))
					{
						// array('address', 'name')
							if (isset($email[0]))
							{
								$address = $email[0];
								$name = $email[1];
							}
						// array('name' => 'Name', 'address' => 'name@address.com')
							else if (isset($email['name']) && isset($email['address']))
							{
								$name = $email['name'];
								$address = $email['address'];
							}
						// array('address' => 'name')
							else
							{
								foreach ($email as $address => $mail)
								{
									break;
								}
							}
						// return
							return $name . '<' .$address. '>';
					}
				
			// no matches
				return 'INVALID EMAIL: ' . $email;
		}
		
	// ------------------------------------------------------------------------------------------------
	// protected methods: HTML generation

		protected function email_string($email)
		{
			return self::create_email($email);
		}

		/**
		 * Parses email address in 'Joe Bloggs<joe@bloggs.com>' format into array('joe@bloggs.com' => 'Joe Bloggs'),
		 * since SwiftMailer won't take such format
		 * @return mixed
		 */
		protected function parse_email($email)
		{
			$rx_email = '/^([\w -_]+)<([A-Z0-9._%+-]+@[A-Z0-9.-]+)>$/i';
			if (is_string($email) AND preg_match($rx_email, $email, $matches))
			{
				// Name<name@address.com> => array('name@address.com' => 'Name')
				return array($matches[2], $matches[1]);
			}
			return $email;
		}

		/**
		 * Parses the CSS from the template (both head CSS and external files) and rewrites the tags to use inline CSS 
		 * 
		 * @return 
		 */
		protected function parse_css()
		{
			// ----------------------------------------------------------------------------------------------------
			// variables
			
				// css
					$css				= '';
					$html 				= $this->template;
					
				// rxs
					$rx_head			= '%(<head\b.+?</head>)%sim';
					$rx_body			= '%(<body\b.+?</body>)%sim';
					$rx_link			= '%<link[^>]+/>%ims';
					$rx_style			= '%<style\b[^>]*>(.+)</style>%ims'; 
					$rx_empty_line		= '%^\t*[\r\n]+%im';
					
			// ----------------------------------------------------------------------------------------------------
			// grab css declarations
			
				// css from style tag
					preg_match($rx_style, $html, $matches);
					if ($matches)
					{
						$css				.= $matches[1];
						$html 				= preg_replace($rx_style, '', $html);
					}
				
				// css from links
					preg_match_all($rx_link, $html, $matches);
					if ($matches)
					{
						foreach ($matches[0] as $match)
						{
							$attributes = $this->get_attributes($match);
							if (isset($attributes['href']) && preg_match('%\.css$%', $attributes['href']))
							{
								// grab css file
									$css_file	= DOCROOT . $attributes['href'];

								// grab css
									if (file_exists($css_file))
									{
										$css	.= file_get_contents($css_file);
									}
								
								// remove link
									//$html 	= str_replace($match, '', $html);
									
							}
						}
					}
				
				// update all root-relative urls to site-relative urls 
					$html = preg_replace('%"(/.+?css)"%', '"' . trim(url::base(), '/') . '$1"', $html);
					
			
			// ----------------------------------------------------------------------------------------------------
			// css
					
				// convert any CSS declarations to inline styles
					if (isset($css))
					{
						
						// grab body html
							preg_match($rx_body, $html, $matches);
							$body 				= $matches[1];
							
						// add inline css
							$conversion_object	= new CSSToInlineStyles($body, $css);
							$body 				= $conversion_object->convert(TRUE);
							
						// clean up return html
							$body 				= str_replace('&#xD;', '', $body);
							
						// replace badly-converted <br/>s
							$body				= preg_replace('%<br\b.*?></?br>%i', '<br/>', $body);
							
						// replace original body with new body
							preg_match($rx_body, $body, $matches);
							$html				= preg_replace($rx_body, $matches[1], $html);
							
						// cleanup invalid <br>s which seem to get created during the conversion
							$html = str_replace('<br></br>', '<br/>', $html);
							
						// add CSS to head
							//$html 				= str_replace('</head>', '	<style type="css/text">' . $css . "	</style>\n</head>", $html);
						
					}
									
			// ----------------------------------------------------------------------------------------------------
			// template
			
				// cleanup head content
					preg_match($rx_head, $html, $matches);
					$head 				= $matches[1];
					$head				= preg_replace($rx_empty_line, '', $head);
					$html				= preg_replace($rx_head, $head, $html);
			
				// set template and html as instance-level html
					$this->template		= $html;
					$this->html			= $this->template;
		}
		
		/**
		 * Grabs email parameters title (subject) and <meta...> (from email) from the head of the template
		 * 
		 * Also cleans up the head html
		 * 
		 * @return 
		 */
		protected function parse_params()
		{
			// ----------------------------------------------------------------------------------------------------
			// variables
			
				// rxs
					$rx_title				= '%<title>([^>]+?)</title>%i';
					$rx_meta_tag			= '%<meta\b.+?/>%ims'; 
					
				// html
					$html 					= $this->template;
					
			// ----------------------------------------------------------------------------------------------------
			// html
			
				// title
					preg_match($rx_title, $html, $matches);
					if ($matches)
					{
						$this->title		= $matches[1];
					}
				
				// from email address
					preg_match_all($rx_meta_tag, $html, $matches);
					if ($matches)
					{
						foreach ($matches[0] as $match)
						{
							$attributes = $this->get_attributes($match);
							if (isset($attributes['name']) && $attributes['name'] == 'from')
							{
								$this->set_from($attributes['content']);
								break;
							}
						}
					}
					
		}



		/**
		 * Parses {{placeholder}} variables within the template with suppplied data
		 * 
		 * @param object $html		Source HTML
		 * @param object $data		Associative array of key => value pairs
		 * @return 
		 */
		protected function parse_variables($html, array $data)
		{
			if ($data != NULL)
			{
				foreach ($data as $key => $value)
				{
					$html = str_replace('{{' . $key . '}}', (string) $value, $html);
				}
			}
			return $html;
		}


	// ------------------------------------------------------------------------------------------------
	// protected methods: caching

		/**
		 * Internal function to return any cached template file, unless the original template file has been updated since 
		 * 
		 * @param object $template_path
		 * @return 
		 */
		protected function get_cache($template_path)
		{
			// log
				$this->log("Checking for cached file");
				
			// get the cached output
				$cache = Kohana::cache($this->_cache_prefix.$template_path);
				
			// if no cached object, return false
				if ( ! $cache)
				{
					$this->log("No cached file");
					return NULL;
				}
				
			// if there is cached output, compare to see if the cache is the latest version 
				else
				{
					// get the modified date of the template file
						$last_modified = $this->get_modified_time($template_path);
						
					// add in checks for CSS files?
						
					// compare modified dates
						if ($last_modified > $cache['last_modified'])
						{
							$this->log("Cached file out of date");
							return NULL;
						}
						else
						{
							$this->log("Loading cached data");
							return $cache['template'];
						}
				}
		}

		/**
		 * Internal function to cache the processed HTML for future use
		 * 
		 * @param object $template_path
		 * @return 
		 */
		protected function set_cache($template_path)
		{
			// log
				$this->log("Caching template");
				
			// cache
				$last_modified	= $this->get_modified_time($template_path);
				$data			= array('last_modified' => $last_modified, 'template' => $this->template);
				Kohana::cache($this->_cache_prefix.$template_path, $data);
		}


	// ------------------------------------------------------------------------------------------------
	// protected methods: utilities

		/**
		 * Utilty function to get the modified time of a file
		 * 
		 * @param object $template_path
		 * @return 
		 */
		protected function get_modified_time($template_path)
		{
			$file = Kohana::find_file('views', $template_path);
			if (file_exists($file))
			{
				return filemtime($file);
			}
			return 0;
		}

		/**
		 * Utility function to return tag attributes as an associative array
		 * 
		 * @param object $tag
		 * @return 
		 */
		protected function get_attributes($tag)
		{
			$rx_attribute = '%(\w+)="([^"]+)"%'; 
			preg_match_all($rx_attribute, $tag, $matches);
			if ($matches)
			{
				return array_combine($matches[1], $matches[2]);
			}
		}

		/**
		 * Internal method to log actions and times 
		 * 
		 * @param object $message
		 * @return 
		 */
		protected function log($message)
		{
			$microtime		= round(microtime(TRUE) - $this->_time, 4);
			if ($microtime == 0)
			{
				$microtime = '0.0';
			}
			$microtime		= explode('.', $microtime);
			$ms				= $microtime[1];
			$time			= date('G:i:s', $microtime[0]) . ':' . str_pad($ms, 4, '0', STR_PAD_LEFT);
			$this->_log[$time] = $message;
		}

		/**
		 * Internal error handler which allows Template_Mailer to throw an exception if Swift mail fails  
		 * 
		 * @param object $level
		 * @param object $message
		 * @param object $file
		 * @param object $line
		 * @return 
		 */
		public static function sendmail_error_handler($level, $message, $file, $line)
		{
			throw new Kohana_Exception('Email Send Error: :message', array(':message' => $message));
		}
	// ------------------------------------------------------------------------------------------------
	// magic methods


		/**
		 * Public magic getter function which returns all protected instance-level variables 
		 * 
		 * @param object $name
		 * @return 
		 */
		public function __get($name)
		{
			switch ($name)
			{
				case 'status':
				case 'success':
				case 'log':
					$name = '_' . $name;
				case 'template_path':
				case 'from':
				case 'to':
				case 'title':
				case 'subject':
				case 'template':
				case 'html':
					return $this->$name;
				break;
				
				default:
					throw new Kohana_Exception('No such public property :property in Template_Mailer', array(':property' => $name));
			}
		}

		/**
		 * Returns the HTML content of the populated email
		 * 
		 * @return 
		 */
		public function __tostring()
		{
			return $this->html;
		}

}