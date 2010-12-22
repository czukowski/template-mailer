<?php defined('SYSPATH') or die('No direct script access.');

if (Kohana::$environment !== Kohana::PRODUCTION)
{
	Route::set('mailer_demo', 'mailer/demo(/<mode>)')
		->defaults(array(
			'controller' => 'mailer_demo',
			'action'     => 'demo',
		));
}