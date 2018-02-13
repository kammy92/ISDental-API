<?php
ini_set("display_errors", "1");
error_reporting(E_ALL);

require_once '../include/v2.0/Security.php';
require_once '../include/v2.0/DbHandler.php';
require_once '../include/v2.0/vFirstSMS.php';


require '.././libs/Slim/Slim.php';
require '.././libs/PHPMailer/PHPMailerAutoload.php';
require '.././libs/vendor/autoload.php';
  
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
    

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$user_id = NULL;
$sms_gateway = 2;   //1=> bulksmsgateway, 2=> valuefirst


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
//	        $db = new DbHandler();
	        $response["message"] = "For testing purpose";

            

//$response["sms"]= array();


//$mclass = new sendSms();
//$response["sms"]=$mclass->sendSmsToUser("999999 is your login OTP for ISDental application", "9873684678", "");



	        
// //	        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
//             $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
//             $randomString = '';
//             for ($i = 0; $i < 2; $i++) {
//                 $randomString .= $characters[rand(0, strlen($characters) - 1)];
//             }


// 	        $characters2 = '0123456789';
//             $randomString2 = '';
//             for ($i = 0; $i < 6; $i++) {
//                 $randomString2 .= $characters2[rand(0, strlen($characters2) - 1)];
//             }
            
// 	        $response["random"] = $randomString."8737".$randomString2;
	        
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



$app->post('/user/exist', 'authenticate', function() use ($app) {
            verifyRequiredParams(array('mobile'));
            $response = array();
            $mobile = $app->request->post('mobile');
            global $user_id;
            $db = new DbHandler();
            
            if($db->userExistByMobile($mobile)) {
                $result = $db->getUserDetails($mobile);
                $response["error"] = false;
                $response["message"] = "User exist and details fetched successfully";
                $response["user_exist"] = 1;
                while ($user = $result->fetch_assoc()) {
                    $response["user_id"] = $user["usr_isdental_id"];
                    $response["user_name"] = $user["usr_name"];
                    $response["user_mobile"] = $user["usr_mobile"];
                    $response["user_email"] = $user["usr_email"];
                    $response["user_type"] = $user["usr_type"];
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = false;
                $response["message"] = "User does not exist";
                $response["user_exist"] = 0;
                echoResponse(200, $response);
            }
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
                if (!$message["error"]) {
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



$app->get('/home/offers', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $banner_result = $db->getOfferBanners();
            $offer_result = $db->getAllHomeOffers();
            if($banner_result){
                $response["error"] = false;
                $response["message"] = "Offers and banners fetched succesfully";
                $response["banners"] = array();
                while ($banner = $banner_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['banner_id'] = $banner["bnnr_id"];
                    $tmp['banner_title'] = $banner["bnnr_title"];
                    $tmp['banner_url'] = $banner["bnnr_url"];
                    
                    $tmp['banner_type'] = $banner["bnnr_type"];
                    $tmp['banner_type_id'] = $banner["bnnr_type_id"];

                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['banner_image'] = siteURL()."api/images/v2.0/banners/".$banner["bnnr_image"];
                    } else {
                        $tmp['banner_image'] = siteURL()."isdental/api/images/v2.0/banners/".$banner["bnnr_image"];
                    }
                    array_push($response["banners"], $tmp);
                }
                
                $response["offers"] = array();
                while ($offer = $offer_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['offer_id'] = $offer["ofr_id"];
                    $tmp['offer_name'] = $offer["ofr_name"];
                    $tmp['offer_description'] = $offer["ofr_description"];
                    $tmp['offer_packaging'] = $offer["ofr_packaging"];
                    $tmp['offer_mrp'] = $offer["ofr_mrp"];
                    $tmp['offer_regular_price'] = $offer["ofr_regular_price"];
                    $tmp['offer_price'] = $offer["ofr_price"];
                    $tmp['offer_start_date'] = $offer["ofr_start_date"];
                    $tmp['offer_end_date'] = $offer["ofr_end_date"];
                    $tmp['offer_html_dates'] = $offer["ofr_html_dates"];
                    $tmp['offer_html_details'] = $offer["ofr_html_details"];
                    $tmp['offer_html_tandc'] = $offer["ofr_html_tandc"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['offer_image'] = siteURL()."api/images/v2.0/offers/".$offer["ofr_image"];
                    } else {
                        $tmp['offer_image'] = siteURL()."isdental/api/images/v2.0/offers/".$offer["ofr_image"];
                    }
                    array_push($response["offers"], $tmp);
                }

                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Offers";
                echoResponse(200, $response);
            }
        });

$app->get('/home/featured', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $banner_result = $db->getFeaturedBanners
            ();
            $company_result = $db->getAllFeaturedCompanies();
            if($banner_result){
                $response["error"] = false;
                $response["message"] = "Exhibitors and banners fetched succesfully";
                $response["banners"] = array();
                while ($banner = $banner_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['banner_id'] = $banner["bnnr_id"];
                    $tmp['banner_title'] = $banner["bnnr_title"];
                    $tmp['banner_url'] = $banner["bnnr_url"];
                    
                    $tmp['banner_type'] = $banner["bnnr_type"];
                    $tmp['banner_type_id'] = $banner["bnnr_type_id"];

                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['banner_image'] = siteURL()."api/images/v2.0/banners/".$banner["bnnr_image"];
                    } else {
                        $tmp['banner_image'] = siteURL()."isdental/api/images/v2.0/banners/".$banner["bnnr_image"];
                    }
                    array_push($response["banners"], $tmp);
                }
                
                $response["companies"] = array();
                while ($company = $company_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['company_id'] = $company["cmpny_id"];
                    $tmp['company_name'] = $company["cmpny_name"];
                    $tmp['company_website'] = $company["cmpny_website"];
                    $tmp['company_email'] = $company["cmpny_email"];
                    $tmp['company_description'] = $company["cmpny_description"];
                    $tmp['company_rating'] = $company["cmpny_rating"];
                    $tmp['company_categories'] = $company["cmpny_categories"];
                    $tmp['total_ratings'] = $company["total_ratings"];
                    $tmp['total_contacts'] = $company["total_contacts"];
                    $tmp['total_offers'] = $company["total_offers"];
                    $tmp['total_products'] = $company["total_products"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['company_image'] = siteURL()."api/images/v2.0/companies/".$company["cmpny_image"];
                    } else {
                        $tmp['company_image'] = siteURL()."isdental/api/images/v2.0/companies/".$company["cmpny_image"];
                    }
                    array_push($response["companies"], $tmp);
                }

                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Events";
                echoResponse(200, $response);
            }
        });

