<?php

require_once '../include/Security.php';
require_once '../include/DbHandler.php';
require '.././libs/Slim/Slim.php';
require '.././libs/PHPMailer/PHPMailerAutoload.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$user_id = NULL;

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
            echoRespnse(401, $response);
            $app->stop();
        } else {
            if (array_key_exists("user-login-key", $headers)) {
                $login_key = $headers['user-login-key'];
                if (!$db->isValidUserLoginKey($login_key)) {
                    $response["error"] = true;
                    $response["message"] = "Access Denied. Invalid Login key";
                    echoRespnse(401, $response);
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
        echoRespnse(400, $response);
        $app->stop();
    }
}

$app->get('/test', function () {
	        $response = array();
	        $db = new DbHandler();
	        $response["message"] = "For testing purpose";
	
	
	        echo microtime(true);
	      	$mt = explode('.', microtime(true));
	      	echo "\n".substr($mt[0],2, 8);
	      	echo "\n".substr($mt[1],0,2);
		    echo "\n".$str =substr($mt[0],2, 8).substr($mt[1],0,2);

	
	        
	        
//	        $response["email"] = $email = sendForgetAccessPINEmail("karman.singh@actiknowbi.com", "karman", 1234);
//	        $response["patient_id"] =  $patient_id = $db->getPatientInternalID(1, 'MH111112');
//	        $response["cwd"] = getcwd();
//	        $response["server_url"] = $_SERVER['HTTP_HOST'];
	        echoRespnse(200, $response);
        });

$app->get('/test/keys', 'authenticate', function () {
            global $user_id;
            $response = array();
	        $response["error"] = false;
	        $response["visitor_id"] = $user_id;
	        $response["message"] = "Key validation passed";
	        echoRespnse(200, $response);
        });

$app->get('/test/echo/:message', function ($message) {
	        $response = array();
	        $response["message"] = $message;
	        echoRespnse(200, $response);
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
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to generate OTP. Please try again";
                echoRespnse(200, $response);
            }
        });

$app->get('/test/encryption/:message', function ($message) {
	        $response = array();
	        $response["message_original"] = $message;
	        $response["message_encrypted"] = Security::encrypt($message);
	        $response["message_decrypted"] = Security::decrypt(Security::encrypt($message));
	        echoRespnse(200, $response);
        });

$app->get('/test/encrypt/:message', function ($message) {
	        $response = array();
	        $response["message_original"] = $message;
	        $response["message_encrypted"] = Security::encrypt($message);
	        echoRespnse(200, $response);
        });

$app->get('/test/decrypt/:message', function ($message) {
	        $response = array();
	        $response["message_original"] = $message;
	        $response["message_decrepted"] = Security::decrypt($message);
	        echoRespnse(200, $response);
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
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to generate OTP. Please try again";
                echoRespnse(200, $response);
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
                echoRespnse(200, $response);
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
                    echoRespnse(200, $response);
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
                        echoRespnse(200, $response);
                    } else {
                        $response["error"] = true;
                        $response["message"] = "Failed to insert user. Please try again";
                        echoRespnse(200, $response);
                    }
                }
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
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch companies";
                echoRespnse(200, $response);
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
                    $response['company_pinterest'] = "";
                    $response['company_youtube'] = $company_detail["scl_lnk_youtube"];
                    $response['company_instagram'] = "";
                    $response['company_googleplus'] = "";
              
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
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch company_details";
                echoRespnse(200, $response);
            }
        });





