<?php
// pull password out of $_GET (base64 encoded?)
$configFile = "./tt.json";
$id = 0;
    // Make sure settings file exists
    touch('./tt.json');
    if(!filesize($configFile)) {
        echo 'empty';
        // file is empty, write defaults to config file
        $config = (object)array(
            'cookie1' => '',
            'cookie2' => '',
            'exp' => time()
        );
        $cFile = fopen($configFile, "w");
        fwrite($cFile, json_encode($config, JSON_PRETTY_PRINT));
        fclose($cFile);
    } 
    // Set CORS header
    header('Access-Control-Allow-Origin: *'); 
    // Load JSON data
    $json_data = file_get_contents($configFile);
    $json = json_decode($json_data);
    // Check exp for freshness
    var_dump($json);
    if ($json->exp < time()) {
        //expiration has passed. generate new credentials.
        echo 'exp < now';
        //call for new salt
        //call for new token
        //calculate exp
        //save token and exp to config file
        // are curl calls sync or async?
    } 

    // Get appt list
    // Return JSON object
    $id = 0;
    $pw = "Idiot3@2";
    $salt = "@n6`pfvicgyre2Oc";
    
    function makeHash($pw, $salt) {
        $hash = base64_encode(MD5($pw, true));
        return base64_encode(MD5($hash.$salt, true));
    }
    echo "\r\n";
    echo makeHash($pw, $salt);
?>