$app->get('/company/:company_id', 'authenticate', function ($company_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();


            $company_result = $db->getCompanyDetails($company_id);
            $group_result = $db->getCompanyProductGroups($company_id);
            $contact_result = $db->getCompanyContacts($user_id, $company_id);
                 
            
            if($company_result){
                $response["error"] = false;
                $response["message"] = "Company details fetched succesfully";
                $response["company_id"] = $company_result["cmpny_id"];
                $response["company_name"] = $company_result["cmpny_name"];
                 if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                    $response["company_image"] = siteURL()."api/images/v2.0/companies/".$company_result["cmpny_image"];
                } else {
                    $response["company_image"] = siteURL()."isdental/api/images/v2.0/companies/".$company_result["cmpny_image"];
                }
                $response["company_website"] = $company_result["cmpny_website"];
                $response["company_email"] = $company_result["cmpny_email"];
                $response["company_description"] = $company_result["cmpny_description"];
                $response["company_total_contacts"] = $company_result["total_contacts"];
                $response["company_categories"] = $company_result["cmpny_categories"];
                if ($company_result["cmpny_rating"] == null){
                    $response["company_rating"] = "0.0";
                }else{
                    $response["company_rating"] = $company_result["cmpny_rating"];
                }
                $response["company_total_ratings"] = $company_result["total_ratings"];
                if($company_result["cmpny_offers"] == null){
                    $response["company_offers"] = "";
                } else {
                    $response["company_offers"] = $company_result["cmpny_offers"];
                }
                if($group_result){
                    $response["product_groups"] = array();
                    while ($group = $group_result->fetch_assoc()) {
                        $tmp = array();
                        $tmp['group_title'] = $group["prdct_grp_name"];
                        $tmp['group_type'] = $group["prdct_grp_type"];
                    
                        $tmp['products'] = array();
                        $product_result = $db->getCompanyProductsByGroup($user_id, $company_id, $group["prdct_grp_id"]);
                        while ($product = $product_result->fetch_assoc()) {
                            $tmp2 = array();
                            $tmp2['product_id'] = $product["prdct_id"];
                            $tmp2['product_name'] = $product["prdct_name"];
                            try{
                                $tmp2['product_price'] = "Rs ".number_format($product["prdct_price"])."/-";
                            } catch(Exception $e){
                                $tmp2['product_price'] = $product["prdct_price"];
                            }
                            $tmp2['product_category'] = $product["ctgry_name"];
                            if(strlen($product["prdct_image"])>0){
                                if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                                    $tmp2['product_image'] = siteURL()."api/images/v2.0/products/".$product["prdct_image"];
                                } else {
                                    $tmp2['product_image'] = siteURL()."isdental/api/images/v2.0/products/".$product["prdct_image"];
                                }                            
                            } else {
                                $tmp2['product_image'] = "";
                            }
                            $tmp2['product_description'] = $product["prdct_description"];
                            $tmp2['product_packaging'] = $product["prdct_packaging"];
                            if($product["prdct_enquiry"] > 0){
                                $tmp2['product_enquiry'] = 1;
                            } else {
                                $tmp2['product_enquiry'] = 0;
                            }
                            array_push($tmp["products"], $tmp2);
                        }
                        array_push($response["product_groups"], $tmp);
                    }
                }
                if($contact_result){
                    $response['company_contacts'] = array();
                    while ($contact = $contact_result->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['contact_id'] = $contact["cntct_id"];
                        $tmp2['contact_name'] = $contact["cntct_name"];
                        if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                            $tmp2['contact_image'] = siteURL()."api/images/v2.0/contacts/".$contact["cntct_image"];
                        } else {
                            $tmp2['contact_image'] = siteURL()."isdental/api/images/v2.0/contacts/".$contact["cntct_image"];
                        }
                        $tmp2['contact_phone'] = $contact["cntct_phone"];
                        $tmp2['contact_location'] = $contact["cntct_location"];
                        if($contact["cntct_favourite"]>0){
                            $tmp2['contact_favourite'] = true;
                        } else {
                            $tmp2['contact_favourite'] = false;
                        }
                        $tmp2['contact_email'] = $contact["cntct_email"];
                        $tmp2['contact_website'] = $contact["cntct_website"];
                        $tmp2['contact_type'] = $contact["cntct_type"];
                        array_push($response["company_contacts"], $tmp2);
                    }
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch company details";
                echoResponse(200, $response);
            }
        });

$app->get('/home/events', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->getAllCurrentAndUpcomingEvents();
//            $result2 = $db->getAllPastEvents();
            if($result){
                $response["error"] = false;
                $response["message"] = "Events fetched succesfully";
                $response["events"] = array();
                while ($event = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['event_id'] = $event["evnt_id"];
                    $tmp['event_name'] = $event["evnt_name"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['event_image'] = siteURL()."api/images/v2.0/events/".$event["evnt_image"];
                    } else {
                        $tmp['event_image'] = siteURL()."isdental/api/images/v2.0/events/".$event["evnt_image"];
                    }
                    $tmp['event_type'] = $event["evnt_type"];
                    $tmp['event_start_date'] = $event["evnt_start_date"];
                    $tmp['event_end_date'] = $event["evnt_end_date"];
                    $tmp['event_city'] = $event["evnt_city"];
                    array_push($response["events"], $tmp);
                }
                
                // while ($event2 = $result2->fetch_assoc()) {
                //     $tmp = array();
                //     $tmp['event_id'] = $event2["evnt_id"];
                //     $tmp['event_name'] = $event2["evnt_name"];
                //      if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                //         $tmp['event_image'] = siteURL()."api/images/v2.0/events/".$event2["evnt_image"];
                //     } else {
                //         $tmp['event_image'] = siteURL()."isdental/api/images/v2.0//events/".$event2["evnt_image"];
                //     }
                //     $tmp['event_type'] = $event2["evnt_type"];
                //     $tmp['event_start_date'] = $event2["evnt_start_date"];
                //     $tmp['event_end_date'] = $event2["evnt_end_date"];
                //     $tmp['event_city'] = $event2["evnt_city"];
                //     array_push($response["events"], $tmp);
                // }
                
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
            $exhibitor_result = $db->getEventExhibitors($event_id);
            $speaker_result = $db->getEventSpeakers($event_id);
            $date_result = $db->getEventDates($event_id);
            $schedule_result = $db->getEventSchedules($event_id);
          
            if($result){
                $response["error"] = false;
                $response["message"] = "Event details fetched succesfully";
                while ($event_detail = $result->fetch_assoc()) {
                    $response['event_id'] = $event_detail["evnt_id"];
                    $response['event_name'] = $event_detail["evnt_name"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $response['event_image'] = siteURL()."api/images/v2.0//events/".$event_detail["evnt_image"];
                    } else {
                        $response['event_image'] = siteURL()."isdental/api/images/v2.0//events/".$event_detail["evnt_image"];
                    }
                    $response['event_type'] = $event_detail["evnt_type"];
                    $response['event_website'] = $event_detail["evnt_website"];
                    $response['event_information'] = $event_detail["evnt_information"];
                    $response['event_registrations'] = $event_detail["evnt_registrations"];
                    $response['event_start_date'] = $event_detail["evnt_start_date"];
                    $response['event_end_date'] = $event_detail["evnt_end_date"];
                    $response['event_city'] = $event_detail["evnt_city"];
                    $response['event_venue'] = $event_detail["evnt_venue"];
                    $response['event_latitude'] = $event_detail["evnt_latitude"];
                    $response['event_longitude'] = $event_detail["evnt_longitude"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $response['event_floor_plan'] = siteURL()."api/images/v2.0//floor-plans/".$event_detail["evnt_floor_plan"];
                    } else {
                        $response['event_floor_plan'] = siteURL()."isdental/api/images/v2.0//floor-plans/".$event_detail["evnt_floor_plan"];
                    }
                    
                    $response["event_exhibitors"] = array();
                    while ($exhibitor = $exhibitor_result->fetch_assoc()) {
                        $tmp = array();
                        $tmp['exhibitor_id'] = $exhibitor["exbtr_id"];
                        $tmp['exhibitor_name'] = $exhibitor["exbtr_name"];
                        $tmp['exhibitor_description'] = $exhibitor["exbtr_description"];
                        if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                            $tmp['exhibitor_image'] = siteURL()."api/images/v2.0//exhibitors/".$exhibitor["exbtr_image"];
                        } else {
                            $tmp['exhibitor_image'] = siteURL()."isdental/api/images/v2.0//exhibitors/".$exhibitor["exbtr_image"];
                        }
                        array_push($response["event_exhibitors"], $tmp);
                    }
                    
                    $response["event_speakers"] = array();
                    while ($speaker = $speaker_result->fetch_assoc()) {
                        $tmp = array();
                        $tmp['speaker_id'] = $speaker["spkr_id"];
                        $tmp['speaker_name'] = $speaker["spkr_name"];
                        $tmp['speaker_description'] = $speaker["spkr_description"];
                        if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                            $tmp['speaker_image'] = siteURL()."api/images/v2.0//speakers/".$speaker["spkr_image"];
                        } else {
                            $tmp['speaker_image'] = siteURL()."isdental/api/images/v2.0//speakers/".$speaker["spkr_image"];
                        }
                        array_push($response["event_speakers"], $tmp);
                    }
                    
                    $response["event_schedule"]["dates"] = array();
                    $response["event_schedule"]["schedules"] = array();
                    while ($date = $date_result->fetch_assoc()) {
                        $tmp = array();
                        $tmp['date_id'] = $date["dat_id"];
                        $tmp['date_title'] = $date["dat_title"];
                        $tmp['date_date'] = $date["dat_date"];
                        if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                            $tmp['date_image'] = siteURL()."api/images/v2.0/schedule-days/".$date["dat_image"];
                        } else {
                            $tmp['date_image'] = siteURL()."isdental/api/images/v2.0/schedule-days/".$date["dat_image"];
                        }
                        array_push($response["event_schedule"]["dates"], $tmp);
                    }
                    
                    while ($schedule = $schedule_result->fetch_assoc()) {
                        $tmp = array();
                        $tmp['schedule_id'] = $schedule["shdul_id"];
                        $tmp['schedule_date_id'] = $schedule["shdul_dat_id"];
                        $tmp['schedule_description'] = $schedule["shdul_description"];
                        $tmp['schedule_date'] = $schedule["shdul_date"];
                        $tmp['schedule_start_time'] = $schedule["shdul_start_time"];
                        $tmp['schedule_end_time'] = $schedule["shdul_end_time"];
                        $tmp['schedule_location'] = $schedule["shdul_location"];
                        if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                            $tmp['schedule_image'] = siteURL()."api/images/v2.0/schedules/".$schedule["shdul_image"];
                        } else {
                            $tmp['schedule_image'] = siteURL()."isdental/api/images/v2.0/schedules/".$schedule["shdul_image"];
                        }
                        array_push($response["event_schedule"]["schedules"], $tmp);
                    }
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch company_details";
                echoResponse(200, $response);
            }
        });

