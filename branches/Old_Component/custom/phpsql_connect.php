<?php

// Connecting to mysql
$conn = mysql_connect("$server", "$username", "$password");
// Connection error checking
if (!$conn) {
    echo "Unable to connect to DB: " . mysql_error();
    exit;
}
// Solecting Database with error System  
if (!mysql_select_db("$database")) {
    echo "Unable to select mydbname: " . mysql_error();
    exit;
}
//Solecting Table info
$sql = "SELECT * 
        FROM   $table
        WHERE  1";
$result = mysql_query($sql);

if (!$result) {
    echo "Could not successfully run query ($sql) from DB: " . mysql_error();
    exit;
}

if (mysql_num_rows($result) == 0) {
    echo "No rows found, nothing to print so am exiting";
    exit;
}

?>