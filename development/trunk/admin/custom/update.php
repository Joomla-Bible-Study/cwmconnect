<?php
require('phpsql_dbinfo.php');
require('phpsql_connect.php');
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
$kml = array('<?xml version="1.0" encoding="UTF-8"?>');
$kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2">';
$kml[] = ' <Document>';
$kml[] = ' <name>Nasvhille First SDA Church Members</name>';
$kml[] = ' <open>0</open>';
$kml[] = ' <LookAt>
    		 <longitude>-86.812370</longitude>
    		 <latitude>36.131973</latitude>
		 <altitude>0</altitude>
		 <range>110027.8255488604</range>
		 <tilt>0</tilt>
		 <heading>-1.119363650863577e-006</heading>
		 <gx:altitudeMode>relativeToSeaFloor</gx:altitudeMode>
  	     </LookAt>    <!-- Camera or LookAt -->';

// gotta get my array of folders
while ($row = @mysql_fetch_assoc($result)) {
$rows[] = $row;
}

$teams = groupit(array('items' => $rows, 'field' => 'catid'));

foreach ($teams as $c => $catid) {
$newrows[$c] = groupit(array('items' => $teams[$c], 'field' => 'suburb'));
};
$mycounter = '0';
//print $c; die("url not loading");
foreach ($newrows as $c => $suburb) {
  $kml[] = '<Folder id="' . $mycounter++ . '"> ';
	$kml[] = '<name>Members Team '. $c . '</name>';
	$kml[] = ' <open>1</open>           <!-- boolean -->';
  //$kml[] = '<Snippet maxLines="2">' . $row['con_position'] . '</Snippet>   <!-- string -->';

foreach  ($suburb as $s => $rows) {
  $kml[] = ' <Folder id="' . $mycounter++ . '"> ';
	$kml[] = ' <name>' . $s . ' </name> ';
	$kml[] = ' <open>0</open>           	   <!-- boolean -->';
  //$kml[] = '<Snippet maxLines="2">' . $row['con_position'] . '</Snippet>   <!-- string -->';

foreach ($rows as $row) {
	if($row['published'] == '0')
	{$kml[] = '';}
	else {
	$kml[] = ' <Style id="text_photo_banner">';
	$kml[] = ' <IconStyle>
         <scale>1.1</scale>';
	$kml[] = ' <Icon>';
	$kml[] = ' <href>';
	$kml[] = 'http://www.nfsda.org/images/gmapsicons/icon' . $row['catid'] . '.png';
	$kml[] = ' </href>';
	$kml[] = ' </Icon>';
	$kml[] = ' </IconStyle>';
	$kml[] = '<LabelStyle><color>ffffffff</color><colorMode>normal</colorMode><scale>.6</scale></LabelStyle>';
    	//$kml[] = '<LineStyle><color>ff0000ff</color><width>15</width></LineStyle>';
    	//$kml[] = '<PolyStyle><color>7f7faaaa</color><colorMode>random</colorMode></PolyStyle>';
	$kml[] = '  </Style> ';
  	$kml[] = ' <Placemark id="placemark' . $mycounter++ . ' "> ';
	$kml[] = ' <name>' . $row['name'] . '</name>';
	$kml[] = '';
	$kml[] = ' <visibility>0</visibility><open>0</open>';
	$kml[] = '<gx:balloonVisibility>0</gx:balloonVisibility>';
 	$kml[] = ' <address><![CDATA[';
	if($row['address'] == '')
		{$kml[] = '';}
		else {$kml[] = $row['address'] . ',<br />';}
	$kml[] = $row['suburb']. ', ' . $row['state'] . ' ' . $row['postcode'];
	if($row['postcodeaddon'] == '')
		{$kml[] = '';}
		 else {$kml[] = '-' . $row['postcodeaddon'];}
	$kml[] = ']]></address> <!-- string -->';
  	$kml[] = ' <phoneNumber>' . $row['telephone'] . '</phoneNumber> <!-- string -->';
  	$kml[] = ' <Snippet maxLines="2"><![CDATA[' . $row['con_position'] . ' <br />Team ' . $row['catid'] . ']]></Snippet>   <!-- string -->';
  	$kml[] = ' <description>' . '<![CDATA[<div>';
  	if($row['image'] == '')
		{$kml[] = '<img src="http://www.nfsda.org/images/members/1st_church_8x12.jpg" alt="Photo" width="100" hight="100" /><br />';}
		 else {$kml[] = '<img src="http://www.nfsda.org/images/members/' . $row['image'] . '" alt="Photo" width="100" hight="100" /><br />';}
  	if($row['con_position'] == '')
		{$kml[] = '';}
	 	 else {$kml[] = '<b>Position: ' . $row['con_position'] . '</b><br />';}
  	if($row['spouse'] == '')
		{$kml[] = '';}
		else {$kml[] = 'Spouse: ' . $row['spouse'] . '<br />';}
  	if($row['children'] == '')
		{$kml[] = '';}
		else {$kml[] = 'Children: ' . $row['children'] . '<br />';}
  	if($row['misc'] == '')
		{$kml[] = '';}
		else {$kml[] = $row['misc'];}
  	if($row['telephone'] == '')
  	       {$kml[] = '';}
              else {$kml[] = '<br />PH: ' . $row['telephone'];}
	if($row['fax'] == '')
              {$kml[] = '';}
              else {$kml[] = '<br />Fax: ' . $row['fax'];}
  	if($row['mobile'] == '')
         	{$kml[] = '';}
          	 else {$kml[] = '<br />Cell: ' . $row['mobile'];}
  	if($row['email_to'] == '')
         	{$kml[] = '';}
          	 else {$kml[] = '<br />Email: <a href="mailto:' . $row['email_to'] . '">' . $row['email_to'] . '</a>';}
  	$kml[] = '</div>]]>' . '</description>';
  	$kml[] = '<styleUrl>#text_photo_banner</styleUrl> <!-- anyURI -->';
	$kml[] = '<Point>';
	$kml[] = '<coordinates>' . $row['lng'] . ','  . $row['lat'] . '</coordinates>';
  	$kml[] = '</Point>';
  	$kml[] = '</Placemark>';
} // end of published status
} // end the state folder
	$kml[] = '</Folder>';
} // end the country folder
	$kml[] = '</Folder>';
}
// End XML file
$kml[] = '</Document>';
$kml[] = '</kml>';
$kmlOutput = join("\n", $kml);
header("Content-type: application/vnd.google-earth.kml+xml");
//header("Content-type: application/xml");
$output = "$kmlOutput";
//$f = fopen("active-members.kml", "w");
//fwrite ($f, $output);
//fclose($f);

echo $kmlOutput;
//print $output; //display in the browser
//echo " Updated";
//header("Location: https://www.nfsda.org/administrator/index.php?option=com_qcontacts");

mysql_free_result($result);
    print mysql_error();

error_reporting(E_ALL);
ini_set('display_errors', '1');
?>