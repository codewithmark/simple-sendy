# Simple Sendy

With this simple class you can easily send transactional email via the [sendgrid](https://sendgrid.com/) api.

### How To Use It


	include  'class_simple_sendy.php';
 
	$sendy = new SimpleSendy("YourSendGridAPIKey");

Send simple transactional emails to your user(s)

	//note "to_emails" can be single or multiple arrays
	$d = $sendy->email(array(	 
		'from_name' =>'Code With Mark ' ,
		'from_email' =>'info@codewithmark.com' ,
		'to_emails' =>array('test1@gmail.com', ), //<<<add your user emails here in an array
		'subject_line' =>'Test subject line...',	 
		'email_body' => '<p>Testing 123.. .</p>
		<h2>Your code > {user_code}</h2>'
	));


	if($d['status'] == 'success') 
	{
		echo "email sent";
	}
	elseif ($d['status'] == 'error') 
	{  
		echo "fail to sent email";
		echo "<br>";

		var_dump($d);
	}

Send emails with "Substitution Tags".
This will come in handy if you are sending same email to multiple users but only changing a few things in the body of the email

	$d = $sendy->email(array(	 
		'from_name' =>'Code With Mark ' ,
		'from_email' =>'info@apimk.com' ,
		'to_emails' =>array('test1@gmail.com','test2@gmail.com' ),
		'replyto_email'=>'info@gmail.com',
		'subject_line' =>'Test subject line...',
		'sub_tags'=>array('{user_name}' =>array('code with mark','Mike Cohen' ),'{user_code}'=> array('codewithmark-'.uniqid(),'mike-'.uniqid()) ),
		'email_body' => '<h1>Hi{user_name}, </h1>
		<p>Below is your personalize code</p>
		<h3>Your code > {user_code}</h3>'
	));


	if($d['status'] == 'success') 
	{
	    echo "email sent";
	}
	elseif ($d['status'] == 'error') 
	{  
	    echo "fail to sent email";
	    echo "<br>";

	    var_dump($d);

	}

Send emails with more options.

	$d = $sendy->email(array(	 
		'from_name' =>'Code With Mark ' ,
		'from_email' =>'info@apimk.com' ,
		'to_emails' =>array('test1@gmail.com','test2@gmail.com' ),
		'replyto_email'=>'info@gmail.com',
		'subject_line' =>'Test subject line...',
		'sub_tags'=>array('{user_name}' =>array('code with mark','Mike Cohen' ),'{user_code}'=> array('codewithmark-'.uniqid(),'mike-'.uniqid()) ),
		'email_body' => '<h1>Hi{user_name}, </h1>
		<p>Below is your personalize code</p>
		<h3>Your code > {user_code}</h3>',
		'future_timestamp' =>'2019-05-11 07:30:18', //your email will be sent at this date and time
		'category' =>'test_api_call', //will help you identify easily in sendgrid dashboard
		'batch_id' =>'test_'.uniqid(), //you can set this if you are using webhook to have stats send to your server 	
	));


	if($d['status'] == 'success') 
	{
	    echo "email sent";
	}
	elseif ($d['status'] == 'error') 
	{  
	    echo "fail to sent email";
	    echo "<br>";

	    var_dump($d);

	}

For more helpful tips, check out: [Code With Mark](http://codewithmark.com/)

    
    
    

