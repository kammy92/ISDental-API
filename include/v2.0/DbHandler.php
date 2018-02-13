<?php
class DbHandler {
	private $conn;
	function __construct() {
		require_once dirname(__FILE__) . '/DbConnect.php';
		$db = new DbConnect();
		$this->conn = $db->connect();
	}
	
	public function isValidApiKey($api_key) {
		$stmt = $this->conn->prepare("SELECT api_key_id FROM `tbl_api_key` WHERE api_key = ?");
		$stmt->bind_param("s", $api_key);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	public function isValidUserLoginKey($login_key) {
		$stmt = $this->conn->prepare("SELECT usr_id FROM `tbl_users` WHERE usr_login_key = ?");
		$stmt->bind_param("s", $login_key);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	public function getUserId($login_key) {
		$stmt = $this->conn->prepare("SELECT usr_id FROM `tbl_users` WHERE usr_login_key = ?");
		$stmt->bind_param("s", $login_key);
		if ($stmt->execute()) {
			$stmt->bind_result($user_id);
			$stmt->fetch();
			$stmt->close();
			return $user_id;
		} else {
			return NULL;
		}
	}

	public function isUserExist($user_mobile){
		$stmt = $this->conn->prepare("SELECT usr_id FROM `tbl_users` WHERE usr_mobile = ?");
		$stmt->bind_param("s", $user_mobile);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}
	
	
	public function generateOTP($mobile) {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d H:i:s');
		$newdt = $dt->format('Y-m-d H:i:s');
		$expiry_time = date("Y-m-d H:i:s",strtotime($newdt." +30 minutes"));

		$random_id_length = 6; 
		$rnd_id = uniqid(rand(),10); 
		$rnd_id = substr($rnd_id,0,$random_id_length); 
		$otp = $rnd_id;

		$stmt = $this->conn->prepare("SELECT * FROM `tbl_otps` WHERE otp_is_used = 0 AND otp_mobile = ?");
		$stmt->bind_param("s", $mobile);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		if ($num_rows > 0) {
			return NULL;
		} else {
			$stmt2 = $this->conn->prepare("INSERT INTO `tbl_otps`(`otp_mobile`, `otp_generated`, `otp_expires_at`, `otp_created_at`) VALUES (?,?,?,?)");
			$stmt2->bind_param("iiss", $mobile, $otp, $expiry_time, $newdt);
			$result = $stmt2->execute();
			$stmt2->close();
			return $result;
		}
	}

	public function getOTP($mobile) {
		$stmt = $this->conn->prepare("SELECT `otp_generated` FROM `tbl_otps` WHERE otp_mobile = ? && otp_is_used = 0");
		$stmt->bind_param("s", $mobile);
		if ($stmt->execute()) {
			$stmt->bind_result($otp);
			$stmt->fetch();
			$stmt->close();
			return $otp;
		} else {
			return NULL;
		}
	}

	public function setOTPUsed($mobile, $otp) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_otps` WHERE otp_mobile = ? && otp_generated = ?");
		$stmt->bind_param("ii", $mobile, $otp);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		if ($num_rows) {
			$stmt2 = $this->conn->prepare("UPDATE `tbl_otps` SET `otp_is_used` = '1' WHERE otp_mobile = ? && otp_generated = ?");
			$stmt2->bind_param("ii", $mobile, $otp);
			$stmt2->execute();
			$stmt2->store_result();
			$num_rows2 = $stmt2->num_rows;
			$stmt2->close();
		}
		if($num_rows > 0){
			return true;
		} else {
			return false;
		}
	}

	public function checkOTP($mobile, $otp) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_otps` WHERE otp_mobile = ? AND otp_generated = ? AND otp_is_used = 0");
		$stmt->bind_param("ii", $mobile, $otp);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		if ($num_rows) {
			$stmt2 = $this->conn->prepare("UPDATE `tbl_otps` SET `otp_is_used` = '1' WHERE otp_mobile = ? && otp_generated = ?");
			$stmt2->bind_param("ii", $mobile, $otp);
			$stmt2->execute();
			$stmt2->store_result();
			$num_rows2 = $stmt2->num_rows;
			$stmt2->close();
		}
		if($num_rows > 0){
			return true;
		} else {
			return false;
		}
	}
  
	public function userExist($mobile, $name, $email, $user_type, $firebase_id, $device_details) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_users` where usr_mobile = ?");
		$stmt->bind_param("i", $mobile);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		if ($num_rows) {
			$stmt2 = $this->conn->prepare("UPDATE `tbl_users` SET `usr_firebase_id` = ?, usr_name = ?, usr_email = ?, usr_type = ?, usr_device_details = ? WHERE usr_mobile = ?");
			$stmt2->bind_param("sssssi", $firebase_id, $name, $email, $user_type, $device_details, $mobile);
			$stmt2->execute();
			$stmt2->store_result();
			$num_rows2 = $stmt2->num_rows;
			$stmt2->close();
		}
		return $num_rows > 0;   
	}

	public function userExistByMobile($mobile) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_users` where usr_mobile = ?");
		$stmt->bind_param("i", $mobile);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;   
	}

