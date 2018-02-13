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

	public function getUserDetails($mobile) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_users` where usr_mobile = ?");
		$stmt->bind_param("i", $mobile);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
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
	

	public function getAllCategories() {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_categories` GROUP BY `ctgry_name` ORDER BY `ctgry_sort_order` ASC");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getAllCategoryCompanies($category_name) {
		$stmt = $this->conn->prepare("SELECT *, (SELECT GROUP_CONCAT(b.`brnd_name`) FROM `tbl_brands` AS b INNER JOIN `tbl_brand_mappings` AS bm ON bm.`brnd_map_brnd_id` = b.`brnd_id` WHERE bm.`brnd_map_cmpny_id` = `cmpny_id` AND b.brnd_active = 1) AS cmpny_brands FROM `tbl_companies` WHERE find_in_set (cmpny_id,(SELECT GROUP_CONCAT(DISTINCT(ctgry_map_cmpny_id))  FROM `tbl_category_mappings` WHERE find_in_set(ctgry_map_ctgry_id, (SELECT GROUP_CONCAT(ctgry_id) FROM `tbl_categories` where ctgry_name = ?)) != 0))");
		$stmt->bind_param("s", $category_name);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}
	
	public function getAllCompanies() {
		$stmt = $this->conn->prepare("SELECT *, (SELECT GROUP_CONCAT(b.`brnd_name`) FROM `tbl_brands` AS b INNER JOIN `tbl_brand_mappings` AS bm ON bm.`brnd_map_brnd_id` = b.`brnd_id` WHERE bm.`brnd_map_cmpny_id` = `cmpny_id` AND b.brnd_active = 1) AS cmpny_brands FROM `tbl_companies` WHERE `cmpny_active` = 1 ORDER BY `cmpny_name` ASC");
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getCompanyDetails($company_id) {
		$type = "COMPANY";
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_companies` LEFT JOIN `tbl_social_links` ON `scl_lnk_type_id` = `cmpny_id` AND `scl_lnk_type` = ? WHERE `cmpny_id` = ? AND cmpny_active = 1");
		$stmt->bind_param("si", $type, $company_id);
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
	
	public function getCompanyContacts($company_id) {
		$stmt = $this->conn->prepare("SELECT c.*, cm.`cntct_map_cntct_type` AS `cntct_type` FROM `tbl_contacts` AS c INNER JOIN `tbl_contact_mappings` AS cm ON cm.`cntct_map_cntct_id` = c.`cntct_id` WHERE cm.`cntct_map_cmpny_id` = ? AND c.cntct_active = 1");
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
	

	public function getAllCurrentAndUpcomingEvents($event_type) {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d');
		$newdt = $dt->format('Y-m-d');

		$stmt = $this->conn->prepare("SELECT *, (SELECT GROUP_CONCAT(o.`orgnsr_name`) FROM `tbl_organisers` AS o INNER JOIN `tbl_organiser_mappings` AS om ON om.`orgnsr_map_orgnsr_id` = o.`orgnsr_id` WHERE om.`orgnsr_map_evnt_id` = `evnt_id`) AS `evnt_organiser_name` FROM `tbl_events` WHERE `evnt_active` = 1 AND `evnt_type` = ? AND `evnt_end_date` > ? ORDER BY `evnt_start_date` ASC");
		$stmt->bind_param("ss", $event_type, $newdt);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getAllPastEvents($event_type) {
		date_default_timezone_set("Asia/Kolkata");
		$dt = new DateTime();
		$dt->format('Y-m-d');
		$newdt = $dt->format('Y-m-d');

		$stmt = $this->conn->prepare("SELECT *, (SELECT GROUP_CONCAT(o.`orgnsr_name`) FROM `tbl_organisers` AS o INNER JOIN `tbl_organiser_mappings` AS om ON om.`orgnsr_map_orgnsr_id` = o.`orgnsr_id` WHERE om.`orgnsr_map_evnt_id` = `evnt_id`) AS `evnt_organiser_name` FROM `tbl_events` WHERE `evnt_active` = 1 AND `evnt_type` = ? AND `evnt_end_date` < ? ORDER BY `evnt_start_date` ASC");
		$stmt->bind_param("ss", $event_type, $newdt);
		$stmt->execute();
		$result = getResult($stmt);
		$stmt->close();
		return $result;
	}

	public function getEventDetails($event_id) {
		$type = "EVENT";
		$stmt = $this->conn->prepare("SELECT *, (SELECT GROUP_CONCAT(o.`orgnsr_name`) FROM `tbl_organisers` AS o INNER JOIN `tbl_organiser_mappings` AS om ON om.`orgnsr_map_orgnsr_id` = o.`orgnsr_id` WHERE om.`orgnsr_map_evnt_id` = `evnt_id`) AS `evnt_organiser_name`, (SELECT GROUP_CONCAT(o.`orgnsr_id`) FROM `tbl_organisers` AS o INNER JOIN `tbl_organiser_mappings` AS om ON om.`orgnsr_map_orgnsr_id` = o.`orgnsr_id` WHERE om.`orgnsr_map_evnt_id` = `evnt_id`) AS `evnt_organiser_id` FROM `tbl_events` LEFT JOIN `tbl_social_links` ON `scl_lnk_type` = ? AND `scl_lnk_type_id` = ? WHERE `evnt_id` = ? AND evnt_active = 1");
		$stmt->bind_param("sii", $type, $event_id, $event_id);
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

	public function getBanners($visitor_id) {
		$stmt = $this->conn->prepare("SELECT * FROM `tbl_banners` WHERE bnnr_active = 1");
		$stmt->execute();
		$banners = getResult($stmt);
		$stmt->close();
		return $banners;
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
}

function getResult($stmt){
        return $stmt->get_result();
	}
?>