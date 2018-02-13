<?php

require_once '../include/Security.php';
require_once '../include/DbHandler.php';
require '.././libs/Slim/Slim.php';
require '.././libs/PHPMailer/PHPMailerAutoload.php';
require '.././libs/vendor/autoload.php';
  
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
    

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

$app->get('/test/phpinfo', function () {
            phpinfo();
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

$app->post('/user/register/event', 'authenticate', function() use ($app) {
            global $user_id;
            verifyRequiredParams(array('event_name'));
            $response = array();
            $event_name = strtoupper($app->request->post('event_name'));

            $db = new DbHandler();
            
            if (!$db->registerUserToEvent($user_id, $event_name)) {
                $response["error"] = true;
                $response["message"] = "Failed to register user to event";
                echoResponse(200, $response);
            } else {
                $response["error"] = false;
                $result = $db->getUserNameAndMobileByID($user_id);
                $message = sendExpodentRegisterationSMS($result["user_name"], $result["user_mobile"]);
                if ($message) {
                    $response["message"] = "User registered and SMS sent successfully";
                } else {
                    $response["message"] = "User registered but SMS not sent";
                }
                echoResponse(200, $response);
            }
    });


$app->get('/user/register/event/:event_name', 'authenticate', function ($event_name) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            if($db->isUserRegisteredToEvent($user_id, strtoupper($event_name))){
                $response["error"] = false;
                $response["message"] = "User already registered to event";
                $response["event_name"] = strtoupper($event_name);
                $response["registered"] = true;
                echoResponse(200, $response);
            } else {
                $response["error"] = false;
                $response["message"] = "User not registered to event";
                $response["event_name"] = strtoupper($event_name);
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

$app->get('/event/custom/:event_name', 'authenticate', function ($event_name) {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            
                $response["error"] = false;
                $response["message"] = "Event details fetched succesfully";
                $response['event_id'] = 9999;
                $response['event_name'] = "EXPODENT, Chandigarh";
                $response['event_description'] = "<head><meta name='viewport' content='target-densityDpi=device-dpi'/></head><p>Expodent 2017, Chandigarh would see a consequence of enthusiastic members of the entire Dental Fraternity consisting of Dentists, Professionals, Faculty and Students and eminent experts in the Industry &amp; Trade. Expodent 2017, Chandigarh will be a platform for professionals and students to learn new technologies and innovations in this specialized field. Expodent 2017 chandigarh is going to be one of the greatest shows in India. We request all of you to participate in this knowledge con uence and make it a grand success.<br/>ADITI (Association of Dental Industry and Trade of India) is committed to organizing Expodents in various regions of India. Expodents are mega events held at different parts of our country bringing together members of various branches of dentistry - Dental Specialists, Practitioners, Technicians, Students and Manufacturers &amp; Distributors of Clinical as well as Laboratory Dental Equipments, Instruments, Materials etc.</p>";
                $response['event_website'] = "http://www.aditidental.co.in/";
                $response['event_start_date'] = "2017-09-09";
                $response['event_end_date'] = "2017-09-10";
                $response['event_faq'] = "";
                $response['event_fees'] = 
"<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">
<tbody>
<tr>
<td ><strong>Company Name</strong></td>
<td><strong>Stall No.</strong></td>
</tr>
<tr>
<td >AARTI DENTAL</td>
<td>8</td>
</tr>
<tr>
<td >ACEMARKETING</td>
<td>143</td>
</tr>
<tr>
<td >ACTEON INDIAPVT LTD</td>
<td>106,107</td>
</tr>
<tr>
<td >AGGARWAL DENTALSUPPLY</td>
<td>111</td>
</tr>
<tr>
<td >AGKEM IMPEXPVT LTD</td>
<td>135,136</td>
</tr>
<tr>
<td >AKSHAR TRADING CO</td>
<td>22</td>
</tr>
<tr>
<td >ALBSURGICALSPVT LTD</td>
<td>54</td>
</tr>
<tr>
<td >ALFASURGICAL</td>
<td>144,145</td>
</tr>
<tr>
<td >ALPINEPOLYDENT</td>
<td>152</td>
</tr>
<tr>
<td >AMMDENT (AMRIT CHEM.&amp;MIN.AG.)</td>
<td>11,12</td>
</tr>
<tr>
<td >ASHOOSONS</td>
<td>6,7,175,176</td>
</tr>
<tr>
<td >AURANG DENTAL &amp;SURGICALS</td>
<td>69,70</td>
</tr>
<tr>
<td >AVCO CONSULTANCYSERVICES (P)LTD</td>
<td>3,4,5</td>
</tr>
<tr>
<td >BANSAL DENTAL TRADERS</td>
<td>146</td>
</tr>
<tr>
<td >BHATNAGAR DENTALSUPPLY</td>
<td>99</td>
</tr>
<tr>
<td >BOMBAY DENTAL &amp;SURGICAL</td>
<td>112,113</td>
</tr>
<tr>
<td >BRULON INTERNATIONAL</td>
<td>55</td>
</tr>
<tr>
<td >CAPRISONS</td>
<td>66,67</td>
</tr>
<tr>
<td >CHESA INC.</td>
<td>43</td>
</tr>
<tr>
<td >CLINIX INTELLIGENTMEDICALSYSTEM</td>
<td>71</td>
</tr>
<tr>
<td >COLTENE WHALEDENTPVT LTD</td>
<td>9,89</td>
</tr>
<tr>
<td >CONFIDENT DENTAL EQUIPMENTS LTD</td>
<td>60,61</td>
</tr>
<tr>
<td >CRB INTERNATIONAL</td>
<td>109,110</td>
</tr>
<tr>
<td >CROWN DENTAL</td>
<td>169,170</td>
</tr>
<tr>
<td >DDDMARKETING</td>
<td>57</td>
</tr>
<tr>
<td >DEEP DENTALSUPPLIER</td>
<td>101</td>
</tr>
<tr>
<td >DENFORT INTERNATIONAL</td>
<td>167,168</td>
</tr>
<tr>
<td >DENTAL TREESUPPLY</td>
<td>147,148</td>
</tr>
<tr>
<td >DENTALAUTOMATION</td>
<td>84</td>
</tr>
<tr>
<td >DENTALAVENUE INDIAPVT LTD</td>
<td>73,74</td>
</tr>
<tr>
<td >DENTAX INTERNATIONAL</td>
<td>58</td>
</tr>
<tr>
<td >DENTPRO DENTALMATERIAL CO.</td>
<td>10,45</td>
</tr>
<tr>
<td >DHRUVA ENTERPRISES -VARANASI</td>
<td>165</td>
</tr>
<tr>
<td >DIBYA INDUSTRIES INDIA</td>
<td>137,138</td>
</tr>
<tr>
<td >EDGE HEALTH CAREPVT. LTD.</td>
<td>149</td>
</tr>
<tr>
<td >ELECTOMACK</td>
<td>28</td>
</tr>
<tr>
<td >GC INDIA DENTALPVT. LTD.</td>
<td>166A</td>
</tr>
<tr>
<td >GDCMARKETING</td>
<td>18,19</td>
</tr>
<tr>
<td >GEMINI ENTERPRISES</td>
<td>1,2</td>
</tr>
<tr>
<td >GENTSPLY INDIAPVT LTD.</td>
<td>87,88</td>
</tr>
<tr>
<td >GIFT DENTAL CO</td>
<td>46,47</td>
</tr>
<tr>
<td >GLOBAL DENTAIDSPVT. LTD.</td>
<td>31</td>
</tr>
<tr>
<td >GOLDEN NIMBUS INDIAPVT. LTD.</td>
<td>118</td>
</tr>
<tr>
<td >HUNDAL DENTAL</td>
<td>130,131</td>
</tr>
<tr>
<td >IDS DENMEDPVT. LTD.</td>
<td>34,35</td>
</tr>
<tr>
<td >INDIA VIKING</td>
<td>95</td>
</tr>
<tr>
<td >INNODENT INDIA</td>
<td>13,14</td>
</tr>
<tr>
<td >INTERNATIONAL DENTALSYSTEM</td>
<td>36,37,38</td>
</tr>
<tr>
<td >IVOCLAR VIVADENTMARKETING INDIA</td>
<td>162,163</td>
</tr>
<tr>
<td >J J DENTAL CORPORATION</td>
<td>52</td>
</tr>
<tr>
<td >JAISHREESURGIDENT INDIAPVT. LTD.</td>
<td>50</td>
</tr>
<tr>
<td >KCK EQUIPMENTS</td>
<td>150</td>
</tr>
<tr>
<td >KHANNA ENTERPRISES</td>
<td>81,82</td>
</tr>
<tr>
<td >LALJI DENTAL EQUIPMENT</td>
<td>48,49</td>
</tr>
<tr>
<td >LIBRAL TRADERSPVT. LTD</td>
<td>41,42</td>
</tr>
<tr>
<td >LIFESTERIWARE - DELHI</td>
<td>83</td>
</tr>
<tr>
<td >M &amp;M DENTAL</td>
<td>151</td>
</tr>
<tr>
<td >MACRO DENTAL WORLDPVT LTD</td>
<td>140</td>
</tr>
<tr>
<td >MAX DENT</td>
<td>51</td>
</tr>
<tr>
<td >MD DENTAL CO.</td>
<td>17</td>
</tr>
<tr>
<td >MH DENTALPVT LTD.</td>
<td>102,103</td>
</tr>
<tr>
<td >MODERNORTHODONTICS</td>
<td>132</td>
</tr>
<tr>
<td >NAVKAR DENTALSUPPLY CO</td>
<td>72</td>
</tr>
<tr>
<td >NEELKANTH HEALTH CAREPVT LTD</td>
<td>30</td>
</tr>
<tr>
<td >OBERIO ENTERPRISES</td>
<td>53</td>
</tr>
<tr>
<td >ORACRAFT INSTRUMENT EXPORT</td>
<td>108</td>
</tr>
<tr>
<td >ORICAM HEALTHCARE INDIAPVTLTD</td>
<td>29</td>
</tr>
<tr>
<td >PIVOT FABRIQUE HP</td>
<td>164</td>
</tr>
<tr>
<td >PREMIER DENT INTERNATIONAL</td>
<td>122,123,124</td>
</tr>
<tr>
<td >PREVEST DENPRO LTD</td>
<td>39,40</td>
</tr>
<tr>
<td >PRIME DENTALPRODUCTSPVT LTD</td>
<td>68</td>
</tr>
<tr>
<td >PROMIS DENTALSYSTEM</td>
<td>92</td>
</tr>
<tr>
<td >PROVIDER</td>
<td>27</td>
</tr>
<tr>
<td >PUNJAB DENTAL &amp;MEDICALSUPPLY CO</td>
<td>96</td>
</tr>
<tr>
<td >R &amp; D IMPEX LUDHIANA</td>
<td>125,126,127</td>
</tr>
<tr>
<td >R.G. ENTERPRISES</td>
<td>32</td>
</tr>
<tr>
<td >R.K. DENTAL CO.</td>
<td>62,63</td>
</tr>
<tr>
<td >RAJLAKSHMI ENTERPRISES</td>
<td>139</td>
</tr>
<tr>
<td >REACH GLOBEL INDIAPVTLTD</td>
<td>85,86</td>
</tr>
<tr>
<td >REX DENT</td>
<td>104,105</td>
</tr>
<tr>
<td >RPK DENTAL CORPORATION</td>
<td>121</td>
</tr>
<tr>
<td >RUTHINIUM DENTALPRODUCT</td>
<td>44</td>
</tr>
<tr>
<td >S K DENMED</td>
<td>172,173</td>
</tr>
<tr>
<td >SAIKRIPA DIAGNOPHARMA</td>
<td>154</td>
</tr>
<tr>
<td >SAINI DENTAL CORPORATION</td>
<td>93</td>
</tr>
<tr>
<td >SAIPRANEET IMPEXPVT LTD</td>
<td>158</td>
</tr>
<tr>
<td >SARK HEALTH CAREPVT LTD</td>
<td>114,115</td>
</tr>
<tr>
<td >SELMARK</td>
<td>33</td>
</tr>
<tr>
<td >SETH INTERNATIONAL CORPORATION</td>
<td>75</td>
</tr>
<tr>
<td >SETHBROTHERS</td>
<td>177</td>
</tr>
<tr>
<td >SHAH DENTALSUPPLIERS</td>
<td>153</td>
</tr>
<tr>
<td >SHAIL DENTALSYSTEM</td>
<td>100</td>
</tr>
<tr>
<td >SHIVA ENTERPRISES</td>
<td>142</td>
</tr>
<tr>
<td >SHIVAPRODUCTS</td>
<td>119,120</td>
</tr>
<tr>
<td >SMRMARKETING</td>
<td>64,65</td>
</tr>
<tr>
<td >SS WHITE DENTALPVT LTD</td>
<td>15,16</td>
</tr>
<tr>
<td >STARDENT-CHANDIGARH</td>
<td>166,</td>
</tr>
<tr>
<td >SUDHAMASURGICAL INDUSTRIES</td>
<td>56</td>
</tr>
<tr>
<td >SUNNYDEEP ENTERPRISES</td>
<td>90,91,174</td>
</tr>
<tr>
<td >SUZDENT INDIAPVT LTD</td>
<td>156,157</td>
</tr>
<tr>
<td >SWARNIMMEDICARE</td>
<td>116</td>
</tr>
<tr>
<td >TOP DENT</td>
<td>59</td>
</tr>
<tr>
<td >TRUDENT INDIA</td>
<td>25,26</td>
</tr>
<tr>
<td >TRUDENT INTERNATIONAL</td>
<td>133,134</td>
</tr>
<tr>
<td >UNICORN DENMART LTD</td>
<td>76,77,78,79,80</td>
</tr>
<tr>
<td >UNION DENTAL</td>
<td>155</td>
</tr>
<tr>
<td >UNIQUE DENTAL COMPONY</td>
<td>160,161</td>
</tr>
<tr>
<td >UNIVERSAL DENTAL TRADERS</td>
<td>20,21</td>
</tr>
<tr>
<td >VIJAY DENTALSUPPLY CO</td>
<td>171</td>
</tr>
<tr>
<td >VILLA INDIA</td>
<td>97,98</td>
</tr>
<tr>
<td >VINIT ENTERPRISES</td>
<td>141</td>
</tr>
<tr>
<td >VISHAL DENTOCARE (P) LTD</td>
<td>159</td>
</tr>
<tr>
<td >WELCAREORTHODONTICS</td>
<td>128,129</td>
</tr>
<tr>
<td >ZENITH DENTALSUPPLYPVTLTD</td>
<td>23,24</td>
</tr>
<tr>
<td >ZENITH ENTERPRISES</td>
<td>117</td>
</tr>
</tbody>
</table>";

                $response['event_schedule'] = "";
                $response['event_venue'] = "<a href=\"geo:30.624395,76.823793?q=30.624395,76.823793(Palms Banquet),z=18\">Palms Banquet Zirakpur</a>";
                $response['event_city'] = "Chandigarh";
                $response['event_latitude'] = "30.624395";
                $response['event_longitude'] = "76.823793";
                $response['event_inclusions'] = "";
                $response['event_contact_details'] = "";
                $response['event_organiser_name'] = "ADITI (Association of Dental Industry and Trade of India)";
                $response['event_organiser_id'] = 4;
                $response['event_facebook'] = "";
                $response['event_twitter'] = "";
                $response['event_linkedin'] = "";
                $response['event_youtube'] = "";
                echoResponse(200, $response);
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

$app->get('/campaign/sms', function () {
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

            
                $user_details = array();
                
                foreach ($listFeed->getEntries() as $entry) {
                    $tmp = array();
                    if (strtoupper($entry->getValues()['ihavealreadyregisteredonisdentalapp']) != 'YES') {
                        $entry->update(array_merge($entry->getValues(), ['ihavealreadyregisteredonisdentalapp' => 'YES']));
                        $tmp["name"] = $entry->getValues()["name"];
                        $tmp["mobile"] = $entry->getValues()["mobilenumber"];
                        sendExpodentRegisterationSMS($tmp["name"], $tmp["mobile"]);
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


$app->post('/campaign/sms/new', function () use ($app){
            verifyRequiredParams(array('name', 'email', 'mobile'));
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $mobile = $app->request->post('mobile');
        
            $response = array();
            $db = new DbHandler();

		    $result = $db->insertTempDetails($name, $mobile, $email);
            if($result){
                $response["error"] = false;
                $response["message"] = "Inserted successfully";
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Error occurred";
                echoResponse(200, $response);
            }
        });






$app->get('/campaign/sms/test', function () {
    
            $response = array();
            $db = new DbHandler();
       
            putenv('GOOGLE_APPLICATION_CREDENTIALS=../include/credentials.json');
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
            ->getByTitle('Test Sheet');
 
            // Get the first worksheet (tab)
            $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
            $worksheet = $worksheets[0];
            $listFeed = $worksheet->getListFeed();
 
// $worksheet->clear();
 

            $response["data"]= array();



            foreach ($listFeed->getEntries() as $entry) {
                $entry->delete();
            }


            // $banner_result = $db->getBanners(1);
            // while ($banner = $banner_result->fetch_assoc()) {
            //     $tmp = array();
            //     $listFeed->insert([
            //         'timestamp' => $banner["bnnr_id"],
            //         'name' => $banner["bnnr_title"],
            //         'email' => $banner["bnnr_url"],
            //         'name' => $banner["bnnr_image"],
            //         'phone' => $banner["bnnr_type"]
            //         ]);
            // }


            // foreach ($listFeed->getEntries() as $entry) {
            //     if($entry->getValues()["id"] == 5){
                    
            //     } else {
            //         $listFeed->insert([
            //         'timestamp' => '9/7/2017',
            //         'name' => "karman",
            //         'email' => "karman.singh@gmail.com",
            //         'phone' => "9898989898",
            //         'message' => "hello"
            //         ]);

            //     }
                
            //     $tmp = array();
            //     $tmp["message"] = $entry->getValues()["message"];
            //     array_push($response["data"], $tmp);
            // }
  
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
    return true;
}

function sendExpodentRegisterationSMS($user_name, $user_mobile){
    // check balance messages
    //http://login.bulksmsgateway.in/userbalance.php?user=actiknow&password=actiknow@2017&type=3
    $username="actiknow";
    $password="actiknow@2017";
//    $username="shout";
//    $password="shout@share";
	$message= "Dear ".$user_name.",\nCongratulations! You have been registered for Expodent Chandigarh. Please show this message at registration desk and get your entry badge. Thanks.";
	$sender="INSPLY"; //ex:INVITE
	$mobile_number = $user_mobile;
	$url = "login.bulksmsgateway.in/sendmessage.php?user=".urlencode($username)."&password=".urlencode($password)."&mobile=".urlencode($mobile_number)."&message=".urlencode($message)."&sender=".urlencode($sender)."&type=".urlencode('3');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$curl_scraped_page = curl_exec($ch);
	curl_close($ch);
    return true;
}


$app->run();
?>