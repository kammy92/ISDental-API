<?php

ini_set("display_errors", "1");
error_reporting(E_ALL);


require_once '../include/Security.php';
require_once '../include/mail_content.php';
require_once '../include/DbHandler.php';
require_once '../include/sms.php';
require '.././libs/Slim/Slim.php';
require '.././libs/PHPMailer/PHPMailerAutoload.php';
require '.././libs/vendor/autoload.php';
  
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
    

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$user_id = NULL;
$sms_gateway = 2;//1=> bulksmsgateway, 2=> valuefirst
$mail_gateway = 2; //1=> gmail, 2=> valuefirst

function authenticate(\Slim\Route $route) {
    $response = array();
    $app = \Slim\Slim::getInstance();

    $headers = getHeaders();
  
//    $headers = array();
//    $headers["api-key"] = "9e3d710529e11ab2be4e39402ae544ce";
//    $headers["visitor-login-key"] = "5a4341e71c8127f9550d61c8da4809cc";

    $db = new DbHandler();
    if (array_key_exists("api-key",$headers)) {
        $api_key = $headers['api-key'];
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid API key";
            echoResponse(401, $response);
            $app->stop();
        } else {
            if (array_key_exists("user-login-key", $headers)) {
                $login_key = $headers['user-login-key'];
                if (!$db->isValidUserLoginKey($login_key)) {
                    $response["error"] = true;
                    $response["message"] = "Access Denied. Invalid Login key";
                    echoResponse(401, $response);
                    $app->stop();
                } else {
                    global $user_id;
                    $user_id = $db->getUserId($login_key);
                }
            }
        }
    } else {
        $response["error"] = true;
        $response["message"] = "Access Denied. API key is not present";
        echoResponse(400, $response);
        $app->stop();
    }
}

$app->get('/test', function () {
	        $response = array();
	        $db = new DbHandler();
	        $response["message"] = "For testing purpose";

          $response["sms"]= array();

$mclass = new sendSms();
$response["sms"]=$mclass->sendSmsToUser("121212 is your login OTP for ISDental application", "9873684678", "");


	
	   //     echo microtime(true);
	   //   	$mt = explode('.', microtime(true));
	   //   	echo "\n".substr($mt[0],2, 8);
	   //   	echo "\n".substr($mt[1],0,2);
		  //  echo "\n".$str =substr($mt[0],2, 8).substr($mt[1],0,2);
	        
//	        $response["email"] = $email = sendForgetAccessPINEmail("karman.singh@actiknowbi.com", "karman", 1234);
//	        $response["patient_id"] =  $patient_id = $db->getPatientInternalID(1, 'MH111112');
//	        $response["cwd"] = getcwd();
//	        $response["server_url"] = $_SERVER['HTTP_HOST'];

//$mobile = "9873684678";
//$user_type = "DENTIST";
//$email = "karman.singh@actiknowbi.com";
//$name = "Karman";

    //sendWelcomeSMS($mobile);
//    if(strtoupper($user_type)=="DENTIST"){
//        sendWelcomeEmail($email,"Dr. ".$name);
//    } else {
//        sendWelcomeEmail($email,$name);
//    }


	        echoResponse(200, $response);
        });

$app->get('/test/keys', 'authenticate', function () {
            global $user_id;
            $response = array();
	        $response["error"] = false;
	        $response["visitor_id"] = $user_id;
	        $response["message"] = "Key validation passed";
	        echoResponse(200, $response);
        });

$app->get('/test/echo/:message', function ($message) {
	        $response = array();
	        $response["message"] = $message;
	        echoResponse(200, $response);
        });

$app->get('/test/server_configuration', function () {
            $hasMySQL = false; 
            $hasMySQLi = false; 
            $withMySQLnd = false; 
            $sentence = '';
            if (function_exists('mysql_connect')) {
                $hasMySQL = true;
                $sentence.= "(Deprecated) MySQL is <b>installed</b> ";
            } else{
                $sentence.= "(Deprecated) MySQL is <b>not installed</b> ";
            }
            if (function_exists('mysqli_connect')) {
                $hasMySQLi = true;
                $sentence.= "and the new (improved) MySQL is <b>installed</b>. "; 
            } else{
                $sentence.= "and the new (improved) MySQL is <b>not installed</b>. ";
            }
            if (function_exists('mysqli_get_client_stats')) {
                $withMySQLnd = true;
                $sentence.= "This server is using <b>MySQLnd</b> as the driver."; 
            } else{
                $sentence.= "This server is using <b>libmysqlclient</b> as the driver.";
            }
            echo $sentence;
        });

$app->get('/test/db_connection/:mobile', function ($mobile) {
            $db = new DbHandler();
            $otp = NULL;
            $result = $db->generateOTP($mobile);
            $otp = $db->getOTP($mobile);

            if ($otp != NULL) {
                $response["error"] = false;
                $response["message"] = "OTP generated succesfully but unable to send";
                $response["otp"] = $otp;
                echoResponse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to generate OTP. Please try again";
                echoResponse(200, $response);
            }
        });

$app->get('/test/encryption/:message', function ($message) {
	        $response = array();
	        $response["message_original"] = $message;
	        $response["message_encrypted"] = Security::encrypt($message);
	        $response["message_decrypted"] = Security::decrypt(Security::encrypt($message));
	        echoResponse(200, $response);
        });

$app->get('/test/encrypt/:message', function ($message) {
	        $response = array();
	        $response["message_original"] = $message;
	        $response["message_encrypted"] = Security::encrypt($message);
	        echoResponse(200, $response);
        });

$app->get('/test/decrypt/:message', function ($message) {
	        $response = array();
	        $response["message_original"] = $message;
	        $response["message_decrepted"] = Security::decrypt($message);
	        echoResponse(200, $response);
        });

$app->get('/test/phpinfo', function () {
            phpinfo();
        });

$app->get('/user/registered/:event_name', function ($event_name) {
	        $response = array();
	        $db = new DbHandler();

            $result = $db->totalUserRegisteredToEvent(strtoupper($event_name));
            if($result){
                $response["error"] = false;
    	        $response["message"] = "Data fetched successfully";
    	        $response["total_registrations"] = $result;
            } else {
                $response["error"] = false;
    	        $response["message"] = "Error occurred";
            }
	        echoResponse(200, $response);
        });



$app->post('/user/otp', 'authenticate', function() use ($app) {
            verifyRequiredParams(array('mobile'));
            $response = array();
            $mobile = $app->request->post('mobile');
            global $user_id;
            $db = new DbHandler();
            $otp = NULL;
            $db->generateOTP($mobile);
            $otp = $db->getOTP($mobile);

            if ($otp != NULL) {
                $response["error"] = false;
                $message = sendOTPSMS($mobile, $otp);
                if ($message) {
                    $response["message"] = "OTP generated and sent successfully";
                } else {
                    $response["message"] = "OTP generated succesfully but unable to send";
                }
                $response["otp"] = $otp;
                echoResponse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to generate OTP. Please try again";
                echoResponse(200, $response);
            }
        });
        
$app->post('/user/register', 'authenticate', function() use ($app) {
// 0=>OTP not match, 1=> OTP match and user inserted, 2=> OTP match and user exist, 3=> OTP match but error or occured
            verifyRequiredParams(array('name', 'mobile', 'email', 'user_type', 'otp'));
            $response = array();
            $name = $app->request->post('name');
            $mobile = $app->request->post('mobile');
            $email = $app->request->post('email');
            $user_type = $app->request->post('user_type');
            $otp = $app->request->post('otp');

            if($app->request->post('firebase_id')){
                $firebase_id = $app->request->post('firebase_id');
            } else {
                $firebase_id = "";
            }
            
            if($app->request->post('device_details')){
                $device_details = $app->request->post('device_details');
            }else {
                $device_details = "";
            }


            $db = new DbHandler();
            
            if (!$db->checkOTP($mobile, $otp)) {
                $response["error"] = true;
                $response["message"] = "Failed to match OTP or OTP expired. Please try again";
                $response["status"] = 0;
                echoResponse(200, $response);
            } else {
                if($db->userExist($mobile, $name, $email, $user_type, $firebase_id, $device_details)) {
                    $result = $db->getUserDetails($mobile);
                    $response["error"] = false;
                    $response["message"] = "OTP match and user exist";
                    $response["status"] = 2;
                    while ($user = $result->fetch_assoc()) {
                        $response["user_id"] = $user["usr_isdental_id"];
                        $response["user_name"] = $user["usr_name"];
                        $response["user_mobile"] = $user["usr_mobile"];
                        $response["user_email"] = $user["usr_email"];
                        $response["user_type"] = $user["usr_type"];
                        $response["user_login_key"] = $user["usr_login_key"];
                    }
                    echoResponse(200, $response);
                } else{
                    if($db->insertUser($name, $mobile, $email, $user_type, $firebase_id, $device_details)) {
                        $result2 = $db->getUserDetails($mobile);
                        $response["error"] = false;
                        $response["message"] = "OTP matched and user inserted successfully";
                        $response["status"] = 1;
                        while ($user = $result2->fetch_assoc()) {
                            $response["user_id"] = $user["usr_isdental_id"];
                            $response["user_name"] = $user["usr_name"];
                            $response["user_mobile"] = $user["usr_mobile"];
                            $response["user_email"] = $user["usr_email"];
                            $response["user_type"] = $user["usr_type"];
                            $response["user_login_key"] = $user["usr_login_key"];
                        }
                        sendWelcomeSMS($mobile);
                        if(strtoupper($user_type)=="DENTIST"){
                            sendWelcomeEmail($email,"Dr. ".$name);
                        } else {
                            sendWelcomeEmail($email,$name);
                        }
                        
                        echoResponse(200, $response);
                    } else {
                        $response["error"] = true;
                        $response["message"] = "Failed to insert user. Please try again";
                        echoResponse(200, $response);
                    }
                }
            }
    });

$app->post('/user/register/event', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('event_id'));
            $response = array();
            $event_id = strtoupper($app->request->post('event_id'));

            $db = new DbHandler();
            
            if (!$db->registerUserToSpecialEvent($user_id, $event_id)) {
                $response["error"] = true;
                $response["message"] = "Failed to register user to event";
                echoResponse(200, $response);
            } else {
                $response["error"] = false;
                $result = $db->getUserNameAndMobileByID($user_id);
                $event_name = $db->getEventNameByID($event_id);
                
                $message = sendEventRegisterationSMS($result["user_name"], $result["user_mobile"], $event_name);
                if ($message) {
                    $response["message"] = "User registered and SMS sent successfully";
                } else {
                    $response["message"] = "User registered but SMS not sent";
                }
                echoResponse(200, $response);
            }
    });


$app->get('/user/register/event/:event_id', 'authenticate', function ($event_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            if($db->isUserRegisteredToSpecialEvent($user_id, $event_id)){
                $response["error"] = false;
                $response["message"] = "User already registered to event";
                $response["registered"] = true;
                echoResponse(200, $response);
            } else {
                $response["error"] = false;
                $response["message"] = "User not registered to event";
                $response["registered"] = false;
                echoResponse(200, $response);
            }
        });


$app->get('/category', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllCategories();
            if($result){
                $response["error"] = false;
                $response["message"] = "Categories fetched succesfully";
                $response["categories"] = array();
                while ($company = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['category_id'] = $company["ctgry_id"];
                    $tmp['category_name'] = $company["ctgry_name"];
                if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                    $tmp['category_icon'] = siteURL()."api/images/categories/".$company["ctgry_icon"];
                } else {
                    $tmp['category_icon'] = siteURL()."isdental/api/images/categories/".$company["ctgry_icon"];
                }
                    array_push($response["categories"], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch categories";
                echoResponse(200, $response);
            }
        });

