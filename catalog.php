<?php

/*

This file is part of Primitive Point of Sale.

    Primitive Point of Sale is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Primitive Point of Sale is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Primitive Point of Sale.  If not, see <http://www.gnu.org/licenses/>.

*/

require("init.php");

header("Content-Type: application/json");


//
// search catalog
//
if(isset($_GET['q']))
{

	$items = array();
	$skn = $db->real_escape_string($_GET['q']);
	
	$tmpskn = $_GET['q']; // clean copy to regex on

	if(!is_numeric($skn))
	{
	
		// enable this to divide up the database, since no categories have been implemented or 
		// ways to group search data
		if($pos->config->use_catalog_filter)
		{
			($_GET['use_ws'] == '1') ? $ws_switch = ' AND barcode > 100000' : $ws_switch = ' AND barcode < 100000';
		} else
			$ws_switch = '';
	
			//	echo "SELECT * FROM catalog WHERE name LIKE '%$skn%'$ws_switch limit $catalog_limit";
		
		$fraction_regex = "/^[1-9][1-9]?\/[1-8][1-9]?.*$/";
		$dimension_regex = "/^[1-9][1-9]?x[1-9][1-9]?.*$/";
		//$cmpd_fraction_regex = "/^

		$query = "SELECT * FROM catalog WHERE name";

		if(preg_match($fraction_regex, $tmpskn) || preg_match($dimension_regex, $tmpskn))
			$query .= " LIKE '$skn%' ";
		else
			$query .= " LIKE '%$skn%' ";

		$query .= "$ws_switch ORDER BY name DESC LIMIT " . $pos->config->catalog_limit;
// manufacturer_id='$skn'

		$result = $db->query($query);
		
	}
	else
	{

		if(strlen($skn) == 12) // standard UPC length
		{
			// pad skn since catalog import of skn's have left zeros in front
			while(strlen($skn) < $pos->config->skuPadding)
				$skn = '0' . $skn;
		}

		
		$result = $db->query("SELECT * FROM catalog WHERE barcode='$skn' OR product_id = '$skn'");
// OR manufacturer_id='$skn'

	}
	
	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();
	
		if($row->product_id == '')
			$row->product_id = '';
		if($row->manufacturer_id == '')
			$row->manufacturer_id = '';
		if($row->vendor_name == '')
			$row->vendor_name = '';
	
		$tmp = array("barcode"=>$row->barcode, "name"=>$row->name, "vendor"=>$row->vendor_name, "sku"=>$row->product_id, "mft_id"=>$row->manufacturer_id, "qty"=>$row->qty, "price"=>$row->price);
	
		$items[] = $tmp;
	
	}
	
	echo json_encode($items);
	
	exit;
}

if(isset($_POST['edit_item']))
{
	$barcode = $db->real_escape_string($_POST['barcode']);
	$name = $db->real_escape_string($_POST['name']);
	$vendor = $db->real_escape_string($_POST['vendor']);
	$skn = $db->real_escape_string($_POST['sku']);
	$manufacturer_id = $db->real_escape_string($_POST['manufacturer_id']);
	$price = $db->real_escape_string($_POST['price']);
	$qty = $db->real_escape_string($_POST['qty']);

	$edit_item = 1;

	$result = $db->query("SELECT * FROM catalog WHERE barcode=$barcode");
	$row = $result->fetch_object();
	$old_price = $row->price;
	
	($price != $old_price) ? $override_price = 1 : $override_price = 0;


	$product_id_conflict = 0;

	// prevent conflict of product_id (UPC)
	if($skn != '')
	{
		// padd skn to 14 digits
		while(strlen($skn) < $pos->config->skuPadding)
			$skn = '0' . $skn;
	
		$result = $db->query("SELECT * FROM catalog WHERE product_id='$skn' AND barcode != $barcode");
		if($result->num_rows > 0) 
			$product_id_conflict = 1;
	}

	$query = "UPDATE catalog SET name = \"$name\", vendor_name=\"$vendor\", price='$price', override_price=$override_price, manufacturer_id=\"$manufacturer_id\", product_id=\"$skn\", qty='$qty' WHERE barcode=$barcode";
	
	//echo $query;
	
	if(!$product_id_conflict)
	{
		($db->query($query)) ? $status = 1 : $status = 0;
	} else
		$status = 0;

	$rv = array("status"=>"$status", "product_id_conflict"=>$product_id_conflict, "price"=>number_format($price, 2), "skn"=>$skn);

	echo json_encode($rv);
	exit;
}

//
// add item to catalog
//

$name = $db->real_escape_string($_POST['item_name']);
$price = $db->real_escape_string($_POST['item_price']);
//$category = $db->real_escape_string($_POST['category']);
$skn = $db->real_escape_string($_POST['item_skn']);

$db->query("LOCK TABLES catalog WRITE");

if($pos->config->use_catalog_filter)
	$filterLimit = " WHERE barcode < 100000";
else
	$filterLimit = "";

$result = $db->query("SELECT max(barcode) FROM catalog $filterLimit");
if($result->num_rows > 0)
{
    $row = $result->fetch_row();

    if(!is_numeric($row[0]))
	$new_id = 0; // zero will fail
    else
	$new_id = $row[0] + 1;

} else
    $new_id = 0;


$db->query("UNLOCK TABLES");

// new_id = 0 is a failure, so exit
if(!($new_id > 0))
    echo array("new_id"=>$new_id);

// pad skn since catalog import of skn's have left zeros in front
while(strlen($skn) < $pos->config->skuPadding)
	$skn = '0' . $skn;

$result = $db->query("INSERT INTO catalog(barcode, name, price, product_id) VALUES('$new_id', '$name', '$price', '$skn')");



$rv = array("new_id"=>$new_id);

echo json_encode($rv);

?>