$app->get('/home/companies', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $category_group_result = $db->getCategoryGroups();

            $company_result = $db->getAllCompanies();
            if($company_result){
                $response["error"] = false;
                $response["message"] = "Companies fetched succesfully";
                $response["companies"] = array();
                while ($company = $company_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['company_id'] = $company["cmpny_id"];
                    $tmp['company_name'] = $company["cmpny_name"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['company_image'] = siteURL()."api/images/v2.0/companies/".$company["cmpny_image"];
                    } else {
                        $tmp['company_image'] = siteURL()."isdental/api/images/v2.0/companies/".$company["cmpny_image"];
                    }
                    $tmp['company_website'] = $company["cmpny_website"];
                    $tmp['company_email'] = $company["cmpny_email"];
                    $tmp['company_description'] = $company["cmpny_description"];
                    $tmp['company_categories'] = $company["cmpny_categories"];

                    $tmp['company_contacts'] = array();
                    $contact_result = $db->getCompanyContacts($user_id, $company["cmpny_id"]);
                    while ($contact = $contact_result->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['contact_id'] = $contact["cntct_id"];
                        $tmp2['contact_name'] = $contact["cntct_name"];
                        if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                            $tmp2['contact_image'] = siteURL()."api/images/v2.0/contacts/".$contact["cntct_image"];
                        } else {
                            $tmp2['contact_image'] = siteURL()."isdental/api/images/v2.0/contacts/".$contact["cntct_image"];
                        }
                        $tmp2['contact_phone'] = $contact["cntct_phone"];
                        $tmp2['contact_location'] = $contact["cntct_location"];
                        if($contact["cntct_favourite"]>0){
                            $tmp2['contact_favourite'] = true;
                        } else {
                            $tmp2['contact_favourite'] = false;
                        }
                        $tmp2['contact_email'] = $contact["cntct_email"];
                        $tmp2['contact_website'] = $contact["cntct_website"];
                        $tmp2['contact_type'] = $contact["cntct_type"];

                        array_push($tmp["company_contacts"], $tmp2);
                    }
                    array_push($response["companies"], $tmp);
                }
                
                $response["filters"] = array();
                while ($category_group = $category_group_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['group_name'] = $category_group["ctgry_group"];
                    $tmp["categories"] = array();
                    $category_result = $db->getCategoriesByGroup($category_group["ctgry_group"]);
                    while ($category = $category_result->fetch_assoc()) {
                        $tmp2=array();
                        $tmp2["category_id"] = $category["ctgry_id"];
                        $tmp2["category_name"] = $category["ctgry_name"];
                        array_push($tmp["categories"], $tmp2);
                    }
                    array_push($response["filters"], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Events";
                echoResponse(200, $response);
            }
        });
        
$app->get('/home/service', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $product_result = $db->getUserProducts($user_id);
            $category_result = $db->getAllCategories();
            $brand_result = $db->getAllCompanies2();
       
            if($product_result && $category_result && $brand_result){
                $response["error"] = false;
                $response["message"] = "Details fetched succesfully";
                $response["products"] = array();
                while ($product = $product_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['product_id'] = $product["prdct_id"];
                    $tmp['product_name'] = $product["prdct_name"];
                    $tmp['product_description'] = $product["prdct_description"];
                    $tmp['product_brand'] = $product["prdct_brand"];
                    $tmp['product_model_number'] = $product["prdct_model_number"];
                    $tmp['product_serial_number'] = $product["prdct_serial_number"];
                    $tmp['product_purchase_date'] = $product["prdct_purchase_date"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['product_image'] = siteURL()."api/images/v2.0/user-products/".$product["prdct_image1"];
                    } else {
                        $tmp['product_image'] = siteURL()."isdental/api/images/v2.0/user-products/".$product["prdct_image1"];
                    }
                    
                    
                    $request_status = $db->getUserProductLastRequestStatus($user_id, $product["prdct_id"]);
                    if($request_status["request_created_at"] != null){
                        $tmp['request_created_at'] = $request_status["request_created_at"];
                        switch($request_status["request_status"]){
                            case 0:
                                $tmp['request_status'] = "OPEN";
                                break;
                            case 1:
                                $tmp['request_status'] = "CLOSED";
                                break;
                        }
                        $tmp['request_ticket_number'] = $request_status["request_ticket_number"];
                    } else {
                        $tmp['request_created_at'] = "";
                        $tmp['request_status'] = "";
                        $tmp['request_ticket_number'] = "";
                    }
                    
                    array_push($response["products"], $tmp);
                }
                
                $response["categories"] = array();
                while ($category = $category_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp["category_id"] = $category["ctgry_id"];
                    $tmp["category_name"] = $category["ctgry_name"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['category_image'] = siteURL()."api/images/v2.0/categories/".$category["ctgry_image"];
                    } else {
                        $tmp['category_image'] = siteURL()."isdental/api/images/v2.0/categories/".$category["ctgry_image"];
                    }
                    array_push($response["categories"], $tmp);
                }
                        
                $response["brands"] = array();
                while ($brand = $brand_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp["brand_id"] = $brand["cmpny_id"];
                    $tmp["brand_name"] = $brand["cmpny_name"];
                    array_push($response["brands"], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Requests and Products";
                echoResponse(200, $response);
            }
        });

$app->get('/user/product/:product_id', 'authenticate', function ($product_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            if($result = $db->getUserProductDetails($user_id, $product_id)){
                $response["error"] = false;
                $response["message"] = "User products fetched succesfully";
                $response['product_name'] = $result["product_name"];
                
                if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                    $response['product_image1'] = siteURL()."api/images/v2.0/user-products/".$result["product_image1"];
                } else {
                    $response['product_image1'] = siteURL()."isdental/api/images/v2.0/user-products/".$result["product_image1"];
                }
                    
                if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                    $response['product_image2'] = siteURL()."api/images/v2.0/user-products/".$result["product_image2"];
                } else {
                    $response['product_image2'] = siteURL()."isdental/api/images/v2.0/user-products/".$result["product_image2"];
                }
                    
                if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                    $response['product_image3'] = siteURL()."api/images/v2.0/user-products/".$result["product_image3"];
                } else {
                    $response['product_image3'] = siteURL()."isdental/api/images/v2.0/user-products/".$result["product_image3"];
                }
                
                $response['product_description'] = $result["product_description"];
                $response['product_brand'] = $result["product_brand"];
                $response['product_model_number'] = $result["product_model_number"];
                $response['product_serial_number'] = $result["product_serial_number"];
                $response['product_purchase_date'] = $result["product_purchase_date"];

                $request_result = $db->getUserProductRequests($user_id, $product_id);
                $response["requests"] = array();
                while ($request = $request_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['request_id'] = $request["rqst_id"];
                    $tmp['request_description'] = $request["rqst_description"];
                 
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['request_image1'] = siteURL()."api/images/v2.0/user-requests/".$request["rqst_image1"];
                    } else {
                        $tmp['request_image1'] = siteURL()."isdental/api/images/v2.0/user-requests/".$request["rqst_image1"];
                    }
                    
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['request_image2'] = siteURL()."api/images/v2.0/user-requests/".$request["rqst_image2"];
                    } else {
                        $tmp['request_image2'] = siteURL()."isdental/api/images/v2.0/user-requests/".$request["rqst_image2"];
                    }
                    
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['request_image3'] = siteURL()."api/images/v2.0/user-requests/".$request["rqst_image3"];
                    } else {
                        $tmp['request_image3'] = siteURL()."isdental/api/images/v2.0/user-requests/".$request["rqst_image3"];
                    }
                 
                 
                    $tmp['request_ticket_number'] = $request["rqst_ticket_number"];
                    
                    switch($request["rqst_status"]){
                        case 0:
                            $tmp['request_status'] = "OPEN";
                            break;
                        case 1:
                            $tmp['request_status'] = "CLOSED";
                            break;
                    }
                    
                    $tmp['request_created_at'] = $request["rqst_created_at"];
        
                    $tmp['request_comments'] = array();
                    $request_comments = $db->getUserRequestComments($user_id, $request["rqst_id"]);
                    while ($comment = $request_comments->fetch_assoc()) {
                        $tmp2 = array();
                        $tmp2['comment_id'] = $comment["cmnt_id"];
                        $tmp2['comment_from'] = $comment["cmnt_from"];
                        $tmp2['comment_text'] = $comment["cmnt_text"];
                        $tmp2['comment_type'] = $comment["cmnt_type"];
                        $tmp2['comment_created_at'] = $comment["cmnt_created_at"];
                        array_push($tmp["request_comments"], $tmp2);
                    }
                    array_push($response["requests"], $tmp);
                }
    
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Events";
                echoResponse(200, $response);
            }
        });

