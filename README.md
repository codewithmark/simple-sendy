# Simple Sendy

With this simple class you can easily send transactional email via the [sendgrid](https://sendgrid.com/) api.

### How To Use It


	include  'class_simple_sendy.php';
 
	$sendy = new SimpleSendy("YourSendGridAPIKey");

Simple Email to your users
	 
	//note "to_emails" can be single or multiple arrays
	$d = $sendy->mailer(array(	 
		'from_name' =>'Code With Mark ' ,
		'from_email' =>'info@codewithmark.com' ,
		'to_emails' =>array('test1@gmail.com','test2@gmail.com','test2@gmail.com'), //<<<add your users emails here in an array
		'subject_line' =>'Test subject line...'.uniqid() ,	 
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

For more helpful tips, check out: [Code With Mark](http://codewithmark.com/)

    
    
    

