<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['connect_to'] = "SANDBOX";

if ($config['connect_to'] == "LIVE")
{
	$config['merchant_id'] 		= '';
	$config['merchant_token'] 	= '';
	$config['app_identifier']	= '';
	$config['app_secret']		= '';
	
	$config['subscriptions_url'] 		= 'https://gocardless.com/connect/subscriptions/new';
	$config['pre_authorizations_url'] 	= 'https://gocardless.com/connect/pre_authorizations/new';
	$config['bills_url'] 				= 'https://gocardless.com/connect/bills/new';
	$config['api_url'] 					= 'https://gocardless.com/api/v1/';
}

if ($config['connect_to'] == "SANDBOX")
{
	$config['merchant_id'] 		= '';
	$config['merchant_token'] 	= '';
	$config['app_identifier']	= '';
	$config['app_secret']		= '';

	$config['subscriptions_url'] 		= 'https://sandbox.gocardless.com/connect/subscriptions/new';
	$config['pre_authorizations_url'] 	= 'https://sandbox.gocardless.com/connect/pre_authorizations/new';
	$config['bills_url'] 				= 'https://sandbox.gocardless.com/connect/bills/new';
	$config['api_url'] 					= 'https://sandbox.gocardless.com/api/v1/';
}