$app->post('/user/request/comment', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('request_id', 'comment'));
            $response = array();
            $request_id = $app->request->post('request_id');
            $comment = $app->request->post('comment');

            $db = new DbHandler();
            
            if($db->insertUserRequestComment($request_id, $comment)) {
                $response["error"] = false;
                $response["message"] = "Comment inserted successfully";
                sendRequestCommentEmail($request_id, $user_id);
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to insert comment. Please try again";
                echoResponse(200, $response);
            }
    });


$app->get('/home/service/bkp', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $product_result = $db->getUserProducts($user_id);
            $request_result = $db->getUserRequests($user_id);
            $category_result = $db->getAllCategories();
            $brand_result = $db->getAllBrands();
       
            if($product_result && $request_result && $category_result && $brand_result){
                $response["error"] = false;
                $response["message"] = "Details fetched succesfully";
                $response["products"] = array();
                while ($product = $product_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['product_id'] = $product["prdct_id"];
                    $tmp['product_name'] = $product["prdct_name"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['product_image'] = siteURL()."api/images/v2.0/categories/".$product["prdct_image"];
                    } else {
                        $tmp['product_image'] = siteURL()."isdental/api/images/v2.0/categories/".$product["prdct_image"];
                    }
                    $tmp['product_description'] = $product["prdct_description"];
                    $tmp['product_category'] = $product["prdct_category"];
                    $tmp['product_brand'] = $product["prdct_brand"];
                    $tmp['product_model_number'] = $product["prdct_model_number"];
                    $tmp['product_serial_number'] = $product["prdct_serial_number"];
                    $tmp['product_purchase_date'] = $product["prdct_purchase_date"];
                    array_push($response["products"], $tmp);
                }
                
                $response["requests"] = array();
                while ($request = $request_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['request_id'] = $request["rqst_id"];
                    $tmp['request_description'] = $request["rqst_description"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['request_image'] = siteURL()."api/images/v2.0/categories/".$request["ctgry_image"];
                    } else {
                        $tmp['request_image'] = siteURL()."isdental/api/images/v2.0/categories/".$request["ctgry_image"];
                    }
                    $tmp['request_ticket_number'] = $request["rqst_ticket_number"];
                    $tmp['product_serial_number'] = $request["prdct_serial_number"];
                    $tmp['request_created_at'] = $request["rqst_created_at"];
        
                    array_push($response["requests"], $tmp);
                }
                
                $response["categories"] = array();
                while ($category = $category_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp["category_id"] = $category["ctgry_id"];
                    $tmp["category_name"] = $category["ctgry_name"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['category_image'] = siteURL()."api/images/v2.0/categories/".$category["ctgry_image"];
                    } else {
                        $tmp['category_image'] = siteURL()."isdental/api/images/v2.0/categories/".$category["ctgry_image"];
                    }
                    array_push($response["categories"], $tmp);
                }
                        
                $response["brands"] = array();
                while ($brand = $brand_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp["brand_id"] = $brand["brnd_id"];
                    $tmp["brand_name"] = $brand["brnd_name"];
                    array_push($response["brands"], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Requests and Products";
                echoResponse(200, $response);
            }
        });

$app->get('/user/products', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            
            if($result = $db->getUserProducts($user_id)){
                $response["error"] = false;
                $response["message"] = "User products fetched succesfully";
                $response["products"] = array();
                while ($product = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['product_id'] = $product["prdct_id"];
                    $tmp['product_name'] = $product["prdct_name"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['product_image'] = siteURL()."api/images/v2.0/categories/".$product["prdct_image"];
                    } else {
                        $tmp['product_image'] = siteURL()."isdental/api/images/v2.0/categories/".$product["prdct_image"];
                    }
                    $tmp['product_description'] = $product["prdct_description"];
                    $tmp['product_category'] = $product["prdct_category"];
                    $tmp['product_brand'] = $product["prdct_brand"];
                    $tmp['product_model_number'] = $product["prdct_model_number"];
                    $tmp['product_serial_number'] = $product["prdct_serial_number"];
                    $tmp['product_purchase_date'] = $product["prdct_purchase_date"];
                    array_push($response["products"], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Events";
                echoResponse(200, $response);
            }
        });

$app->post('/user/product/bkp', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('brand_id', 'brand_name', 'category_id', 'category_name', 'model_number', 'serial_number', 'purchase_date'));
            $response = array();
            $brand_id = $app->request->post('brand_id');
            $name = $app->request->post('brand_name');
            $category_id = $app->request->post('category_id');
            $description = $app->request->post('category_name');
            $model_number = $app->request->post('model_number');
            $serial_number = $app->request->post('serial_number');
            $purchase_date = $app->request->post('purchase_date');

            $db = new DbHandler();
            
            if($db->isUserProductExist($user_id, $model_number, $serial_number)) {
                $response["error"] = true;
                $response["message"] = "Product already exist.";
                echoResponse(200, $response);
            } else {
                if($db->insertUserProduct($user_id, $brand_id, $category_id, $name, $description, $model_number, $serial_number, $purchase_date)) {
                    $response["error"] = false;
                    $response["message"] = "Product inserted successfully";
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to insert product. Please try again";
                    echoResponse(200, $response);
                }
            }
              
    });

$app->post('/user/product', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('brand_id', 'brand_name', 'description', 'purchase_date'));
            $response = array();

	        date_default_timezone_set("Asia/Kolkata");
		    $dt = new DateTime();
		    $newdt = $dt->format('Ymd_His');

            $brand_id = $app->request->post('brand_id');
            $name = $app->request->post('brand_name');
            $description = $app->request->post('description');
            $model_number = $app->request->post('model_number');
            $serial_number = $app->request->post('serial_number');
            $purchase_date = $app->request->post('purchase_date');
            
            
            if($app->request->post('image1')){
                $image1 = $user_id."_".$newdt."_1.png";
                file_put_contents(".././images/v2.0/user-products/".$image1,base64_decode($app->request->post('image1')));
            } else {
                $image1 = "";
            }

            if($app->request->post('image2')){
                $image2 = $user_id."_".$newdt."_2.png";
                file_put_contents(".././images/v2.0/user-products/".$image2,base64_decode($app->request->post('image2')));
            } else {
                $image2 = "";
            }
            
            if($app->request->post('image3')){
                $image3 = $user_id."_".$newdt."_3.png";
                file_put_contents(".././images/v2.0/user-products/".$image3,base64_decode($app->request->post('image3')));
            } else {
                $image3 = "";
            }


            $db = new DbHandler();
            
            if(strlen($model_number) > 0 || strlen($serial_number) > 0){
                if($db->isUserProductExist($user_id, $model_number, $serial_number)) {
                    $response["error"] = true;
                    $response["message"] = "Product already exist.";
                    echoResponse(200, $response);
                } else {
                    if($db->insertUserProduct($user_id, $brand_id, $name, $description, $model_number, $serial_number, $purchase_date, $image1, $image2, $image3)) {
                        $response["error"] = false;
                        $response["message"] = "Product inserted successfully";
                        echoResponse(200, $response);
                    } else {
                        $response["error"] = true;
                        $response["message"] = "Failed to insert product. Please try again";
                        echoResponse(200, $response);
                    }
                }    
            } else {
                if($db->insertUserProduct($user_id, $brand_id, $name, $description, $model_number, $serial_number, $purchase_date, $image1, $image2, $image3)) {
                    $response["error"] = false;
                    $response["message"] = "Product inserted successfully";
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to insert product. Please try again";
                    echoResponse(200, $response);
                }
            }
            
    });

