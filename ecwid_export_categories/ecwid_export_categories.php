<?php
header("Content-Type: application/csv");
header("Content-Disposition: attachment; Filename=ecwid_categories.csv");
header("Pragma: no-cache");
header("Expires: 0");

$ecwid_settings = parse_ini_file("ecwid_settings.ini", false);
if( $ecwid_settings["storeid"] > 0 ) {
	$storeid = $ecwid_settings["storeid"];
} 
else 
{
	//Required: storeid parameter

	if( !isSet($_GET['storeid'])) {
		echo "Failed - you must pass a storeID at the end of the url";
		exit;
	}
	if( !is_numeric($_GET['storeid']) ) {
		echo "ERROR: storeid may only be a number.";
		exit;
	}
	$storeid = $_GET['storeid'];
}

//Optional: menudelim (1 character) parameter, default ">"
//Optional: filedelim (1 character) parameter, default ","
//Ex1: ecwid_export_categories.php?storeid=123&menudelim=/&filedelim=;

include('ecwid_product_api.php');
$api = new EcwidProductApi($_GET['storeid']);
$categories = $api->get_all_categories();

function escape_csv_value($value) {
	$value = str_replace('"', '""', $value); // First off escape all " and make them ""
	if(preg_match('/,/', $value) or preg_match("/\n/", $value) or preg_match('/"/', $value)) {
		return '"'.$value.'"'; // If I have new lines or commas escape them
	} else {
		return $value; // If no new lines or commas just return the value
	}
}

//Build Breadcrumb Description Array
$breadcrumbs ='';
foreach($categories as $category)
{
	$row = '';
	$row['id'] = $category['id'];
	$row['parentId'] = $category['parentId'];
	$row['name'] = $category['name'];
	$row['breadcrumb'] = ''; //placeholder for calculated name
	$breadcrumbs[strval($category['id'])] = $row;
}
$menudelim = '/';
if ( isSet($_GET['menudelim'])) { $menudelim = $_GET['menudelim']; }
foreach($breadcrumbs as $breadcrumb)
{
	$categoryId = strval($breadcrumb['id']);
	$parentId = strval($breadcrumb['parentId']);
	$parentname = $breadcrumbs[$parentId]['name'];
	$breadcrumbdesc = $breadcrumb['name'];
	$counter = 0;
	//keep inserting parent name, if any
	while ( $parentname != '' AND $counter <= 30) {
		$breadcrumbdesc = $parentname . ' ' . $menudelim . ' ' . $breadcrumbdesc;
		$parentId = strval($breadcrumbs[strval($parentId)]['parentId']);
		$parentname = $breadcrumbs[$parentId]['name'];
		$counter = $counter + 1;
	}
	$breadcrumbs[$categoryId]['breadcrumb'] = $breadcrumbdesc;
}


//Determine File Delimiter
$filedelim = ',';
if ( isSet($_GET['filedelim'])) { $filedelim = $_GET['filedelim']; }

//Build CSV Header
	$row = '';
	$row[] = escape_csv_value("id");
	$row[] = escape_csv_value("parentId");
	$row[] = escape_csv_value("name");
	$row[] = escape_csv_value("url");
	$row[] = escape_csv_value("thumbnailURL");
	$row[] = escape_csv_value("breadcrumb");
	$header = join($filedelim, $row)."\n";

//Build CSV Data
$data = '';
foreach($categories as $category)
{
	$row = '';
	$row[] = escape_csv_value($category['id']);
	$row[] = escape_csv_value($category['parentId']);
	$row[] = escape_csv_value($category['name']);
	$row[] = escape_csv_value($category['url']);
	$row[] = escape_csv_value($category['thumbnailURL']);
	$row[] = escape_csv_value($breadcrumbs[strval($category['id'])]['breadcrumb']);
	$data .= join($filedelim, $row)."\n";
}

echo $header.$data;

?>
