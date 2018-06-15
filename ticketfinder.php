<?php
$servername = "localhost";
$username = "root";
$password = "dfxy74UV";
$dbname = "sixbitclients";
$tid = $_GET["tid"];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
$sql = "SELECT id FROM sixbitclients.tbltickets WHERE tid = ".$tid;
//echo $sql;
$result = $conn->query($sql);
//print_r($result);
if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo '{"tid":'.$tid.',"id":'.$row["id"].'}';
    }
} else {
    echo "0 results";
}
$conn->close();
?>