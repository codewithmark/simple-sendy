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
	public function __construct($api_key="your_api_key",$base_url = 'https://api.sendgrid.com/v3/mail/')
	{ 
		$this->APIKey = $api_key;	 
		$this->BaseURL = $base_url;
	}
	//--->Get api key -- End

	
	
	//--->Send data - Start
	function Send($url = '', $transmission_data = array() )
	{		
		$data_string = json_encode($transmission_data); 		
		 
		$call_url = $this->BaseURL.$url;
		$ch = curl_init($call_url);
		
		//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $call_method);                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
				'Content-Type: application/json',                                                                                
				'Content-Length: ' . strlen($data_string),
				'Authorization: Bearer '. $this->APIKey                                                                   
				)
		);                                                                                                                   


		$result = curl_exec($ch);

		 
		//For error checking
		if(curl_error($ch))
		{
			$d = array(
				'curl_error'=>'curl_error',
				'msg'=>  curl_error($ch),
				 
			);

			return json_encode($d) ;
			//echo 'error:' . curl_error($ch);
		}
		else 
		{
			//Success messages
			//var_dump($result); 
			return $result ;
		}
		
	}
	//--->Send data - End

	
	//--->Transmissions - Start

	function Mailer($from_email ='',$to_email ='',$subject_line = '', $email_body ='')
	{

		//For transactional email 

		$email_content = '{"personalizations": [{"to": [{"email": "'.$to_email.'"}]}],"from": {"email": "'.$from_email.'"},"subject": "'.$subject_line.'","content": [{"type": "text/html", "value": "'.$email_body.'"}]}';

		
		$res = $this->Send( 'send', json_decode($email_content) );		
	 

		$json = json_decode($res);
  		
  	
		
		if(isset($json->curl_error)  )
		{
			//curl_error
			$d = array(
				'status'=>'error',
				'msg'=> $json->msg,
				'code'=> "7001",
			);

			return $d ;
		}
		else if( !isset( $json) )
		{
			$d = array(
				'status'=>'success',				 
				'message'=> $json,
			);

			return  $d;
		}
		else if($json->errors)
		{
			$d = array(
				'status'=>'error',				 
				'message'=> $json->errors[0]->message,
				'help'=> $json->errors[0]->help,
				'field'=> $json->errors[0]->field,
				'sendy' =>$json
			);

			return  $d;

		}
		else 
		{
			return  $json;
		}
		
	}		
	//--->Transmissions - End

 	
 
}

?>
