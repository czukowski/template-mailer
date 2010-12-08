<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Demo controller to show the functionality of the Template Mailer module
 *
 * Navigate to /mailer/demo in the URL and follow teh instructions...
 */
class Controller_Mailer_Demo extends Controller
{
	public function action_demo($action = NULL)
	{
		// grab the data
			$data = $_POST;

			if ($action == 'template')
			{
				$this->request->response = View::factory('mailer/email/example');
				return;
			}
		// show the demo page if no post
			elseif ($data == NULL)
			{
				echo View::factory('mailer/demo')->render();
			}
			
		// if the email form has been posted, do something with the data
			else
			{
				// get the template file
					$template = $data['template'];
					
				// check name and email are set
					$errors = array();
					if(trim($data['name'])  == '')
					{
						$data['errors']['name'] = 'Please enter a name';
					}
					
					if( ! preg_match('/^([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})$/i', $data['email']))
					{
						$data['errors']['email'] = 'Please enter a valid email';
					}
					
				// grab variables
					$name		= $data['name'];
					$email		= $data['email'];
					$from		= 'The Team<team@keyframesandcode.com>';
					$subject	= 'Welcome to the Site, ' . $data['name'];
									  
				// instantiate a template mailer instance, and load in the example email page
					$mailer = Template_Mailer::factory($template, TRUE);
					
				// populate the page with the data from the posted form
					$mailer->set_data($data);
						
				// if sending the acid test, add data manually
					if(strstr($template, 'acid') !== FALSE)
					{
						$mailer
							->set_to(array($email, $name))
							->set_from($from)
							->set_subject($subject)
							;
					}
					
				// take action!
					switch($action)
					{
						case 'view':
							echo View::factory($template)->render();
						break;
						
						case 'html':
							echo $mailer->html;
						break;
						
						case 'preview':
							echo View::factory('mailer/preview', array('mailer' => $mailer))->render();
						break;
						
						case 'send':
						
							// if errors, show form again
								if( ! empty($data['errors']) )
								{
									die(View::factory('mailer/demo', $data)->render());
								}
								
							// if not, send
								else
								{
									$mailer->send();
									echo View::factory('mailer/results', array('mailer' => $mailer))->render();
								}

						break;
						
						case 'send_native':
						
							// variables
								$html		= View::factory($template)->render();
								$html		= str_replace('{{name}}', $data['name'], $html);
								$html		= str_replace('{{message}}', $data['message'], $html);
							
							// send the mail
								$mailer->success = email::send($email, $from, $subject . ' (Unconverted)', $html, TRUE);
								
							// update vars if failed
								if( ! $mailer->success)
								{
									$mailer->status = 'The mail was not sent';
									$mailer->log = array();
								}
								
							// view
								echo View::factory('mailer/results', array('mailer' => $mailer))->render();


						break;
						
						default:
							echo 'No action specified';
					}
					
			}
	
	}
	
}
?>