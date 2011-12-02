<?
require('phpsql_dbovinfo.php');
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
function join()
{

}

// Creates an array of strings to hold the lines of the KML file.
$kml = array('<?xml version="1.0" encoding="UTF-8"?>');
$kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2">';
$kml[] = ' <Document>';
$kml[] = ' <name>NFSDA Sow 1000</name>';

// gotta get my array of folders
while ($row = @mysql_fetch_assoc($result)) {
$rows[] = $row;
}
$mycounter = '0';
foreach ($rows as $row) {
	$kml[] = '<Folder id="' . $mycounter++ . '"> ';
	//$kml[] = '<Folder id="' . $mycounter++ . '"> ';
	//$kml[] = '<name>'. $row['name']  . '</name>';
	//$kml[] = ' <open>0</open>           <!-- boolean -->';
	$kml[] = ' <Style id="text_photo_banner">';
	$kml[] = ' <IconStyle>';
	$kml[] = ' <Icon>';
	$kml[] = ' <href>';
	$kml[] = 'http://www.nfsda.org/images/gmapsicons/icon'.$row['icon'].'.png';
	$kml[] = ' </href>';
	$kml[] = ' </Icon>';
	$kml[] = ' </IconStyle>';
	//$kml[] = '<LabelStyle><color>7fffaaff</color><scale>1.5</scale></LabelStyle>';
    	//$kml[] = '<LineStyle><color>ff0000ff</color><width>15</width></LineStyle>';
    	//$kml[] = '<PolyStyle><color>7f7faaaa</color><colorMode>random</colorMode></PolyStyle>';
	$kml[] = ' </Style> ';
  	$kml[] = ' <Placemark id="placemark' . $mycounter++ . ' "> ';
	$kml[] = ' <name>' . $row['name'] . '</name>';
       $kml[] = ' <description><![CDATA[<div>';
	if($row['disc'] == '')
	{$kml[] = '';}
	else {$kml[] = $row['disc'];}
	if($row['addressgp'] == '')
	{$kml[] = '';}
	else {$kml[] = '<br /><br /><b>Cross Streets:</b><br />' . $row['addressgp'];}
	$kml[] = '</div>]]></description>';
  	$kml[] = '<styleUrl>#text_photo_banner</styleUrl> <!-- anyURI -->';
	$kml[] = '<Point>';
	$kml[] = '<coordinates>' . $row['lng'] . ','  . $row['lat'] . '</coordinates>';
  	$kml[] = '</Point>';
  	$kml[] = '</Placemark>';
$kml[] = '<name>Group '. $row['id'] . '</name>';
$kml[] = '<open>0</open> <!-- boolean -->';
$kml[] = '<Placemark>';
$kml[] = '<name>' . $row['name'] . '</name>';
$kml[] = '<Style>';
$kml[] = '<LineStyle>';
$kml[] = '<color>' . $row['linestyle'] . '</color>';
$kml[] = '</LineStyle>';
$kml[] = '<PolyStyle>';
$kml[] = '<color>' . $row['polystyle'] . '</color>';
$kml[] = '</PolyStyle>';
$kml[] = '</Style>';
$kml[] = '<Polygon>';
$kml[] = '<outerBoundaryIs>';
$kml[] = '<LinearRing>';
$kml[] = '<coordinates>'; 
$kml[] = $row['params'];           
$kml[] = '</coordinates>';
$kml[] = '</LinearRing>';
$kml[] = '</outerBoundaryIs>';
$kml[] = '</Polygon>';
$kml[] = '</Placemark>';
$kml[] = '</Folder>';
}
// End XML file
$kml[] = '</Document>';
$kml[] = '</kml>';
$kmlOutput = join("\n", $kml);
header("Content-type: application/vnd.google-earth.kml+xml");
//header("Content-type: application/xml");
//$output = "$kmlOutput";
//$f = fopen("active-members.kml", "w");
//fwrite ($f, $output);
//fclose($f);

echo $kmlOutput;

mysql_free_result($result);
    print mysql_error();

error_reporting(E_ALL);
ini_set('display_errors', '1');
?>