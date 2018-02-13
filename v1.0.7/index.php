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
	
	        echo microtime(true);
	      	$mt = explode('.', microtime(true));
	      	echo "\n".substr($mt[0],2, 8);
	      	echo "\n".substr($mt[1],0,2);
		    echo "\n".$str =substr($mt[0],2, 8).substr($mt[1],0,2);
	        
//	        $response["email"] = $email = sendForgetAccessPINEmail("karman.singh@actiknowbi.com", "karman", 1234);
//	        $response["patient_id"] =  $patient_id = $db->getPatientInternalID(1, 'MH111112');
//	        $response["cwd"] = getcwd();
//	        $response["server_url"] = $_SERVER['HTTP_HOST'];
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
                        echoResponse(200, $response);
                    } else {
                        $response["error"] = true;
                        $response["message"] = "Failed to insert user. Please try again";
                        echoResponse(200, $response);
                    }
                }
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
            
            echoResponse(200, $response);
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

$app->run();
?>