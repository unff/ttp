<?php
    // Set CORS header
    header('Access-Control-Allow-Origin: *'); 
    // pull password out of $_GET (base64 encoded?)
    $pw = base64_decode($_GET["p"]);
    $email = base64_decode($_GET["e"]);
    // TODO (BIG): store configs by email address.
    $configFile = "./tt.json";
    $apiUrl = "https://www.timetrade.com/td/json/connectorApi";
    $config = new stdClass();
    $config->$email = (object)array(
        'id' => 0,
        'cookie1' => '',
        'cookie2' => '',
        'exp' => time()
    );
    // Make sure settings file exists
    touch('./tt.json');
    if(!filesize($configFile)) {
            //echo 'empty';
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
        $config->$email->id = 0; // reset the id for a new set of conversations.
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
                //'proxy'=>"tcp://127.0.0.1:8888",
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
        // Check for valid login
        if($data->result == false){
            echo json_encode(array((object)array(
                'time'=>'',
                'questionResponse'=>'',
                'calendarEventSubject'=>'invalid login'
            )));
            exit;
        }
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
    }

    // Get appt list
    // {"id":4,"method":"connectorApi.getAppointments","params":["2018-06-14T13:36:00Z",null]}
    // {"id":2,"method":"connectorApi.getAppointments","params":["2018-06-14T00:00:00Z","2018-06-14T11:59:59Z"]}
    $body = (object)array(
        "id" => ++$config->$email->id,
        "method" => "connectorApi.getAppointments",
        "params" => array(date("Y-m-d\T00:00:00\Z"), NULL)
    );
    $opts = array(
        "ssl" => [
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ],
        'http'=>array(
            'header'=>array('Content-Type: text/plain',
                    'Cookie: '.$config->$email->cookie1.'; '.$config->$email->cookie2
        ),
            'method'=>'POST',
            //'proxy'=>"tcp://127.0.0.1:8888",
            'content'=>json_encode($body)
        )
    );
    $context = stream_context_create($opts);
    $fp = file_get_contents($apiUrl, false, $context);
    $data = json_decode($fp);
        //print_r($data);
    
    // Check for "Missing User Credentials", blank the token and 302 with query string
    //if ($data->error->code == 4901) {
    if (property_exists($data, "error")) {  //assume 4901 and reset
        //echo "ding: ".$email;
        $config->$email = (object)array(
            'id' => 0,
            'cookie1' => '',
            'cookie2' => '',
            'exp' => time()
        );
        $cFile = fopen($configFile, "w");
        fwrite($cFile, json_encode($config, JSON_PRETTY_PRINT));
        fclose($cFile);
        //echo "dong";
        // stored credentials are now blank. Redirect to ourself to generate new.
        header('Location: '.$_SERVER["REQUEST_URI"], true, 302);
        exit;  //kill program
    }
    if (is_null($data->result)) {
        $data->result = (object)array(
            'time'=>'',
            'questionResponse'=>'',
            'calendarEventSubject'=>'no appointments today'
        );
    }
    //update config after to persist id counts
    $cFile = fopen($configFile, "w");
    fwrite($cFile, json_encode($config, JSON_PRETTY_PRINT));
    fclose($cFile);
        
    // Return JSON object
    echo json_encode($data->result);

    // functions
    function makeHash($pw, $salt) {
        $hash = base64_encode(MD5($pw, true));
        return base64_encode(MD5($hash.$salt, true));
    }


?>
