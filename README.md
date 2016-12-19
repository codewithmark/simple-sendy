# Simple Sendy

With this simple class you can easily send transactional email via the [sendgrid](https://sendgrid.com/) api.

### How To Use It

	
    
    //Your email variables
    $from_email = 'info@yoursite.com';
	$to_email  = 'useremail@gmail.com';
	$subject_line = "My First sendgrid email";
    $email_body = ' This is a test email sent via sendgrid api';
    
    $sendy = new SimpleSendy("YourSendGridAPIKey");
    $d = $sendy->Mailer($from_email ,$to_email  ,$subject_line  , $email_body  );
    
    
    if($d['status'] == 'success') 
    {
        echo "email sent";
    }
    elseif ($d['status'] == 'error') 
    {
        # code...
        echo "fail to sent email";
        echo "<br>";

        var_dump($d);

    }


    
    
    