$app->put('/user/product/:product_id', 'authenticate', function($product_id) use($app) {
            global $user_id;
            verifyRequiredParams(array('description'));
            $response = array();
            $db = new DbHandler();

	        date_default_timezone_set("Asia/Kolkata");
		    $dt = new DateTime();
		    $newdt = $dt->format('Ymd_His');

            $description = $app->request->post('description');
            $model_number = $app->request->post('model_number');
            $serial_number = $app->request->post('serial_number');
            
            if($app->request->post('image1')){
                $image1 = $user_id."_".$newdt."_1.png";
                file_put_contents(".././images/v2.0/user-products/".$image1,base64_decode($app->request->post('image1')));
            } else {
                $image1 = "";
            }

            if($app->request->post('image2')){
                $image2 = $user_id."_".$newdt."_2.png";
                file_put_contents(".././images/v2.0/user-products/".$image2,base64_decode($app->request->post('image2')));
            } else {
                $image2 = "";
            }
            
            if($app->request->post('image3')){
                $image3 = $user_id."_".$newdt."_3.png";
                file_put_contents(".././images/v2.0/user-products/".$image3,base64_decode($app->request->post('image3')));
            } else {
                $image3 = "";
            }

            
            if($db->updateUserProduct($user_id, $product_id, $description, $model_number, $serial_number, $image1, $image2, $image3)) {
                $response["error"] = false;
                $response["message"] = "Product updated successfully";
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to update product. Please try again";
                echoResponse(200, $response);
            }
        });


$app->get('/user/requests', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            if($result = $db->getUserRequests($user_id)){
                $response["error"] = false;
                $response["message"] = "User requests fetched succesfully";
                $response["requests"] = array();
                while ($request = $result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['request_id'] = $request["rqst_id"];
                    $tmp['request_description'] = $request["rqst_description"];
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp['request_image'] = siteURL()."api/images/v2.0/categories/".$request["ctgry_image"];
                    } else {
                        $tmp['request_image'] = siteURL()."isdental/api/images/v2.0/categories/".$request["ctgry_image"];
                    }
                    $tmp['request_ticket_number'] = $request["rqst_ticket_number"];
                    $tmp['product_serial_number'] = $request["prdct_serial_number"];
                    $tmp['request_created_at'] = $request["rqst_created_at"];
                    
                    array_push($response["requests"], $tmp);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Events";
                echoResponse(200, $response);
            }
        });

$app->post('/user/request', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('product_id', 'description'));
            $response = array();
            $product_id = $app->request->post('product_id');
            $description = $app->request->post('description');
            
            date_default_timezone_set("Asia/Kolkata");
		    $dt = new DateTime();
		    $newdt = $dt->format('Ymd_His');

            if($app->request->post('image1')){
                $image1 = $user_id."_".$newdt."_1.png";
                file_put_contents(".././images/v2.0/user-requests/".$image1,base64_decode($app->request->post('image1')));
            } else {
                $image1 = "";
            }

            if($app->request->post('image2')){
                $image2 = $user_id."_".$newdt."_2.png";
                file_put_contents(".././images/v2.0/user-requests/".$image2,base64_decode($app->request->post('image2')));
            } else {
                $image2 = "";
            }

            if($app->request->post('image3')){
                $image3 = $user_id."_".$newdt."_3.png";
                file_put_contents(".././images/v2.0/user-requests/".$image3,base64_decode($app->request->post('image3')));
            } else {
                $image3 = "";
            }


            $db = new DbHandler();
            
            // if($db->isUserRequestExist($user_id, $product_id)) {
                // $response["error"] = true;
            //     $response["message"] = "Request already exist for this product.";
            //     echoResponse(200, $response);
            // } else {
            $request_id =$db->insertUserRequest($user_id, $product_id, $description, $image1, $image2, $image3);
                if($request_id) {
                    $response["error"] = false;
                    $response["message"] = "Service request generated successfully";
                    $response["ticket_number"] = $db->getTicketNumber($request_id);
                    sendRequestEmail($response["ticket_number"], $request_id, $product_id, $user_id);
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to generate request. Please try again";
                    echoResponse(200, $response);
                }
            // }
              
    });

$app->put('/user/request/:request_id', 'authenticate', function($request_id) use($app) {
            global $user_id;
            verifyRequiredParams(array('description'));
            $response = array();
            $db = new DbHandler();

	        date_default_timezone_set("Asia/Kolkata");
		    $dt = new DateTime();
		    $newdt = $dt->format('Ymd_His');

            $description = $app->request->post('description');
            
            if($app->request->post('image1')){
                $image1 = $user_id."_".$newdt."_1.png";
                file_put_contents(".././images/v2.0/user-requests/".$image1,base64_decode($app->request->post('image1')));
            } else {
                $image1 = "";
            }

            if($app->request->post('image2')){
                $image2 = $user_id."_".$newdt."_2.png";
                file_put_contents(".././images/v2.0/user-requests/".$image2,base64_decode($app->request->post('image2')));
            } else {
                $image2 = "";
            }
            
            if($app->request->post('image3')){
                $image3 = $user_id."_".$newdt."_3.png";
                file_put_contents(".././images/v2.0/user-requests/".$image3,base64_decode($app->request->post('image3')));
            } else {
                $image3 = "";
            }

            
            if($db->updateUserRequest($user_id, $request_id, $description, $image1, $image2, $image3)) {
                $response["error"] = false;
                $response["message"] = "Request updated successfully";
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to update request. Please try again";
                echoResponse(200, $response);
            }
        });

$app->post('/user/request/close', 'authenticate', function () use ($app) {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            verifyRequiredParams(array('request_id', 'rating'));
            $request_id = $app->request->post('request_id');
            $rating = $app->request->post('rating');
            
            if($app->request->post('comment')){
                $comment = $app->request->post('comment');
            } else {
                $comment = "";
            }
            
            
            $company_result = $db->getCompanyFromRequestId($request_id);

            if($db->closeUserRequest($user_id, $request_id)){
                $db->insertUserRating($user_id, $request_id, $company_result["cmpny_id"], $rating, $comment);
                $response["error"] = false;
                $response["message"] = "User requests closed succesfully";
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to close request";
                echoResponse(200, $response);
            }
        });