	public function getUserDetails($mobile) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_users` where usr_mobile = ?");
		$stmt->bind_param("i", $mobile);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getUserDetailsById2($user_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `usr_id`, `usr_name`, `usr_mobile`, `usr_email` FROM `tbl_users` where usr_id = ?");
		$stmt->bind_param("i", $user_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["user_id"], $response["user_name"], $response["user_mobile"], $response["user_email"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return NULL;
		}
	}

	public function insertUser($name, $mobile, $email, $user_type, $firebase_id, $device_details){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d H:i:s');
		$newdt = $dt->format('Y-m-d H:i:s');
		
		
		$mt = explode('.', microtime(true));
//		echo microtime(true);
//		echo "\n".substr($mt[0],2, 8);
//		echo "\n".substr($mt[1],0,2);

		$isdental_id = "IS".substr($mt[0],2, 8).substr($mt[1],0,2);

		$login_key = md5($newdt.$name);

		$stmt = $this->conn->prepare("INSERT INTO `tbl_users` (`usr_isdental_id`, `usr_name`, `usr_mobile`, `usr_email`, `usr_type`, `usr_firebase_id`, `usr_login_key`, `usr_created_at`, `usr_device_details`) VALUES (?,?,?,?,?,?,?,?,?)");
		$stmt->bind_param("ssissssss", $isdental_id, $name, $mobile, $email, $user_type, $firebase_id, $login_key, $newdt, $device_details);
		if ($stmt->execute()) {
			$stmt->fetch();
			$stmt->close();
			return 1;
		} else {
			return NULL;
		}
	}
	
	
	
	public function getAllCategoryCompanies($category_name) {
		$stmt = $this->conn->prepare("SELECT *, (SELECT GROUP_CONCAT(b.`brnd_name`) FROM `tbl_brands` AS b INNER JOIN `tbl_brand_mappings` AS bm ON bm.`brnd_map_brnd_id` = b.`brnd_id` WHERE bm.`brnd_map_cmpny_id` = `cmpny_id` AND b.brnd_active = 1) AS cmpny_brands FROM `tbl_companies` WHERE find_in_set (cmpny_id,(SELECT GROUP_CONCAT(DISTINCT(ctgry_map_cmpny_id))  FROM `tbl_category_mappings` WHERE find_in_set(ctgry_map_ctgry_id, (SELECT GROUP_CONCAT(ctgry_id) FROM `tbl_categories` where ctgry_name = ?)) != 0))");
		$stmt->bind_param("s", $category_name);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	


	public function getCompanyBrands($company_id) {
		$stmt = $this->conn->prepare("SELECT b.* FROM `tbl_brands` AS b INNER JOIN `tbl_brand_mappings` AS bm ON bm.`brnd_map_brnd_id` = b.`brnd_id` WHERE bm.`brnd_map_cmpny_id` = ? AND b.brnd_active = 1");
		$stmt->bind_param("i", $company_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	
	public function getCompanyCategories($company_id) {
		$stmt = $this->conn->prepare("SELECT c.`ctgry_id`, c.`ctgry_level2` AS `ctgry_name` FROM `tbl_categories` AS c INNER JOIN `tbl_category_mappings` AS cm ON cm.`ctgry_map_ctgry_id` = c.`ctgry_id` WHERE cm.`ctgry_map_cmpny_id` = ?");
		$stmt->bind_param("i", $company_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	


	
	
	public function getOrganiserDetails($organiser_id) {
		$type = 'ORGANISER';
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_organisers` LEFT JOIN `tbl_social_links` ON `scl_lnk_type` = ? AND `scl_lnk_type_id` = ? WHERE `orgnsr_id` = ?");
		$stmt->bind_param("sii", $type, $organiser_id, $organiser_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getOrganiserEvents($organiser_id) {
		$stmt = $this->conn->prepare("SELECT `tbl_events`.* FROM `tbl_organisers` INNER JOIN `tbl_organiser_mappings` ON `orgnsr_map_orgnsr_id` = `orgnsr_id` RIGHT JOIN `tbl_events` ON `evnt_id` = `orgnsr_map_evnt_id` WHERE `orgnsr_id` = ? AND `evnt_active` = 1 ORDER BY `evnt_end_date` ASC");
		$stmt->bind_param("i", $organiser_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getDbVersionCode() {
		$stmt = $this->conn->prepare("SELECT db_vrsn_code FROM `tbl_db_version` ORDER BY `db_vrsn_updated_on` DESC, `db_vrsn_code` DESC LIMIT 1");
		if ($stmt->execute()) {
			$stmt->bind_result($db_vrsn_code);
			$stmt->fetch();
			$stmt->close();
			return $db_vrsn_code;
		} else {
			return 0;
		}
	}

	
	public function updateAppVersionInUserTable($user_id, $app_version){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d H:i:s');
		$newdt = $dt->format('Y-m-d H:i:s');

		$stmt2 = $this->conn->prepare("UPDATE `tbl_users` SET `usr_app_vrsn_code`= ?, `usr_last_login_at` = ? WHERE `usr_id` = ?");
		$stmt2->bind_param("isi", $app_version, $newdt, $user_id);
		$stmt2->execute();
		$stmt2->store_result();
		$num_rows2 = $stmt2->num_rows;
		$stmt2->close();
	}
	
	public function getCurrentAppVersion($device) {
		$stmt = $this->conn->prepare("SELECT app_vrsn_code FROM `tbl_app_versions` WHERE app_vrsn_device = ? ORDER BY app_vrsn_updated_on DESC LIMIT 1");
		$stmt->bind_param("s", $device);
		if ($stmt->execute()) {
			$stmt->bind_result($app_vrsn_code);
			$stmt->fetch();
			$stmt->close();
			return $app_vrsn_code;
		} else {
			return NULL;
		}
	}
	


	
	
	public function isUserRegisteredToEvent($user_id, $event_name){
		$stmt = $this->conn->prepare("SELECT `usr_reg_id` FROM `tbl_user_registrations` WHERE `usr_reg_user_id` = ? AND `usr_reg_event_name` = ?");
		$stmt->bind_param("is", $user_id, $event_name);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}
	
	public function registerUserToEvent($user_id, $event_name){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');
		
		$stmt = $this->conn->prepare("INSERT INTO `tbl_user_registrations`(`usr_reg_user_id`, `usr_reg_event_name`, `usr_reg_created_at`) VALUES (?,?,?)");
		$stmt->bind_param("iss", $user_id, $event_name, $newdt);
		if ($stmt->execute()) {
			$stmt->fetch();
			$stmt->close();
			return 1;
		} else {
			return NULL;
		}
	}


	public function getSpecialEventDetails($event_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_special_events` WHERE `spcl_evnt_id` = ?");
		$stmt->bind_param("i", $event_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function isUserRegisteredToSpecialEvent($user_id, $event_id){
		$stmt = $this->conn->prepare("SELECT `spcl_evnt_reg_id` FROM `tbl_special_event_registrations` WHERE `spcl_evnt_user_id` = ? AND `spcl_evnt_event_id` = ?");
		$stmt->bind_param("ii", $user_id, $event_id);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}
	
	public function registerUserToSpecialEvent($user_id, $event_id){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');
		
		$stmt = $this->conn->prepare("INSERT INTO `tbl_special_event_registrations`(`spcl_evnt_user_id`, `spcl_evnt_event_id`, `spcl_evnt_created_at`) VALUES (?,?,?)");
		$stmt->bind_param("iis", $user_id, $event_id, $newdt);
		if ($stmt->execute()) {
			$stmt->fetch();
			$stmt->close();
			return 1;
		} else {
			return NULL;
		}
	}


	
	public function getUserNameAndMobileByID($user_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `usr_mobile`, `usr_name` FROM `tbl_users` where `usr_id` = ?");
		$stmt->bind_param("i", $user_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["user_mobile"], $response["user_name"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return NULL;
		}
	}
	
	public function getEventNameByID($event_id) {
		$stmt = $this->conn->prepare("SELECT `spcl_evnt_name` FROM `tbl_special_events` WHERE `spcl_evnt_id` = ?");
		$stmt->bind_param("i", $event_id);
		if ($stmt->execute()) {
			$stmt->bind_result($event_name);
			$stmt->fetch();
			$stmt->close();
			return $event_name;
		} else {
			return NULL;
		}
	}
	
	
	public function insertTempDetails($name, $mobile, $email) {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');

		$stmt = $this->conn->prepare("INSERT INTO `tbl_delete`(`name`, `email`, `mobile`, `created_at`) VALUES (?,?,?,?)");
		$stmt->bind_param("ssss", $name, $email, $mobile, $newdt);
		if ($stmt->execute()) {
    		$stmt->fetch();
			$stmt->close();
            return 1;
		} else {
			return NULL;
		}
	}
	
	public function totalUserRegisteredToEvent($event_name){
		$stmt = $this->conn->prepare("SELECT `usr_reg_id` FROM `tbl_user_registrations` WHERE `usr_reg_event_name` = ?");
		$stmt->bind_param("s", $event_name);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows;
	}

	public function totalUserRegisteredToSpecialEvent($event_id){
		$stmt = $this->conn->prepare("SELECT `spcl_evnt_reg_id` FROM `tbl_special_event_registrations` WHERE `spcl_evnt_event_id` = ?");
		$stmt->bind_param("i", $event_id);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	// for getting avg_rating   
	// SELECT ROUND(AVG(rtng_value), 1) AS avg_rating FROM `tbl_rating` where rtng_cmpny_id = 2
	
	
	
	
	
	
	
	
	
	public function getAllCurrentAndUpcomingEvents() {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d');
		$newdt = $dt->format('Y-m-d');

		$stmt = $this->conn->prepare("SELECT * FROM `tbl_events` WHERE `evnt_active` = 1 AND `evnt_end_date` > ? ORDER BY `evnt_start_date` ASC");
		$stmt->bind_param("s", $newdt);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getAllPastEvents() {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d');
		$newdt = $dt->format('Y-m-d');

		$stmt = $this->conn->prepare("SELECT * FROM `tbl_events` WHERE `evnt_active` = 1 AND `evnt_end_date` < ? ORDER BY `evnt_start_date` ASC");
		$stmt->bind_param("s", $newdt);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getEnquiryDetails($enquiry_id, $product_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `prdct_id`, `prdct_name`, `prdct_description`, `cmpny_name`, (SELECT `nqry_comment` FROM `tbl_user_enquiries` WHERE `nqry_id` = ?) AS `nqry_comment` FROM `tbl_products` INNER JOIN `tbl_companies` ON `prdct_cmpny_id` = `cmpny_id` WHERE `prdct_id` = ?");
		$stmt->bind_param("ii", $enquiry_id, $product_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["product_id"], $response["product_name"], $response["product_description"], $response["company_name"], $response["enquiry_comment"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return NULL;
		}
	}
	
	

	public function getEventDetails($event_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_events` WHERE `evnt_id` = ? AND evnt_active = 1");
		$stmt->bind_param("i", $event_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getEventExhibitors($event_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_event_exhibitors` WHERE `exbtr_active` = 1 AND `exbtr_evnt_id` = ?");
		$stmt->bind_param("i", $event_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getEventSpeakers($event_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_event_speakers` WHERE `spkr_evnt_id` = ? AND `spkr_active` = 1");
		$stmt->bind_param("i", $event_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getEventDates($event_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_event_dates` WHERE `dat_evnt_id` = ?");
		$stmt->bind_param("i", $event_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getEventSchedules($event_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_event_schedule` WHERE `shdul_evnt_id` = ? AND `shdul_active` = 1");
		$stmt->bind_param("i", $event_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function isUserProductExist($user_id, $model_number, $serial_number){
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_user_products` WHERE `prdct_usr_id` = ? AND `prdct_model_number` = ? AND `prdct_serial_number` = ?");
		$stmt->bind_param("iss", $user_id, $model_number, $serial_number);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}
	
	public function insertUserProductBkp($user_id, $brand_id, $category_id, $name, $description, $model_number, $serial_number, $purchase_date) {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');

		$stmt = $this->conn->prepare("INSERT INTO `tbl_user_products`(`prdct_usr_id`, `prdct_brnd_id`, `prdct_ctgry_id`, `prdct_name`, `prdct_description`, `prdct_model_number`, `prdct_serial_number`, `prdct_purchase_date`, `prdct_created_at`) VALUES (?,?,?,?,?,?,?,?,?)");
		$stmt->bind_param("iiissssss", $user_id, $brand_id, $category_id, $name, $description, $model_number, $serial_number, $purchase_date, $newdt);
		if ($stmt->execute()) {
    		$stmt->fetch();
			$stmt->close();
            return 1;
		} else {
			return NULL;
		}
	}
	
	public function insertUserProduct($user_id, $brand_id, $name, $description, $model_number, $serial_number, $purchase_date, $image1, $image2, $image3) {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');

		$stmt = $this->conn->prepare("INSERT INTO `tbl_user_products`(`prdct_usr_id`, `prdct_brnd_id`, `prdct_name`, `prdct_description`, `prdct_model_number`, `prdct_serial_number`, `prdct_purchase_date`, `prdct_image1`, `prdct_image2`, `prdct_image3`, `prdct_created_at`) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
		$stmt->bind_param("iisssssssss", $user_id, $brand_id, $name, $description, $model_number, $serial_number, $purchase_date, $image1, $image2, $image3, $newdt);
		if ($stmt->execute()) {
    		$stmt->fetch();
			$stmt->close();
            return 1;
		} else {
			return NULL;
		}
	}
	
	public function updateUserProduct($user_id, $product_id, $description, $model_number, $serial_number, $image1, $image2, $image3) {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');

		$stmt = $this->conn->prepare("UPDATE `tbl_user_products` SET `prdct_description` = ?, `prdct_model_number` = ?,`prdct_serial_number` = ?, `prdct_image1` = ?,`prdct_image2` = ?,`prdct_image3` = ? WHERE `prdct_usr_id` = ? AND `prdct_id` = ?");
		$stmt->bind_param("ssssssii", $description, $model_number, $serial_number, $image1, $image2, $image3, $user_id, $product_id);
		if ($stmt->execute()) {
    		$stmt->fetch();
			$stmt->close();
            return 1;
		} else {
			return NULL;
		}
	}
	
	public function getUserProducts($user_id) {
		$stmt = $this->conn->prepare("SELECT `tbl_user_products`.*, `brnd_name` AS `prdct_brand`  FROM `tbl_user_products` INNER JOIN `tbl_brands` ON `prdct_brnd_id` = `brnd_id` WHERE `prdct_usr_id` = ?");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getUserProductLastRequestStatus($user_id, $product_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `rqst_ticket_number`, `rqst_status`, `rqst_created_at` FROM `tbl_user_requests` WHERE `rqst_active` = 1 AND `rqst_usr_id` = ? AND `rqst_usr_prdct_id` = ? ORDER BY `rqst_created_at` DESC LIMIT 1");
				$stmt->bind_param("ii", $user_id, $product_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["request_ticket_number"], $response["request_status"], $response["request_created_at"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return NULL;
		}
	}
	
	public function getUserProductDetails($user_id, $product_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `prdct_name`, `prdct_description`, `prdct_model_number`, `prdct_serial_number`, `prdct_purchase_date`, `prdct_image1`, `prdct_image2`, `prdct_image3`, `brnd_name` AS `prdct_brand` FROM `tbl_user_products` LEFT JOIN `tbl_brands` ON `prdct_brnd_id` = `brnd_id` WHERE `prdct_usr_id` = ? AND `prdct_id` = ?");
		$stmt->bind_param("ii", $user_id, $product_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["product_name"], $response["product_description"], $response["product_model_number"], $response["product_serial_number"], $response["product_purchase_date"], $response["product_image1"], $response["product_image2"], $response["product_image3"], $response["product_brand"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return NULL;
		}
	}
	
	public function getUserProductRequests($user_id, $product_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_user_requests` INNER JOIN `tbl_user_products` ON `rqst_usr_prdct_id` = `prdct_id` WHERE `rqst_usr_id` = ? AND `rqst_usr_prdct_id` = ? AND `rqst_active` = 1");
		$stmt->bind_param("ii", $user_id, $product_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getUserRequestComments($user_id, $request_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_user_request_comments` WHERE `cmnt_rqst_id` = ?");
		$stmt->bind_param("i", $request_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function insertUserRequestComment($request_id, $comment) {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');
		
		$stmt = $this->conn->prepare("INSERT INTO `tbl_user_request_comments`(`cmnt_rqst_id`, `cmnt_from`, `cmnt_text`, `cmnt_type`, `cmnt_created_at`) VALUES (?,'YOU',?,0,?)");
		$stmt->bind_param("iss", $request_id,  $comment, $newdt);
		if ($stmt->execute()) {
			$request_id = $stmt->insert_id;
    		$stmt->fetch();
			$stmt->close();
            return $request_id;
		} else {
			return NULL;
		}
	}
	
	public function isUserRequestExist($user_id, $product_id){
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_user_requests` WHERE `rqst_usr_prdct_id` = ? AND `rqst_usr_id` = ?");
		$stmt->bind_param("ii", $product_id, $user_id);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	public function insertUserRequest($user_id, $product_id, $description, $image1, $image2, $image3) {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');
		
		$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    	$randomString = '';
        for ($i = 0; $i < 2; $i++) {
        	$randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
	    
	    $characters2 = '0123456789';
        $randomString2 = '';
        for ($i = 0; $i < 6; $i++) {
        	$randomString2 .= $characters2[rand(0, strlen($characters2) - 1)];
    	}
            
	    $ticket_number = $randomString."8737".$randomString2;

		$stmt = $this->conn->prepare("INSERT INTO `tbl_user_requests`(`rqst_usr_prdct_id`, `rqst_usr_id`, `rqst_ticket_number`, `rqst_description`, `rqst_image1`, `rqst_image2`, `rqst_image3`, `rqst_active`, `rqst_created_at`) VALUES (?,?,?,?,?,?,?,1,?)");
		$stmt->bind_param("iissssss", $product_id, $user_id, $ticket_number, $description, $image1, $image2, $image3, $newdt);
		if ($stmt->execute()) {
			$request_id = $stmt->insert_id;
    		$stmt->fetch();
			$stmt->close();
            return $request_id;
		} else {
			return NULL;
		}
	}

	public function updateUserRequest($user_id, $request_id, $description, $image1, $image2, $image3) {
		$stmt = $this->conn->prepare("UPDATE `tbl_user_requests` SET `rqst_description` = ?,`rqst_image1` = ?,`rqst_image2` = ?,`rqst_image3` = ? WHERE `rqst_id` = ? AND `rqst_usr_id` = ?");
		$stmt->bind_param("ssssii", $description, $image1, $image2, $image3, $request_id, $user_id);
		if ($stmt->execute()) {
    		$stmt->fetch();
			$stmt->close();
            return 1;
		} else {
			return NULL;
		}
	}

	public function getTicketNumber($request_id) {
		$stmt = $this->conn->prepare("SELECT `rqst_ticket_number` FROM `tbl_user_requests` WHERE `rqst_id` = ?");
		$stmt->bind_param("i", $request_id);
		if ($stmt->execute()) {
			$stmt->bind_result($ticket_number);
			$stmt->fetch();
			$stmt->close();
			return $ticket_number;
		} else {
			return NULL;
		}
	}

	public function getUserRequests($user_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_user_requests` INNER JOIN `tbl_user_products` ON `rqst_usr_prdct_id` = `prdct_id` INNER JOIN `tbl_categories` ON `ctgry_id` = `prdct_ctgry_id` WHERE `rqst_usr_id` = ? AND `rqst_active` = 1");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getUserRequestDetails($request_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT rqst_id, rqst_description, rqst_image1, rqst_image2, rqst_image3, prdct_id, prdct_name, prdct_description, prdct_model_number, prdct_serial_number, prdct_image1, prdct_image2, prdct_image3, prdct_purchase_date FROM `tbl_user_requests` INNER JOIN `tbl_user_products` ON `rqst_usr_prdct_id` = `prdct_id` WHERE `rqst_id` = ?");
		$stmt->bind_param("i", $request_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["request_id"], $response["request_description"], $response["request_image1"], $response["request_image2"],$response["request_image3"],$response["product_id"],$response["product_name"],$response["product_description"],$response["product_model_number"],$response["product_serial_number"],$response["product_image1"],$response["product_image2"],$response["product_image3"],$response["product_purchase_date"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return NULL;
		}
	}

	public function getAllCompanies() {
		$stmt = $this->conn->prepare("SELECT *, (SELECT COUNT(*) FROM `tbl_user_ratings` WHERE `rtng_cmpny_id` = `cmpny_id` AND `rtng_type` = 1) AS `total_ratings`, (SELECT ROUND(AVG(`rtng_value`), 1) FROM `tbl_user_ratings` WHERE `rtng_cmpny_id` = `cmpny_id` AND `rtng_type` = 1) AS `cmpny_rating`, (SELECT COUNT(*) FROM `tbl_company_contacts` WHERE `cntct_cmpny_id` = 1 AND `cntct_active` = 1) AS `total_contacts`, (SELECT GROUP_CONCAT(`ctgry_name` SEPARATOR ' / ') FROM `tbl_category_mappings` INNER JOIN `tbl_categories` ON `ctgry_map_ctgry_id`= `ctgry_id` WHERE `ctgry_map_cmpny_id` = `cmpny_id`) AS `cmpny_categories` FROM `tbl_companies` WHERE `cmpny_active` = 1 ORDER BY `cmpny_featured` DESC, `cmpny_name` ASC");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getAllCompanies2() {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_companies` WHERE `cmpny_active` = 1 ORDER BY `cmpny_featured` DESC, `cmpny_name` ASC");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getCompanyFromRequestId($request_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `cmpny_id`, `cmpny_name` FROM `tbl_user_requests` INNER JOIN `tbl_user_products` ON `rqst_usr_prdct_id` = `prdct_id` INNER JOIN `tbl_companies` ON `prdct_brnd_id` = `cmpny_id` WHERE `rqst_id` = ?");
		$stmt->bind_param("i", $request_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["cmpny_id"], $response["cmpny_name"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return $response;
		}
	}

	public function getCompanyContacts($user_id, $company_id) {
		$stmt = $this->conn->prepare("SELECT *, (SELECT COUNT(*) FROM `tbl_user_favourites` WHERE `fvrt_usr_id` = ? AND `fvrt_type` = 3 AND `fvrt_type_id` = `cntct_id`) AS `cntct_favourite` FROM `tbl_company_contacts` WHERE `cntct_cmpny_id` = ? AND `cntct_active` = 1");
		$stmt->bind_param("ii", $user_id,  $company_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getCompanyDetails($company_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `cmpny_id`, `cmpny_name`, `cmpny_image`, `cmpny_website`, `cmpny_email`, `cmpny_description`, (SELECT COUNT(*) FROM `tbl_user_ratings` WHERE `rtng_cmpny_id` = `cmpny_id` AND `rtng_type` = 1) AS `total_ratings`, (SELECT ROUND(AVG(`rtng_value`), 1) FROM `tbl_user_ratings` WHERE `rtng_cmpny_id` = `cmpny_id` AND `rtng_type` = 1)  AS `cmpny_rating`, (SELECT COUNT(*) FROM `tbl_company_contacts` WHERE `cntct_cmpny_id` = `cmpny_id` AND `cntct_active` = 1) AS `total_contacts`, (SELECT GROUP_CONCAT(`ctgry_name` SEPARATOR ' / ') FROM `tbl_category_mappings` INNER JOIN `tbl_categories` ON `ctgry_map_ctgry_id`= `ctgry_id` WHERE `ctgry_map_cmpny_id` = `cmpny_id` ORDER BY `ctgry_group_order` ASC, `ctgry_sort_order` ASC)  AS `cmpny_categories`, (SELECT GROUP_CONCAT(`cmpny_ofr_text` SEPARATOR '\n') FROM `tbl_company_offers` WHERE `cmpny_ofr_cmpny_id` = `cmpny_id` AND `cmpny_ofr_active` = 1)  AS `cmpny_offers` FROM `tbl_companies` WHERE `cmpny_id` = ?");
		$stmt->bind_param("i", $company_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["cmpny_id"], $response["cmpny_name"], $response["cmpny_image"], $response["cmpny_website"], $response["cmpny_email"], $response["cmpny_description"], $response["total_ratings"], $response["cmpny_rating"], $response["total_contacts"], $response["cmpny_categories"], $response["cmpny_offers"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return $response;
		}
	}

	public function getCompanyProductGroups($company_id) {
		$stmt = $this->conn->prepare("SELECT `pc`.`prdct_grp_id`, `pc`.`prdct_grp_name`, `pc`.`prdct_grp_type` FROM `tbl_products` AS `p` INNER JOIN `tbl_product_groups` AS `pc` ON `p`.`prdct_grp_id` = `pc`.`prdct_grp_id` WHERE `prdct_cmpny_id` = ? AND `prdct_active` = 1 GROUP BY `p`.`prdct_grp_id` ORDER BY `prdct_grp_sort_order` ASC");
		$stmt->bind_param("i", $company_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getCompanyProductsByGroup($user_id, $company_id, $product_group_id) {
		$stmt = $this->conn->prepare("SELECT *, (SELECT COUNT(*) FROM `tbl_user_enquiries` WHERE `nqry_usr_id` = ? AND `nqry_status` = 0 AND `nqry_prdct_id` = `prdct_id`) AS `prdct_enquiry` FROM `tbl_products` INNER JOIN `tbl_categories` ON `ctgry_id` = `prdct_ctgry_id` WHERE `prdct_cmpny_id` = ? AND `prdct_grp_id` = ? AND `prdct_active` = 1");
		$stmt->bind_param("iii", $user_id, $company_id, $product_group_id);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getAllCategories() {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_categories` ORDER BY `ctgry_group_order` ASC, `ctgry_sort_order` ASC");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getCategoryGroups() {
		$stmt = $this->conn->prepare("SELECT DISTINCT(ctgry_group) FROM `tbl_categories` ORDER BY `ctgry_group_order` ASC");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getCategoriesByGroup($category_group) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_categories` WHERE `ctgry_group` = ? ORDER BY `ctgry_group_order` ASC, `ctgry_sort_order` ASC");
		$stmt->bind_param("s", $category_group);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getAllBrands() {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_brands` WHERE `brnd_active` = 1");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getAllBanners() {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_banners` WHERE bnnr_active = 1 AND `bnnr_screen` IS NULL");
		$stmt->execute();
		$banners = getResult($stmt);
		$stmt->close();
		return $banners;
	}
	
	public function getFeaturedBanners() {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_banners` WHERE bnnr_active = 1 AND `bnnr_screen` = 1");
		$stmt->execute();
		$banners = getResult($stmt);
		$stmt->close();
		return $banners;
	}
	
	public function getOfferBanners() {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_banners` WHERE bnnr_active = 1 AND `bnnr_screen` = 0");
		$stmt->execute();
		$banners = getResult($stmt);
		$stmt->close();
		return $banners;
	}
	
	public function getAllFeaturedCompanies() {
		$stmt = $this->conn->prepare("SELECT *, (SELECT COUNT(*) FROM `tbl_user_ratings` WHERE `rtng_cmpny_id` = `cmpny_id` AND `rtng_type` = 1) AS `total_ratings`, (SELECT ROUND(AVG(`rtng_value`), 1) FROM `tbl_user_ratings` WHERE `rtng_cmpny_id` = `cmpny_id` AND `rtng_type` = 1) AS `cmpny_rating`, (SELECT COUNT(*) FROM `tbl_company_contacts` WHERE `cntct_cmpny_id` = `cmpny_id` AND `cntct_active` = 1) AS `total_contacts`, (SELECT GROUP_CONCAT(`ctgry_name` SEPARATOR ' / ') FROM `tbl_category_mappings` INNER JOIN `tbl_categories` ON `ctgry_map_ctgry_id`= `ctgry_id` WHERE `ctgry_map_cmpny_id` = `cmpny_id`) AS `cmpny_categories`, (SELECT COUNT(*) FROM `tbl_company_offers` WHERE `cmpny_ofr_cmpny_id` = `cmpny_id` AND `cmpny_ofr_active` = 1)  AS `total_offers`, (SELECT COUNT(*) FROM `tbl_products` WHERE `prdct_cmpny_id` = `cmpny_id` AND `prdct_active` = 1)  AS `total_products` FROM `tbl_companies` WHERE `cmpny_active` = 1 AND `cmpny_featured` = 1 ORDER BY `cmpny_sort_order` ASC");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	
	public function getAllHomeOffers() {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_offers` WHERE `ofr_active` = 1 ORDER BY `ofr_end_date` ASC");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}


	public function getUserDetailsById($user_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `usr_isdental_id`, `usr_mobile`, `usr_name`, `usr_email` FROM `tbl_users` WHERE usr_id = ?");
		$stmt->bind_param("i", $user_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["usr_isdental_id"], $response["usr_mobile"], $response["usr_name"], $response["usr_email"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return $response;
		}
	}
	
	public function getAppPages() {
		$response = array();
		$stmt = $this->conn->prepare("SELECT  `app_pgs_privacy_policy`, `app_pgs_terms_of_use`, `app_pgd_about_us`, `app_pgs_help_support` FROM `tbl_app_pages` WHERE `app_pgs_active` = 1 LIMIT 1");
		if ($stmt->execute()) {
			$stmt->bind_result($response["privacy_policy"], $response["terms_of_use"], $response["about_us"], $response["help_support"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return $response;
		}
	}


	public function isFavouriteExist($user_id, $type_id, $type){
		//type  1=>Company, 2=>Brand, 3=>Contact, 4=>Service
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_user_favourites` WHERE `fvrt_usr_id` = ? AND `fvrt_type` = ? AND `fvrt_type_id` = ?");
		$stmt->bind_param("iii", $user_id, $type, $type_id);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	public function insertUserFavourite($user_id, $type_id, $type){
		//type  1=>Company, 2=>Brand, 3=>Contact, 4=>Service
	
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');
	
		$stmt2 = $this->conn->prepare("INSERT INTO `tbl_user_favourites`(`fvrt_usr_id`, `fvrt_type`, `fvrt_type_id`, `fvrt_created_at`) VALUES (?,?,?,?)");
		$stmt2->bind_param("iiis", $user_id, $type, $type_id, $newdt);
		$result = $stmt2->execute();
		$stmt2->close();
		return $result;
	}
	
	public function removeUserFavourite($user_id, $type_id, $type){
		//type  1=>Company, 2=>Brand, 3=>Contact, 4=>Service
		$stmt2 = $this->conn->prepare("DELETE FROM `tbl_user_favourites` WHERE `fvrt_usr_id` = ? AND `fvrt_type` = ? AND `fvrt_type_id` = ?");
		$stmt2->bind_param("iii", $user_id, $type, $type_id);
		$result = $stmt2->execute();
		$stmt2->close();
		return $result;
	}

	public function getUserFavourite($user_id){
		//type  1=>Company, 2=>Brand, 3=>Contact, 4=>Service
		$stmt2 = $this->conn->prepare("SELECT * FROM `tbl_user_favourites` INNER JOIN `tbl_company_contacts` ON `fvrt_type_id` = `cntct_id` INNER JOIN `tbl_companies` ON `cmpny_id` = `cntct_cmpny_id` WHERE `fvrt_usr_id` = ?");
		$stmt2->bind_param("i", $user_id);
		$stmt2->execute();
		$result = getResult($stmt2);
		$stmt2->close();
		return $result;
	}


	public function isEnquiryExist($user_id, $product_id){
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_user_enquiries` WHERE `nqry_usr_id` = ? AND `nqry_prdct_id` = ? AND `nqry_status` = 0");
		$stmt->bind_param("ii", $user_id, $product_id);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	public function insertUserEnquiry($user_id, $product_id, $comment){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');
	
		$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    	$randomString = '';
        for ($i = 0; $i < 2; $i++) {
        	$randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
	    
	    $characters2 = '0123456789';
        $randomString2 = '';
        for ($i = 0; $i < 6; $i++) {
        	$randomString2 .= $characters2[rand(0, strlen($characters2) - 1)];
    	}
            
	    $ticket_number = $randomString."8737".$randomString2;

	
		$stmt2 = $this->conn->prepare("INSERT INTO `tbl_user_enquiries`(`nqry_usr_id`, `nqry_prdct_id`, `nqry_ticket_number`, `nqry_comment`, `nqry_status`, `nqry_created_at`) VALUES (?,?,?,?,0,?)");
		$stmt2->bind_param("iisss", $user_id, $product_id, $ticket_number, $comment, $newdt);
		if ($stmt2->execute()) {
			$enquiry_id = $stmt2->insert_id;
    		$stmt2->fetch();
			$stmt2->close();
            return $enquiry_id;
		} else {
			return NULL;
		}
	}
	
	public function getEnquiryTicketNumber($enquiry_id) {
		$stmt = $this->conn->prepare("SELECT `nqry_ticket_number` FROM `tbl_user_enquiries` WHERE `nqry_id` = ?");
		$stmt->bind_param("i", $enquiry_id);
		if ($stmt->execute()) {
			$stmt->bind_result($ticket_number);
			$stmt->fetch();
			$stmt->close();
			return $ticket_number;
		} else {
			return NULL;
		}
	}
	
	public function getUserEnquiries($user_id){
		//type  1=>Company, 2=>Brand, 3=>Contact, 4=>Service
		$stmt2 = $this->conn->prepare("SELECT * FROM `tbl_user_enquiries` INNER JOIN `tbl_products` ON `prdct_id` = `nqry_prdct_id` INNER JOIN `tbl_companies` ON `cmpny_id` = `prdct_cmpny_id` INNER JOIN `tbl_categories` ON `prdct_ctgry_id` = `ctgry_id` WHERE `nqry_usr_id` = ? AND `nqry_type` = 0 ORDER BY `nqry_created_at` DESC");
		$stmt2->bind_param("i", $user_id);
		$stmt2->execute();
		$result = getResult($stmt2);
		$stmt2->close();
		return $result;
	}
	
	
	public function getUserOffers($user_id){
		//type  1=>Company, 2=>Brand, 3=>Contact, 4=>Service
		$stmt2 = $this->conn->prepare("SELECT * FROM `tbl_user_offers` WHERE `ofr_active` = 1 AND `ofr_expire_date` >= DATE(NOW()) - INTERVAL 7 DAY AND `ofr_start_date` <= DATE(NOW()) + INTERVAL 7 DAY AND (`ofr_usr_id` = ? OR `ofr_usr_id` = 0)");
		$stmt2->bind_param("i", $user_id);
		$stmt2->execute();
		$result = getResult($stmt2);
		$stmt2->close();
		return $result;
	}
	
	
	public function closeUserRequest($user_id, $request_id){
		$stmt2 = $this->conn->prepare("UPDATE `tbl_user_requests` SET `rqst_status` = 1 WHERE `rqst_id` = ? AND `rqst_usr_id` = ?");
		$stmt2->bind_param("ii", $request_id, $user_id);
		if ($stmt2->execute()) {
			$stmt2->store_result();
			$num_rows2 = $stmt2->num_rows;
			$stmt2->close();
			return 1;
		} else {
			return 0;
		}
	}
	
	public function insertUserRating($user_id, $request_id, $company_id, $rating, $comment){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');
		
		$stmt = $this->conn->prepare("INSERT INTO `tbl_user_ratings`(`rtng_cmpny_id`, `rtng_review`, `rtng_value`, `rtng_usr_id`, `rtng_created_at`, `rtng_type`) VALUES (?,?,?,?,?,1)");
		$stmt->bind_param("issis", $company_id, $comment, $rating, $user_id, $newdt);
		if ($stmt->execute()) {
			$stmt->fetch();
			$stmt->close();
			return 1;
		} else {
			return NULL;
		}
	}

	public function incrementContactCalled($contact_id){
		$stmt = $this->conn->prepare("UPDATE `tbl_company_contacts` SET `cntct_called` = `cntct_called` + 1 WHERE `cntct_id` = ?");
		$stmt->bind_param("i", $contact_id);
		if ($stmt->execute()) {
			$stmt->fetch();
			$stmt->close();
			return 1;
		} else {
			return NULL;
		}
	}

	public function incrementContactMailed($contact_id){
		$stmt = $this->conn->prepare("UPDATE `tbl_company_contacts` SET `cntct_mailed` = `cntct_mailed` + 1 WHERE `cntct_id` = ?");
		$stmt->bind_param("i", $contact_id);
		if ($stmt->execute()) {
			$stmt->fetch();
			$stmt->close();
			return 1;
		} else {
			return NULL;
		}
	}
	
	public function incrementEventClicked($event_id){
		$stmt = $this->conn->prepare("UPDATE `tbl_events` SET `evnt_clicked` = `evnt_clicked` + 1 WHERE `evnt_id` = ?");
		$stmt->bind_param("i", $event_id);
		if ($stmt->execute()) {
			$stmt->fetch();
			$stmt->close();
			return 1;
		} else {
			return NULL;
		}
	}
	

	public function getProductDetails($product_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `prdct_cmpny_id`, `prdct_name` FROM `tbl_products` WHERE `prdct_id` = ?");
		$stmt->bind_param("i", $product_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["company_id"], $response["product_name"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return NULL;
		}
	}

	public function getUserMobileNumber($user_id) {
		$stmt = $this->conn->prepare("SELECT `usr_mobile` FROM `tbl_users` WHERE `usr_id` = ?");
		$stmt->bind_param("i", $user_id);
		if ($stmt->execute()) {
			$stmt->bind_result($user_mobile);
			$stmt->fetch();
			$stmt->close();
			return $user_mobile;
		} else {
			return NULL;
		}
	}

	
	
	
	public function isEnquiryExist2($user_id, $offer_id){
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_user_enquiries` WHERE `nqry_usr_id` = ? AND `nqry_ofr_id` = ? AND `nqry_type` = 1 AND `nqry_status` = 0");
		$stmt->bind_param("ii", $user_id, $offer_id);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	public function insertUserEnquiry2($user_id, $offer_id){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');
	
		$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    	$randomString = '';
        for ($i = 0; $i < 2; $i++) {
        	$randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
	    
	    $characters2 = '0123456789';
        $randomString2 = '';
        for ($i = 0; $i < 6; $i++) {
        	$randomString2 .= $characters2[rand(0, strlen($characters2) - 1)];
    	}
            
	    $ticket_number = $randomString."8737".$randomString2;
	
		$stmt2 = $this->conn->prepare("INSERT INTO `tbl_user_enquiries`(`nqry_usr_id`, `nqry_ofr_id`, `nqry_ticket_number`, `nqry_status`, `nqry_type`, `nqry_created_at`) VALUES (?,?,?,0,1,?)");
		$stmt2->bind_param("iiss", $user_id, $offer_id, $ticket_number, $newdt);
		if ($stmt2->execute()) {
			$enquiry_id = $stmt2->insert_id;
    		$stmt2->fetch();
			$stmt2->close();
            return $enquiry_id;
		} else {
			return NULL;
		}
	}

	public function getOfferDetails($offer_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `ofr_name` FROM `tbl_offers` WHERE `ofr_id` = ?");
		$stmt->bind_param("i", $offer_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["offer_name"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return NULL;
		}
	}
	
	public function getEnquiryDetails2($enquiry_id, $offer_id) {
		$response = array();
		$stmt = $this->conn->prepare("SELECT `ofr_id`, `ofr_name`, `ofr_description`, (SELECT `nqry_comment` FROM `tbl_user_enquiries` WHERE `nqry_id` = ?) AS `nqry_comment` FROM `tbl_offers` WHERE `ofr_id` = ?");
		$stmt->bind_param("ii", $enquiry_id, $offer_id);
		if ($stmt->execute()) {
			$stmt->bind_result($response["offer_id"], $response["offer_name"], $response["offer_description"], $response["enquiry_comment"]);
			$stmt->fetch();
			$stmt->close();
			return $response;
		} else {
			return NULL;
		}
	}

	
	
	
	
	
	
	
	
	public function isProductExistInCart($user_id, $product_id){
		$stmt = $this->conn->prepare("SELECT `crt_id` FROM `tbl_user_cart` WHERE `crt_usr_id` = ? AND `crt_prdct_id` = ?");
		$stmt->bind_param("ii", $user_id, $product_id);
		$stmt->execute();
		$stmt->store_result();
		$num_rows = $stmt->num_rows;
		$stmt->close();
		return $num_rows > 0;
	}

	public function insertProductInCart($user_id, $product_id){
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$newdt = $dt->format('Y-m-d H:i:s');

		$stmt = $this->conn->prepare("INSERT INTO `tbl_user_cart`(`crt_usr_id`, `crt_prdct_id`, `crt_qty`, `crt_created_at`) VALUES (?,?,1,?)");
		$stmt->bind_param("iis", $user_id, $product_id, $newdt);
		if ($stmt->execute()) {
			$stmt->fetch();
			$stmt->close();
			return 1;
		} else {
			return NULL;
		}
	}

	public function getUserCartDetails($user_id){
		$stmt2 = $this->conn->prepare("SELECT * FROM `tbl_user_cart` INNER JOIN `tbl_products` ON `crt_prdct_id` = `prdct_id` INNER JOIN `tbl_companies` ON `prdct_cmpny_id` = `cmpny_id` WHERE `crt_usr_id` = ?");
		$stmt2->bind_param("i", $user_id);
		$stmt2->execute();
		$result = getResult($stmt2);
		$stmt2->close();
		return $result;
	}
	
	public function updateQuantityInCart($user_id, $product_id, $quantity) {
		$stmt = $this->conn->prepare("UPDATE `tbl_user_cart` SET `crt_qty` = ? WHERE `crt_usr_id` = ? AND `crt_prdct_id` = ?");
		$stmt->bind_param("iii", $quantity, $user_id, $product_id);
		if ($stmt->execute()) {
    		$stmt->fetch();
			$stmt->close();
            return 1;
		} else {
			return NULL;
		}
	}
	
	public function getUserAddresses($user_id){
		$stmt2 = $this->conn->prepare("SELECT * FROM `tbl_user_cart` INNER JOIN `tbl_products` ON `crt_prdct_id` = `prdct_id` INNER JOIN `tbl_companies` ON `prdct_cmpny_id` = `cmpny_id` WHERE `crt_usr_id` = ?");
		$stmt2->bind_param("i", $user_id);
		$stmt2->execute();
		$result = getResult($stmt2);
		$stmt2->close();
		return $result;
	}
	
	
	
	
	
	
	

// SELECT * FROM `tbl_user_offers` WHERE `usr_ofr_active` = 1 AND `usr_ofr_expire_date` >= DATE(NOW()) - INTERVAL 7 DAY AND `usr_ofr_start_date` <= DATE(NOW()) + INTERVAL 7 DAY AND (`usr_ofr_usr_id` = 1 OR `usr_ofr_usr_id` = 0)
}

function getResult($stmt){
	return $stmt->get_result();
}
	
	
// function generateRandomString($length = 10) {
//     $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
//     $charactersLength = strlen($characters);
//     $randomString = '';
//     for ($i = 0; $i < $length; $i++) {
//         $randomString .= $characters[rand(0, $charactersLength - 1)];
//     }
//     return $randomString;
// }
?>