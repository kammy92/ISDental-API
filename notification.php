<?php 

date_default_timezone_set('America/Denver');
ini_set('max_execution_time', 300);

ini_set('memory_limit', '1024M');

$date = date('Y-m-d H:i:s');

    include("firebase/firebase.php");
    include("firebase/push.php");
    if (strcmp($_SERVER['HTTP_HOST'], 'project-clearsale-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-clearsale-cammy92.c9users.io')  == 0){
      $username = "cammy92"; 
      $password = "";  
      $hostname = "0.0.0.0"; 
      $databasename = "clearsaledb_new";
    } else {
      $username = "root"; 
      $password = "QqC154VoSOOGVL98";  
      $hostname = "localhost"; 
      $databasename = "clearsaledb";
    }

    $conn = new mysqli($hostname, $username, $password, $databasename);

    $notification_type = 1;
    
    $firebase = new Firebase();
    $push = new Push();
    $payload = array();




  if ($stmt = $conn->query("SELECT at.auction_id,at.property_id,at.start_date,at.end_date,pj.property_state,pj.property_address,pj.property_zip, pj.bedrooms, pj.bathrooms, pj.latitude, pj.longitude, sj.name as state_name,cn.city_name,pzv.year_built, pzv.finished_sqft,pzv.arv FROM auction_absolutes at 
        LEFT JOIN properties pj ON at.property_id=pj.id 
        LEFT JOIN states sj ON pj.property_state=sj.id 
        LEFT JOIN cities cn ON pj.property_city=cn.id 
        LEFT JOIN property_zillow_values pzv ON at.property_id = pzv.property_id
        WHERE at.auction_id=142")) {

    
    if ($stmt->num_rows > 0) {
         $n = $stmt->fetch_array(MYSQLI_ASSOC);
        
        
        $imagequery = $conn->query("SELECT property_id, image_name, type FROM properties_images WHERE property_id = '".$n['property_id']."' and type = 'exterior' ORDER BY id ASC");  
       
        $rowimage = $imagequery->fetch_array(MYSQLI_ASSOC);
      
        
        $query = $conn->query("SELECT bj.email,bj.firebase_id,bj.device_type FROM buyers bj LEFT JOIN buyer_geographic_locations bk ON bj.id=bk.buyer_id WHERE bk.location_id='" . $n['state_name'] . "'  AND bj.firebase_id !='' GROUP BY bj.email ORDER BY bj.email ASC");
        
        $queryforall = $conn->query("SELECT email,firebase_id,device_type FROM buyers WHERE firebase_id !='' AND first_name = '' AND email = '' GROUP BY email ORDER BY email ASC");
        
        while ($dataforall = $queryforall->fetch_array(MYSQLI_ASSOC)) {    
                $payload['notification_type'] = 1;
                $payload['notification_priority'] = 2;
                $payload['notification_style'] = 5;
                $payload['property_id'] = $n["property_id"]==""? "NA" : $n["property_id"];
                $payload['property_address'] = $n["property_address"]==""? "NA" : $n["property_address"];
                $payload["property_city"] = $n["city_name"]==""? "NA" : $n["city_name"].", ".$n["state_name"]==""? "NA" : $n["state_name"]." ".$n["property_zip"]==""? "NA" : $n["property_zip"];
                $payload['property_state'] = $n["state_name"]==""? "NA" : $n["state_name"];
                $payload['property_latitude'] = $n["latitude"]==""? "NA" : $n["latitude"];
                $payload['property_longitude'] = $n["longitude"]==""? "NA" : $n["longitude"];
                $payload['property_price'] = "$".number_format($n["arv"]);
                $payload['property_built_year'] = $n["year_built"]==""? "NA" : $n["year_built"];
                $payload['property_bedrooms'] = $n["bedrooms"]==""? "NA" : $n["bedrooms"];
                $payload['property_bathrooms'] = $n["bathrooms"]==""? "NA" : $n["bathrooms"];
                $payload['property_area'] = $n["finished_sqft"]==""? "NA" : $n["finished_sqft"];
                $push->setTitle("New Deal from HomeTrust");
                $push->setMessage($n["city_name"]==""? "NA" : $n["city_name"].", ".$n["state_name"]==""? "NA" : $n["state_name"].", ".$n["property_zip"]==""? "NA" : $n["property_zip"]);
               
                if(!empty($rowimage["image_name"])){
                    $push->setImage("http://clearsale.com/theme/theme1/seller_files/exterior/property_".$rowimage['property_id']."/thumb_".str_replace(" ","%20",$rowimage["image_name"]));
                }else{
                    $push->setImage("http://clearsale.com/theme/theme1/seller_files/exterior/property_1130/thumb_131e7b98113f36e96adf67ffe341bf81IMG_0871.jpg");
                }
               
                $push->setIsBackground(FALSE);
                $push->setPayload($payload);
                $push->setPropertyID($n["property_id"]);
                $json = $push->getPush();
               // $firebase_response = $firebase->send($data["firebase_id"], $json);
                if($data['device_type'] == "ANDROID"){
                    $firebase_response = $firebase->sendToAndroid($dataforall['firebase_id'], $json);
                   // print_r($firebase_response);      
                }
                if($dataforall['device_type'] == "IOS"){
                    $notification = array('title' =>"New Deal from HomeTrust" , 'text' => $payload['property_address'].", ".$payload['property_city'].", ".$payload['property_state']);
                    $firebase_response = $firebase->sendToiOS($dataforall['firebase_id'], $json, $notification);
                    print_r($firebase_response);  
                }
              
        }  
        
        


    while ($data = $query->fetch_array(MYSQLI_ASSOC)) {    
                $data = $query->fetch_array(MYSQLI_ASSOC);
                $payload['notification_type'] = 1;
                $payload['notification_priority'] = 2;
                $payload['notification_style'] = 5;
                $payload['property_id'] = $n["property_id"]==""? "NA" : $n["property_id"];
                $payload['property_address'] = $n["property_address"]==""? "NA" : $n["property_address"];
                $payload["property_city"] = $n["city_name"]==""? "NA" : $n["city_name"].", ".$n["state_name"]==""? "NA" : $n["state_name"]." ".$n["property_zip"]==""? "NA" : $n["property_zip"];
                $payload['property_state'] = $n["state_name"]==""? "NA" : $n["state_name"];
                $payload['property_latitude'] = $n["latitude"]==""? "NA" : $n["latitude"];
                $payload['property_longitude'] = $n["longitude"]==""? "NA" : $n["longitude"];
                $payload['property_price'] = "$".number_format($n["arv"]);
                $payload['property_built_year'] = $n["year_built"]==""? "NA" : $n["year_built"];
                $payload['property_bedrooms'] = $n["bedrooms"]==""? "NA" : $n["bedrooms"];
                $payload['property_bathrooms'] = $n["bathrooms"]==""? "NA" : $n["bathrooms"];
                $payload['property_area'] = $n["finished_sqft"]==""? "NA" : $n["finished_sqft"];
                $push->setTitle("New Deal from HomeTrust");
                $push->setMessage($n["city_name"]==""? "NA" : $n["city_name"].", ".$n["state_name"]==""? "NA" : $n["state_name"].", ".$n["property_zip"]==""? "NA" : $n["property_zip"]);
               
                if(!empty($rowimage["image_name"])){
                    $push->setImage("http://clearsale.com/theme/theme1/seller_files/exterior/property_".$rowimage['property_id']."/thumb_".str_replace(" ","%20",$rowimage["image_name"]));
                }else{
                    $push->setImage("http://clearsale.com/theme/theme1/seller_files/exterior/property_1130/thumb_131e7b98113f36e96adf67ffe341bf81IMG_0871.jpg");
                }
               
                $push->setIsBackground(FALSE);
                $push->setPayload($payload);
                $push->setPropertyID($n["property_id"]);
                $json = $push->getPush();
               // $firebase_response = $firebase->send($data["firebase_id"], $json);
                if($data['device_type'] == "ANDROID"){
                    $firebase_response = $firebase->sendToAndroid("cg9ZzYHeFuo:APA91bG6bIJt_DjE3RnBuwmPazmo0njRbmphe4Fn6r_fmlw9XukYi-l3iRrmdXDJQXwbVmvah0kPdy_8cUX_OZ_ZhsFATUa84SxwhBsUIadNAAHXx8BOzyrXz-KYDfXOAM2xa_RfKs4Y", $json);
                   // print_r($firebase_response);      
                }
                if($data['device_type'] == "IOS"){
                    $notification = array('title' =>"New Deal from HomeTrust" , 'text' => $payload['property_address'].", ".$payload['property_city'].", ".$payload['property_state']);
                    $firebase_response = $firebase->sendToiOS("fzMPOzSy3l4:APA91bH1TvspDl-NbgHT-efpbn0IOCVmRvFCb_tyqwYGgcE52NG9vXJnN4xvWvxwZcWz0B8XdoM2c4oVaVT26Z-st_6iq3LIuTYKvAakBrn_SlzTYIfanqsQ5sXlH4LIV65sceExl69_", $json, $notification);
                    print_r($firebase_response);  
                    echo "sudhanshu";
                }
              
      }
      $updatequery = $conn->query("UPDATE auction_absolutes SET notification_status = 1 WHERE auction_id = '".$n['auction_id']."'");
    }
  }




?>