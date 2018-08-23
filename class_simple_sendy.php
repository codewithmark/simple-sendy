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
	{ 
		$this->APIKey = $api_key;	 
		$this->BaseURL = $base_url;
	}
	//--->Get api key -- End

	
	
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


		// Tell PHP not to use SSLv3 (instead opting for TLS)
		//curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

		//Turn off SSL
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);//New line
		curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);//New line

		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		// obtain response
		$result = curl_exec($session);

		curl_close($session);

		return $result ;
		
	}
	//--->Send data - End

	
	//--->Transmissions - Start

	function Mailer($a1 = array(
		'from_name' =>'' ,
		'from_email' =>'' ,
		'to_emails' =>'' ,
		'subject_line' =>'' ,
		'email_body' =>array(),
		'get_category' =>null ,
		'get_future_timestamp' =>null,
		'sub' => array() 
	))
	{
		/*
			$sendy = new SimpleSendy($sendgrid_api_key);
			$d = $sendy->mailer(array(
				'from_name' =>'Code With Mark ' ,
				'from_email' =>'codewithmark@gmail.com' ,
				'to_emails' =>array('test1@gmail.com','test2@gmail.com'),
				'subject_line' =>'Test subject line...' ,
				'email_body' => 'Testing 1232'
			));
		*/

		//variables
		$from_name 				= $a1['from_name'];
		$from_email 			= $a1['from_email'];
		$to_emails 				= $a1['to_emails'];		
		$subject_line 			= $a1['subject_line'];
		$email_body 			= $a1['email_body']; 
		$get_future_timestamp 	= $a1['get_future_timestamp'];
		$get_category 			= $a1['get_category'];
		$substitution_tags 		= $a1['sub_tags'];


		//For transactional email 

		//More info check out: https://sendgrid.com/docs/API_Reference/Web_API/mail.html

		$future_timestamp = isset($get_future_timestamp)? strtotime($get_future_timestamp ) : date('U'); 

		//You can use this to track user email devlivery/open/click events via webhook
		$batch_id = $this->AutoHash();
		
	  
		$category = isset($get_category)? $get_category : 'auto_cat_id_'.$batch_id;

		$timestamp = date('Y-m-d H:i:s',$future_timestamp);
	
		if(isset($substitution_tags))
		{
			$json_string = array(
				'to' => $to_emails,		  
			  	'category' => $category,
			  	'sub' => $substitution_tags,
			  	'send_at'	=> $future_timestamp,
				'unique_args' => array(
					'batch_id'	=> $batch_id,					
				),				 
			);

		}
		else
		{
			$json_string = array(
				'to' => $to_emails,		  
			  	'category' => $category,
			  	'send_at'	=> $future_timestamp,
				'unique_args' => array(
					'batch_id'	=> $batch_id,					
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
		}


		$params = array(			
			'x-smtpapi' => json_encode($json_string),
			'fromname' 	=> $from_name,
			'from'      => $from_email,
			
			//'toname'	=> $to_name,
			'to'        => $from_email, //--> It won't send the email to it... but you still need it for multiple recipients send...
			//'replyto'	=> $replyto_email,
			'subject'   => $subject_line,
			'html'      => $email_body,
			'text'      => $email_body,
			
		);

		$res = $this->Send( 'mail.send.json', $params ); 
		$json = json_decode($res);

	 
		if( isset($json->error) )
		{
			$d = array(
				'status'=>'error',				 
				'message'=> $json,
			);

			return  $d;
		}
		else if($json->message == "success")
		{
			$d = array(
				'status'=>'success',
				'id' =>$batch_id,				  
				'message' =>$json
			);
			return  $d;
		}
		else 
		{
			return  $json;
		}
	}		
	//--->Transmissions - End


	//--->Local functions - Start
	function AutoHash($has_name = 'crc32') 
	{
	    /*
	        Use this to create auto/random code

	        More hash options - http://codewithmark.com/best-option-for-hashing-your-passwords-in-php
	    */
	    
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