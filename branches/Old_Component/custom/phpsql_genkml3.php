<?
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
$kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">';
$kml[] = ' <Document>';
$kml[] = ' <name>Nasvhille First SDA Church Members</name>';
$kml[] = ' <open>1</open>';
$kml[] = '<LookAt>
    		<longitude>-86.812370</longitude>
    		<latitude>36.131973</latitude>
		<altitude>0</altitude>
		<range>110027.8255488604</range>
		<tilt>0</tilt>
		<heading>-1.119363650863577e-006</heading>
		<altitudeMode>relativeToGround</altitudeMode>
		<gx:altitudeMode>relativeToSeaFloor</gx:altitudeMode>
  	   </LookAt>    <!-- Camera or LookAt -->
';

// gotta get my array of folders
while ($row = @mysql_fetch_assoc($result)) {
$rows[] = $row;
}

$suburbs = groupit(array('items' => $rows, 'field' => 'suburb'));

foreach ($suburbs as $c => $suburb) {
$newrows[$c] = groupit(array('items' => $suburbs[$c], 'field' => 'team'));
};
$mycounter = '0';
//print $c; die("url not loading");
foreach ($newrows as $c => $team) {
  $kml[] = '<Folder id="' . $mycounter++ . '"> ';
	$kml[] = '<name>'. $c . '</name>';
  //$kml[] = '<visibility>1</visibility>            <!-- boolean -->';
	$kml[] = ' <open>0</open>           <!-- boolean -->';
  //$kml[] = '<address>...</address>                <!-- string -->';
  //$kml[] = '<xal:AddressDetails>...</xal:AddressDetails>  <!-- xmlns:xal -->';
  //$kml[] = '<phoneNumber>...</phoneNumber>        <!-- string -->';
  //$kml[] = '<Snippet maxLines="2">...</Snippet>   <!-- string -->';
  //$kml[] = '<description>...</description>        <!-- string -->';
  //$kml[] = '<AbstractView>...</AbstractView>      <!-- Camera or LookAt -->';
  //$kml[] = '<TimePrimitive>...</TimePrimitive>
  //$kml[] = '<styleUrl>...</styleUrl>              <!-- anyURI -->';
  //$kml[] = '<Region>...</Region>';
  //$kml[] = '<ExtendedData>...</ExtendedData>      <!-- new in KML 2.2 -->';

foreach  ($team as $s => $rows) {

  $kml[] = ' <Folder id="' . $mycounter++ . '"> ';
	$kml[] = ' <name> Team ' . $s . ' </name> ';
  //$kml[] = '<visibility>1</visibility>            <!-- boolean -->';
	$kml[] = ' <open>0</open>           	   <!-- boolean -->';
  //$kml[] = '<address>...</address>                <!-- string -->';
  //$kml[] = '<xal:AddressDetails>...</xal:AddressDetails>  <!-- xmlns:xal -->';
  //$kml[] = '<phoneNumber>...</phoneNumber>        <!-- string -->';
  //$kml[] = '<Snippet maxLines="2">...</Snippet>   <!-- string -->';
  //$kml[] = '<description>...</description>        <!-- string -->';
  //$kml[] = '<AbstractView>...</AbstractView>      <!-- Camera or LookAt -->';
  //$kml[] = '<TimePrimitive>...</TimePrimitive>
  //$kml[] = '<styleUrl>...</styleUrl>              <!-- anyURI -->';
  //$kml[] = '<Region>...</Region>';
  //$kml[] = '<ExtendedData>...</ExtendedData>      <!-- new in KML 2.2 -->';


foreach ($rows as $row) {
$kml[] = ' <Style id="text_photo_banner">';
	$kml[] = ' <IconStyle>';
	$kml[] = ' <Icon>';
	$kml[] = ' <href>';
	$kml[] = 'http://www.nfsda.org/images/gmapsicons/icon' . $row['teamicon'] . '.png';
	$kml[] = ' </href>';
	$kml[] = ' </Icon>';
	$kml[] = ' </IconStyle>';
	//$kml[] = '<LabelStyle><color>7fffaaff</color><scale>1.5</scale></LabelStyle>';
    	$kml[] = '<LineStyle><color>ff0000ff</color><width>15</width></LineStyle>';
    	$kml[] = '<PolyStyle><color>7f7faaaa</color><colorMode>random</colorMode></PolyStyle>';
	$kml[] = '  </Style> ';
  $kml[] = ' <Placemark id="placemark' . $mycounter++ . ' "> ';
	$kml[] = ' <name>' . $row['name'] . '</name>';
	//$kml[] = ' <visibility>1</visibility>';
	$kml[] = ' <open>0</open>';
	//$kml[] = ' <atom:author>...<atom:author>         <!-- xmlns:atom -->';
  //$kml[] = ' <atom:link>...</atom:link>            <!-- xmlns:atom -->';
  $kml[] = ' <address>' . $row['address'] .', '. $row['suburb']. ', '. $row['state'] . ' '. $row['postcode'] . '-' . $row['postcodeaddon'] . '</address> <!-- string -->';
  //$kml[] = ' <xal:AddressDetails>...</xal:AddressDetails>  <!-- xmlns:xal -->';
  $kml[] = ' <phoneNumber>' . $row['telephone'] .'</phoneNumber>        <!-- string -->';
  $kml[] = ' <Snippet maxLines="2"><![CDATA['. $row['team'] . ']]></Snippet>   <!-- string -->';
  $kml[] = ' <description>' . '<![CDATA['; 
  if($row['image'] == '')
	{$kml[] = '<img src="https://www.nfsda.org/images/members/1st_Church_8x12.jpg" alt="Photo" width="100" hight="100" /><br />';}
	 else {$kml[] = '<img src="https://www.nfsda.org/images/members/' . $row['image'] . '" alt="Photo" width="100" hight="100" /><br />';}
  if($row['con_position'] == '')
	{$kml[] = '';}
	 else {$kml[] = '<h2>' . $row['con_position'] . '</h2>';}
  $kml[] = $row['misc'];
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
          else {$kml[] = '<br />Email: ' . $row['email_to'];}
  $kml[] = '<br /> ]]>' . '</description>';
	//$kml[] = ' <TimePrimitive>...</TimePrimitive>
  $kml[] = ' <styleUrl>#text_photo_banner</styleUrl>              <!-- anyURI -->';
  //$kml[] = ' <StyleSelector>...</StyleSelector>';
  //$kml[] = ' <Region>...</Region>';
  //$kml[] = ' <ExtendedData>...</ExtendedData>      <!-- new in KML 2.2 -->';
	$kml[] = ' <Point>';
  $kml[] = ' <coordinates>' . $row['lng'] . ','  . $row['lat'] . '</coordinates>';
  $kml[] = ' </Point>';
  $kml[] = ' </Placemark>';

} // end the state folder
	$kml[] = ' </Folder>';
} // end the country folder
	$kml[] = ' </Folder>';
 }
// End XML file
$kml[] = ' </Document>';
$kml[] = '</kml>';
$kmlOutput = join("\n", $kml);
//header("Content-type: application/vnd.google-earth.kml+xml");
header("Content-type: application/xml");
$output = "$kmlOutput";
$f = fopen("active-members.kml", "w");
fwrite ($f, $output);
fclose($f);

echo $kmlOutput;
//print $output; //display in the browser
//echo " Updated";
//header("Location: https://www.nfsda.org/administrator/index.php?option=com_qcontacts");

mysql_free_result($result);
    print mysql_error();

error_reporting(E_ALL);
ini_set('display_errors', '1'); 
?>