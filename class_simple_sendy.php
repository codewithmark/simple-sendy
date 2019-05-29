<?php

// is cURL installed yet?
if (!function_exists('curl_init'))
{	
	die('Sorry cURL is not installed!');
}

//Default country/state location
date_default_timezone_set('America/New_York');

class SimpleSendy
{	
	public $APIKey; 
	public $BaseURL;

	//--->Get api key - Start 
	public function __construct($api_key="your_api_key",$base_url = 'https://api.sendgrid.com/api/')
	//https://api.sendgrid.com/v3/mail/send
	{ 
		$this->APIKey = $api_key;	 
		$this->BaseURL = $base_url;
	}
	//--->Get api key -- End

	
	//--->Transmissions - Start

	function email($call_arr = array(
		'from_name' =>null,	
		'from_email' => null,
		'to_emails' =>array(), 
		'subject_line' =>null,
		'email_body' =>null,
		'future_timestamp' =>null,
		'category' =>null,
		'batch_id' =>null,
		'sub_tags' =>array()) )
	{ 
		$from_name 		= isset($call_arr['from_name']) ? $call_arr['from_name'] : 'GET' ;
	    $from_email 	= isset($call_arr['from_email']) ? $call_arr['from_email'] : '' ;
	    $to_emails 		= isset($call_arr['to_emails']) ? $call_arr['to_emails'] : '' ;
	    $subject_line 	= isset($call_arr['subject_line']) ? $call_arr['subject_line'] : '' ;

	    $email_body 	= isset($call_arr['email_body']) ? $call_arr['email_body'] : '' ;

	    $future_timestamp 	= isset($call_arr['future_timestamp']) ? strtotime($call_arr['future_timestamp']) : date('U') ;

	    //if sub_tags are not set, then a test one will be set to work correctly 
	    $sub_tags =  isset($call_arr['sub_tags']) ? $call_arr['sub_tags'] : array('{'.$this->AutoHash().'}'=>array('test')) ;


	    

		//For transactional email 

		//More info check out: https://sendgrid.com/docs/API_Reference/Web_API/mail.html
		$batch_id = isset($call_arr['batch_id']) ? $call_arr['batch_id'] : $this->AutoHash();
		
		$category 	= isset($call_arr['category']) ? $call_arr['category'] : 'auto_cat_id_'.$batch_id ;

		$timestamp = date('Y-m-d H:i:s',$future_timestamp);

		$json_string = array(
			'to' => $to_emails,		  
		  	'category' => $category,
		  	'send_at'	=> $future_timestamp,
		  	'sub' => $sub_tags,

			'unique_args' => array(
				'batch_id'	=> $batch_id,	
				'email_dispatch_dttm'=>	$future_timestamp,
				'timestamp'=>$timestamp,
			),
			/*
			//For constant email footer
			'filters' => array(
				'footer' => array(
					'settings' => array(
						'enable'	=> $batch_id,
						'text/html' =>"<p>Thanks,<br />CodeWithMark.com<p>", 
						'text/plain' =>"<p>Thanks,<br />CodeWithMark.com<p>",
					),
				),
			),
			*/
		);

		$params = array(
			
			'x-smtpapi' => json_encode($json_string),
			'fromname' 	=> $from_name,
			'from'      => $from_email,			
			'to'        => $from_email, //--> It won't send the email to it... but you still need it for multiple recipients send...
			//'replyto'	=> $replyto_email,
			'subject'   => $subject_line,
			'html'      => $email_body,
			//'text'      => $email_body,
			
		);

		$res = $this->Send( 'mail.send.json', $params ); 
 
		$json = ($res);

	 
		if( isset($res['data']['errors']) && $res['http_code'] =400 )
		{
			$d = array(
				'status'=>'error-099',				 
				'msg'=> $res['data']["errors"][0],
			);

			return  $d;
		}
		else if($res['http_code'] =200)
		{
			$d = array(
				'status'=>'success',
				'id' =>$batch_id,				  				
			);
			return  $d;
		}
		else 
		{
			//send raw response
			return  $json;
		}
	}		
	//--->Transmissions - End


	//--->Send data - Start
	function Send($url = '', $transmission_data = array() )
	{	
		$data_string = $transmission_data; 	
		$request = $this->BaseURL.$url;

		// Generate curl request
		$session = curl_init($request);
		// Tell curl to use HTTP POST
		curl_setopt ($session, CURLOPT_POST, true);
		// Tell curl that this is the body of the POST
		curl_setopt ($session, CURLOPT_POSTFIELDS, $data_string);

		curl_setopt($session, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->APIKey)); 
	 
		//Turn off SSL
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);//New line
		curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);//New line

		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		// obtain response
		$result = curl_exec($session);

		$http_code = curl_getinfo($session, CURLINFO_HTTP_CODE);
		
		curl_close($session);
		
	 

		//return $result ;
		return array('http_code'=> $http_code, 'data' =>  json_decode($result,true));
		
	}
	//--->Send data - End


	//--->Local functions - Start
	function AutoHash($has_name = 'crc32') 
	{ 
	    
	    $alphanum = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	    $special  = '~!@#$%^&*(){}[],./?';
	    $alphabet = $alphanum . $special;

	    //This WILL make sure the auto code is random!!!
	    $String = $alphabet.mt_rand().microtime().uniqid();
	    
	    //if $_hash_name is "crc32", it wil return 8 characters long auto code        
	    return hash($has_name,$String);       
	}
 
	function ConvertTimeStamp($user_datetime,$format_type='')
	{
	    $date = new DateTime($user_datetime);
	    //$future_timestamp =$date->format('c');

	    $time_stamp_iso = $date->format('c');
	    $time_stamp_unix = $date->format('U');

	    $time_stamp = $date->format('Y-m-d H:i:s');
	    $date = $date->format('Y-m-d');

	    if($format_type == "iso")
	    {
	        return $time_stamp_iso;
	    }
	    elseif($format_type == "unix")
	    {
	        return $time_stamp_unix;
	    }
	    elseif($format_type == "dttm")
	    {
	        return $time_stamp;
	    }
	    elseif($format_type == "dt")
	    {
	        return $date;
	    }
	    else
	    {
	        return $time_stamp;
	    }
	}

	 
	function GetTimeStamp($format_type='')
	{
	    $time_stamp_iso = date('c');
	    $time_stamp_unix = date('U');

	    $time_stamp = date('Y-m-d H:i:s');
	    $date = date('Y-m-d');

	    if($format_type == "iso")
	    {
	        return $time_stamp_iso;
	    }
	    elseif($format_type == "unix")
	    {
	        return $time_stamp_unix;
	    }
	    elseif($format_type == "dttm")
	    {
	        return $time_stamp;
	    }
	    elseif($format_type == "dt")
	    {
	        return $date;
	    }
	    else
	    {
	        return $time_stamp;
	    }
	}
	//--->Local functions - End
}
?> 