$app->get('/event', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllEvents();
            if($result){
                $response["error"] = false;
                $response["message"] = "Events fetched succesfully";
                $response["events"] = array();
                while ($event = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['event_id'] = $event["evnt_id"];
                    $tmp['event_name'] = $event["evnt_name"];
                    $tmp['event_date'] = $event["evnt_date"];
                    $tmp['event_time'] = $event["evnt_time"];
                    $tmp['event_duration'] = $event["evnt_duration"];
                    $tmp['event_fees'] = $event["evnt_fees"];
                    $tmp['event_location'] = $event["evnt_location"];
                    $tmp['event_notes'] = $event["evnt_notes"];
                    $tmp['event_speakers'] = $event["evnt_speakers"];
                    array_push($response["events"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch events";
                echoRespnse(200, $response);
            }
        });

$app->get('/event/favourite', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllFavouriteEvents($user_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Favourite Events fetched succesfully";
                $response["events"] = array();
                while ($event = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['event_id'] = $event["evnt_id"];
                    $tmp['event_name'] = $event["evnt_name"];
                    $tmp['event_date'] = $event["evnt_date"];
                    $tmp['event_time'] = $event["evnt_time"];
                    $tmp['event_duration'] = $event["evnt_duration"];
                    $tmp['event_fees'] = $event["evnt_fees"];
                    $tmp['event_location'] = $event["evnt_location"];
                    $tmp['event_notes'] = $event["evnt_notes"];
                    $tmp['event_speakers'] = $event["evnt_speakers"];
                    array_push($response["events"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch events";
                echoRespnse(200, $response);
            }
        });

$app->get('/event/:event_id', 'authenticate', function ($event_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getEventDetails($event_id);
            $result2 = $db->getEventTopics($event_id);
            $result3 = $db->getEventSpeakers($event_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Events fetched succesfully";
                while ($event_detail = $result->fetch_assoc()) {
                    $response['event_id'] = $event_detail["evnt_id"];
                    $response['event_name'] = $event_detail["evnt_name"];
                    $response['event_date'] = $event_detail["evnt_date"];
                    $response['event_time'] = $event_detail["evnt_time"];
                    $response['event_duration'] = $event_detail["evnt_duration"];
                    $response['event_fees'] = $event_detail["evnt_fees"];
                    $response['event_location'] = $event_detail["evnt_location"];
                    $response['event_notes'] = $event_detail["evnt_notes"];
                    $response['event_favourite'] = $db->isEventFavourite($user_id, $event_id);
                }
                $response["event_topics"] = array();
                while ($event_topics = $result2->fetch_assoc()) {
                    $tmp = array();
                    $tmp['topic_id'] = $event_topics["evnt_topc_id"];
                    $tmp['topic_text'] = $event_topics["evnt_topc_text"];
                    array_push($response["event_topics"], $tmp);
                }
                $response["event_speakers"] = array();
                while ($speakers = $result3->fetch_assoc()) {
                    $tmp = array();
                    $tmp['speaker_id'] = $speakers["evnt_spkr_id"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-famdent-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-famdent-cammy92.c9users.io')  == 0){
                        $tmp['speaker_image'] = siteURL()."api/images/speakers/".$speakers["evnt_spkr_image"];
                    } else {
                        $tmp['speaker_image'] = siteURL()."/api/images/speakers/".$speakers["evnt_spkr_image"];
                    }
                    
                    $tmp['speaker_name'] = $speakers["evnt_spkr_name"];
                    $tmp['speaker_qualification'] = $speakers["evnt_spkr_qualification"];
                    $tmp['speaker_experience'] = $speakers["evnt_spkr_experience"];
                    array_push($response["event_speakers"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch events";
                echoRespnse(200, $response);
            }
        });

$app->get('/exhibitor', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllExhibitors();
            if($result){
                $response["error"] = false;
                $response["message"] = "Exhibitors fetched succesfully";
                $response["exhibitors"] = array();
                while ($exhibitor = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['exhibitor_id'] = $exhibitor["exhbtr_id"];
                    $tmp['exhibitor_logo'] = "";//$exhibitor["exhbtr_logo"];
                    $tmp['exhibitor_name'] = $exhibitor["exhbtr_name"];
                    $tmp["stall_details"] = array();
                    $result2 = $db->getExhibitorStallDetails($exhibitor["exhbtr_id"]);
                    while ($stall_details = $result2->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['stall_name'] = $stall_details["exhbtn_pln_stall_name"];
                        $tmp2['hall_number'] = $stall_details["exhbtn_pln_hall_number"];
                        $tmp2['stall_number'] = $stall_details["exhbtn_pln_stall_number"];
                        array_push($tmp["stall_details"], $tmp2);
                    }
                    array_push($response["exhibitors"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch exhibitors";
                echoRespnse(200, $response);
            }
        });

$app->get('/exhibitor/favourite', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllFavouriteExhibitors($user_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Exhibitors fetched succesfully";
                $response["exhibitors"] = array();
                while ($exhibitor = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['exhibitor_id'] = $exhibitor["exhbtr_id"];
                    $tmp['exhibitor_logo'] = "";//$exhibitor["exhbtr_logo"];
                    $tmp['exhibitor_name'] = $exhibitor["exhbtr_name"];
                    $tmp["stall_details"] = array();
                    $result2 = $db->getExhibitorStallDetails($exhibitor["exhbtr_id"]);
                    while ($stall_details = $result2->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['stall_name'] = $stall_details["exhbtn_pln_stall_name"];
                        $tmp2['hall_number'] = $stall_details["exhbtn_pln_hall_number"];
                        $tmp2['stall_number'] = $stall_details["exhbtn_pln_stall_number"];
                        array_push($tmp["stall_details"], $tmp2);
                    }
                    array_push($response["exhibitors"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch exhibitors";
                echoRespnse(200, $response);
            }
        });

$app->get('/exhibitor/:exhibitor_id', 'authenticate', function ($exhibitor_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getExhibitorDetails($exhibitor_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Exhibitor details fetched succesfully";
                while ($exhibitor = $result->fetch_assoc()) {
                    $response['exhibitor_id'] = $exhibitor["exhbtr_id"];
                    $response['exhibitor_name'] = $exhibitor["exhbtr_name"];
                    $response['exhibitor_logo'] = "";//$exhibitor["exhbtr_logo"];
                    $response['exhibitor_address'] = $exhibitor["exhbtr_address"];
                    $response['exhibitor_contact_person'] = $exhibitor["exhbtr_contact_person"];
                    $response['exhibitor_email'] = $exhibitor["exhbtr_email"];
                    $response['exhibitor_description'] = $exhibitor["exhbtr_description"];
                    $response['exhibitor_website'] = $exhibitor["exhbtr_website"];
                    $response['exhibitor_contacts'] = array();
                    if($exhibitor["exhbtr_contact1"] != ""){
                        $tmp3 = array();
                        $tmp3['contact'] = $exhibitor["exhbtr_contact1"];
                        array_push($response['exhibitor_contacts'], $tmp3);
                    }
                    if($exhibitor["exhbtr_contact2"] != ""){
                        $tmp3 = array();
                        $tmp3['contact'] = $exhibitor["exhbtr_contact2"];
                        array_push($response['exhibitor_contacts'], $tmp3);
                    }
                    if($exhibitor["exhbtr_contact3"] != ""){
                        $tmp3 = array();
                        $tmp3['contact'] = $exhibitor["exhbtr_contact3"];
                        array_push($response['exhibitor_contacts'], $tmp3);
                    }
                    $response['exhibitor_favourite'] = $db->isExhibitorFavourite($user_id, $exhibitor_id);   
                    $response['exhibitor_notes'] = $db->getExhibitorNote($user_id, $exhibitor_id);
                    
                    $response["stall_details"] = array();
                    $result2 = $db->getExhibitorStallDetails($exhibitor["exhbtr_id"]);
                    while ($stall_details = $result2->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['stall_name'] = $stall_details["exhbtn_pln_stall_name"];
                        $tmp2['hall_number'] = $stall_details["exhbtn_pln_hall_number"];
                        $tmp2['stall_number'] = $stall_details["exhbtn_pln_stall_number"];
                        array_push($response["stall_details"], $tmp2);
                    }
                
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch exhibitors";
                echoRespnse(200, $response);
            }
        });

$app->get('/session', 'authenticate', function () {
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllSessions();
            if($result){
                $response["error"] = false;
                $response["message"] = "Sessions fetched succesfully";
                $response["sessions"] = array();
                while ($session = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['session_id'] = $session["ssion_id"];
                    $tmp['session_title'] = $session["ssion_title"];
                    $tmp['session_date'] = $session["ssion_date"];
                    $tmp['session_time'] = $session["ssion_time"];
                    $tmp['session_location'] = $session["ssion_location"];
                    $tmp['session_category'] = $session["ssion_category"];
                    $tmp['session_speakers'] = $session["ssion_speakers"];
                    array_push($response["sessions"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch sessions";
                echoRespnse(200, $response);
            }
        });

$app->get('/session/favourite', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllFavouriteSessions($user_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Sessions fetched succesfully";
                $response["sessions"] = array();
                while ($session = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['session_id'] = $session["ssion_id"];
                    $tmp['session_title'] = $session["ssion_title"];
                    $tmp['session_date'] = $session["ssion_date"];
                    $tmp['session_time'] = $session["ssion_time"];
                    $tmp['session_location'] = $session["ssion_location"];
                    $tmp['session_category'] = $session["ssion_category"];
                    $tmp['session_speakers'] = $session["ssion_speakers"];
                    array_push($response["sessions"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch sessions";
                echoRespnse(200, $response);
            }
        });

$app->get('/session/:session_id', 'authenticate', function ($session_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getSessionDetails($session_id);
            $result2 = $db->getSessionTopics($session_id);
            $result3 = $db->getSessionSpeakers($session_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "Session details fetched succesfully";
                while ($session_detail = $result->fetch_assoc()) {
                    $response['session_id'] = $session_detail["ssion_id"];
                    $response['session_title'] = $session_detail["ssion_title"];
                    $response['session_date'] = $session_detail["ssion_date"];
                    $response['session_time'] = $session_detail["ssion_time"];
                    $response['session_location'] = $session_detail["ssion_location"];
                    $response['session_category'] = $session_detail["ssion_category"];
                    $response['session_favourite'] = $db->isSessionFavourite($user_id, $session_id);
                }
                $response["session_topics"] = array();
                while ($event_topics = $result2->fetch_assoc()) {
                    $tmp = array();
                    $tmp['topic_id'] = $event_topics["ssion_topc_id"];
                    $tmp['topic_text'] = $event_topics["ssion_topc_text"];
                    array_push($response["session_topics"], $tmp);
                }
                $response["session_speakers"] = array();
                while ($speakers = $result3->fetch_assoc()) {
                    $tmp = array();
                    $tmp['speaker_id'] = $speakers["ssion_spkr_id"];
                    $tmp['speaker_image'] = siteURL()."api/images/speakers/".$speakers["ssion_spkr_image"];
                    $tmp['speaker_name'] = $speakers["ssion_spkr_name"];
                    array_push($response["session_speakers"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch sessions";
                echoRespnse(200, $response);
            }
        });


$app->post('/favourite/exhibitor/add', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('exhibitor_id'));
            $response = array();
            $exhibitor_id = $app->request->post('exhibitor_id');
            $db = new DbHandler();
            
            switch($db->addFavouriteExhibitor($user_id, $exhibitor_id)){
                case 0: 
                    $response["error"] = true;
                    $response["message"] = "Favourite already exist";
                    echoRespnse(200, $response);
                    break;
                case 1:
                    $response["error"] = false;
                    $response["message"] = "Favourite added successfully";
                    echoRespnse(200, $response);
                    break;
                case 2:
                    $response["error"] = false;
                    $response["message"] = "Failed to add favourite";
                    echoRespnse(200, $response);    
                    break;
            }
    });

$app->post('/favourite/event/add', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('event_id'));
            $response = array();
            $event_id = $app->request->post('event_id');
            $db = new DbHandler();
            
            switch($db->addFavouriteEvent($user_id, $event_id)){
                case 0: 
                    $response["error"] = true;
                    $response["message"] = "Favourite already exist";
                    echoRespnse(200, $response);
                    break;
                case 1:
                    $response["error"] = false;
                    $response["message"] = "Favourite added successfully";
                    echoRespnse(200, $response);
                    break;
                case 2:
                    $response["error"] = false;
                    $response["message"] = "Failed to add favourite";
                    echoRespnse(200, $response);    
                    break;
            }
    });

$app->post('/favourite/session/add', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('session_id'));
            $response = array();
            $session_id = $app->request->post('session_id');
            $db = new DbHandler();
            
            switch($db->addFavouriteSession($user_id, $session_id)){
                case 0: 
                    $response["error"] = true;
                    $response["message"] = "Favourite already exist";
                    echoRespnse(200, $response);
                    break;
                case 1:
                    $response["error"] = false;
                    $response["message"] = "Favourite added successfully";
                    echoRespnse(200, $response);
                    break;
                case 2:
                    $response["error"] = false;
                    $response["message"] = "Failed to add favourite";
                    echoRespnse(200, $response);    
                    break;
            }
    });


$app->post('/favourite/exhibitor/remove', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('exhibitor_id'));
            $response = array();
            $exhibitor_id = $app->request->post('exhibitor_id');
            $db = new DbHandler();
            
            switch($db->removeFavouriteExhibitor($user_id, $exhibitor_id)){
                case 0: 
                    $response["error"] = true;
                    $response["message"] = "Favourite already removed";
                    echoRespnse(200, $response);
                    break;
                case 1:
                    $response["error"] = false;
                    $response["message"] = "Favourite removed successfully";
                    echoRespnse(200, $response);
                    break;
                case 2:
                    $response["error"] = false;
                    $response["message"] = "Failed to remove favourite";
                    echoRespnse(200, $response);    
                    break;
            }
    });

$app->post('/favourite/event/remove', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('event_id'));
            $response = array();
            $event_id = $app->request->post('event_id');
            $db = new DbHandler();
            
            switch($db->removeFavouriteEvent($user_id, $event_id)){
                case 0: 
                    $response["error"] = true;
                    $response["message"] = "Favourite already removed";
                    echoRespnse(200, $response);
                    break;
                case 1:
                    $response["error"] = false;
                    $response["message"] = "Favourite removed successfully";
                    echoRespnse(200, $response);
                    break;
                case 2:
                    $response["error"] = false;
                    $response["message"] = "Failed to remove favourite";
                    echoRespnse(200, $response);    
                    break;
            }
    });

$app->post('/favourite/session/remove', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('session_id'));
            $response = array();
            $session_id = $app->request->post('session_id');
            $db = new DbHandler();
            
            switch($db->removeFavouriteSession($user_id, $session_id)){
                case 0: 
                    $response["error"] = true;
                    $response["message"] = "Favourite already removed";
                    echoRespnse(200, $response);
                    break;
                case 1:
                    $response["error"] = false;
                    $response["message"] = "Favourite removed successfully";
                    echoRespnse(200, $response);
                    break;
                case 2:
                    $response["error"] = false;
                    $response["message"] = "Failed to remove favourite";
                    echoRespnse(200, $response);    
                    break;
            }
    });


$app->post('/notes/exhibitor', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('exhibitor_id'));
            $response = array();
            $exhibitor_id = $app->request->post('exhibitor_id');
            $notes = $app->request->post('exhibitor_notes');
            $db = new DbHandler();
            
            switch($db->addNotesForExhibitor($user_id, $exhibitor_id, $notes)){
                case 1:
                    $response["error"] = false;
                    $response["message"] = "Note created successfully";
                    echoRespnse(200, $response);
                    break;
                case 2:
                    $response["error"] = false;
                    $response["message"] = "Note updated successfully";
                    echoRespnse(200, $response);
                    break;
                case 3:
                    $response["error"] = false;
                    $response["message"] = "Note deleted successfully";
                    echoRespnse(200, $response);
                    break;
                case 4: 
                    $response["error"] = true;
                    $response["message"] = "Failed to add/edit note";
                    echoRespnse(200, $response);    
                    break;
            }
    });


$app->get('/categories', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllCategories();
            if($result){
                $response["error"] = false;
                $response["message"] = "Categories fetched succesfully";
                $response["categories"] = array();
                while ($category = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['category_id'] = $category["ctgry_id"];
                    $tmp['category_name'] = $category["ctgry_name"];
                    $tmp['category_is_parent'] = $category["ctgry_parent"];
                    if($category["ctgry_parent"] == 1){
                        $tmp["sub_categories"] = array();
                        $result2 = $db->getAllSubCategories($category["ctgry_id"]);
                        while ($sub_category = $result2->fetch_assoc()) {
                            $tmp2 = array();
                            $tmp2['sub_category_id'] = $sub_category["ctgry_id"];
                            $tmp2['sub_category_name'] = $sub_category["ctgry_name"];
                            array_push($tmp["sub_categories"], $tmp2);
                        }
                    }

                    array_push($response["categories"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch exhibitors";
                echoRespnse(200, $response);
            }
        });



$app->get('/send_promo_notification', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            date_default_timezone_set("Asia/Kolkata");
            $dt = new DateTime();
            $current_date = $dt->format('Y-m-d');
            $current_day = $dt->format('D');

//            echo "current date".$current_date;
//            echo "current day".$current_day;

            $result = $db->sendPromoNotification($user_id, $current_date, $current_day);
            if ($result) {
                $response["error"] = false;
                $response["message"] = "Promo Notification executed successfully";

                while ($notification = $result->fetch_assoc()) {
                    $firebase = new Firebase();
                    $push = new Push();
                    $payload = array();

                    $payload['notification_type'] = 3;
                    $payload['notification_priority'] = 1;
                    $payload['notification_style'] = 4;

                    $payload['notification_promotion_id'] = $notification["id"];
                    $payload['notification_promotion_status'] = $notification["status"];


//  echo"<pre>";
//  print_r($notification);

                    $push->setTitle($notification["title"]);
                    $push->setMessage($notification["content"]);
                    if (strcmp($_SERVER['HTTP_HOST'], 'blood-connect-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.blood-connect-cammy92.c9users.io')  == 0){
                        $push->setImage(siteURL()."api/images/promotions_images/".$notification["image"]);
                    } else {
                        $push->setImage(siteURL()."bloodkonnect/api/images/promotions_images/".$notification["image"]);
                    }  
                    $push->setIsBackground(FALSE);
                    $push->setPayload($payload);
                    $json = $push->getPush();
                    $firebase_response = $firebase->send($notification["user_firebase_id"], $json);
//                    echo $firebase_response;
//                    echo "<pre>";
//                    print_r($firebase_response);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to send Promo Notifications Please try again";
                $response["status"] = 0;
                echoRespnse(200, $response);
            }
        });


$app->get('/banners', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            $result = $db->getBanners($user_id);
            if ($result) {
                $response["error"] = false;
                $response["message"] = "Banners fetched successfully";
                $response["banners"] = array();
                // looping through result and preparing tasks array
                while ($banner = $result->fetch_assoc()) {
//                    echo "abc";
                    $tmp = array();
                    $tmp["banner_id"] = $banner["bnnr_id"];
                    $tmp["banner_title"] = $banner["bnnr_title"];
                    $tmp["banner_type"] = $banner["bnnr_type"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-famdent-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-famdent-cammy92.c9users.io')  == 0){
                        $tmp['banner_image'] = siteURL()."api/images/banners/".$banner["bnnr_image"];
                    } else {
                        $tmp['banner_image'] = siteURL()."/api/images/banners/".$banner["bnnr_image"];
                    }
                    array_push($response["banners"], $tmp);
                }
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch banners Please try again";
                echoRespnse(200, $response);
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
            
            echoRespnse(200, $response);
        });




$app->post('/init2/application/', 'authenticate', function () use ($app){
            global $user_id;
            verifyRequiredParams(array('db_version', 'favourites_json', 'app_version'));
            $db_version = $app->request->post('db_version');
            $app_version = $app->request->post('app_version');
            $favourites = json_decode($app->request->post('favourites_json'), true);
        
        
//        {"favourites":[{"favourite_type":"EXHIBITOR","favourite_exhibitor_id":1},{"favourite_type":"EVENT","favourite_event_id":1},{"favourite_type":"SESSION","favourite_session_id":1}]}

        
        //echo "<pre>";
        //print_r($favourites);
        //exit;
            
            $response = array();
            $db = new DbHandler();

            date_default_timezone_set("Asia/Kolkata");
		    $dt = new DateTime();
		    $dt->format('Y-m-d H:i:s');
		    $newdt = $dt->format('Y-m-d H:i:s');

            
            $db->deleteAllVisitorFavourites($user_id);
            $db->updateFavourites($user_id, $favourites);
            
            $db->updateAppVersionInVisitorTable($user_id, $app_version);

            $banner_result = $db->getBanners($user_id);


            if($db->getDbVersionCode() > $db_version){
                $category_result = $db->getAllCategories();
                $category_mapping_result = $db->getAllCategoryMappings();
                $exhibitor_result = $db->getAllExhibitors();
                $event_result = $db->getAllEvents();
//                $session_result = $db->getAllSessions();
                $response["error"] = false;
                $response["status"] = 2;
                $response["message"] = "Init details fetched successfully";
                if($db->getCurrentAppVersion("ANDROID") > $app_version){
                    $response["version_update"] = true;
                } else {
                    $response["version_update"] = false;
                }
                $response["database_version"] = $db->getDbVersionCode();
                $response["visitor_id"] = $db->getVisitorFamdentId($user_id);
               
               
               
                
                $response["categories"] = array();
                while ($category = $category_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['category_id'] = $category["ctgry_id"];
                    $tmp['category_name'] = $category["ctgry_name"];
                    $tmp['category_level2'] = $category["ctgry_level2"];
                    $tmp['category_level3'] = $category["ctgry_level3"];
                    array_push($response["categories"], $tmp);
                }

                $response["category_mappings"] = array();
                while ($category_mapping = $category_mapping_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['category_mapping_exhibitor_id'] = $category_mapping["ctgry_map_exhbtr_id"];
                    $tmp['category_mapping_category_id'] = $category_mapping["ctgry_map_ctgry_id"];
                    $tmp['category_mapping_exhibitor_name'] = $category_mapping["ctgry_map_exhbtr_name"];
                    array_push($response["category_mappings"], $tmp);
                }
               
                
                $response["banners"] = array();
                while ($banner = $banner_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp["banner_id"] = $banner["bnnr_id"];
                    $tmp["banner_title"] = $banner["bnnr_title"];
                    $tmp["banner_type"] = $banner["bnnr_type"];
                    $tmp["banner_url"] = $banner["bnnr_url"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-famdent-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-famdent-cammy92.c9users.io')  == 0){
                        $tmp['banner_image'] = siteURL()."api/images/banners/".$banner["bnnr_image"];
                    } else {
                        $tmp['banner_image'] = siteURL()."/api/images/banners/".$banner["bnnr_image"];
                    }
                    array_push($response["banners"], $tmp);
                }
                
                $response["exhibitors"] = array();
                while ($exhibitor = $exhibitor_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['exhibitor_id'] = $exhibitor["exhbtr_id"];
                    $tmp['exhibitor_name'] = $exhibitor["exhbtr_name"];
                    $tmp['exhibitor_logo'] = "";//$exhibitor["exhbtr_logo"];
                    $tmp['exhibitor_address'] = $exhibitor["exhbtr_address"];
                    $tmp['exhibitor_contact_person'] = $exhibitor["exhbtr_contact_person"];
                    $tmp['exhibitor_email'] = $exhibitor["exhbtr_email"];
                    $tmp['exhibitor_description'] = $exhibitor["exhbtr_description"];
                    $tmp['exhibitor_website'] = $exhibitor["exhbtr_website"];
                    $tmp['exhibitor_favourite'] = $db->isExhibitorFavourite($user_id, $exhibitor["exhbtr_id"]);   
                    $tmp['exhibitor_notes'] = $db->getExhibitorNote($user_id, $exhibitor["exhbtr_id"]);
                    $tmp['exhibitor_contacts'] = array();
                    if($exhibitor["exhbtr_contact1"] != ""){
                        $tmp3 = array();
                        $tmp3['contact'] = $exhibitor["exhbtr_contact1"];
                        array_push($tmp['exhibitor_contacts'], $tmp3);
                    }
                    if($exhibitor["exhbtr_contact2"] != ""){
                        $tmp3 = array();
                        $tmp3['contact'] = $exhibitor["exhbtr_contact2"];
                        array_push($tmp['exhibitor_contacts'], $tmp3);
                    }
                    if($exhibitor["exhbtr_contact3"] != ""){
                        $tmp3 = array();
                        $tmp3['contact'] = $exhibitor["exhbtr_contact3"];
                        array_push($tmp['exhibitor_contacts'], $tmp3);
                    }
                    $tmp["stall_details"] = array();
                    $result2 = $db->getExhibitorStallDetails($exhibitor["exhbtr_id"]);
                    while ($stall_details = $result2->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['stall_name'] = $stall_details["exhbtn_pln_stall_name"];
                        $tmp2['hall_number'] = $stall_details["exhbtn_pln_hall_number"];
                        $tmp2['stall_number'] = $stall_details["exhbtn_pln_stall_number"];
                        array_push($tmp["stall_details"], $tmp2);
                    }
                    array_push($response["exhibitors"], $tmp);
                }
                
                $response["events"] = array();
                while ($event = $event_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['event_id'] = $event["evnt_id"];
                    $tmp['event_name'] = $event["evnt_name"];
                    $tmp['event_date'] = $event["evnt_date"];
                    $tmp['event_time'] = $event["evnt_time"];
                    $tmp['event_duration'] = $event["evnt_duration"];
                    $tmp['event_fees'] = $event["evnt_fees"];
                    $tmp['event_location'] = $event["evnt_location"];
                    $tmp['event_notes'] = $event["evnt_notes"];
                    $tmp['event_speakers'] = $event["evnt_speakers"];
                    $tmp['event_favourite'] = $db->isEventFavourite($user_id, $event["evnt_id"]);
                    
                    $result3 = $db->getEventTopics($event["evnt_id"]);
                    $result4 = $db->getEventSpeakers($event["evnt_id"]);
                        
                    $tmp["event_topics"] = array();
                    while ($event_topics = $result3->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['topic_id'] = $event_topics["evnt_topc_id"];
                        $tmp2['topic_text'] = $event_topics["evnt_topc_text"];
                        array_push($tmp["event_topics"], $tmp2);
                    }
                    $tmp["event_speakers"] = array();
                    while ($speakers = $result4->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['speaker_id'] = $speakers["evnt_spkr_id"];
                        if (strcmp($_SERVER['HTTP_HOST'], 'project-dental101-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-dental101-cammy92.c9users.io')  == 0){
                            $tmp2['speaker_image'] = siteURL()."api/images/speakers/".$speakers["evnt_spkr_image"];
                        } else {
                            $tmp2['speaker_image'] = siteURL()."isdental/api/images/speakers/".$speakers["evnt_spkr_image"];
                        }
                        $tmp2['speaker_name'] = $speakers["evnt_spkr_name"];
                        $tmp2['speaker_qualification'] = $speakers["evnt_spkr_qualification"];
                        $tmp2['speaker_experience'] = $speakers["evnt_spkr_experience"];
                        array_push($tmp["event_speakers"], $tmp2);
                    }
                    array_push($response["events"], $tmp);
                }
    /*
                $response["sessions"] = array();
                while ($session = $session_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['session_id'] = $session["ssion_id"];
                    $tmp['session_title'] = $session["ssion_title"];
                    $tmp['session_date'] = $session["ssion_date"];
                    $tmp['session_time'] = $session["ssion_time"];
                    $tmp['session_location'] = $session["ssion_location"];
                    $tmp['session_category'] = $session["ssion_category"];
                    $tmp['session_favourite'] = $db->isSessionFavourite($user_id, $session["ssion_id"]);
                    
                
                    $result5 = $db->getSessionTopics($session["ssion_id"]);
                    $result6 = $db->getSessionSpeakers($session["ssion_id"]);
                        
                    $tmp["session_topics"] = array();
                    while ($session_topic = $result5->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['topic_id'] = $session_topic["ssion_topc_id"];
                        $tmp2['topic_text'] = $session_topic["ssion_topc_text"];
                        array_push($tmp["session_topics"], $tmp2);
                    }
                    $tmp["session_speakers"] = array();
                    while ($session_speaker = $result6->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['speaker_id'] = $session_speaker["ssion_spkr_id"];
                        if (strcmp($_SERVER['HTTP_HOST'], 'project-famdent-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www    .project-famdent-cammy92.c9users.io')  == 0){
                            $tmp2['speaker_image'] = siteURL()."api/images/speakers/".$session_speaker["ssion_spkr_image"];
                        } else {
                            $tmp2['speaker_image'] = siteURL()."/api/images/speakers/".$session_speaker["ssion_spkr_image"];
                        }
                        $tmp2['speaker_name'] = $session_speaker["ssion_spkr_name"];
                        array_push($tmp["session_speakers"], $tmp2);
                    }
                    array_push($response["sessions"], $tmp);
                }
                
                */
            } else {
                $response["error"] = false;
                $response["status"] = 1;
                $response["message"] = "Databse version already latest";
                if($db->getCurrentAppVersion("ANDROID") > $app_version){
                    $response["version_update"] = true;
                } else {
                    $response["version_update"] = false;
                }
                $response["database_version"] = $db->getDbVersionCode();
                $response["visitor_id"] = $db->getVisitorFamdentId($user_id);

                $response["banners"] = array();
                while ($banner = $banner_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp["banner_id"] = $banner["bnnr_id"];
                    $tmp["banner_title"] = $banner["bnnr_title"];
                    $tmp["banner_url"] = $banner["bnnr_url"];
                    $tmp["banner_type"] = $banner["bnnr_type"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-famdent-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-famdent-cammy92.c9users.io')  == 0){
                        $tmp['banner_image'] = siteURL()."api/images/banners/".$banner["bnnr_image"];
                    } else {
                        $tmp['banner_image'] = siteURL()."/api/images/banners/".$banner["bnnr_image"];
                    }
                    array_push($response["banners"], $tmp);
                }
            }
            echoRespnse(200, $response);
        });






    
function siteURL(){
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol.$domainName."/";
}

function getIDFromString($id){
    return filter_var($id, FILTER_SANITIZE_NUMBER_INT);
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
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
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
    // check balance messages
    //http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    $username="actiknow";
    $password="actiknow@2017";
//    $username="shout";
//    $password="shout@share";
	$message= $otp." is your login OTP for ISDental application";
	$sender="INSPLY"; //ex:INVITE
	$mobile_number = $mobile;
	$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$curl_scraped_page = curl_exec($ch);
	curl_close($ch);
    return true;
}


function sendForgetPasswordSMS($user_mobile, $login_username, $login_password){
    // check balance messages
    //http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
 
    $username="actiknow";
    $password="actiknow@2017";
	$message= "Your Login Credentials for ACTIPATIENT are\nUsername\n".$login_username."\nPassword\n".$login_password;
	$sender="ACTIPT"; //ex:INVITE
	$mobile_number = $user_mobile;
	$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$curl_scraped_page = curl_exec($ch);
	curl_close($ch);

    return true;
}

function sendForgetPasswordEmail($user_email, $login_username, $login_password){
    try{
        //PHPMailer Object
        $mail = new PHPMailer;
    
        //Enable SMTP debugging. 
    //    $mail->SMTPDebug = 3;                               
        //Set PHPMailer to use SMTP.
        $mail->isSMTP();            
        //Set SMTP host name                          
        $mail->Host = "smtp.gmail.com";
        //Set this to true if SMTP host requires authentication to send email
        $mail->SMTPAuth = true;                          
        //Provide username and password     
    //    $mail->Username = "actipatient@gmail.com";                 
    //    $mail->Password = "actipatient1234";                           
        
        $mail->Username = "support@actiknow.com";                 
        $mail->Password = "actiknow@123";                           
      
    //If SMTP requires TLS encryption then set it
        $mail->SMTPSecure = "tls";                           
        //Set TCP port to connect to 
        $mail->Port = 587;                                   
        
        $mail->From = "noreply@actipatient.com";
        $mail->FromName = "Actipatient Support";
        
        $mail->addAddress($user_email);
        
        //$mail->isHTML(true);
        
        $mail->Subject = "Actipatient Login Credentials";
        $mail->Body = "Your Login Credentials for ACTIPATIENT are\nUsername: ".$login_username."\nPassword: ".$login_password;
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

function sendForgetAccessPINSMS($user_mobile, $hospital_name, $access_pin){
    // check balance messages
    //http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    
    $username="actiknow";
    $password="actiknow@2017";
	$message= "Access PIN to be used in ACTIPATIENT for ".$hospital_name." is ".$access_pin;
	$sender="ACTIPT"; //ex:INVITE
	$mobile_number = $user_mobile;
	$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$curl_scraped_page = curl_exec($ch);
	curl_close($ch);
	
    return true;
}

function sendForgetAccessPINEmail($user_email, $hospital_name, $access_pin){
    try{
        //PHPMailer Object
        $mail = new PHPMailer;

        //Enable SMTP debugging. 
    //    $mail->SMTPDebug = 3;                               
        //Set PHPMailer to use SMTP.
        $mail->isSMTP();            
        //Set SMTP host name                          
        $mail->Host = "smtp.gmail.com";
        //Set this to true if SMTP host requires authentication to send email
        $mail->SMTPAuth = true;                          
        //Provide username and password     
    
    //    $mail->Username = "actipatient@gmail.com";                 
    //    $mail->Password = "actipatient1234";                           
        
        $mail->Username = "support@actiknow.com";                 
        $mail->Password = "actiknow@123";                           
        //If SMTP requires TLS encryption then set it
        $mail->SMTPSecure = "tls";                           
        //Set TCP port to connect to 
        $mail->Port = 587;                                   
        
        $mail->From = "noreply@actipatient.com";
        $mail->FromName = "Actipatient Support";
        
        $mail->addAddress($user_email);
        
        //$mail->isHTML(true);
        
        $mail->Subject = "Actipatient Access PIN";
        $mail->Body = "Access PIN to be used in ACTIPATIENT for ".$hospital_name." is ".$access_pin;
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

function sendForgetAdminPasswordSMS($user_mobile, $hospital_name, $login_username, $login_password){
    // check balance messages
    //http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    
    $username="actiknow";
    $password="actiknow@2017";
	$message= "Login Credentials to be used in Admin for ".$hospital_name." are\nUsername\n".$login_username."\nPassword\n".$login_password;
	$sender="ACTIPT"; //ex:INVITE
	$mobile_number = $user_mobile;
	$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$curl_scraped_page = curl_exec($ch);
	curl_close($ch);
	
    return true;
}

function sendForgetAdminPasswordEmail($user_email, $hospital_name, $login_username, $login_password){
    try {
        //PHPMailer Object
        $mail = new PHPMailer;
    
        //Enable SMTP debugging. 
    //    $mail->SMTPDebug = 3;                               
        //Set PHPMailer to use SMTP.
        $mail->isSMTP();            
        //Set SMTP host name                          
        $mail->Host = "smtp.gmail.com";
        //Set this to true if SMTP host requires authentication to send email
        $mail->SMTPAuth = true;                          
        //Provide username and password     
    
    //    $mail->Username = "actipatient@gmail.com";                 
    //    $mail->Password = "actipatient1234";                           
    
        $mail->Username = "support@actiknow.com";                 
        $mail->Password = "actiknow@123";                           
        //If SMTP requires TLS encryption then set it
        $mail->SMTPSecure = "tls";                           
        //Set TCP port to connect to 
        $mail->Port = 587;                                   
        
        $mail->From = "noreply@actipatient.com";
        $mail->FromName = "Actipatient Support";
        
        $mail->addAddress($user_email);
        
        //$mail->isHTML(true);
        
        $mail->Subject = "Actipatient Access PIN";
        $mail->Body = "Login Credentials to be used in Admin for ".$hospital_name." are\nUsername\n".$login_username."\nPassword\n"    .$login_password;
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

$app->run();
?>