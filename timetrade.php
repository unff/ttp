<?php
    // Set CORS header
    header('Access-Control-Allow-Origin: *'); 
    // pull password out of $_GET (base64 encoded?)
    $pw = "Idiot3@2";
    $email="jcryan@sixbitsoftware.com";
    // TODO (BIG): store configs by email address.
    $configFile = "./tt.json";
    $apiUrl = "https://www.timetrade.com/td/json/connectorApi";
    $config[$email] = (object)array(
        'id' => 0,
        'cookie1' => '',
        'cookie2' => '',
        'exp' => time()
    );
    // Make sure settings file exists
    touch('./tt.json');
    if(!filesize($configFile)) {
        echo 'empty';
        // file is empty, write defaults to config file
        $cFile = fopen($configFile, "w");
        fwrite($cFile, json_encode($config, JSON_PRETTY_PRINT));
        fclose($cFile);
    }
    // Load JSON data from config file
    $json_data = file_get_contents($configFile);
    $config = json_decode($json_data);
    // Check exp for freshness
    if ($config->$email->exp <= time()) {
        // freshness fail. expiration has passed. generate new credentials.
        // call for new salt
        $body = (object)array(
            "id" => ++$config->$email->id,
            "method" => "connectorApi.initAccess",
            "params" => array($email)
        );
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
                'header'=>'Content-Type: text/plain',
                'method'=>'POST',
                'content'=>json_encode($body)
            )
        );
        $context = stream_context_create($opts);
        $fp =  file_get_contents($apiUrl, false, $context);
        $data = json_decode($fp);
        // got salt. call for new token
        $body = (object)array(
            "id"=> ++$config->$email->id,
            "method"=> "connectorApi.login",
            "params"=> array(
                $email,makeHash($pw, $data->result->authToken)
            )
        );
        $opts['http']['content'] = json_encode($body);
        $context = stream_context_create($opts);
        $fp = file_get_contents($apiUrl, false, $context);
        $data = json_decode($fp);
        $arr = array(); // need a blank array to push into
        foreach ($http_response_header as $value) {
            $t = explode(":",$value,2);
            if ($t[0] == "Set-Cookie") {
                array_push($arr, $t[1]);
            }
            unset($arr[0]);
        }
        foreach ($arr as $key=>$value) {
            $cookie = explode(";", $value, 2);
            $arr[$key] = $cookie[0];
        }
        $config->$email->id = 1;
        $config->$email->cookie1 = $arr[1]; // it's 1 and 2 since we unset $arr[0]
        $config->$email->cookie2 = $arr[2];
        // calculate exp
        $config->$email->exp = time()+(43000);
        //save token and exp to config file
        $cFile = fopen($configFile, "w");
        fwrite($cFile, json_encode($config, JSON_PRETTY_PRINT));
        fclose($cFile);
        // are curl calls sync or async? sync, but not using curl here.
    } else {
        echo $config->$email->exp." vs ".time()."\r\n";
    }

    // Get appt list
    $body = (object)array(
        "id" => ++$config->$email->id,
        "method" => "connectorApi.getAppointments",
        "params" => array($email)
    );
    // modify  gmdate("Y-m-d\TH:i:s\Z"); 
    $opts = array(
        "ssl" => [
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ],
        'http'=>array(
            'header'=>'Content-Type: text/plain',
            'method'=>'POST',
            'content'=>json_encode($body)
        )
    );
    // Return JSON object
    
    function makeHash($pw, $salt) {
        $hash = base64_encode(MD5($pw, true));
        return base64_encode(MD5($hash.$salt, true));
    }


?>