$app->get('/home/account', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            
          	date_default_timezone_set("Asia/Kolkata");
          	$dt = new DateTime();
		    $newdt = $dt->format('Y-m-d H:i:s');
	

            $favourite_result = $db->getUserFavourite($user_id);
            $enquiry_result = $db->getUserEnquiries($user_id);
            $offer_result = $db->getUserOffers($user_id);
            $app_pages = $db->getAppPages();
            $user_details = $db->getUserDetailsById($user_id);
            if($favourite_result){
                $response["error"] = false;
                $response["message"] = "Account details fetched succesfully";
                
                $response["user_isdental_id"] = $user_details["usr_isdental_id"];
                $response["user_name"] = $user_details["usr_name"];
                $response["user_email"] = $user_details["usr_email"];
                $response["user_mobile"] = $user_details["usr_mobile"];
                
                $response["favourites"] = array();
                while ($favourite = $favourite_result->fetch_assoc()) {
                    $tmp2 = array();
                    $tmp2['contact_id'] = $favourite["cntct_id"];
                    $tmp2['contact_name'] = $favourite["cmpny_name"]." (".$favourite["cntct_name"].")";
                    if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                        $tmp2['contact_image'] = siteURL()."api/images/v2.0/contacts/".$favourite["cntct_image"];
                    } else {
                        $tmp2['contact_image'] = siteURL()."isdental/api/images/v2.0/contacts/".$favourite["cntct_image"];
                    }
                    $tmp2['contact_phone'] = $favourite["cntct_phone"];
                    $tmp2['contact_location'] = $favourite["cntct_location"];
                    $tmp2['contact_favourite'] = true;
                    $tmp2['contact_email'] = $favourite["cntct_email"];
                    $tmp2['contact_website'] = $favourite["cntct_website"];
                    $tmp2['contact_type'] = $favourite["cntct_type"];
                    array_push($response["favourites"], $tmp2);
                }
                $response["offers"] = array();
                while ($offer = $offer_result->fetch_assoc()) {
                    $tmp = array();
                    $tmp['offer_id'] = $offer["ofr_id"];
                    $tmp['offer_text'] = $offer["ofr_text"];
                    $tmp['offer_user_id'] = $offer["ofr_usr_id"];
                    $tmp['offer_expire'] = $offer["ofr_expire_date"];
                    $tmp['offer_start'] = $offer["ofr_start_date"];
                    // 0=>Expired, 1=>Active, 2=>Upcoming
                    if(strtotime($newdt)>strtotime($offer["ofr_expire_date"])){
                        $tmp['offer_status'] = 0;
                    }
                    if(strtotime($newdt)<strtotime($offer["ofr_expire_date"]) && strtotime($newdt)>=strtotime($offer["ofr_start_date"]) ){
                        $tmp['offer_status'] = 1;
                    } 
                    
                    if(strtotime($offer["ofr_start_date"])>strtotime($newdt)){
                        $tmp["offer_status"] = 2;
                    }
                        
                    array_push($response["offers"], $tmp);
                }
                $response["enquiries"] = array();
                while ($enquiry = $enquiry_result->fetch_assoc()) {
                    $tmp2 = array();
                    $tmp2["enquiry_ticket_number"] = $enquiry["nqry_ticket_number"];
                    $tmp2["enquiry_status"] = $enquiry["nqry_status"];
                    $tmp2["enquiry_remark"] = $enquiry["nqry_remark"];
                    $tmp2["enquiry_comment"] = $enquiry["nqry_comment"];
                 
                    $tmp2["company_name"] = $enquiry["cmpny_name"];
                    $tmp2['product_id'] = $enquiry["prdct_id"];
                    $tmp2['product_name'] = $enquiry["prdct_name"];
                    try{
                        $tmp2['product_price'] = "Rs ".number_format($enquiry["prdct_price"])."/-";
                    } catch(Exception $e){
                        $tmp2['product_price'] = $enquiry["prdct_price"];
                    }
                    $tmp2['product_category'] = $enquiry["ctgry_name"];
                    if(strlen($enquiry["prdct_image"])>0){
                        if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
                            $tmp2['product_image'] = siteURL()."api/images/v2.0/products/".$enquiry["prdct_image"];
                        } else {
                            $tmp2['product_image'] = siteURL()."isdental/api/images/v2.0/products/".$enquiry["prdct_image"];
                        }
                    } else {
                        $tmp2['product_image'] = "";
                    }
                    $tmp2['product_description'] = $enquiry["prdct_description"];
                    $tmp2['product_packaging'] = $enquiry["prdct_packaging"];
                    array_push($response["enquiries"], $tmp2);
                }
                    
                $response["html_privacy_policy"] = $app_pages["privacy_policy"];
                $response["html_terms_of_use"] = $app_pages["terms_of_use"];
                $response["html_about_us"] = $app_pages["about_us"];
                $response["html_faqs"] = $app_pages["about_us"];
                $response["html_help_and_support"] = $app_pages["help_support"];
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Events";
                echoResponse(200, $response);
            }
        });

$app->post('/favourite', 'authenticate', function() use ($app) {
          	//type  1=>Company, 2=>Brand, 3=>Contact, 4=>Service
          	global $user_id;
            verifyRequiredParams(array('type', 'type_id'));
            $response = array();
            $type = $app->request->post('type');
            $type_id = $app->request->post('type_id');

            $db = new DbHandler();
            
            if($db->isFavouriteExist($user_id, $type_id, $type)) {
                $request_id =$db->removeUserFavourite($user_id, $type_id, $type);
                if($request_id) {
                    $response["error"] = false;
                    $response["message"] = "Favourite removed successfully";
                    $response["status"] = 2;
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to remove favourite. Please try again";
                    $response["status"] = 0;
                    echoResponse(200, $response);
                }
            } else {
                $request_id =$db->insertUserFavourite($user_id, $type_id, $type);
                if($request_id) {
                    $response["error"] = false;
                    $response["message"] = "Favourite added successfully";
                    $response["status"] = 1;
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to add favourite. Please try again";
                    $response["status"] = 0;
                    echoResponse(200, $response);
                }
            }
              
    });

$app->post('/enquiry', 'authenticate', function() use ($app) {
          	global $user_id;
            verifyRequiredParams(array('product_id'));
            $response = array();
            $product_id = $app->request->post('product_id');
            
            if($app->request->post('comment')){
                $comment = $app->request->post('comment');
            } else {
                $comment = "";
            }
          
            $db = new DbHandler();
            
            if(!$db->isEnquiryExist($user_id, $product_id)) {
                $enquiry_id =$db->insertUserEnquiry($user_id, $product_id, $comment);
                if($enquiry_id) {
                    $response["error"] = false;
                    $response["message"] = "Enquiry request generated successfully";
                    $response["ticket_number"] = $db->getEnquiryTicketNumber($enquiry_id);
                    $result = $db->getProductDetails($product_id);
                    $user_mobile = $db->getUserMobileNumber($user_id);

                    sendEnquiryEmail($response["ticket_number"], $enquiry_id, $product_id, $user_id);
                    sendEnquirySMSToUser($user_mobile, $result["company_id"], $result["product_name"]);
//                    sendEnquirySMSToUser("9873684678", 1, 2);
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to generate enquiry. Please try again";
                    echoResponse(200, $response);
                }
            } else {
                $response["error"] = true;
                $response["message"] = "Enquiry already exist for this product";
                echoResponse(200, $response);
            }
    });

$app->post('/enquiry2', 'authenticate', function() use ($app) {
          	global $user_id;
            verifyRequiredParams(array('offer_id'));
            $response = array();
            $offer_id = $app->request->post('offer_id');
            
            $db = new DbHandler();
            
            if(!$db->isEnquiryExist2($user_id, $offer_id)) {
                $enquiry_id =$db->insertUserEnquiry2($user_id, $offer_id);
                if($enquiry_id) {
                    $response["error"] = false;
                    $response["message"] = "Enquiry request generated successfully. We will contact you within 24 hours.";
                    $response["ticket_number"] = $db->getEnquiryTicketNumber($enquiry_id);
                    $result = $db->getOfferDetails($offer_id);
                    $user_mobile = $db->getUserMobileNumber($user_id);

                    sendEnquiryEmail2($response["ticket_number"], $enquiry_id, $offer_id, $user_id);
                    sendEnquirySMSToUser2($user_mobile, $result["offer_name"]);
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Some error occurred. Please try again";
                    echoResponse(200, $response);
                }
            } else {
                $response["error"] = true;
                $response["message"] = "You have already enquired for this product. We will contact you within 24 hours.";
                echoResponse(200, $response);
            }
    });









$app->get('/user/cart', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

          	date_default_timezone_set("Asia/Kolkata");
          	$dt = new DateTime();
		    $newdt = $dt->format('Y-m-d H:i:s');

            $cart_result = $db->getUserCartDetails($user_id);
            if($cart_result){
                $response["error"] = false;
                $response["message"] = "Cart details fetched succesfully";
                $response["products"] = array();
                while ($cart = $cart_result->fetch_assoc()) {
                    $tmp2 = array();
                    $tmp2['cart_id'] = $cart["crt_id"];
                    $tmp2['cart_qty'] = $cart["crt_qty"];
                    $tmp2['cart_added_on'] = $cart["crt_created_at"];
                    $tmp2['product_id'] = $cart["prdct_id"];
                    $tmp2['product_name'] = $cart["prdct_name"];
                    $tmp2['product_description'] = $cart["prdct_description"];
                    $tmp2['product_price'] = $cart["prdct_price"];
                    $tmp2['product_price2'] = $cart["prdct_price2"];
                    $tmp2['product_packaging'] = $cart["prdct_packaging"];
                    $tmp2['company_id'] = $cart["cmpny_id"];
                    $tmp2['company_name'] = $cart["cmpny_name"];
                    array_push($response["products"], $tmp2);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Cart details";
                echoResponse(200, $response);
            }
        });