$app->post('/company', 'authenticate', function () use ($app){
            verifyRequiredParams(array('category_name'));
            global $user_id;
            $response = array();
            $db = new DbHandler();
        
            $category_name = $app->request->post('category_name');
        
            $result = $db->getAllCategoryCompanies($category_name);
            if($result){
                $response["error"] = false;
                $response["message"] = "Companies fetched succesfully";
                $response["companies"] = array();
                while ($company = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['company_id'] = $company["cmpny_id"];
                    $tmp['company_name'] = $company["cmpny_name"];
                    $tmp['company_description'] = $company["cmpny_description"];
                    $tmp['company_website'] = $company["cmpny_website"];
                    $tmp['company_brands'] = $company["cmpny_brands"];
                    array_push($response["companies"], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch companies";
                echoResponse(200, $response);
            }
        });

$app->get('/company', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllCompanies();
            if($result){
                $response["error"] = false;
                $response["message"] = "Companies fetched succesfully";
                $response["companies"] = array();
                while ($company = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['company_id'] = $company["cmpny_id"];
                    $tmp['company_name'] = $company["cmpny_name"];
                    $tmp['company_description'] = $company["cmpny_description"];
                    $tmp['company_website'] = $company["cmpny_website"];
                    $tmp['company_brands'] = $company["cmpny_brands"];
                    array_push($response["companies"], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch companies";
                echoResponse(200, $response);
            }
        });

$app->get('/company/:company_id', 'authenticate', function ($company_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getCompanyDetails($company_id);
            $result2 = $db->getCompanyBrands($company_id);
            $result3 = $db->getCompanyContacts($company_id);
            $result4 = $db->getCompanyCategories($company_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Company details fetched succesfully";
                while ($company_detail = $result->fetch_assoc()) {
                    $response['company_id'] = $company_detail["cmpny_id"];
                    $response['company_name'] = $company_detail["cmpny_name"];
                    $response['company_image'] = $company_detail["cmpny_image"];
                    $response['company_website'] = $company_detail["cmpny_website"];
                    $response['company_description'] = $company_detail["cmpny_description"];
                    $response['company_facebook'] = $company_detail["scl_lnk_facebook"];
                    $response['company_twitter'] = $company_detail["scl_lnk_twitter"];
                    $response['company_linkedin'] = $company_detail["scl_lnk_linkedin"];
                    $response['company_youtube'] = $company_detail["scl_lnk_youtube"];
                }
                $response["brands"] = array();
                while ($brands = $result2->fetch_assoc()) {
                    $tmp = array();
                    $tmp['brand_id'] = $brands["brnd_id"];
                    $tmp['brand_name'] = $brands["brnd_name"];
                    array_push($response["brands"], $tmp);
                }
                $response["contacts"] = array();
                while ($contacts = $result3->fetch_assoc()) {
                    $tmp = array();
                    $tmp['contact_id'] = $contacts["cntct_id"];
                    $tmp['contact_type'] = $contacts["cntct_type"];
                    $tmp['contact_title'] = $contacts["cntct_title"];
                    $tmp['contact_attendant'] = $contacts["cntct_attendant"];
                    $tmp['contact_designation'] = $contacts["cntct_designation"];
                    $tmp['contact_phone1'] = $contacts["cntct_phone1"];
                    $tmp['contact_phone2'] = $contacts["cntct_phone2"];
                    $tmp['contact_address'] = $contacts["cntct_address"];
                    $tmp['contact_email'] = $contacts["cntct_email"];
                    $tmp['contact_website'] = $contacts["cntct_website"];
                    $tmp['contact_open_time'] = $contacts["cntct_open_time"];
                    $tmp['contact_close_time'] = $contacts["cntct_close_time"];
                    $tmp['contact_holidays'] = $contacts["cntct_holidays"];
                    array_push($response["contacts"], $tmp);
                }
                $response["categories"] = array();
                while ($categories = $result4->fetch_assoc()) {
                    $tmp = array();
                    $tmp['category_id'] = $categories["ctgry_id"];
                    $tmp['category_name'] = $categories["ctgry_name"];
                    array_push($response["categories"], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch company_details";
                echoResponse(200, $response);
            }
        });

$app->post('/event', 'authenticate', function () use ($app) {
            verifyRequiredParams(array('event_type'));
            global $user_id;
            $response = array();
            $db = new DbHandler();
            $event_type = $app->request->post('event_type');
            
            $result = $db->getAllCurrentAndUpcomingEvents($event_type);
            $result2 = $db->getAllPastEvents($event_type);
            if($result){
                $response["error"] = false;
                $response["message"] = "Events fetched succesfully";
                $response["events"] = array();
                while ($event = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['event_id'] = $event["evnt_id"];
                    $tmp['event_name'] = $event["evnt_name"];
                    $tmp['event_start_date'] = $event["evnt_start_date"];
                    $tmp['event_end_date'] = $event["evnt_end_date"];
                    $tmp['event_type'] = $event["evnt_type"];
                    $tmp['event_city'] = $event["evnt_city"];
                    $tmp['event_organiser_name'] = $event["evnt_organiser_name"];
                    array_push($response["events"], $tmp);
                }
                
                while ($event2 = $result2->fetch_assoc()) {
                    $tmp = array();
                    $tmp['event_id'] = $event2["evnt_id"];
                    $tmp['event_name'] = $event2["evnt_name"];
                    $tmp['event_start_date'] = $event2["evnt_start_date"];
                    $tmp['event_end_date'] = $event2["evnt_end_date"];
                    $tmp['event_type'] = $event["evnt_type"];
                    $tmp['event_city'] = $event2["evnt_city"];
                    $tmp['event_organiser_name'] = $event2["evnt_organiser_name"];
                    array_push($response["events"], $tmp);
                }
                
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Events";
                echoResponse(200, $response);
            }
        });

$app->get('/event/:event_id', 'authenticate', function ($event_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getEventDetails($event_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Event details fetched succesfully";
                while ($event_detail = $result->fetch_assoc()) {
                    $response['event_id'] = $event_detail["evnt_id"];
                    $response['event_name'] = $event_detail["evnt_name"];
                    $response['event_description'] = $event_detail["evnt_description"];
                    $response['event_website'] = $event_detail["evnt_website"];
                    $response['event_start_date'] = $event_detail["evnt_start_date"];
                    $response['event_end_date'] = $event_detail["evnt_end_date"];
                    $response['event_faq'] = $event_detail["evnt_faq"];
                    $response['event_fees'] = $event_detail["evnt_fees"];
                    $response['event_schedule'] = $event_detail["evnt_schedule"];
                    $response['event_venue'] = $event_detail["evnt_venue"];
                    $response['event_city'] = $event_detail["evnt_city"];
                    $response['event_latitude'] = $event_detail["evnt_latitude"];
                    $response['event_longitude'] = $event_detail["evnt_longitude"];
                    $response['event_inclusions'] = $event_detail["evnt_inclusions"];
                    $response['event_contact_details'] = $event_detail["evnt_contact_details"];
                    $response['event_organiser_name'] = $event_detail["evnt_organiser_name"];
                    $response['event_organiser_id'] = $event_detail["evnt_organiser_id"];
                    $response['event_facebook'] = $event_detail["scl_lnk_facebook"];
                    $response['event_twitter'] = $event_detail["scl_lnk_twitter"];
                    $response['event_linkedin'] = $event_detail["scl_lnk_linkedin"];
                    $response['event_youtube'] = $event_detail["scl_lnk_youtube"];
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch company_details";
                echoResponse(200, $response);
            }
        });

$app->get('/event/special/:event_id', 'authenticate', function ($event_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();
         
            $result = $db->getSpecialEventDetails($event_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Special Event details fetched succesfully";
                while ($event_detail = $result->fetch_assoc()) {
                    $response['special_event_id'] = $event_detail["spcl_evnt_id"];
                    $response['special_event_name'] = $event_detail["spcl_evnt_name"];
                    $response['special_event_type'] = $event_detail["spcl_evnt_type"];
                    $response['special_event_description'] = $event_detail["spcl_evnt_description"];
                    $response['special_event_website'] = $event_detail["spcl_evnt_website"];
                    $response['special_event_start_date'] = $event_detail["spcl_evnt_start_date"];
                    $response['special_event_end_date'] = $event_detail["spcl_evnt_end_date"];
                    $response['special_event_exhibitors'] = $event_detail["spcl_evnt_exhibitors"];
                    $response['special_event_faq'] = $event_detail["spcl_evnt_faq"];
                    $response['special_event_schedule'] = $event_detail["spcl_evnt_schedule"];
                    $response['special_event_venue'] = $event_detail["spcl_evnt_venue"];
                    $response['special_event_city'] = $event_detail["spcl_evnt_city"];
                    $response['special_event_latitude'] = $event_detail["spcl_evnt_latitude"];
                    $response['special_event_longitude'] = $event_detail["spcl_evnt_longitude"];
                    $response['special_event_inclusions'] = $event_detail["spcl_evnt_inclusions"];
                    $response['special_event_contact_details'] = $event_detail["spcl_evnt_contact_details"];
                    $response['special_event_organiser_name'] = $event_detail["spcl_evnt_organiser_name"];
                    $response['special_event_organiser_id'] = $event_detail["spcl_evnt_organiser_id"];
                    $response['special_event_facebook'] = "";
                    $response['special_event_twitter'] = "";
                    $response['special_event_linkedin'] = "";
                    $response['special_event_youtube'] = "";
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch company_details";
                echoResponse(200, $response);
            }
    
       });

$app->get('/organiser/:organiser_id', 'authenticate', function ($organiser_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            
            date_default_timezone_set("Asia/Kolkata");
		    $dt = new DateTime();
		    $dt->format('Y-m-d');
		    $newdt = $dt->format('Y-m-d');

            $result = $db->getOrganiserDetails($organiser_id);
            $result2 = $db->getOrganiserEvents($organiser_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Organiser details fetched succesfully";
                while ($organiser_detail = $result->fetch_assoc()) {
                    $response['organiser_id'] = $organiser_detail["orgnsr_id"];
                    $response['organiser_name'] = $organiser_detail["orgnsr_name"];
                    $response['organiser_description'] = $organiser_detail["orgnsr_description"];
                    $response['organiser_website'] = $organiser_detail["orgnsr_website"];
                    $response['organiser_trusted'] = $organiser_detail["orgnsr_trusted"];
                    $response['organiser_facebook'] = $organiser_detail["scl_lnk_facebook"];
                    $response['organiser_linkedin'] = $organiser_detail["scl_lnk_linkedin"];
                    $response['organiser_twitter'] = $organiser_detail["scl_lnk_twitter"];
                    $response['organiser_youtube'] = $organiser_detail["scl_lnk_youtube"];
                }
                
                $response["past_events"] = array();
                $response["upcoming_events"] = array();
                while ($event = $result2->fetch_assoc()) {
                    $tmp = array();
                    $tmp['event_id'] = $event["evnt_id"];
                    $tmp['event_name'] = $event["evnt_name"];
                    $tmp['event_start_date'] = $event["evnt_start_date"];
                    $tmp['event_end_date'] = $event["evnt_end_date"];
                    $tmp['event_type'] = $event["evnt_type"];
                    $tmp['event_city'] = $event["evnt_city"];
                    if(strtotime($event["evnt_end_date"]) > strtotime($newdt)){
                        array_push($response["upcoming_events"], $tmp);
                    } else {
                        array_push($response["past_events"], $tmp);                        
                    }
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch company_details";
                echoResponse(200, $response);
            }
        });



$app->post('/init/application/', 'authenticate', function () use ($app){
            global $user_id;
            verifyRequiredParams(array('app_version'));
            $app_version = $app->request->post('app_version');
        
            $response = array();
            $db = new DbHandler();

            date_default_timezone_set("Asia/Kolkata");
		    $dt = new DateTime();
		    $dt->format('Y-m-d H:i:s');
		    $newdt = $dt->format('Y-m-d H:i:s');


            $banner_result = $db->getBanners($user_id);
            
            $category_result = $db->getAllCategories();
            
            $db->updateAppVersionInUserTable($user_id, $app_version);

            $response["error"] = false;
            $response["status"] = 1;
            $response["message"] = "Application Init Successfully";
            if($db->getCurrentAppVersion("ANDROID") > $app_version){
                $response["version_update"] = true;
            } else {
                $response["version_update"] = false;
            }
            
            $response["banners"] = array();
            while ($banner = $banner_result->fetch_assoc()) {
                $tmp = array();
                $tmp["banner_id"] = $banner["bnnr_id"];
                $tmp["banner_title"] = $banner["bnnr_title"];
                $tmp["banner_url"] = $banner["bnnr_url"];
                $tmp["banner_type"] = $banner["bnnr_type"];
                if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                    $tmp['banner_image'] = siteURL()."api/images/banners/".$banner["bnnr_image"];
                } else {
                    $tmp['banner_image'] = siteURL()."isdental/api/images/banners/".$banner["bnnr_image"];
                }
                array_push($response["banners"], $tmp);
            }
            
            $response["categories"] = array();
            while ($company = $category_result->fetch_assoc()) {
                $tmp = array();
                $tmp['category_id'] = $company["ctgry_id"];
                $tmp['category_name'] = $company["ctgry_name"];
                if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                    $tmp['category_icon'] = siteURL()."api/images/categories/".$company["ctgry_icon"];
                } else {
                    $tmp['category_icon'] = siteURL()."isdental/api/images/categories/".$company["ctgry_icon"];
                }
                array_push($response["categories"], $tmp);
            }
            
            echoResponse(200, $response);
        });



$app->get('/campaign/sms/:event_id', function ($event_id) {
            $db = new DbHandler();
            $response = array();
            try {
                putenv('GOOGLE_APPLICATION_CREDENTIALS=../include/indiasupply55.json');
                $client = new Google_Client;
                $client->useApplicationDefaultCredentials();
     
                $client->setApplicationName("Something to do with my representatives");
                $client->setScopes(['https://www.googleapis.com/auth/drive','https://spreadsheets.google.com/feeds']);
     
                if ($client->isAccessTokenExpired()) {
                    $client->refreshTokenWithAssertion();
                }
    
                $accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
                ServiceRequestFactory::setInstance(new DefaultServiceRequest($accessToken));
    
                // Get our spreadsheet
                $spreadsheet = (new Google\Spreadsheet\SpreadsheetService)
                ->getSpreadsheetFeed()
                ->getByTitle('Expodent Registration');
     
                // Get the first worksheet (tab)
                $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
                $worksheet = $worksheets[0];
                $listFeed = $worksheet->getListFeed();

                $event_name = $db->getEventNameByID($event_id);
            
                $user_details = array();
                
                foreach ($listFeed->getEntries() as $entry) {
                    $tmp = array();
                    if (strtoupper($entry->getValues()['ihavealreadyregisteredonisdentalapp']) != 'YES') {
                        $entry->update(array_merge($entry->getValues(), ['ihavealreadyregisteredonisdentalapp' => 'YES']));

//                        print_r($entry->getValues());
//                        exit;
                        $tmp["name"] = $entry->getValues()["name"];
                        $tmp["mobile"] = $entry->getValues()["mobilenumber"];
                        sendEventRegisterationSMS($tmp["name"], $tmp["mobile"], $event_name);
                        sendEventRegisterationSMS2($tmp["name"], $tmp["mobile"], $event_name);
                        array_push($user_details, $tmp);
                    }
                }
            
                $no_of_sms = sizeof($user_details);
      
                if($no_of_sms>0){
                    $response["error"] = false;
                    $response["message"] = "SMS sent successfully to ".$no_of_sms." Users";
                    $response["users"] = $user_details;
                    echoResponse(200, $response);
                } else {
                    $response["error"] = false;
                    $response["message"] = "No new user registered";
                    unset($response["users"]);
                    echoResponse(200, $response);
                }
            } catch(Exception $e) {
                $response["error"] = true;
                $response["message"] = "In exception :".$e->getMessage();
                echoResponse(200, $response);
            }
        });



$app->get('/campaign/sms/vfirst/:event_id', function ($event_id) {
            $db = new DbHandler();
            $response = array();
            try {
                putenv('GOOGLE_APPLICATION_CREDENTIALS=../include/indiasupply55.json');
                $client = new Google_Client;
                $client->useApplicationDefaultCredentials();
     
                $client->setApplicationName("Something to do with my representatives");
                $client->setScopes(['https://www.googleapis.com/auth/drive','https://spreadsheets.google.com/feeds']);
     
                if ($client->isAccessTokenExpired()) {
                    $client->refreshTokenWithAssertion();
                }
    
                $accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
                ServiceRequestFactory::setInstance(new DefaultServiceRequest($accessToken));
    
                // Get our spreadsheet
                $spreadsheet = (new Google\Spreadsheet\SpreadsheetService)
                ->getSpreadsheetFeed()
                ->getByTitle('Expodent Registration');
     
                // Get the first worksheet (tab)
                $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
                $worksheet = $worksheets[0];
                $listFeed = $worksheet->getListFeed();

                $event_name = $db->getEventNameByID($event_id);
            
                $user_details = array();
                
                foreach ($listFeed->getEntries() as $entry) {
                    $tmp = array();
                    if (strtoupper($entry->getValues()['ihavealreadyregisteredonisdentalapp']) != 'YES') {
                        $tmp["name"] = $entry->getValues()["name"];
                        $tmp["mobile"] = $entry->getValues()["mobilenumber"];
                        $result1 = sendEventRegisterationSMS($tmp["name"], $tmp["mobile"], $event_name);
                        $result2 = sendEventRegisterationSMS2($tmp["name"], $tmp["mobile"], $event_name);
                        if ($result1 && $result2) {
	                        $entry->update(array_merge($entry->getValues(), ['ihavealreadyregisteredonisdentalapp' => 'YES']));
                        }
                        array_push($user_details, $tmp);
                    }
                }
            
                $no_of_sms = sizeof($user_details);
      
                if($no_of_sms>0){
                    $response["error"] = false;
                    $response["message"] = "SMS sent successfully to ".$no_of_sms." Users";
                    $response["users"] = $user_details;
                    echoResponse(200, $response);
                } else {
                    $response["error"] = false;
                    $response["message"] = "No new user registered";
                    unset($response["users"]);
                    echoResponse(200, $response);
                }
            } catch(Exception $e) {
                $response["error"] = true;
                $response["message"] = "in exception :".$e->getMessage();
                echoResponse(200, $response);
            }
        });




$app->get('/campaign/planmeca', function () {
           $db = new DbHandler();
            $response = array();
            try {
                putenv('GOOGLE_APPLICATION_CREDENTIALS=../include/planmeca.json');
                $client = new Google_Client;
                $client->useApplicationDefaultCredentials();

     
                $client->setApplicationName("Something to do with my representatives");
                $client->setScopes(['https://www.googleapis.com/auth/drive','https://spreadsheets.google.com/feeds']);
     
                if ($client->isAccessTokenExpired()) {
                    $client->refreshTokenWithAssertion();
                }
    
                $accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
                ServiceRequestFactory::setInstance(new DefaultServiceRequest($accessToken));


          
                // Get our spreadsheet
                $spreadsheet = (new Google\Spreadsheet\SpreadsheetService)
                ->getSpreadsheetFeed()
                ->getByTitle('RSVP (Responses)');
     
          
                // Get the first worksheet (tab)
                $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
                $worksheet = $worksheets[0];
                $listFeed = $worksheet->getListFeed();

                $user_details = array();
                
                foreach ($listFeed->getEntries() as $entry) {
                    $tmp = array();
                    if (strtoupper($entry->getValues()['registered']) != 'YES') {
                        $entry->update(array_merge($entry->getValues(), ['registered' => 'YES']));
                        $tmp["name"] = $entry->getValues()["name"];
                        $tmp["email"] = $entry->getValues()["emailaddress"];
                        $tmp["mobile"] = $entry->getValues()["mobilenumber"];
                        
                        $tmp["sms_result"] = array();
                        $tmp["mail_result"] = array();
                        
                        switch ($entry->getValues()["inwhichcitywouldyouliketojoinus"]) {
                            case 'Chandigarh (8th November)':
                                $tmp["sms_result"] = sendPlanmecaRegistrationSMS($tmp["name"], $tmp["mobile"], "Thank you for your confirmation. We look forward to welcoming you at the event on 8th of Nov at Hotel Park Plaza, Zirakpur. RSVP 9899897404/9212340720\nTeam Planmeca India and W&H India");
//                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], "Thank you for your confirmation. We look forward to welcoming you at the event on 8th of Nov at Hotel Park Plaza, Zirakpur. RSVP 9899897404/9212340720<br>Team Planmeca India and W&H India");
                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], '<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    <head>
        <!-- NAME: SUBTLE -->
        <!--[if gte mso 15]>
        <xml>
            <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
        <![endif]-->
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>*|MC:SUBJECT|*</title>
        
    <style type="text/css">
        p{
            margin:10px 0;
            padding:0;
        }
        table{
            border-collapse:collapse;
        }
        h1,h2,h3,h4,h5,h6{
            display:block;
            margin:0;
            padding:0;
        }
        img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
        }
        body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
        .mcnPreviewText{
            display:none !important;
        }
        #outlook a{
            padding:0;
        }
        img{
            -ms-interpolation-mode:bicubic;
        }
        table{
            mso-table-lspace:0pt;
            mso-table-rspace:0pt;
        }
        .ReadMsgBody{
            width:100%;
        }
        .ExternalClass{
            width:100%;
        }
        p,a,li,td,blockquote{
            mso-line-height-rule:exactly;
        }
        a[href^=tel],a[href^=sms]{
            color:inherit;
            cursor:default;
            text-decoration:none;
        }
        p,a,li,td,body,table,blockquote{
            -ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
            line-height:100%;
        }
        a[x-apple-data-detectors]{
            color:inherit !important;
            text-decoration:none !important;
            font-size:inherit !important;
            font-family:inherit !important;
            font-weight:inherit !important;
            line-height:inherit !important;
        }
        a.mcnButton{
            display:block;
        }
        .mcnImage{
            vertical-align:bottom;
        }
        .mcnTextContent{
            word-break:break-word;
        }
        .mcnTextContent img{
            height:auto !important;
        }
        .mcnDividerBlock{
            table-layout:fixed !important;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        body,#bodyTable{
            /*@editable*/background-color:#FAFAFA;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        #bodyCell{
            /*@editable*/border-top:0;
        }
    /*
    @tab Page
    @section email border
    @tip Set the border for your email.
    */
        #templateContainer{
            /*@editable*/border:1px solid #2e2e2e;
        }
    /*
    @tab Page
    @section heading 1
    @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
    @style heading 1
    */
        h1{
            /*@editable*/color:#606060 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:40px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-1px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 2
    @tip Set the styling for all second-level headings in your emails.
    @style heading 2
    */
        h2{
            /*@editable*/color:#404040 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:26px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.75px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 3
    @tip Set the styling for all third-level headings in your emails.
    @style heading 3
    */
        h3{
            /*@editable*/color:#DC143C !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.5px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 4
    @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
    @style heading 4
    */
        h4{
            /*@editable*/color:#808080 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader style
    @tip Set the background color and borders for your email preheader area.
    */
        #templatePreheader{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:1px solid #222222;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Preheader
    @section preheader text
    @tip Set the styling for your email preheader text. Choose a size and color that is easy to read.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .preheaderContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Header
    @section header style
    @tip Set the background color and borders for your email header area.
    */
        #templateHeader{
            /*@editable*/background-color:#transparent;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:repeat-y;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #606060;
        }
    /*
    @tab Header
    @section header text
    @tip Set the styling for your email header text. Choose a size and color that is easy to read.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Header
    @section header link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .headerContainer .mcnTextContent a{
            /*@editable*/color:#6DC6DD;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Upper Body
    @section upper body style
    @tip Set the background color and borders for your email upper body area.
    */
        #templateUpperBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Upper Body
    @section upper body text
    @tip Set the styling for your email upper body text. Choose a size and color that is easy to read.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Upper Body
    @section upper body link
    @tip Set the styling for your email upper body links. Choose a color that helps them stand out from your text.
    */
        .upperBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section column style
    @tip Set the background color and borders for your email columns area.
    */
        #templateColumns{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Columns
    @section left column text
    @tip Set the styling for your email left column text. Choose a size and color that is easy to read.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section left column link
    @tip Set the styling for your email left column links. Choose a color that helps them stand out from your text.
    */
        .leftColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section right column text
    @tip Set the styling for your email right column text. Choose a size and color that is easy to read.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section right column link
    @tip Set the styling for your email right column links. Choose a color that helps them stand out from your text.
    */
        .rightColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Lower Body
    @section lower body style
    @tip Set the background color and borders for your email lower body area.
    */
        #templateLowerBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Lower Body
    @section lower body text
    @tip Set the styling for your email lower body text. Choose a size and color that is easy to read.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Lower Body
    @section lower body link
    @tip Set the styling for your email lower body links. Choose a color that helps them stand out from your text.
    */
        .lowerBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Footer
    @section footer style
    @tip Set the background color and borders for your email footer area.
    */
        #templateFooter{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Footer
    @section footer text
    @tip Set the styling for your email footer text. Choose a size and color that is easy to read.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Footer
    @section footer link
    @tip Set the styling for your email footer links. Choose a color that helps them stand out from your text.
    */
        .footerContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    @media only screen and (max-width: 480px){
        body,table,td,p,a,li,blockquote{
            -webkit-text-size-adjust:none !important;
        }

}   @media only screen and (max-width: 480px){
        body{
            width:100% !important;
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        #templateContainer,#templatePreheader,#templateHeader,#templateUpperBody,#templateColumns,#templateLowerBody,#templateFooter{
            max-width:600px !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .columnsContainer{
            display:block!important;
            max-width:600px !important;
            padding-bottom:18px !important;
            padding-left:0 !important;
            width:100%!important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImage{
            height:auto !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer{
            max-width:100% !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnBoxedTextContentContainer{
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupContent{
            padding:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
            padding-top:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardTopImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
            padding-top:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardBottomImageContent{
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockInner{
            padding-top:0 !important;
            padding-bottom:0 !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockOuter{
            padding-top:9px !important;
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnTextContent,.mcnBoxedTextContentColumn{
            padding-right:18px !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
            padding-right:18px !important;
            padding-bottom:0 !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcpreview-image-uploader{
            display:none !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 1
    @tip Make the first-level headings larger in size for better readability on small screens.
    */
        h1{
            /*@editable*/font-size:24px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 2
    @tip Make the second-level headings larger in size for better readability on small screens.
    */
        h2{
            /*@editable*/font-size:20px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 3
    @tip Make the third-level headings larger in size for better readability on small screens.
    */
        h3{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 4
    @tip Make the fourth-level headings larger in size for better readability on small screens.
    */
        h4{
            /*@editable*/font-size:16px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Boxed Text
    @tip Make the boxed text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .mcnBoxedTextContentContainer .mcnTextContent,.mcnBoxedTextContentContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Visibility
    @tip Set the visibility of the email preheader on small screens. You can hide it to save space.
    */
        #templatePreheader{
            /*@editable*/display:block !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Text
    @tip Make the preheader text larger in size for better readability on small screens.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Header Text
    @tip Make the header text larger in size for better readability on small screens.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:center !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Upper Body Text
    @tip Make the upper body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Left Column Text
    @tip Make the left column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Right Column Text
    @tip Make the right column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Lower Body Text
    @tip Make the lower body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section footer text
    @tip Make the body content text larger in size for better readability on small screens.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:center !important;
        }

}</style></head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <!--*|IF:MC_PREVIEW_TEXT|*-->
        <!--[if !gte mso 9]><!----><span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;">*|MC_PREVIEW_TEXT|*</span><!--<![endif]-->
        <!--*|END:IF|*-->
        <center>
            <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                <tr>
                    <td align="center" valign="top" id="bodyCell">
                        <!-- BEGIN TEMPLATE // -->
                        <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateContainer" style="border-top:0 !important;">
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN PREHEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templatePreheader">
                                        <tr>
                                            <td valign="top" class="preheaderContainer" style="padding-top:9px; padding-bottom:9px"></td>
                                        </tr>
                                    </table>
                                    <!-- // END PREHEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN HEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateHeader">
                                        <tr>
                                            <td valign="top" class="headerContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <span style="font-size:24px"><span style="color:#2e2e2e"><strong>Thank you for registering for the Planmeca Dream Clinic Show and W&amp;H Innovation day.</strong></span></span>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;border: 1px none;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        <p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Dear Doctor,</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">We have your seat reserved for you and a great evening planned.<br>
So just sit back and relax, as we take you to the world of<br>
<span style="font-size:15px"><strong>Digital Dentistry and the Latest cutting-edge Technologies.</strong></span><br>
<br>
As a special guest of Planmeca and W&amp;H you will be welcomed at<br>
<strong><span style="font-size:15px">Hotel Park Plaza, Zirakpur</span></strong><br>
on 8th of November 2017. The program would start by 7.00 pm<br>
&nbsp;</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;"><a href="https://www.google.co.in/maps/place/park+plaza+zirakpur" target="_blank">Get Directions</a></p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;">
    <tbody class="mcnImageBlockOuter">
            <tr>
                <td valign="top" style="padding:9px" class="mcnImageBlockInner">
                    <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width:100%;">
                        <tbody><tr>
                            <td class="mcnImageContent" valign="top" style="padding-right: 9px; padding-left: 9px; padding-top: 0; padding-bottom: 0; text-align:center;">
                                
                                    
                                        <img align="center" alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/a483308c-06fe-41a5-9e9c-b99bf15386ce.jpg" width="564" style="max-width:732px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="mcnImage">
                                    
                                
                            </td>
                        </tr>
                    </tbody></table>
                </td>
            </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END HEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN UPPER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateUpperBody">
                                        <tr>
                                            <td valign="top" class="upperBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <strong id="docs-internal-guid-f7d23ce9-90f5-7b91-9087-f35eb3fb1847">Here is a sneak-peak of few products at the event !</strong>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END UPPER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN COLUMNS // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateColumns">
                                        <tr>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="leftColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="rightColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // END COLUMNS -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN LOWER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateLowerBody">
                                        <tr>
                                            <td valign="top" class="lowerBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/CADCAM/CADCAM-for-dental-clinics/planmeca-planmill-40/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/63e53725-831a-4c62-899c-0d622367c401.jpeg" width="564" style="max-width:800px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <p dir="ltr" style="text-align: center;"><strong>PlanMill 40 S</strong><br>
The smart and connected mill</p>

        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.wh.com/en_global/dental-products/oralsurgery-implantology/surgical-devices/implantmed/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/27a20f5e-76b4-435b-bc1d-858c0032ce99.jpg" width="564" style="max-width:1200px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong>Implantmed</strong><br>
New Surgical Unit for a Stable Implant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="right" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/na/CADCAM/cadcam-for-chairside/planmeca-emerald/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/acf340f9-d3b7-49b5-9af8-ac892490f83b.jpg" width="564" style="max-width:1280px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong id="docs-internal-guid-ce30127a-90d6-18d7-06b7-5c836a9963ee">Planmeca Emerald</strong><br>
Go Beyond The Standard. Be Brilliant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 1px solid #EAEAEA;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        &nbsp;
<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">If you have any questions please contact<br>
<strong>Mr. Anshul / Gopal on 9212340720 / 9899897404</strong></p>

<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Thank you and see you at the event !</p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END LOWER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN FOOTER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateFooter">
                                        <tr>
                                            <td valign="top" class="footerContainer" style="padding-top:9px; padding-bottom:9px;"></td>
                                        </tr>
                                    </table>
                                    <!-- // END FOOTER -->
                                </td>
                            </tr>
                        </table>
                        <!-- // END TEMPLATE -->
                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>');
                                break;
                            case 'Delhi (9th November)':
                                $tmp["sms_result"] = sendPlanmecaRegistrationSMS($tmp["name"], $tmp["mobile"], "Thank you for your confirmation. We look forward to welcoming you at the event on 9th of Nov at Hotel The Royal Plaza, Connaught Place. RSVP 9899897404/9212340720\nTeam Planmeca India and W&H India");
//                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], "Thank you for your confirmation. We look forward to welcoming you at the event on 9th of Nov at Hotel The Royal Plaza, Connaught Place. RSVP 9899897404/9212340720<br>Team Planmeca India and W&H India");
                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], '<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    <head>
        <!-- NAME: SUBTLE -->
        <!--[if gte mso 15]>
        <xml>
            <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
        <![endif]-->
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>*|MC:SUBJECT|*</title>
        
    <style type="text/css">
        p{
            margin:10px 0;
            padding:0;
        }
        table{
            border-collapse:collapse;
        }
        h1,h2,h3,h4,h5,h6{
            display:block;
            margin:0;
            padding:0;
        }
        img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
        }
        body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
        .mcnPreviewText{
            display:none !important;
        }
        #outlook a{
            padding:0;
        }
        img{
            -ms-interpolation-mode:bicubic;
        }
        table{
            mso-table-lspace:0pt;
            mso-table-rspace:0pt;
        }
        .ReadMsgBody{
            width:100%;
        }
        .ExternalClass{
            width:100%;
        }
        p,a,li,td,blockquote{
            mso-line-height-rule:exactly;
        }
        a[href^=tel],a[href^=sms]{
            color:inherit;
            cursor:default;
            text-decoration:none;
        }
        p,a,li,td,body,table,blockquote{
            -ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
            line-height:100%;
        }
        a[x-apple-data-detectors]{
            color:inherit !important;
            text-decoration:none !important;
            font-size:inherit !important;
            font-family:inherit !important;
            font-weight:inherit !important;
            line-height:inherit !important;
        }
        a.mcnButton{
            display:block;
        }
        .mcnImage{
            vertical-align:bottom;
        }
        .mcnTextContent{
            word-break:break-word;
        }
        .mcnTextContent img{
            height:auto !important;
        }
        .mcnDividerBlock{
            table-layout:fixed !important;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        body,#bodyTable{
            /*@editable*/background-color:#FAFAFA;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        #bodyCell{
            /*@editable*/border-top:0;
        }
    /*
    @tab Page
    @section email border
    @tip Set the border for your email.
    */
        #templateContainer{
            /*@editable*/border:1px solid #2e2e2e;
        }
    /*
    @tab Page
    @section heading 1
    @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
    @style heading 1
    */
        h1{
            /*@editable*/color:#606060 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:40px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-1px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 2
    @tip Set the styling for all second-level headings in your emails.
    @style heading 2
    */
        h2{
            /*@editable*/color:#404040 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:26px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.75px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 3
    @tip Set the styling for all third-level headings in your emails.
    @style heading 3
    */
        h3{
            /*@editable*/color:#DC143C !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.5px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 4
    @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
    @style heading 4
    */
        h4{
            /*@editable*/color:#808080 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader style
    @tip Set the background color and borders for your email preheader area.
    */
        #templatePreheader{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:1px solid #222222;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Preheader
    @section preheader text
    @tip Set the styling for your email preheader text. Choose a size and color that is easy to read.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .preheaderContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Header
    @section header style
    @tip Set the background color and borders for your email header area.
    */
        #templateHeader{
            /*@editable*/background-color:#transparent;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:repeat-y;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #606060;
        }
    /*
    @tab Header
    @section header text
    @tip Set the styling for your email header text. Choose a size and color that is easy to read.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Header
    @section header link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .headerContainer .mcnTextContent a{
            /*@editable*/color:#6DC6DD;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Upper Body
    @section upper body style
    @tip Set the background color and borders for your email upper body area.
    */
        #templateUpperBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Upper Body
    @section upper body text
    @tip Set the styling for your email upper body text. Choose a size and color that is easy to read.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Upper Body
    @section upper body link
    @tip Set the styling for your email upper body links. Choose a color that helps them stand out from your text.
    */
        .upperBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section column style
    @tip Set the background color and borders for your email columns area.
    */
        #templateColumns{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Columns
    @section left column text
    @tip Set the styling for your email left column text. Choose a size and color that is easy to read.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section left column link
    @tip Set the styling for your email left column links. Choose a color that helps them stand out from your text.
    */
        .leftColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section right column text
    @tip Set the styling for your email right column text. Choose a size and color that is easy to read.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section right column link
    @tip Set the styling for your email right column links. Choose a color that helps them stand out from your text.
    */
        .rightColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Lower Body
    @section lower body style
    @tip Set the background color and borders for your email lower body area.
    */
        #templateLowerBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Lower Body
    @section lower body text
    @tip Set the styling for your email lower body text. Choose a size and color that is easy to read.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Lower Body
    @section lower body link
    @tip Set the styling for your email lower body links. Choose a color that helps them stand out from your text.
    */
        .lowerBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Footer
    @section footer style
    @tip Set the background color and borders for your email footer area.
    */
        #templateFooter{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Footer
    @section footer text
    @tip Set the styling for your email footer text. Choose a size and color that is easy to read.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Footer
    @section footer link
    @tip Set the styling for your email footer links. Choose a color that helps them stand out from your text.
    */
        .footerContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    @media only screen and (max-width: 480px){
        body,table,td,p,a,li,blockquote{
            -webkit-text-size-adjust:none !important;
        }

}   @media only screen and (max-width: 480px){
        body{
            width:100% !important;
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        #templateContainer,#templatePreheader,#templateHeader,#templateUpperBody,#templateColumns,#templateLowerBody,#templateFooter{
            max-width:600px !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .columnsContainer{
            display:block!important;
            max-width:600px !important;
            padding-bottom:18px !important;
            padding-left:0 !important;
            width:100%!important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImage{
            height:auto !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer{
            max-width:100% !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnBoxedTextContentContainer{
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupContent{
            padding:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
            padding-top:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardTopImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
            padding-top:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardBottomImageContent{
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockInner{
            padding-top:0 !important;
            padding-bottom:0 !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockOuter{
            padding-top:9px !important;
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnTextContent,.mcnBoxedTextContentColumn{
            padding-right:18px !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
            padding-right:18px !important;
            padding-bottom:0 !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcpreview-image-uploader{
            display:none !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 1
    @tip Make the first-level headings larger in size for better readability on small screens.
    */
        h1{
            /*@editable*/font-size:24px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 2
    @tip Make the second-level headings larger in size for better readability on small screens.
    */
        h2{
            /*@editable*/font-size:20px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 3
    @tip Make the third-level headings larger in size for better readability on small screens.
    */
        h3{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 4
    @tip Make the fourth-level headings larger in size for better readability on small screens.
    */
        h4{
            /*@editable*/font-size:16px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Boxed Text
    @tip Make the boxed text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .mcnBoxedTextContentContainer .mcnTextContent,.mcnBoxedTextContentContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Visibility
    @tip Set the visibility of the email preheader on small screens. You can hide it to save space.
    */
        #templatePreheader{
            /*@editable*/display:block !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Text
    @tip Make the preheader text larger in size for better readability on small screens.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Header Text
    @tip Make the header text larger in size for better readability on small screens.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:center !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Upper Body Text
    @tip Make the upper body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Left Column Text
    @tip Make the left column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Right Column Text
    @tip Make the right column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Lower Body Text
    @tip Make the lower body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section footer text
    @tip Make the body content text larger in size for better readability on small screens.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:center !important;
        }

}</style></head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <!--*|IF:MC_PREVIEW_TEXT|*-->
        <!--[if !gte mso 9]><!----><span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;">*|MC_PREVIEW_TEXT|*</span><!--<![endif]-->
        <!--*|END:IF|*-->
        <center>
            <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                <tr>
                    <td align="center" valign="top" id="bodyCell">
                        <!-- BEGIN TEMPLATE // -->
                        <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateContainer" style="border-top:0 !important;">
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN PREHEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templatePreheader">
                                        <tr>
                                            <td valign="top" class="preheaderContainer" style="padding-top:9px; padding-bottom:9px"></td>
                                        </tr>
                                    </table>
                                    <!-- // END PREHEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN HEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateHeader">
                                        <tr>
                                            <td valign="top" class="headerContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <span style="font-size:24px"><span style="color:#2e2e2e"><strong>Thank you for registering for the Planmeca Dream Clinic Show and W&amp;H Innovation day.</strong></span></span>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;border: 1px none;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        <p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Dear Doctor,</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">We have your seat reserved for you and a great evening planned.<br>
So just sit back and relax, as we take you to the world of<br>
<span style="font-size:15px"><strong>Digital Dentistry and the Latest cutting-edge Technologies.</strong></span><br>
<br>
As a special guest of Planmeca and W&amp;H you will be welcomed at<br>
<strong><span style="font-size:15px">Hotel The Royal Plaza, Connaught Place.</span></strong><br>
on 9th of November 2017. The program would start by 7.00 pm<br>
&nbsp;</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;"><a href="https://www.google.co.in/maps/place/Hotel+The+Royal+Plaza" target="_blank">Get Directions</a></p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;">
    <tbody class="mcnImageBlockOuter">
            <tr>
                <td valign="top" style="padding:9px" class="mcnImageBlockInner">
                    <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width:100%;">
                        <tbody><tr>
                            <td class="mcnImageContent" valign="top" style="padding-right: 9px; padding-left: 9px; padding-top: 0; padding-bottom: 0; text-align:center;">
                                
                                    
                                        <img align="center" alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/a483308c-06fe-41a5-9e9c-b99bf15386ce.jpg" width="564" style="max-width:732px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="mcnImage">
                                    
                                
                            </td>
                        </tr>
                    </tbody></table>
                </td>
            </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END HEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN UPPER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateUpperBody">
                                        <tr>
                                            <td valign="top" class="upperBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <strong id="docs-internal-guid-f7d23ce9-90f5-7b91-9087-f35eb3fb1847">Here is a sneak-peak of few products at the event !</strong>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END UPPER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN COLUMNS // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateColumns">
                                        <tr>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="leftColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="rightColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // END COLUMNS -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN LOWER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateLowerBody">
                                        <tr>
                                            <td valign="top" class="lowerBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/CADCAM/CADCAM-for-dental-clinics/planmeca-planmill-40/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/63e53725-831a-4c62-899c-0d622367c401.jpeg" width="564" style="max-width:800px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <p dir="ltr" style="text-align: center;"><strong>PlanMill 40 S</strong><br>
The smart and connected mill</p>

        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.wh.com/en_global/dental-products/oralsurgery-implantology/surgical-devices/implantmed/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/27a20f5e-76b4-435b-bc1d-858c0032ce99.jpg" width="564" style="max-width:1200px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong>Implantmed</strong><br>
New Surgical Unit for a Stable Implant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="right" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/na/CADCAM/cadcam-for-chairside/planmeca-emerald/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/acf340f9-d3b7-49b5-9af8-ac892490f83b.jpg" width="564" style="max-width:1280px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong id="docs-internal-guid-ce30127a-90d6-18d7-06b7-5c836a9963ee">Planmeca Emerald</strong><br>
Go Beyond The Standard. Be Brilliant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 1px solid #EAEAEA;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        &nbsp;
<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">If you have any questions please contact<br>
<strong>Mr. Anshul / Gopal on 9212340720 / 9899897404</strong></p>

<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Thank you and see you at the event !</p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END LOWER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN FOOTER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateFooter">
                                        <tr>
                                            <td valign="top" class="footerContainer" style="padding-top:9px; padding-bottom:9px;"></td>
                                        </tr>
                                    </table>
                                    <!-- // END FOOTER -->
                                </td>
                            </tr>
                        </table>
                        <!-- // END TEMPLATE -->
                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>');
                                break;
                            case 'Mumbai (10th November)':
                                $tmp["sms_result"] = sendPlanmecaRegistrationSMS($tmp["name"], $tmp["mobile"], "Thank you for your confirmation. We look forward to welcoming you at the event on 10th of Nov at Hotel Courtyard by Marriot, Andheri East. RSVP 9702642000/9422322695\nTeam Planmeca India and W&H India");
//                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], "Thank you for your confirmation. We look forward to welcoming you at the event on 10th of Nov at Hotel Courtyard by Marriot, Andheri East. RSVP 9702642000/9422322695<br>Team Planmeca India and W&H India");
                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], '<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    <head>
        <!-- NAME: SUBTLE -->
        <!--[if gte mso 15]>
        <xml>
            <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
        <![endif]-->
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>*|MC:SUBJECT|*</title>
        
    <style type="text/css">
        p{
            margin:10px 0;
            padding:0;
        }
        table{
            border-collapse:collapse;
        }
        h1,h2,h3,h4,h5,h6{
            display:block;
            margin:0;
            padding:0;
        }
        img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
        }
        body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
        .mcnPreviewText{
            display:none !important;
        }
        #outlook a{
            padding:0;
        }
        img{
            -ms-interpolation-mode:bicubic;
        }
        table{
            mso-table-lspace:0pt;
            mso-table-rspace:0pt;
        }
        .ReadMsgBody{
            width:100%;
        }
        .ExternalClass{
            width:100%;
        }
        p,a,li,td,blockquote{
            mso-line-height-rule:exactly;
        }
        a[href^=tel],a[href^=sms]{
            color:inherit;
            cursor:default;
            text-decoration:none;
        }
        p,a,li,td,body,table,blockquote{
            -ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
            line-height:100%;
        }
        a[x-apple-data-detectors]{
            color:inherit !important;
            text-decoration:none !important;
            font-size:inherit !important;
            font-family:inherit !important;
            font-weight:inherit !important;
            line-height:inherit !important;
        }
        a.mcnButton{
            display:block;
        }
        .mcnImage{
            vertical-align:bottom;
        }
        .mcnTextContent{
            word-break:break-word;
        }
        .mcnTextContent img{
            height:auto !important;
        }
        .mcnDividerBlock{
            table-layout:fixed !important;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        body,#bodyTable{
            /*@editable*/background-color:#FAFAFA;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        #bodyCell{
            /*@editable*/border-top:0;
        }
    /*
    @tab Page
    @section email border
    @tip Set the border for your email.
    */
        #templateContainer{
            /*@editable*/border:1px solid #2e2e2e;
        }
    /*
    @tab Page
    @section heading 1
    @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
    @style heading 1
    */
        h1{
            /*@editable*/color:#606060 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:40px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-1px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 2
    @tip Set the styling for all second-level headings in your emails.
    @style heading 2
    */
        h2{
            /*@editable*/color:#404040 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:26px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.75px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 3
    @tip Set the styling for all third-level headings in your emails.
    @style heading 3
    */
        h3{
            /*@editable*/color:#DC143C !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.5px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 4
    @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
    @style heading 4
    */
        h4{
            /*@editable*/color:#808080 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader style
    @tip Set the background color and borders for your email preheader area.
    */
        #templatePreheader{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:1px solid #222222;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Preheader
    @section preheader text
    @tip Set the styling for your email preheader text. Choose a size and color that is easy to read.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .preheaderContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Header
    @section header style
    @tip Set the background color and borders for your email header area.
    */
        #templateHeader{
            /*@editable*/background-color:#transparent;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:repeat-y;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #606060;
        }
    /*
    @tab Header
    @section header text
    @tip Set the styling for your email header text. Choose a size and color that is easy to read.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Header
    @section header link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .headerContainer .mcnTextContent a{
            /*@editable*/color:#6DC6DD;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Upper Body
    @section upper body style
    @tip Set the background color and borders for your email upper body area.
    */
        #templateUpperBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Upper Body
    @section upper body text
    @tip Set the styling for your email upper body text. Choose a size and color that is easy to read.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Upper Body
    @section upper body link
    @tip Set the styling for your email upper body links. Choose a color that helps them stand out from your text.
    */
        .upperBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section column style
    @tip Set the background color and borders for your email columns area.
    */
        #templateColumns{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Columns
    @section left column text
    @tip Set the styling for your email left column text. Choose a size and color that is easy to read.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section left column link
    @tip Set the styling for your email left column links. Choose a color that helps them stand out from your text.
    */
        .leftColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section right column text
    @tip Set the styling for your email right column text. Choose a size and color that is easy to read.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section right column link
    @tip Set the styling for your email right column links. Choose a color that helps them stand out from your text.
    */
        .rightColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Lower Body
    @section lower body style
    @tip Set the background color and borders for your email lower body area.
    */
        #templateLowerBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Lower Body
    @section lower body text
    @tip Set the styling for your email lower body text. Choose a size and color that is easy to read.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Lower Body
    @section lower body link
    @tip Set the styling for your email lower body links. Choose a color that helps them stand out from your text.
    */
        .lowerBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Footer
    @section footer style
    @tip Set the background color and borders for your email footer area.
    */
        #templateFooter{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Footer
    @section footer text
    @tip Set the styling for your email footer text. Choose a size and color that is easy to read.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Footer
    @section footer link
    @tip Set the styling for your email footer links. Choose a color that helps them stand out from your text.
    */
        .footerContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    @media only screen and (max-width: 480px){
        body,table,td,p,a,li,blockquote{
            -webkit-text-size-adjust:none !important;
        }

}   @media only screen and (max-width: 480px){
        body{
            width:100% !important;
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        #templateContainer,#templatePreheader,#templateHeader,#templateUpperBody,#templateColumns,#templateLowerBody,#templateFooter{
            max-width:600px !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .columnsContainer{
            display:block!important;
            max-width:600px !important;
            padding-bottom:18px !important;
            padding-left:0 !important;
            width:100%!important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImage{
            height:auto !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer{
            max-width:100% !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnBoxedTextContentContainer{
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupContent{
            padding:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
            padding-top:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardTopImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
            padding-top:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardBottomImageContent{
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockInner{
            padding-top:0 !important;
            padding-bottom:0 !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockOuter{
            padding-top:9px !important;
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnTextContent,.mcnBoxedTextContentColumn{
            padding-right:18px !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
            padding-right:18px !important;
            padding-bottom:0 !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcpreview-image-uploader{
            display:none !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 1
    @tip Make the first-level headings larger in size for better readability on small screens.
    */
        h1{
            /*@editable*/font-size:24px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 2
    @tip Make the second-level headings larger in size for better readability on small screens.
    */
        h2{
            /*@editable*/font-size:20px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 3
    @tip Make the third-level headings larger in size for better readability on small screens.
    */
        h3{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 4
    @tip Make the fourth-level headings larger in size for better readability on small screens.
    */
        h4{
            /*@editable*/font-size:16px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Boxed Text
    @tip Make the boxed text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .mcnBoxedTextContentContainer .mcnTextContent,.mcnBoxedTextContentContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Visibility
    @tip Set the visibility of the email preheader on small screens. You can hide it to save space.
    */
        #templatePreheader{
            /*@editable*/display:block !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Text
    @tip Make the preheader text larger in size for better readability on small screens.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Header Text
    @tip Make the header text larger in size for better readability on small screens.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:center !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Upper Body Text
    @tip Make the upper body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Left Column Text
    @tip Make the left column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Right Column Text
    @tip Make the right column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Lower Body Text
    @tip Make the lower body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section footer text
    @tip Make the body content text larger in size for better readability on small screens.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:center !important;
        }

}</style></head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <!--*|IF:MC_PREVIEW_TEXT|*-->
        <!--[if !gte mso 9]><!----><span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;">*|MC_PREVIEW_TEXT|*</span><!--<![endif]-->
        <!--*|END:IF|*-->
        <center>
            <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                <tr>
                    <td align="center" valign="top" id="bodyCell">
                        <!-- BEGIN TEMPLATE // -->
                        <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateContainer" style="border-top:0 !important;">
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN PREHEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templatePreheader">
                                        <tr>
                                            <td valign="top" class="preheaderContainer" style="padding-top:9px; padding-bottom:9px"></td>
                                        </tr>
                                    </table>
                                    <!-- // END PREHEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN HEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateHeader">
                                        <tr>
                                            <td valign="top" class="headerContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <span style="font-size:24px"><span style="color:#2e2e2e"><strong>Thank you for registering for the Planmeca Dream Clinic Show and W&amp;H Innovation day.</strong></span></span>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;border: 1px none;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        <p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Dear Doctor,</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">We have your seat reserved for you and a great evening planned.<br>
So just sit back and relax, as we take you to the world of<br>
<span style="font-size:15px"><strong>Digital Dentistry and the Latest cutting-edge Technologies.</strong></span><br>
<br>
As a special guest of Planmeca and W&amp;H you will be welcomed at<br>
<strong><span style="font-size:15px">Hotel Courtyard by Marriot, Andheri east</span></strong><br>
on 10th of November 2017. The program would start by 7.00 pm<br>
&nbsp;</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;"><a href="https://www.google.co.in/maps/place/Courtyard+by+Marriott+Mumbai+International+Airport/@19.1141093,72.8616895,17z/data" target="_blank">Get Directions</a></p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;">
    <tbody class="mcnImageBlockOuter">
            <tr>
                <td valign="top" style="padding:9px" class="mcnImageBlockInner">
                    <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width:100%;">
                        <tbody><tr>
                            <td class="mcnImageContent" valign="top" style="padding-right: 9px; padding-left: 9px; padding-top: 0; padding-bottom: 0; text-align:center;">
                                
                                    
                                        <img align="center" alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/a483308c-06fe-41a5-9e9c-b99bf15386ce.jpg" width="564" style="max-width:732px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="mcnImage">
                                    
                                
                            </td>
                        </tr>
                    </tbody></table>
                </td>
            </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END HEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN UPPER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateUpperBody">
                                        <tr>
                                            <td valign="top" class="upperBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <strong id="docs-internal-guid-f7d23ce9-90f5-7b91-9087-f35eb3fb1847">Here is a sneak-peak of few products at the event !</strong>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END UPPER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN COLUMNS // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateColumns">
                                        <tr>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="leftColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="rightColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // END COLUMNS -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN LOWER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateLowerBody">
                                        <tr>
                                            <td valign="top" class="lowerBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/CADCAM/CADCAM-for-dental-clinics/planmeca-planmill-40/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/63e53725-831a-4c62-899c-0d622367c401.jpeg" width="564" style="max-width:800px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <p dir="ltr" style="text-align: center;"><strong>PlanMill 40 S</strong><br>
The smart and connected mill</p>

        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.wh.com/en_global/dental-products/oralsurgery-implantology/surgical-devices/implantmed/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/27a20f5e-76b4-435b-bc1d-858c0032ce99.jpg" width="564" style="max-width:1200px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong>Implantmed</strong><br>
New Surgical Unit for a Stable Implant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="right" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/na/CADCAM/cadcam-for-chairside/planmeca-emerald/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/acf340f9-d3b7-49b5-9af8-ac892490f83b.jpg" width="564" style="max-width:1280px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong id="docs-internal-guid-ce30127a-90d6-18d7-06b7-5c836a9963ee">Planmeca Emerald</strong><br>
Go Beyond The Standard. Be Brilliant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 1px solid #EAEAEA;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        &nbsp;
<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">If you have any questions please contact<br>
<strong>Mr. Anshul / Gopal on 9212340720 / 9899897404</strong></p>

<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Thank you and see you at the event !</p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END LOWER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN FOOTER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateFooter">
                                        <tr>
                                            <td valign="top" class="footerContainer" style="padding-top:9px; padding-bottom:9px;"></td>
                                        </tr>
                                    </table>
                                    <!-- // END FOOTER -->
                                </td>
                            </tr>
                        </table>
                        <!-- // END TEMPLATE -->
                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>');
                                break;
                            case 'Pune (11th November)':
                                $tmp["sms_result"] = sendPlanmecaRegistrationSMS($tmp["name"], $tmp["mobile"], "Thank you for your confirmation. We look forward to welcoming you at the event on 11th of Nov at Crowne Plaza, Pune City Centre. RSVP 9702642000/9422322695\nTeam Planmeca India and W&H India");
//                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], "Thank you for your confirmation. We look forward to welcoming you at the event on 11th of Nov at Crowne Plaza, Pune City Centre. RSVP 9702642000/9422322695<br>Team Planmeca India and W&H India");
                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], '<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    <head>
        <!-- NAME: SUBTLE -->
        <!--[if gte mso 15]>
        <xml>
            <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
        <![endif]-->
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>*|MC:SUBJECT|*</title>
        
    <style type="text/css">
        p{
            margin:10px 0;
            padding:0;
        }
        table{
            border-collapse:collapse;
        }
        h1,h2,h3,h4,h5,h6{
            display:block;
            margin:0;
            padding:0;
        }
        img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
        }
        body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
        .mcnPreviewText{
            display:none !important;
        }
        #outlook a{
            padding:0;
        }
        img{
            -ms-interpolation-mode:bicubic;
        }
        table{
            mso-table-lspace:0pt;
            mso-table-rspace:0pt;
        }
        .ReadMsgBody{
            width:100%;
        }
        .ExternalClass{
            width:100%;
        }
        p,a,li,td,blockquote{
            mso-line-height-rule:exactly;
        }
        a[href^=tel],a[href^=sms]{
            color:inherit;
            cursor:default;
            text-decoration:none;
        }
        p,a,li,td,body,table,blockquote{
            -ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
            line-height:100%;
        }
        a[x-apple-data-detectors]{
            color:inherit !important;
            text-decoration:none !important;
            font-size:inherit !important;
            font-family:inherit !important;
            font-weight:inherit !important;
            line-height:inherit !important;
        }
        a.mcnButton{
            display:block;
        }
        .mcnImage{
            vertical-align:bottom;
        }
        .mcnTextContent{
            word-break:break-word;
        }
        .mcnTextContent img{
            height:auto !important;
        }
        .mcnDividerBlock{
            table-layout:fixed !important;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        body,#bodyTable{
            /*@editable*/background-color:#FAFAFA;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        #bodyCell{
            /*@editable*/border-top:0;
        }
    /*
    @tab Page
    @section email border
    @tip Set the border for your email.
    */
        #templateContainer{
            /*@editable*/border:1px solid #2e2e2e;
        }
    /*
    @tab Page
    @section heading 1
    @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
    @style heading 1
    */
        h1{
            /*@editable*/color:#606060 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:40px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-1px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 2
    @tip Set the styling for all second-level headings in your emails.
    @style heading 2
    */
        h2{
            /*@editable*/color:#404040 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:26px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.75px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 3
    @tip Set the styling for all third-level headings in your emails.
    @style heading 3
    */
        h3{
            /*@editable*/color:#DC143C !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.5px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 4
    @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
    @style heading 4
    */
        h4{
            /*@editable*/color:#808080 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader style
    @tip Set the background color and borders for your email preheader area.
    */
        #templatePreheader{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:1px solid #222222;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Preheader
    @section preheader text
    @tip Set the styling for your email preheader text. Choose a size and color that is easy to read.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .preheaderContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Header
    @section header style
    @tip Set the background color and borders for your email header area.
    */
        #templateHeader{
            /*@editable*/background-color:#transparent;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:repeat-y;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #606060;
        }
    /*
    @tab Header
    @section header text
    @tip Set the styling for your email header text. Choose a size and color that is easy to read.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Header
    @section header link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .headerContainer .mcnTextContent a{
            /*@editable*/color:#6DC6DD;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Upper Body
    @section upper body style
    @tip Set the background color and borders for your email upper body area.
    */
        #templateUpperBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Upper Body
    @section upper body text
    @tip Set the styling for your email upper body text. Choose a size and color that is easy to read.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Upper Body
    @section upper body link
    @tip Set the styling for your email upper body links. Choose a color that helps them stand out from your text.
    */
        .upperBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section column style
    @tip Set the background color and borders for your email columns area.
    */
        #templateColumns{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Columns
    @section left column text
    @tip Set the styling for your email left column text. Choose a size and color that is easy to read.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section left column link
    @tip Set the styling for your email left column links. Choose a color that helps them stand out from your text.
    */
        .leftColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section right column text
    @tip Set the styling for your email right column text. Choose a size and color that is easy to read.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section right column link
    @tip Set the styling for your email right column links. Choose a color that helps them stand out from your text.
    */
        .rightColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Lower Body
    @section lower body style
    @tip Set the background color and borders for your email lower body area.
    */
        #templateLowerBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Lower Body
    @section lower body text
    @tip Set the styling for your email lower body text. Choose a size and color that is easy to read.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Lower Body
    @section lower body link
    @tip Set the styling for your email lower body links. Choose a color that helps them stand out from your text.
    */
        .lowerBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Footer
    @section footer style
    @tip Set the background color and borders for your email footer area.
    */
        #templateFooter{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Footer
    @section footer text
    @tip Set the styling for your email footer text. Choose a size and color that is easy to read.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Footer
    @section footer link
    @tip Set the styling for your email footer links. Choose a color that helps them stand out from your text.
    */
        .footerContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    @media only screen and (max-width: 480px){
        body,table,td,p,a,li,blockquote{
            -webkit-text-size-adjust:none !important;
        }

}   @media only screen and (max-width: 480px){
        body{
            width:100% !important;
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        #templateContainer,#templatePreheader,#templateHeader,#templateUpperBody,#templateColumns,#templateLowerBody,#templateFooter{
            max-width:600px !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .columnsContainer{
            display:block!important;
            max-width:600px !important;
            padding-bottom:18px !important;
            padding-left:0 !important;
            width:100%!important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImage{
            height:auto !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer{
            max-width:100% !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnBoxedTextContentContainer{
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupContent{
            padding:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
            padding-top:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardTopImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
            padding-top:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardBottomImageContent{
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockInner{
            padding-top:0 !important;
            padding-bottom:0 !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockOuter{
            padding-top:9px !important;
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnTextContent,.mcnBoxedTextContentColumn{
            padding-right:18px !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
            padding-right:18px !important;
            padding-bottom:0 !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcpreview-image-uploader{
            display:none !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 1
    @tip Make the first-level headings larger in size for better readability on small screens.
    */
        h1{
            /*@editable*/font-size:24px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 2
    @tip Make the second-level headings larger in size for better readability on small screens.
    */
        h2{
            /*@editable*/font-size:20px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 3
    @tip Make the third-level headings larger in size for better readability on small screens.
    */
        h3{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 4
    @tip Make the fourth-level headings larger in size for better readability on small screens.
    */
        h4{
            /*@editable*/font-size:16px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Boxed Text
    @tip Make the boxed text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .mcnBoxedTextContentContainer .mcnTextContent,.mcnBoxedTextContentContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Visibility
    @tip Set the visibility of the email preheader on small screens. You can hide it to save space.
    */
        #templatePreheader{
            /*@editable*/display:block !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Text
    @tip Make the preheader text larger in size for better readability on small screens.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Header Text
    @tip Make the header text larger in size for better readability on small screens.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:center !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Upper Body Text
    @tip Make the upper body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Left Column Text
    @tip Make the left column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Right Column Text
    @tip Make the right column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Lower Body Text
    @tip Make the lower body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section footer text
    @tip Make the body content text larger in size for better readability on small screens.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:center !important;
        }

}</style></head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <!--*|IF:MC_PREVIEW_TEXT|*-->
        <!--[if !gte mso 9]><!----><span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;">*|MC_PREVIEW_TEXT|*</span><!--<![endif]-->
        <!--*|END:IF|*-->
        <center>
            <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                <tr>
                    <td align="center" valign="top" id="bodyCell">
                        <!-- BEGIN TEMPLATE // -->
                        <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateContainer" style="border-top:0 !important;">
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN PREHEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templatePreheader">
                                        <tr>
                                            <td valign="top" class="preheaderContainer" style="padding-top:9px; padding-bottom:9px"></td>
                                        </tr>
                                    </table>
                                    <!-- // END PREHEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN HEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateHeader">
                                        <tr>
                                            <td valign="top" class="headerContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <span style="font-size:24px"><span style="color:#2e2e2e"><strong>Thank you for registering for the Planmeca Dream Clinic Show and W&amp;H Innovation day.</strong></span></span>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;border: 1px none;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        <p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Dear Doctor,</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">We have your seat reserved for you and a great evening planned.<br>
So just sit back and relax, as we take you to the world of<br>
<span style="font-size:15px"><strong>Digital Dentistry and the Latest cutting-edge Technologies.</strong></span><br>
<br>
As a special guest of Planmeca and W&amp;H you will be welcomed at<br>
<strong><span style="font-size:15px">Hotel CROWNE PLAZA PUNE CITY CENTRE.</span></strong><br>
on 11th of November 2017. The program would start by 7.00 pm<br>
&nbsp;</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;"><a href="https://www.google.co.in/maps/place/Crowne+Plaza+Pune+City+Centre/@18.5312771,73.8746446,17z/data" target="_blank">Get Directions</a></p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;">
    <tbody class="mcnImageBlockOuter">
            <tr>
                <td valign="top" style="padding:9px" class="mcnImageBlockInner">
                    <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width:100%;">
                        <tbody><tr>
                            <td class="mcnImageContent" valign="top" style="padding-right: 9px; padding-left: 9px; padding-top: 0; padding-bottom: 0; text-align:center;">
                                
                                    
                                        <img align="center" alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/a483308c-06fe-41a5-9e9c-b99bf15386ce.jpg" width="564" style="max-width:732px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="mcnImage">
                                    
                                
                            </td>
                        </tr>
                    </tbody></table>
                </td>
            </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END HEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN UPPER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateUpperBody">
                                        <tr>
                                            <td valign="top" class="upperBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <strong id="docs-internal-guid-f7d23ce9-90f5-7b91-9087-f35eb3fb1847">Here is a sneak-peak of few products at the event !</strong>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END UPPER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN COLUMNS // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateColumns">
                                        <tr>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="leftColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="rightColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // END COLUMNS -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN LOWER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateLowerBody">
                                        <tr>
                                            <td valign="top" class="lowerBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/CADCAM/CADCAM-for-dental-clinics/planmeca-planmill-40/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/63e53725-831a-4c62-899c-0d622367c401.jpeg" width="564" style="max-width:800px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <p dir="ltr" style="text-align: center;"><strong>PlanMill 40 S</strong><br>
The smart and connected mill</p>

        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.wh.com/en_global/dental-products/oralsurgery-implantology/surgical-devices/implantmed/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/27a20f5e-76b4-435b-bc1d-858c0032ce99.jpg" width="564" style="max-width:1200px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong>Implantmed</strong><br>
New Surgical Unit for a Stable Implant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="right" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/na/CADCAM/cadcam-for-chairside/planmeca-emerald/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/acf340f9-d3b7-49b5-9af8-ac892490f83b.jpg" width="564" style="max-width:1280px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong id="docs-internal-guid-ce30127a-90d6-18d7-06b7-5c836a9963ee">Planmeca Emerald</strong><br>
Go Beyond The Standard. Be Brilliant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 1px solid #EAEAEA;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        &nbsp;
<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">If you have any questions please contact<br>
<strong>Mr. Anshul / Gopal on 9212340720 / 9899897404</strong></p>

<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Thank you and see you at the event !</p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END LOWER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN FOOTER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateFooter">
                                        <tr>
                                            <td valign="top" class="footerContainer" style="padding-top:9px; padding-bottom:9px;"></td>
                                        </tr>
                                    </table>
                                    <!-- // END FOOTER -->
                                </td>
                            </tr>
                        </table>
                        <!-- // END TEMPLATE -->
                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>');
                                break;
                            case 'Cochin (13th November)':
                                $tmp["sms_result"] = sendPlanmecaRegistrationSMS($tmp["name"], $tmp["mobile"], "Thank you for your confirmation. We look forward to welcoming you at the event on 13th of Nov at Le Meridien, Maradu. RSVP 9900422007/9739011368\nTeam Planmeca India and W&H India");
//                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], "Thank you for your confirmation. We look forward to welcoming you at the event on 13th of Nov at Le Meridien, Maradu. RSVP 9900422007/9739011368<br>Team Planmeca India and W&H India");
                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], '<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    <head>
        <!-- NAME: SUBTLE -->
        <!--[if gte mso 15]>
        <xml>
            <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
        <![endif]-->
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>*|MC:SUBJECT|*</title>
        
    <style type="text/css">
        p{
            margin:10px 0;
            padding:0;
        }
        table{
            border-collapse:collapse;
        }
        h1,h2,h3,h4,h5,h6{
            display:block;
            margin:0;
            padding:0;
        }
        img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
        }
        body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
        .mcnPreviewText{
            display:none !important;
        }
        #outlook a{
            padding:0;
        }
        img{
            -ms-interpolation-mode:bicubic;
        }
        table{
            mso-table-lspace:0pt;
            mso-table-rspace:0pt;
        }
        .ReadMsgBody{
            width:100%;
        }
        .ExternalClass{
            width:100%;
        }
        p,a,li,td,blockquote{
            mso-line-height-rule:exactly;
        }
        a[href^=tel],a[href^=sms]{
            color:inherit;
            cursor:default;
            text-decoration:none;
        }
        p,a,li,td,body,table,blockquote{
            -ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
            line-height:100%;
        }
        a[x-apple-data-detectors]{
            color:inherit !important;
            text-decoration:none !important;
            font-size:inherit !important;
            font-family:inherit !important;
            font-weight:inherit !important;
            line-height:inherit !important;
        }
        a.mcnButton{
            display:block;
        }
        .mcnImage{
            vertical-align:bottom;
        }
        .mcnTextContent{
            word-break:break-word;
        }
        .mcnTextContent img{
            height:auto !important;
        }
        .mcnDividerBlock{
            table-layout:fixed !important;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        body,#bodyTable{
            /*@editable*/background-color:#FAFAFA;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        #bodyCell{
            /*@editable*/border-top:0;
        }
    /*
    @tab Page
    @section email border
    @tip Set the border for your email.
    */
        #templateContainer{
            /*@editable*/border:1px solid #2e2e2e;
        }
    /*
    @tab Page
    @section heading 1
    @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
    @style heading 1
    */
        h1{
            /*@editable*/color:#606060 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:40px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-1px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 2
    @tip Set the styling for all second-level headings in your emails.
    @style heading 2
    */
        h2{
            /*@editable*/color:#404040 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:26px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.75px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 3
    @tip Set the styling for all third-level headings in your emails.
    @style heading 3
    */
        h3{
            /*@editable*/color:#DC143C !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.5px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 4
    @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
    @style heading 4
    */
        h4{
            /*@editable*/color:#808080 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader style
    @tip Set the background color and borders for your email preheader area.
    */
        #templatePreheader{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:1px solid #222222;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Preheader
    @section preheader text
    @tip Set the styling for your email preheader text. Choose a size and color that is easy to read.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .preheaderContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Header
    @section header style
    @tip Set the background color and borders for your email header area.
    */
        #templateHeader{
            /*@editable*/background-color:#transparent;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:repeat-y;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #606060;
        }
    /*
    @tab Header
    @section header text
    @tip Set the styling for your email header text. Choose a size and color that is easy to read.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Header
    @section header link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .headerContainer .mcnTextContent a{
            /*@editable*/color:#6DC6DD;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Upper Body
    @section upper body style
    @tip Set the background color and borders for your email upper body area.
    */
        #templateUpperBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Upper Body
    @section upper body text
    @tip Set the styling for your email upper body text. Choose a size and color that is easy to read.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Upper Body
    @section upper body link
    @tip Set the styling for your email upper body links. Choose a color that helps them stand out from your text.
    */
        .upperBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section column style
    @tip Set the background color and borders for your email columns area.
    */
        #templateColumns{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Columns
    @section left column text
    @tip Set the styling for your email left column text. Choose a size and color that is easy to read.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section left column link
    @tip Set the styling for your email left column links. Choose a color that helps them stand out from your text.
    */
        .leftColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section right column text
    @tip Set the styling for your email right column text. Choose a size and color that is easy to read.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section right column link
    @tip Set the styling for your email right column links. Choose a color that helps them stand out from your text.
    */
        .rightColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Lower Body
    @section lower body style
    @tip Set the background color and borders for your email lower body area.
    */
        #templateLowerBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Lower Body
    @section lower body text
    @tip Set the styling for your email lower body text. Choose a size and color that is easy to read.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Lower Body
    @section lower body link
    @tip Set the styling for your email lower body links. Choose a color that helps them stand out from your text.
    */
        .lowerBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Footer
    @section footer style
    @tip Set the background color and borders for your email footer area.
    */
        #templateFooter{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Footer
    @section footer text
    @tip Set the styling for your email footer text. Choose a size and color that is easy to read.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Footer
    @section footer link
    @tip Set the styling for your email footer links. Choose a color that helps them stand out from your text.
    */
        .footerContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    @media only screen and (max-width: 480px){
        body,table,td,p,a,li,blockquote{
            -webkit-text-size-adjust:none !important;
        }

}   @media only screen and (max-width: 480px){
        body{
            width:100% !important;
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        #templateContainer,#templatePreheader,#templateHeader,#templateUpperBody,#templateColumns,#templateLowerBody,#templateFooter{
            max-width:600px !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .columnsContainer{
            display:block!important;
            max-width:600px !important;
            padding-bottom:18px !important;
            padding-left:0 !important;
            width:100%!important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImage{
            height:auto !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer{
            max-width:100% !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnBoxedTextContentContainer{
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupContent{
            padding:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
            padding-top:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardTopImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
            padding-top:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardBottomImageContent{
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockInner{
            padding-top:0 !important;
            padding-bottom:0 !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockOuter{
            padding-top:9px !important;
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnTextContent,.mcnBoxedTextContentColumn{
            padding-right:18px !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
            padding-right:18px !important;
            padding-bottom:0 !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcpreview-image-uploader{
            display:none !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 1
    @tip Make the first-level headings larger in size for better readability on small screens.
    */
        h1{
            /*@editable*/font-size:24px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 2
    @tip Make the second-level headings larger in size for better readability on small screens.
    */
        h2{
            /*@editable*/font-size:20px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 3
    @tip Make the third-level headings larger in size for better readability on small screens.
    */
        h3{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 4
    @tip Make the fourth-level headings larger in size for better readability on small screens.
    */
        h4{
            /*@editable*/font-size:16px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Boxed Text
    @tip Make the boxed text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .mcnBoxedTextContentContainer .mcnTextContent,.mcnBoxedTextContentContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Visibility
    @tip Set the visibility of the email preheader on small screens. You can hide it to save space.
    */
        #templatePreheader{
            /*@editable*/display:block !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Text
    @tip Make the preheader text larger in size for better readability on small screens.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Header Text
    @tip Make the header text larger in size for better readability on small screens.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:center !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Upper Body Text
    @tip Make the upper body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Left Column Text
    @tip Make the left column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Right Column Text
    @tip Make the right column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Lower Body Text
    @tip Make the lower body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section footer text
    @tip Make the body content text larger in size for better readability on small screens.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:center !important;
        }

}</style></head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <!--*|IF:MC_PREVIEW_TEXT|*-->
        <!--[if !gte mso 9]><!----><span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;">*|MC_PREVIEW_TEXT|*</span><!--<![endif]-->
        <!--*|END:IF|*-->
        <center>
            <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                <tr>
                    <td align="center" valign="top" id="bodyCell">
                        <!-- BEGIN TEMPLATE // -->
                        <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateContainer" style="border-top:0 !important;">
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN PREHEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templatePreheader">
                                        <tr>
                                            <td valign="top" class="preheaderContainer" style="padding-top:9px; padding-bottom:9px"></td>
                                        </tr>
                                    </table>
                                    <!-- // END PREHEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN HEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateHeader">
                                        <tr>
                                            <td valign="top" class="headerContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <span style="font-size:24px"><span style="color:#2e2e2e"><strong>Thank you for registering for the Planmeca Dream Clinic Show and W&amp;H Innovation day.</strong></span></span>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;border: 1px none;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        <p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Dear Doctor,</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">We have your seat reserved for you and a great evening planned.<br>
So just sit back and relax, as we take you to the world of<br>
<span style="font-size:15px"><strong>Digital Dentistry and the Latest cutting-edge Technologies.</strong></span><br>
<br>
As a special guest of Planmeca and W&amp;H you will be welcomed at<br>
<strong><span style="font-size:15px">Hotel Le Meridien, Maradu</span></strong><br>
on 13th of November 2017. The program would start by 7.00 pm<br>
&nbsp;</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;"><a href="https://www.google.co.in/maps/place/Le+Meridien+Kochi" target="_blank">Get Directions</a></p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;">
    <tbody class="mcnImageBlockOuter">
            <tr>
                <td valign="top" style="padding:9px" class="mcnImageBlockInner">
                    <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width:100%;">
                        <tbody><tr>
                            <td class="mcnImageContent" valign="top" style="padding-right: 9px; padding-left: 9px; padding-top: 0; padding-bottom: 0; text-align:center;">
                                
                                    
                                        <img align="center" alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/a483308c-06fe-41a5-9e9c-b99bf15386ce.jpg" width="564" style="max-width:732px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="mcnImage">
                                    
                                
                            </td>
                        </tr>
                    </tbody></table>
                </td>
            </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END HEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN UPPER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateUpperBody">
                                        <tr>
                                            <td valign="top" class="upperBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <strong id="docs-internal-guid-f7d23ce9-90f5-7b91-9087-f35eb3fb1847">Here is a sneak-peak of few products at the event !</strong>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END UPPER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN COLUMNS // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateColumns">
                                        <tr>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="leftColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="rightColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // END COLUMNS -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN LOWER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateLowerBody">
                                        <tr>
                                            <td valign="top" class="lowerBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/CADCAM/CADCAM-for-dental-clinics/planmeca-planmill-40/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/63e53725-831a-4c62-899c-0d622367c401.jpeg" width="564" style="max-width:800px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <p dir="ltr" style="text-align: center;"><strong>PlanMill 40 S</strong><br>
The smart and connected mill</p>

        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.wh.com/en_global/dental-products/oralsurgery-implantology/surgical-devices/implantmed/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/27a20f5e-76b4-435b-bc1d-858c0032ce99.jpg" width="564" style="max-width:1200px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong>Implantmed</strong><br>
New Surgical Unit for a Stable Implant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="right" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/na/CADCAM/cadcam-for-chairside/planmeca-emerald/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/acf340f9-d3b7-49b5-9af8-ac892490f83b.jpg" width="564" style="max-width:1280px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong id="docs-internal-guid-ce30127a-90d6-18d7-06b7-5c836a9963ee">Planmeca Emerald</strong><br>
Go Beyond The Standard. Be Brilliant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 1px solid #EAEAEA;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        &nbsp;
<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">If you have any questions please contact<br>
<strong>Mr. Anshul / Gopal on 9212340720 / 9899897404</strong></p>

<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Thank you and see you at the event !</p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END LOWER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN FOOTER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateFooter">
                                        <tr>
                                            <td valign="top" class="footerContainer" style="padding-top:9px; padding-bottom:9px;"></td>
                                        </tr>
                                    </table>
                                    <!-- // END FOOTER -->
                                </td>
                            </tr>
                        </table>
                        <!-- // END TEMPLATE -->
                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>');
                                break;
                            case 'Bangalore (14th November)':
                                $tmp["sms_result"] = sendPlanmecaRegistrationSMS($tmp["name"], $tmp["mobile"], "Thank you for your confirmation. We look forward to welcoming you at the event on 14th of Nov at Le Meridien, Bangalore. RSVP 9900422007/9739011368\nTeam Planmeca India and W&H India");
//                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], "Thank you for your confirmation. We look forward to welcoming you at the event on 14th of Nov at Le Meridien, Bangalore. RSVP 9900422007/9739011368<br>Team Planmeca India and W&H India");
                                $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], '<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    <head>
        <!-- NAME: SUBTLE -->
        <!--[if gte mso 15]>
        <xml>
            <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
        <![endif]-->
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>*|MC:SUBJECT|*</title>
        
    <style type="text/css">
        p{
            margin:10px 0;
            padding:0;
        }
        table{
            border-collapse:collapse;
        }
        h1,h2,h3,h4,h5,h6{
            display:block;
            margin:0;
            padding:0;
        }
        img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
        }
        body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
        .mcnPreviewText{
            display:none !important;
        }
        #outlook a{
            padding:0;
        }
        img{
            -ms-interpolation-mode:bicubic;
        }
        table{
            mso-table-lspace:0pt;
            mso-table-rspace:0pt;
        }
        .ReadMsgBody{
            width:100%;
        }
        .ExternalClass{
            width:100%;
        }
        p,a,li,td,blockquote{
            mso-line-height-rule:exactly;
        }
        a[href^=tel],a[href^=sms]{
            color:inherit;
            cursor:default;
            text-decoration:none;
        }
        p,a,li,td,body,table,blockquote{
            -ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
            line-height:100%;
        }
        a[x-apple-data-detectors]{
            color:inherit !important;
            text-decoration:none !important;
            font-size:inherit !important;
            font-family:inherit !important;
            font-weight:inherit !important;
            line-height:inherit !important;
        }
        a.mcnButton{
            display:block;
        }
        .mcnImage{
            vertical-align:bottom;
        }
        .mcnTextContent{
            word-break:break-word;
        }
        .mcnTextContent img{
            height:auto !important;
        }
        .mcnDividerBlock{
            table-layout:fixed !important;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        body,#bodyTable{
            /*@editable*/background-color:#FAFAFA;
        }
    /*
    @tab Page
    @section background style
    @tip Set the background color and top border for your email. You may want to choose colors that match your company branding.
    */
        #bodyCell{
            /*@editable*/border-top:0;
        }
    /*
    @tab Page
    @section email border
    @tip Set the border for your email.
    */
        #templateContainer{
            /*@editable*/border:1px solid #2e2e2e;
        }
    /*
    @tab Page
    @section heading 1
    @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
    @style heading 1
    */
        h1{
            /*@editable*/color:#606060 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:40px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-1px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 2
    @tip Set the styling for all second-level headings in your emails.
    @style heading 2
    */
        h2{
            /*@editable*/color:#404040 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:26px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.75px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 3
    @tip Set the styling for all third-level headings in your emails.
    @style heading 3
    */
        h3{
            /*@editable*/color:#DC143C !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:-.5px;
            /*@editable*/text-align:left;
        }
    /*
    @tab Page
    @section heading 4
    @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
    @style heading 4
    */
        h4{
            /*@editable*/color:#808080 !important;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/font-style:normal;
            /*@editable*/font-weight:bold;
            /*@editable*/line-height:125%;
            /*@editable*/letter-spacing:normal;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader style
    @tip Set the background color and borders for your email preheader area.
    */
        #templatePreheader{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:1px solid #222222;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Preheader
    @section preheader text
    @tip Set the styling for your email preheader text. Choose a size and color that is easy to read.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Preheader
    @section preheader link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .preheaderContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Header
    @section header style
    @tip Set the background color and borders for your email header area.
    */
        #templateHeader{
            /*@editable*/background-color:#transparent;
            /*@editable*/background-image:none;
            /*@editable*/background-repeat:repeat-y;
            /*@editable*/background-position:center;
            /*@editable*/background-size:cover;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #606060;
        }
    /*
    @tab Header
    @section header text
    @tip Set the styling for your email header text. Choose a size and color that is easy to read.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Header
    @section header link
    @tip Set the styling for your email header links. Choose a color that helps them stand out from your text.
    */
        .headerContainer .mcnTextContent a{
            /*@editable*/color:#6DC6DD;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Upper Body
    @section upper body style
    @tip Set the background color and borders for your email upper body area.
    */
        #templateUpperBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Upper Body
    @section upper body text
    @tip Set the styling for your email upper body text. Choose a size and color that is easy to read.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Upper Body
    @section upper body link
    @tip Set the styling for your email upper body links. Choose a color that helps them stand out from your text.
    */
        .upperBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section column style
    @tip Set the background color and borders for your email columns area.
    */
        #templateColumns{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Columns
    @section left column text
    @tip Set the styling for your email left column text. Choose a size and color that is easy to read.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section left column link
    @tip Set the styling for your email left column links. Choose a color that helps them stand out from your text.
    */
        .leftColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Columns
    @section right column text
    @tip Set the styling for your email right column text. Choose a size and color that is easy to read.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:16px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:left;
        }
    /*
    @tab Columns
    @section right column link
    @tip Set the styling for your email right column links. Choose a color that helps them stand out from your text.
    */
        .rightColumnContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Lower Body
    @section lower body style
    @tip Set the background color and borders for your email lower body area.
    */
        #templateLowerBody{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:1px solid #E6E6E6;
        }
    /*
    @tab Lower Body
    @section lower body text
    @tip Set the styling for your email lower body text. Choose a size and color that is easy to read.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/color:#606060;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:18px;
            /*@editable*/line-height:150%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Lower Body
    @section lower body link
    @tip Set the styling for your email lower body links. Choose a color that helps them stand out from your text.
    */
        .lowerBodyContainer .mcnTextContent a{
            /*@editable*/color:#DC143C;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    /*
    @tab Footer
    @section footer style
    @tip Set the background color and borders for your email footer area.
    */
        #templateFooter{
            /*@editable*/background-color:#FFFFFF;
            /*@editable*/border-top:0;
            /*@editable*/border-bottom:0;
        }
    /*
    @tab Footer
    @section footer text
    @tip Set the styling for your email footer text. Choose a size and color that is easy to read.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/color:#AAAAAA;
            /*@editable*/font-family:Helvetica;
            /*@editable*/font-size:11px;
            /*@editable*/line-height:125%;
            /*@editable*/text-align:center;
        }
    /*
    @tab Footer
    @section footer link
    @tip Set the styling for your email footer links. Choose a color that helps them stand out from your text.
    */
        .footerContainer .mcnTextContent a{
            /*@editable*/color:#606060;
            /*@editable*/font-weight:normal;
            /*@editable*/text-decoration:underline;
        }
    @media only screen and (max-width: 480px){
        body,table,td,p,a,li,blockquote{
            -webkit-text-size-adjust:none !important;
        }

}   @media only screen and (max-width: 480px){
        body{
            width:100% !important;
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        #templateContainer,#templatePreheader,#templateHeader,#templateUpperBody,#templateColumns,#templateLowerBody,#templateFooter{
            max-width:600px !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .columnsContainer{
            display:block!important;
            max-width:600px !important;
            padding-bottom:18px !important;
            padding-left:0 !important;
            width:100%!important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImage{
            height:auto !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer{
            max-width:100% !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnBoxedTextContentContainer{
            min-width:100% !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupContent{
            padding:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
            padding-top:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardTopImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
            padding-top:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardBottomImageContent{
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockInner{
            padding-top:0 !important;
            padding-bottom:0 !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageGroupBlockOuter{
            padding-top:9px !important;
            padding-bottom:9px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnTextContent,.mcnBoxedTextContentColumn{
            padding-right:18px !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
            padding-right:18px !important;
            padding-bottom:0 !important;
            padding-left:18px !important;
        }

}   @media only screen and (max-width: 480px){
        .mcpreview-image-uploader{
            display:none !important;
            width:100% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 1
    @tip Make the first-level headings larger in size for better readability on small screens.
    */
        h1{
            /*@editable*/font-size:24px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 2
    @tip Make the second-level headings larger in size for better readability on small screens.
    */
        h2{
            /*@editable*/font-size:20px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 3
    @tip Make the third-level headings larger in size for better readability on small screens.
    */
        h3{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section heading 4
    @tip Make the fourth-level headings larger in size for better readability on small screens.
    */
        h4{
            /*@editable*/font-size:16px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Boxed Text
    @tip Make the boxed text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .mcnBoxedTextContentContainer .mcnTextContent,.mcnBoxedTextContentContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Visibility
    @tip Set the visibility of the email preheader on small screens. You can hide it to save space.
    */
        #templatePreheader{
            /*@editable*/display:block !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Preheader Text
    @tip Make the preheader text larger in size for better readability on small screens.
    */
        .preheaderContainer .mcnTextContent,.preheaderContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Header Text
    @tip Make the header text larger in size for better readability on small screens.
    */
        .headerContainer .mcnTextContent,.headerContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:center !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Upper Body Text
    @tip Make the upper body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .upperBodyContainer .mcnTextContent,.upperBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Left Column Text
    @tip Make the left column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .leftColumnContainer .mcnTextContent,.leftColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Right Column Text
    @tip Make the right column text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .rightColumnContainer .mcnTextContent,.rightColumnContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section Lower Body Text
    @tip Make the lower body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
    */
        .lowerBodyContainer .mcnTextContent,.lowerBodyContainer .mcnTextContent p{
            /*@editable*/font-size:18px !important;
            /*@editable*/line-height:125% !important;
            /*@editable*/text-align:left !important;
        }

}   @media only screen and (max-width: 480px){
    /*
    @tab Mobile Styles
    @section footer text
    @tip Make the body content text larger in size for better readability on small screens.
    */
        .footerContainer .mcnTextContent,.footerContainer .mcnTextContent p{
            /*@editable*/font-size:14px !important;
            /*@editable*/line-height:115% !important;
            /*@editable*/text-align:center !important;
        }

}</style></head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <!--*|IF:MC_PREVIEW_TEXT|*-->
        <!--[if !gte mso 9]><!----><span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;">*|MC_PREVIEW_TEXT|*</span><!--<![endif]-->
        <!--*|END:IF|*-->
        <center>
            <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                <tr>
                    <td align="center" valign="top" id="bodyCell">
                        <!-- BEGIN TEMPLATE // -->
                        <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateContainer" style="border-top:0 !important;">
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN PREHEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templatePreheader">
                                        <tr>
                                            <td valign="top" class="preheaderContainer" style="padding-top:9px; padding-bottom:9px"></td>
                                        </tr>
                                    </table>
                                    <!-- // END PREHEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN HEADER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateHeader">
                                        <tr>
                                            <td valign="top" class="headerContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <span style="font-size:24px"><span style="color:#2e2e2e"><strong>Thank you for registering for the Planmeca Dream Clinic Show and W&amp;H Innovation day.</strong></span></span>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;border: 1px none;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        <p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Dear Doctor,</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">We have your seat reserved for you and a great evening planned.<br>
So just sit back and relax, as we take you to the world of<br>
<span style="font-size:15px"><strong>Digital Dentistry and the Latest cutting-edge Technologies.</strong></span><br>
<br>
As a special guest of Planmeca and W&amp;H you will be welcomed at<br>
<strong><span style="font-size:15px">Le Meridien, Bangalore, Zirakpur</span></strong><br>
on 14th of November 2017. The program would start by 7.00 pm<br>
&nbsp;</p>

<p dir="ltr" style="color: #FFFFFF;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;"><a href="https://www.google.co.in/maps/place/Le+Méridien+Bangalore" target="_blank">Get Directions</a></p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;">
    <tbody class="mcnImageBlockOuter">
            <tr>
                <td valign="top" style="padding:9px" class="mcnImageBlockInner">
                    <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width:100%;">
                        <tbody><tr>
                            <td class="mcnImageContent" valign="top" style="padding-right: 9px; padding-left: 9px; padding-top: 0; padding-bottom: 0; text-align:center;">
                                
                                    
                                        <img align="center" alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/a483308c-06fe-41a5-9e9c-b99bf15386ce.jpg" width="564" style="max-width:732px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="mcnImage">
                                    
                                
                            </td>
                        </tr>
                    </tbody></table>
                </td>
            </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END HEADER -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN UPPER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateUpperBody">
                                        <tr>
                                            <td valign="top" class="upperBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width:100%;">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td valign="top" class="mcnTextBlockInner" style="padding-top:9px;">
                <!--[if mso]>
                <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
                <tr>
                <![endif]-->
                
                <!--[if mso]>
                <td valign="top" width="600" style="width:600px;">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width:100%; min-width:100%;" width="100%" class="mcnTextContentContainer">
                    <tbody><tr>
                        
                        <td valign="top" class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;">
                        
                            <strong id="docs-internal-guid-f7d23ce9-90f5-7b91-9087-f35eb3fb1847">Here is a sneak-peak of few products at the event !</strong>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if mso]>
                </td>
                <![endif]-->
                
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END UPPER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN COLUMNS // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateColumns">
                                        <tr>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="leftColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td align="left" valign="top" width="50%" class="columnsContainer">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateColumn">
                                                    <tr>
                                                        <td valign="top" class="rightColumnContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%; border-top: 0px;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // END COLUMNS -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN LOWER BODY // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateLowerBody">
                                        <tr>
                                            <td valign="top" class="lowerBodyContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/CADCAM/CADCAM-for-dental-clinics/planmeca-planmill-40/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/63e53725-831a-4c62-899c-0d622367c401.jpeg" width="564" style="max-width:800px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <p dir="ltr" style="text-align: center;"><strong>PlanMill 40 S</strong><br>
The smart and connected mill</p>

        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="center" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.wh.com/en_global/dental-products/oralsurgery-implantology/surgical-devices/implantmed/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/27a20f5e-76b4-435b-bc1d-858c0032ce99.jpg" width="564" style="max-width:1200px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong>Implantmed</strong><br>
New Surgical Unit for a Stable Implant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnCaptionBlock">
    <tbody class="mcnCaptionBlockOuter">
        <tr>
            <td class="mcnCaptionBlockInner" valign="top" style="padding:9px;">
                

<table align="left" border="0" cellpadding="0" cellspacing="0" class="mcnCaptionBottomContent" width="false">
    <tbody><tr>
        <td class="mcnCaptionBottomImageContent" align="right" valign="top" style="padding:0 9px 9px 9px;">
        
            
            <a href="http://www.planmeca.com/na/CADCAM/cadcam-for-chairside/planmeca-emerald/" title="" class="" target="_blank">
            

            <img alt="" src="https://gallery.mailchimp.com/5b28e159883fb574141a70b46/images/acf340f9-d3b7-49b5-9af8-ac892490f83b.jpg" width="564" style="max-width:1280px;" class="mcnImage">
            </a>
        
        </td>
    </tr>
    <tr>
        <td class="mcnTextContent" valign="top" style="padding: 0px 9px; text-align: center;" width="564">
            <strong id="docs-internal-guid-ce30127a-90d6-18d7-06b7-5c836a9963ee">Planmeca Emerald</strong><br>
Go Beyond The Standard. Be Brilliant
        </td>
    </tr>
</tbody></table>





            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 1px solid #EAEAEA;">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
<!--            
                <td class="mcnDividerBlockInner" style="padding: 18px;">
                <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
-->
            </td>
        </tr>
    </tbody>
</table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
    <!--[if gte mso 9]>
    <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
    <![endif]-->
    <tbody class="mcnBoxedTextBlockOuter">
        <tr>
            <td valign="top" class="mcnBoxedTextBlockInner">
                
                <!--[if gte mso 9]>
                <td align="center" valign="top" ">
                <![endif]-->
                <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                    <tbody><tr>
                        
                        <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">
                        
                            <table border="0" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #006E99;">
                                <tbody><tr>
                                    <td valign="top" class="mcnTextContent" style="padding: 18px;color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">
                                        &nbsp;
<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">If you have any questions please contact<br>
<strong>Mr. Anshul / Gopal on 9212340720 / 9899897404</strong></p>

<p dir="ltr" style="color: #F2F2F2;font-family: Helvetica;font-size: 14px;font-weight: normal;text-align: center;">Thank you and see you at the event !</p>

                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                    </tr>
                </tbody></table>
                <!--[if gte mso 9]>
                </td>
                <![endif]-->
                
                <!--[if gte mso 9]>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
                                        </tr>
                                    </table>
                                    <!-- // END LOWER BODY -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- BEGIN FOOTER // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="templateFooter">
                                        <tr>
                                            <td valign="top" class="footerContainer" style="padding-top:9px; padding-bottom:9px;"></td>
                                        </tr>
                                    </table>
                                    <!-- // END FOOTER -->
                                </td>
                            </tr>
                        </table>
                        <!-- // END TEMPLATE -->
                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>');
                                break;
                            default:
                                // $tmp["sms_result"] = sendEventRegisterationSMS($tmp["name"], $tmp["mobile"], "Thank you for your confirmation. We look forward to welcoming you at the event on 8 th of Nov at Hotel Park Plaza, Zirakpur. RSVP 9899897404/9212340720\nTeam Planmeca India and W&H India");
                                // $tmp["mail_result"] = sendPlanmecaRegistrationEmail($tmp["name"], $tmp["email"], "Thank you for your confirmation. We look forward to welcoming you at the event on 8 th of Nov at Hotel Park Plaza, Zirakpur. RSVP 9899897404/9212340720\nTeam Planmeca India and W&H India");
                                break;
                        }
                        
                        array_push($user_details, $tmp);
                    }
                }
            
                $no_of_sms = sizeof($user_details);
      
                if($no_of_sms>0){
                    $response["error"] = false;
                    $response["message"] = "SMS and Mail sent successfully to ".$no_of_sms." Users";
                    $response["users"] = $user_details;
                    echoResponse(200, $response);
                } else {
                    $response["error"] = false;
                    $response["message"] = "No new user registered";
                    unset($response["users"]);
                    echoResponse(200, $response);
                }
            } catch(Exception $e) {
                $response["error"] = true;
                $response["message"] = $e->getMessage();
                echoResponse(200, $response);
            }    
        });






    
function siteURL(){
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol.$domainName."/";
}


/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    
    //header('Content-Type: application/json; charset=UTF-8');
    $app->contentType('application/json;');
    echo json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
//    $app->contentType('text/xml');
//    echo xml_encode($response);
}

function getHeaders(){
    $headers = array();
    foreach ($_SERVER as $k => $v) {
        if (substr($k, 0, 5) == "HTTP_") {
            $k = strtolower(str_replace('_', '-', substr($k, 5)));
//            $k = str_replace(' ', '_', ucwords(strtolower($k)));
            $headers[$k] = $v;
        }
    }
    return $headers;
}

function sendOTPSMS($mobile, $otp){
	global $sms_gateway;
	switch ($sms_gateway) {
		case 1:
    		// check balance messages
    		//http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    		$username="actiknow";
    		$password="actiknow@2017";
		//    $username="shout";
		//    $password="shout@share";
	  		$message= $otp." is your login OTP for ISDental application.";
  			$sender="INSPLY"; //ex:INVITE
  			$mobile_number = $mobile;
  			$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
  			$ch = curl_init($url);
  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  			$curl_scraped_page = curl_exec($ch);
  			curl_close($ch);
      		break;    
    	case 2:
	    	$mclass = new sendSms();
    		$message2= $otp." is your login OTP for ISDental application.";
    		$mclass->sendSmsToUser($message2, $mobile, "");
      		break;
  	}
	return true;
}

function sendEventRegisterationSMS($user_name, $user_mobile, $event_name){
	global $sms_gateway;
  	switch ($sms_gateway) {
    	case 1:
    		// check balance messages
    		//http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    		$username="actiknow";
    		$password="actiknow@2017";
			//    $username="shout";
			//    $password="shout@share";
  			$message= "Dear ".$user_name.",\nCongratulations! You have been registered for ".$event_name.". Please show this message at registration desk and get your entry badge. Thanks.";
  			$sender="INSPLY"; //ex:INVITE
	  		$mobile_number = $user_mobile;
  			$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
  			$ch = curl_init($url);
  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  			$curl_scraped_page = curl_exec($ch);
  			curl_close($ch);
      		break;    
    	case 2:
    		$mclass = new sendSms();
    		$message2= "Dear ".$user_name.",\nCongratulations! You have been registered for ".$event_name.". Please show this message at registration desk and get your entry badge. Thanks.";
    		$mclass->sendSmsToUser($message2, $user_mobile, "");
      		break;
  	}
	return true;
}

function sendEventRegisterationSMS2($user_name, $user_mobile, $event_name){
	global $sms_gateway;
  	switch ($sms_gateway) {
	    case 1:
		    // check balance messages
		    //http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    		$username="actiknow";
    		$password="actiknow@2017";
			//    $username="shout";
			//    $password="shout@share";
  			$message= $event_name." - Now Get Contact Details of all Exhibitors & Stall Nos. Post Enquiry to them. Smart Visitor Download ISDental APP Now http://bit.ly/ExpodentB";
  			$sender="INSPLY"; //ex:INVITE
  			$mobile_number = $user_mobile;
  			$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
  			$ch = curl_init($url);
  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  			$curl_scraped_page = curl_exec($ch);
  			curl_close($ch);
      		break;    
    	case 2:
    		$mclass = new sendSms();
  			$message2= $event_name." - Now Get Contact Details of all Exhibitors & Stall Nos. Post Enquiry to them. Smart Visitor Download ISDental APP Now http://bit.ly/ExpodentB";
    		$mclass->sendSmsToUser($message2, $user_mobile, "");
      		break;
  	}
	return true;
}

function sendWelcomeSMS($user_mobile){
	global $sms_gateway;
	switch ($sms_gateway) {
    	case 1:
    		// check balance messages
    		//http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    		$username="actiknow";
    		$password="actiknow@2017";
		//    $username="shout";
		//    $password="shout@share";
  			$message= "Hi, You have joined 20,000+ Dentists on ISDental App. Now get contact details of ALL Dental Brands and Dealers. Get latest update about upcoming events. Thanks";
  			$sender="INSPLY"; //ex:INVITE
  			$mobile_number = $user_mobile;
  			$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
  			$ch = curl_init($url);
  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  			$curl_scraped_page = curl_exec($ch);
  			curl_close($ch);
      		break;    
    	case 2:
    		$mclass = new sendSms();
    		$message2= "Hi, You have joined 20,000+ Dentists on ISDental App. Now get contact details of ALL Dental Brands and Dealers. Get latest update about upcoming events. Thanks";
    		$mclass->sendSmsToUser($message2, $user_mobile, "");
      		break;
  	}
	return true;
}

function sendWelcomeEmail($user_email, $user_name){
    try{
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
            
        $mail->addAddress($user_email);
            
        //$mail->isHTML(true);
            
        $mail->Subject = "Congratulations! You have joined 20,000+ Dentists";
        $mail->Body = "
Hi ".$user_name.",

Welcome to ISDental App. 

Now you need not keep multiple visiting cards. Find contact details of ALL Dental Brands and Dealers on this app.

Also get latest updates about upcoming expos, conferences and workshops and never miss anything important around you.

We're glad to have you here, kindly contact us at isdental@indiasupply.com for any assistance.

Best Regards,

Team IndiaSupply";
        //$mail->AltBody = "This is the plain text version of the email content";
            
        if(!$mail->send()) {
                //    echo "Mailer Error: " . $mail->ErrorInfo;
            return false;
        } else {
            return true;
        }
    } catch (phpmailerException $e) {
        echo $e->errorMessage();
    }
    return false;
}



function sendPlanmecaRegistrationSMS($user_name, $user_mobile, $text){
    global $sms_gateway;
    switch ($sms_gateway) {
        case 1:
            $response = array();
            $username="actiknow";
            $password="actiknow@2017";
            $message= $text;
            $sender="INSPLY"; //ex:INVITE
            $mobile_number = $user_mobile;
            $url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $curl_scraped_page = curl_exec($ch);
            curl_close($ch);
            $response["error"] = false;
            $response["message"] = "SMS sent successfully";
            $response["content"] = $message;
            $response["number"] = $mobile_number;
            $response["server_response"] = $curl_scraped_page;
            $response["status"] = 1;
            return $response;
            break;
        case 2:
//            $response = array();
            $mclass = new sendSms();
            $message2= $text;
            $response = $mclass->sendSmsToUser($message2, $user_mobile, "");
//            $response["error"] = false;
            return $response;
            break;
    }
}

function sendPlanmecaRegistrationEmail($user_name, $user_email, $text){
    global $mail_gateway;
    switch($mail_gateway){
        case 1:
            try{
                $mail = new PHPMailer;
                $mail->isSMTP();            
                $mail->Host = "smtp.gmail.com";
                $mail->SMTPAuth = true;                          
                $mail->Username = "indiasupply55@gmail.com";                 
                $mail->Password = "indiasupply#2016";                           
                $mail->SMTPSecure = "tls";                           
                $mail->Port = 587;                                   
                $mail->From = "noreply@indiasupply.com";
                $mail->FromName = "IndiaSupply";
                $mail->addAddress($user_email);
                $mail->Subject = "Congratulations! You have joined 20,000+ Dentists";
                $mail->Body = $text;
                if(!$mail->send()) {
                    return false;
                } else {
                    return true;
                }
            } catch (phpmailerException $e) {
                echo $e->errorMessage();
            }
            break;
        case 2:

// $vars = '{
//  "recipients":[{
//      "to":[{
//      "emailid":"karman.singh@actiknowbi.com",
//      "name":"Karman"}]
//  }],
//  "from":{
//      "emailid":"saurabh.rawat@indiasupply.com",
//      "name":"IndiaSupply"
//  },
//  "subject":"Test Subject",
//  "content":[{
//      "type":"text/html",
//      "value":"Test Email"
//  }]
// }';

            $post = array();
            $post["recipients"] = array();
            $tmp3["to"] = array();
            $tmp = array();
            $tmp["emailid"] = $user_email;
            $tmp["name"] = $user_name;
            array_push($tmp3["to"], $tmp);
            array_push($post["recipients"], $tmp3);
            $post["from"] = array();
            $post["from"]["emailid"] = "saurabh.rawat@indiasupply.com";
            $post["from"]["name"] = "Planmeca";
            $post["subject"] = "Event Registration";
            $post["content"] = array();
            $tmp2 = array();
            $tmp2["type"] = "text/html";
            $tmp2["value"] = $text;
            array_push($post["content"], $tmp2);
            $result = json_encode($post);

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,"http://api.strmailer4.in/v1/api/sendmail");
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $result);
            curl_setopt($ch,CURLOPT_HEADER, true);
            $temp = base64_encode('_user77868d86b3a35957:e90960132ff779a35957e4ae92630787');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Basic '.$temp,
                'Content-Type: application/json'
            ));
          
            $result = curl_exec($ch);
            if($result === false){
                echo "Error Number:".curl_errno($ch)."<br>";
                echo "Error String:".curl_error($ch);
            }
            curl_close($ch);
            return $result;
            break;
    }
    return false;
}

$app->run();
?>