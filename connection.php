<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbhost = "localhost";
$dbname = "eventease";
$dbuser = "root";
$dbpassword = "";
$dbport = 3309;
$conn = "";

try{
    $conn = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname, $dbport);

}

catch(mysqli_sql_exception $e){
    die("Connection failed: " . $e->getMessage());
}
if($conn){
    echo "You are connected!";
}

?>