$app->post('/user/cart/add', 'authenticate', function() use ($app) {
          	global $user_id;
            verifyRequiredParams(array('product_id'));
            $response = array();
            $product_id = $app->request->post('product_id');

            $db = new DbHandler();

            if(!$db->isProductExistInCart($user_id, $product_id)) {
                $cart_id =$db->insertProductInCart($user_id, $product_id);
                if($cart_id) {
                    $response["error"] = false;
                    $response["message"] = "Product inserted in cart successfully";
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to insert product in cart. Please try again";
                    echoResponse(200, $response);
                }
            } else {
                $response["error"] = true;
                $response["message"] = "Product already exist in the cart";
                echoResponse(200, $response);
            }
    });

$app->post('/user/cart/:product_id', 'authenticate', function($product_id) use($app) {
            global $user_id;
            verifyRequiredParams(array('quantity'));
            $response = array();
            $db = new DbHandler();

            $quantity = $app->request->post('quantity');
            if($quantity > 0){
                if($db->updateQuantityInCart($user_id, $product_id, $quantity)) {
                    $response["error"] = false;
                    $response["message"] = "Cart updated successfully";
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to update cart. Please try again";
                    echoResponse(200, $response);
                }
            } else {
                if($db->updateQuantityInCart($user_id, $product_id, $quantity)) {
                    $response["error"] = false;
                    $response["message"] = "Cart updated successfully";
                    echoResponse(200, $response);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to update cart. Please try again";
                    echoResponse(200, $response);
                }
            }
            
        });

$app->get('/user/address', 'authenticate', function () {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $address_result = $db->getUserAddresses($user_id);
            if($cart_result){
                $response["error"] = false;
                $response["message"] = "Cart details fetched succesfully";
                $response["products"] = array();
                while ($cart = $cart_result->fetch_assoc()) {
                    $tmp2 = array();
                    $tmp2['cart_id'] = $cart["crt_id"];
                    $tmp2['cart_qty'] = $cart["crt_qty"];
                    $tmp2['cart_added_on'] = $cart["crt_created_at"];
                    $tmp2['product_id'] = $cart["prdct_id"];
                    $tmp2['product_name'] = $cart["prdct_name"];
                    $tmp2['product_description'] = $cart["prdct_description"];
                    $tmp2['product_price'] = $cart["prdct_price"];
                    $tmp2['product_price2'] = $cart["prdct_price2"];
                    $tmp2['product_packaging'] = $cart["prdct_packaging"];
                    $tmp2['company_id'] = $cart["cmpny_id"];
                    $tmp2['company_name'] = $cart["cmpny_name"];
                    array_push($response["products"], $tmp2);
                }
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to fetch Cart details";
                echoResponse(200, $response);
            }
        });







$app->get('/contact/called/:contact_id', 'authenticate', function($contact_id) {
          	global $user_id;
            $response = array();
            $db = new DbHandler();
            if($db->incrementContactCalled($contact_id)) {
                $response["error"] = false;
                $response["message"] = "Contact call incremented successfull";
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Contact call failed to increment";
                echoResponse(200, $response);
            }
    });

$app->get('/contact/mailed/:contact_id', 'authenticate', function($contact_id) {
          	global $user_id;
            $response = array();
            $db = new DbHandler();
            if($db->incrementContactMailed($contact_id)) {
                $response["error"] = false;
                $response["message"] = "Contact mail incremented successfull";
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Contact mail failed to increment";
                echoResponse(200, $response);
            }
    });

$app->get('/event/clicked/:event_id', 'authenticate', function($event_id) {
          	global $user_id;
            $response = array();
            $db = new DbHandler();
            if($db->incrementEventClicked($event_id)) {
                $response["error"] = false;
                $response["message"] = "Event clicked incremented successfull";
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Event clicked failed to increment";
                echoResponse(200, $response);
            }
    });


$app->post('/init/application', 'authenticate', function () use ($app){
            global $user_id;
            verifyRequiredParams(array('app_version'));
            $app_version = $app->request->post('app_version');
        
            $response = array();
            $db = new DbHandler();
            
            $db->updateAppVersionInUserTable($user_id, $app_version);

            $response["error"] = false;
            $response["message"] = "Application Init Successfully";
            if($db->getCurrentAppVersion("ANDROID") > $app_version){
                $response["version_update"] = true;
            } else {
                $response["version_update"] = false;
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
                ->getByTitle('Expodent Chandigarh');
     
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
                        $tmp["name"] = $entry->getValues()["name"];
                        $tmp["mobile"] = $entry->getValues()["mobilenumber"];
                        sendEventRegisterationSMS($tmp["name"], $tmp["mobile"], $event_name);
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
                $response["message"] = $e->getMessage();
                echoResponse(200, $response);
            }
        });



$app->get('/campaign/sms/test', function () {
    
            $response = array();
       
            putenv('GOOGLE_APPLICATION_CREDENTIALS=../include/test.json');
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
            ->getByTitle('Test Google Script SMS');
 
            // Get the first worksheet (tab)
            $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
            $worksheet = $worksheets[0];
            $listFeed = $worksheet->getListFeed();
 

            foreach ($listFeed->getEntries() as $entry) {
                if (strtoupper($entry->getValues()['smssent']) === 'NO') {
                    $entry->update(array_merge($entry->getValues(), ['smssent' => 'YES']));
                    array_push($response, $entry->getValues());
                }
            }
  
            echo json_encode($response,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);   
            
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
    	    $response = array();
            $username="actiknow";
            $password="actiknow@2017";
	        $message= $otp." is your login OTP for IndiaSupply Dental App.";
	        $sender="INSPLY";
	        $mobile_number = $mobile;
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
    		$message2= $otp." is your login OTP for IndiaSupply Dental App.";
    		$response = $mclass->sendSmsToUser($message2, $mobile, "");
//    		$response["error"] = false;
    		return $response;
            break;
       }
}

function sendEventRegisterationSMS($user_name, $user_mobile, $event_name){
    global $sms_gateway;
    switch ($sms_gateway) {
    	case 1:
    	    $response = array();
    	    $username="actiknow";
            $password="actiknow@2017";
	        $message= "Dear ".$user_name.",\nCongratulations! You have been registered for ".$event_name.". Please show this message at registration desk and get your entry badge. Thanks.";
	        $sender="INSPLY";
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
            $mclass = new sendSms();
    		$message2= "Dear ".$user_name.",\nCongratulations! You have been registered for ".$event_name.". Please show this message at registration desk and get your entry badge. Thanks.";
    		$response = $mclass->sendSmsToUser($message2, $user_mobile, "");
    		return $response;
            break;
    }
}

