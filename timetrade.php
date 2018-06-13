<?php
$configFile = "./tt.json";
    // Make sure settings file exists
    touch('./tt.json');
    if(!filesize($configFile)) {
        echo 'empty';
        // file is empty, write defaults to file
        $config = (object)array(
            'c1' => '',
            'c2' => '',
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
        echo 'exp < now';
    } else {
        echo 'exp > now';
    }
    // Get new salt
    // Get new token with salt
    // Get appt list
    // Return JSON object
    $id = 0;
    $pw = "Idiot3@2";
    $salt = "@n6`pfvicgyre2Oc";
    $hashA = base64_encode(MD5($pw, true));
    $hashB = base64_encode(MD5($hashA.$salt, true));
    echo $hashB;
?>
