<?php

require './libs/Slim/Slim.php';
require './libs/PHPMailer/PHPMailerAutoload.php';
require './libs/vendor/autoload.php';
// create a new cURL resource
$ch = curl_init();

// set URL and other appropriate options
curl_setopt($ch, CURLOPT_URL, "http://www.famdent.indiasupply.com/isdental/api/v1.1.3/campaign/planmeca");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);

// grab URL and pass it to the browser
$output = curl_exec($ch);  

// close cURL resource, and free up system resources
curl_close($ch); 
//exit;


        //PHPMailer Object
        $mail = new PHPMailer;
        //Enable SMTP debugging. 
        //$mail->SMTPDebug = 3;                               
        //Set PHPMailer to use SMTP.
        $mail->isSMTP();            
        //Set SMTP host name                          
        $mail->Host = "smtp.gmail.com";
        //Set this to true if SMTP host requires authentication to send email
        $mail->SMTPAuth = true;                          
        //Provide username and password     
        
        //    $mail->Username = "actipatient@gmail.com";                 
        //    $mail->Password = "actipatient1234";                           
            
        $mail->Username = "indiasupply55@gmail.com";                 
        $mail->Password = "indiasupply#2016";                           
        //If SMTP requires TLS encryption then set it
        $mail->SMTPSecure = "tls";                           
        //Set TCP port to connect to 
        $mail->Port = 587;                                   
            
        $mail->From = "noreply@indiasupply.com";
        $mail->FromName = "IndiaSupply";
            
        $mail->addAddress("karman.singh@actiknowbi.com");
            
        //$mail->isHTML(true);
        $mail->Subject = "Response";
        $mail->Body = $output;
        
        //$mail->AltBody = "This is the plain text version of the email content";
        if(!$mail->send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
            
        } else {
            echo  "Mail Sended";
        }



?>