function sendWelcomeSMS($user_mobile){
    global $sms_gateway;
	switch ($sms_gateway) {
    	case 1:
    	    $response = array();
            $username="actiknow";
            $password="actiknow@2017";
	        $message= "Congratulations, You have joined 20,000+ Dentists on IndiaSupply Dental App Get Weekly Offers, Contact details of ALL Dental Brands & updates on upcoming events";
	        $sender="INSPLY";
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
            $mclass = new sendSms();
    		$message2= "Congratulations, You have joined 20,000+ Dentists on IndiaSupply Dental App Get Weekly Offers, Contact details of ALL Dental Brands & updates on upcoming events";
    		$response = $mclass->sendSmsToUser($message2, $user_mobile, "");
    		return $response;
            break;
	}
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
            
        $mail->Subject = "Congratulations! You have joined 20,000+ Dentists - IndiaSupply Dental App";
        $mail->Body = "
Hi ".$user_name.",

Welcome to IndiaSupply Dental App.

Get The Following on App.

    1. Save Big. Get Weekly Expo Offers.

    2. Upcoming Dental Event Details.

    3. Contact Details of All Dental Brands & Brand's Dealers.

We're glad to have you here, kindly contact us at support@indiasupply.com for any assistance.

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


function sendEnquiryEmail($ticket_number, $enquiry_id, $product_id, $user_id){
    try{
        $db = new DbHandler();
        $user_details = $db->getUserDetailsById2($user_id);
        $enquiry_details = $db->getEnquiryDetails($enquiry_id, $product_id);


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
            
        $mail->addAddress('orders@indiasupply.com');
        $mail->addCC('gourav.garg@indiasupply.com');
        $mail->addBCC('karman.singh@actiknowbi.com');
       
        //$mail->isHTML(true);
            
        $mail->Subject = "New Enquiry Generated. #".$ticket_number;
        $mail->Body = "
A new enquiry has been generated

Ticket Number : ".$ticket_number."
Comments : ".$enquiry_details["enquiry_comment"]."

User Details

    ID : ".$user_details["user_id"]."
    Name : ".$user_details["user_name"]."
    Mobile : ".$user_details["user_mobile"]."
    Email : ".$user_details["user_email"]."

Product Details

    ID : ".$enquiry_details["product_id"]."
    Company : ".$enquiry_details["company_name"]."
    Name : ".$enquiry_details["product_name"]."
    Description : ".$enquiry_details["product_description"]."

Enquiry Details

    ID : ".$enquiry_id."
    Ticket Number : ".$ticket_number."
    Comments : ".$enquiry_details["enquiry_comment"]."


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

function sendRequestEmail($ticket_number, $request_id, $product_id, $user_id){
    try{
        $db = new DbHandler();
        $user_details = $db->getUserDetailsById2($user_id);
        $request_details = $db->getUserRequestDetails($request_id);
        
        $site_url;

        if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
            $site_url = siteURL()."api/images/v2.0/";
        } else {
            $site_url = siteURL()."isdental/api/images/v2.0/";
        }



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
            
        $mail->addAddress('orders@indiasupply.com');
        $mail->addCC('gourav.garg@indiasupply.com');
        $mail->addBCC('karman.singh@actiknowbi.com');

        //$mail->isHTML(true);
            
        $mail->Subject = "New Request Generated. #".$ticket_number;
        $mail->Body = "
A new request has been generated

Ticket Number : ".$ticket_number."
Request Description : ".$request_details["request_description"]."

User Details

    ID : ".$user_details["user_id"]."
    Name : ".$user_details["user_name"]."
    Mobile : ".$user_details["user_mobile"]."
    Email : ".$user_details["user_email"]."

Product Details

    ID : ".$request_details["product_id"]."
    Name : ".$request_details["product_name"]."
    Description : ".$request_details["product_description"]."
    Model Number : ".$request_details["product_model_number"]."
    Serial Number : ".$request_details["product_serial_number"]."
    Purchase Date : ".$request_details["product_purchase_date"]."
    Image 1 : ".$site_url."user-products/".$request_details["product_image1"]."
    Image 2 : ".$site_url."user-products/".$request_details["product_image2"]."
    Image 3 : ".$site_url."user-products/".$request_details["product_image3"]."

Request Details

    ID : ".$request_details["request_id"]."
    Description : ".$request_details["request_description"]."
    Image 1 : ".$site_url."user-requests/".$request_details["request_image1"]."
    Image 2 : ".$site_url."user-requests/".$request_details["request_image2"]."
    Image 3 : ".$site_url."user-requests/".$request_details["request_image3"]."



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

function sendRequestCommentEmail($request_id, $user_id){
    try{
        $db = new DbHandler();
        $user_details = $db->getUserDetailsById2($user_id);
        $request_details = $db->getUserRequestDetails($request_id);

        
        $request_comments = $db->getUserRequestComments($user_id, $request_id);
        $comment2 = "";
        while ($comment = $request_comments->fetch_assoc()) {
            $comment2 = $comment2."\n".$comment["cmnt_from"]." (".$comment["cmnt_created_at"].") : ".$comment["cmnt_text"].", Type : ".$comment["cmnt_type"];
        }
                    
                    
        $site_url;

        if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
            $site_url = siteURL()."api/images/v2.0/";
        } else {
            $site_url = siteURL()."isdental/api/images/v2.0/";
        }


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
            
        $mail->addAddress('orders@indiasupply.com');
        $mail->addCC('gourav.garg@indiasupply.com');
        $mail->addBCC('karman.singh@actiknowbi.com');

        //$mail->isHTML(true);
            
        $mail->Subject = "New Request Comment. #".$request_details["request_id"];
        $mail->Body = "
A new comment has been posted


User Details

    ID : ".$user_details["user_id"]."
    Name : ".$user_details["user_name"]."
    Mobile : ".$user_details["user_mobile"]."
    Email : ".$user_details["user_email"]."

Product Details

    ID : ".$request_details["product_id"]."
    Name : ".$request_details["product_name"]."
    Description : ".$request_details["product_description"]."
    Model Number : ".$request_details["product_model_number"]."
    Serial Number : ".$request_details["product_serial_number"]."
    Purchase Date : ".$request_details["product_purchase_date"]."
    Image 1 : ".$site_url."user-products/".$request_details["product_image1"]."
    Image 2 : ".$site_url."user-products/".$request_details["product_image2"]."
    Image 3 : ".$site_url."user-products/".$request_details["product_image3"]."

Request Details

    ID : ".$request_details["request_id"]."
    Description : ".$request_details["request_description"]."
    Image 1 : ".$site_url."user-requests/".$request_details["request_image1"]."
    Image 2 : ".$site_url."user-requests/".$request_details["request_image2"]."
    Image 3 : ".$site_url."user-requests/".$request_details["request_image3"]."

Comment Details

    ".$comment2."


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

function sendEnquirySMSToUser($user_mobile, $company_id, $product_name){
    global $sms_gateway;
	switch ($sms_gateway) {
    	case 1:
    	    $response = array();
            $username="actiknow";
            $password="actiknow@2017";
            if ($company_id == 238){
    	        $message= "Congratulations You have successfully claimed ".$product_name." on IndiaSupply Dental App. We will contact you within 24 hours.";
            } else {
    	        $message= "Thank you for your enquiry at IndiaSupply Dental App for ".$product_name.". We will contact you within 24 hours.";
            }
	        $sender="INSPLY";
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
            $mclass = new sendSms();
            if ($company_id == 238){
    	        $message2= "Congratulations You have successfully claimed ".$product_name." on IndiaSupply Dental App. We will contact you within 24 hours.";
            } else {
    	        $message2= "Thank you for your enquiry at IndiaSupply Dental App for ".$product_name.". We will contact you within 24 hours.";
            }
    		$response = $mclass->sendSmsToUser($message2, $user_mobile, "");
    		return $response;
            break;
	}
}



function sendEnquiryEmail2($ticket_number, $enquiry_id, $offer_id, $user_id){
    try{
        $db = new DbHandler();
        $user_details = $db->getUserDetailsById2($user_id);
        $enquiry_details = $db->getEnquiryDetails2($enquiry_id, $offer_id);


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
            
//        $mail->addAddress('karman.singh@actiknowbi.com');

        $mail->addAddress('orders@indiasupply.com');
        $mail->addCC('gourav.garg@indiasupply.com');
        $mail->addBCC('karman.singh@actiknowbi.com');
       
        //$mail->isHTML(true);
            
        $mail->Subject = "New Offer Enquiry Generated. #".$ticket_number;
        $mail->Body = "
A new enquiry has been generated

Ticket Number : ".$ticket_number."
Comments : ".$enquiry_details["enquiry_comment"]."

User Details

    ID : ".$user_details["user_id"]."
    Name : ".$user_details["user_name"]."
    Mobile : ".$user_details["user_mobile"]."
    Email : ".$user_details["user_email"]."

Offer Details

    ID : ".$enquiry_details["offer_id"]."
    Name : ".$enquiry_details["offer_name"]."
    Description : ".$enquiry_details["offer_description"]."

Enquiry Details

    ID : ".$enquiry_id."
    Ticket Number : ".$ticket_number."
    Comments : ".$enquiry_details["enquiry_comment"]."


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

function sendEnquirySMSToUser2($user_mobile, $offer_name){
    global $sms_gateway;
	switch ($sms_gateway) {
    	case 1:
    	    $response = array();
            $username="actiknow";
            $password="actiknow@2017";
            $message= "Congratulations You have successfully claimed offer on ".$offer_name." on IndiaSupply Dental App. We will contact you within 24 hours.";
	        $sender="INSPLY";
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
            $mclass = new sendSms();
            $message2 = "Congratulations You have successfully claimed offer on ".$offer_name." on IndiaSupply Dental App. We will contact you within 24 hours.";
    		$response = $mclass->sendSmsToUser($message2, $user_mobile, "");
    		return $response;
            break;
	}
}

$app->run();
?>