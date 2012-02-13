<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
	
class GoCardless {
	protected $_ci;
	
	protected $merchant_id		= null;
	protected $app_identifier 	= null;
	protected $app_secret 		= null;
	protected $merchant_token 	= null;
	
	protected $resource 		= null;
	protected $resource_type 	= null;
	protected $state		 	= null;
	protected $url 				= null;
	protected $billing_address 	= null;
	protected $redirect_uri 	= null;
	protected $cancel_uri 		= null;
	
	function __construct($params = array())
	{
		$this->_ci =& get_instance();

		$this->merchant_id 		= $params['merchant_id'];
		$this->app_identifier 	= $params['app_identifier'];
		$this->app_secret 		= $params['app_secret'];
		$this->merchant_token 	= $params['merchant_token'];

		log_message('debug', 'GoCardless Class Initialised');
	}

	public function create_resource($params)
	{
		$this->resource_type = $params['type'];
		
		$this->resource = array('client_id'		=> $this->app_identifier,
						   		'nonce'			=> base64_encode(mt_rand() * mt_rand()),
						   		'timestamp'		=> date('Y-m-d\TH:i:s\Z'),
						   		'state'			=> @$this->state,
						   		'redirect_uri'	=> @$this->redirect_uri,
						   		'cancel_uri'	=> @$this->cancel_uri);

		$this->resource[$this->resource_type] = array('merchant_id'		=> $this->merchant_id,
													'interval_length'	=> @$params['interval_length'],
													'description'		=> @$params['description'],
													'interval_unit'		=> @$params['interval_unit']);
													
		if(isset($this->billing_address))
		{
			$this->resource[$this->resource_type]['user'] = $this->billing_address;
		}
	
		switch ($this->resource_type) {
			case 'subscription':
				$this->resource['subscription']['amount'] = $params['amount'];
				$this->url = 'https://sandbox.gocardless.com/connect/subscriptions/new';
				break;	
							
			case 'pre_authorization':
				$this->resource['pre_authorization']['max_amount'] = $params['amount'];
				$this->url = 'https://sandbox.gocardless.com/connect/pre_authorizations/new';
				break;	
							
			case 'bill':
				$this->resource['bill']['amount'] = $params['amount'];
				$this->url = 'https://sandbox.gocardless.com/connect/bills/new';
				
				// bills dont need intervals
				unset($this->resource['bill']['interval_length']);
				unset($this->resource['bill']['interval_unit']);
				break;			
		}

		$this->resource['signature'] = $this->signature($this->resource, $this->app_secret);

		return true;
	}
	
	public function set_billing_address($address)
	{
		$this->billing_address = $address;
	}
	
	public function set_state($state)
	{
		$this->state = $state;
	}
		
	public function set_redirects($params)
	{
		if(isset($params['redirect_uri']))
		{
			$this->redirect_uri = $params['redirect_uri'];
		}
		if(isset($params['cancel_uri']))
		{
			$this->cancel_uri = $params['cancel_uri'];
		}
	}
	
	// generate hidden form fields for use when POSTing to GoCardless
	public function output($output_type = null)
	{
		switch ($output_type) {
			case 'form':
				$output = $this->build_form_fields();
				break;
				
			case 'iframe':
				$output = $this->build_iframe();
				break;
				
			default:
				return false;
		}
		
		return $output;
	}
	
	public function check_receipt($receipt)
	{
		// setup restclient for confirming receipts
		$this->_ci->load->spark('restclient/2.0.0');
		$this->_ci->load->library('rest');
		$this->_ci->rest->initialize(array('server' => 'https://sandbox.gocardless.com/api/v1/',
											'http_user' => $this->app_identifier,
											'http_pass' => $this->app_secret,
											'http_auth' => 'basic'
											));
	
		$their_sig 	= $receipt['signature'];
		
		// their sig shouldn't be signed with other data
		unset($receipt['signature']);
		
		$our_sig = $this->signature($receipt, $this->app_secret);

		if($our_sig == $their_sig)
		{
			// confirm receipt
			$this->_ci->rest->post('confirm', array('resource_id' => $receipt['resource_id'],
											'resource_type' => $receipt['resource_type']));
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// build hidden inputs for use when POSTing to GoCardless
	protected function build_form_fields()
	{
		$this->_ci->load->helper('form');
	
		$form_data['url'] = $this->url;
		
		$form_data['fields']  = form_hidden("client_id", $this->resource['client_id']);
		$form_data['fields'] .= form_hidden("nonce", $this->resource['nonce']);
		$form_data['fields'] .= form_hidden("timestamp", $this->resource['timestamp']);
		$form_data['fields'] .= form_hidden("signature", $this->resource['signature']);
		
		foreach($this->resource[$this->resource_type] as $key => $value)
		{
			$form_data['fields'] .= form_hidden("{$this->resource_type}[{$key}]", $value);
		}
		
		return $form_data;
	}

	protected function build_iframe()
	{
		return '<iframe id="gocardless-form" src="' . $this->url . '?' . $this->to_query($this->resource) . '"></iframe>';
	}
	
	// Thanks to GoCardless docs for the following two functions
	protected function to_query($obj, &$pairs = array(), $ns = null) {
	    if (is_array($obj) || is_object($obj)) {
	        foreach ((array)$obj as $k => $v) {
	            if (is_int($k)) {
	                $this->to_query($v, $pairs, $ns . "[]");
	            } else {
	                $this->to_query($v, $pairs, $ns !== null ? $ns . "[$k]" : $k);
	            }
	        }
	        if ($ns !== null) return $pairs;
	        if (empty($pairs)) return "";
	        sort($pairs);
	        $strs = array_map("implode", array_fill(0, count($pairs), "="), $pairs);
	        return implode("&", $strs);
	    } else {
	        $pairs[] = array(rawurlencode($ns), rawurlencode($obj));
	    }
	}
	
	protected function signature($data, $secret) {
	    return hash_hmac("sha256", $this->to_query($data), $secret);
	}
}