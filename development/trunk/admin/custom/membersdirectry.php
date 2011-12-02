<?php
require('phpsql_dbinfo.php');
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
$sql = "SELECT * FROM $table ORDER BY lname";
$result = mysql_query($sql);

if (!$result) {
    echo "Could not successfully run query ($sql) from DB: " . mysql_error();
    exit;
}

if (mysql_num_rows($result) == 0) {
    echo "No rows found, nothing to print so am exiting";
    exit;
}
function groupit( $args ) 
{
	extract($args);

	$result = array();
	foreach ($items as $item) {
		if ( !empty($item[$field]) )
			$key = $item[$field];
		else
			$key = 'nomatch';
		if (array_key_exists($key, $result))
			$result[ $key ][] = $item;
		else {
			$result[ $key ] = array();
			$result[ $key ][] = $item;
		}
	}
	return $result;
}



// Creates an array of strings to hold the lines of the KML file.


// gotta get my array of folders
while ($row = @mysql_fetch_assoc($result)) {
$rows[] = $row;
}

$countries = groupit(array('items' => $rows, 'field' => 'state'));

foreach ($countries as $c => $state) {
$newrows[$c] = groupit(array('items' => $countries[$c], 'field' => 'lname'));
};
$mycounter = '0';

//print $c; die("url not loading");
foreach ($newrows as $c => $state) {
$kml = array('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html401/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Nashville First SDA Church Directry</title>
<style type="text/css">

</style>
</head>
<body>
<h1>Nashville First SDA Church Directry</h1>');
$kml[] = '<div style="width: 1011px; border-width:thin; border-style:solid; padding: 5px;">';
foreach  ($state as $s => $rows) {
$kml[] = '<div style="clear: both; border-style:solid;">';
$kml[] = '<b>Last Name: ' . $s . '</b><div style="clear: both;">';

foreach ($rows as $row) {

$kml[] = '<div style="width: 90%; border-width:thin; border-style:solid; margin: 5px;">';
if($row['image'] == '')
		{$kml[] = '<img src="http://www.nfsda.org/images/members/1st_church_8x12.jpg" alt="Photo" width="100" hight="100" />';}
		 else {$kml[] = '<img src="https://www.nfsda.org/images/members/' . $row['image'] . '" alt="Photo" width="100" hight="100" />';}
$kml[] = '<h4> ' . $row['name']. '</h4>';
if($row['address'] == '')
	{$kml[] = '';}
	else {$kml[] = $row['address'] .'<br> ';}
if($row['postcode'] == '')
	{$kml[] = '';}
	else {$kml[] = $row['suburb']. ', ' . $row['state'] . '  ' . $row['postcode'] . '<br />';}
  	if($row['con_position'] == '')
		{$kml[] = '';}
	 	 else {$kml[] = '<h3>' . $row['con_position'] . '</h3>';}
	  	if($row['misc'] == '')
		{$kml[] = '';}
		else {$kml[] = '<div id="misc_members">' . $row['misc'] . '</div>';}
  	if($row['telephone'] == '')
  	       {$kml[] = '';}
       	 else {$kml[] = '<b>PH:</b> ' . $row['telephone'];}
	if($row['fax'] == '')
       	{$kml[] = '';}
          	 else {$kml[] = '<br /><b>Fax:</b> ' . $row['fax'];}
  	if($row['mobile'] == '')
         	{$kml[] = '';}
          	 else {$kml[] = '<br /><b>Cell:</b> ' . $row['mobile'];}
  	if($row['email_to'] == '')
         	{$kml[] = '';}
          	 else {$kml[] = '<br /><b>Email:</b> <a href="mailto:' . $row['email_to'] . '"> ' . $row['email_to'] . '</a>';} 
$kml[] = '</div>';


  
} // end the state folder
$kml[] = '</div></div><br />';

} // end the country folder
$kml[] = '</div>';
 }
// End XML file
$kml[] = ' </body>';
$kml[] = '</html>';
$kmlOutput = join("\n", $kml);
$output = "$kmlOutput";

print $output; //display in the browser

mysql_free_result($result);
    print mysql_error();

error_reporting(E_ALL);
ini_set('display_errors', '1');
?>