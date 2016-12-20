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
	
	
	ac_search.php
			provides autocomplete results
*/


require("init.php");
header("Content-Type: application/json");


	$q = strtolower($_GET["q"]);
	$q = trim($q);
	

	
	if($_GET['type'] == 'catalog')
	{
		$fraction_regex = "/^[1-9][1-9]?\/[1-8][1-9]?.*$/";
		$dimension_regex = "/^[1-9][1-9]?x[1-9][1-9]?.*$/";
		
		$query = "SELECT barcode, name, product_id, vendor_name, product_id, manufacturer_id, qty FROM catalog WHERE name ";
		
		$skn = $db->real_escape_string($q);
		
		if(preg_match($fraction_regex, $q) || preg_match($dimension_regex, $q))
			$query .= " LIKE '$skn%' ";
		else
			$query .= " LIKE '%$skn%' ";	
		


		$query .= " LIMIT " . $pos->config->catalog_limit;
	
		$result = $db->query($query);
	
	}
	else if($_GET['type'] == 'customer')
		$result = $db->query("SELECT first_name, last_name, company, CONCAT(last_name, ' ', first_name, ' ' , mi) AS customer_reverse, CONCAT(first_name, ' ', mi, ' ', last_name) as customer, CONCAT(first_Name, ' ', last_name) AS customer_short, id, tax_exempt, use_company FROM customers HAVING first_name like '$q%' OR last_name like '%$q%' OR customer_short LIKE '$q%' OR customer like '$q%' OR customer_reverse like '$q%' OR company LIKE '%$q%'");
	
	$items = array();

	
	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();
		
		if($_GET['type'] == 'catalog')
		{	
			if($row->product_id != '')
			{
				$row->product_id = ltrim($row->product_id, '0');
			
				$row->product_id = ' &nbsp; ' . $row->product_id . ' &ndash; ';
				
			} else
				$row->product_id = ' &nbsp; &nbsp; &nbsp; ';
		
			$extra_info = $row->product_id  . $row->vendor_name . ' &ndash; ' . $row->manufacturer_id . ' &ndash; Qty: ' . $row->qty;
		
			$tmp = array("barcode"=>$row->barcode, "name"=>"$row->barcode  - $row->name", "extra"=>$extra_info);
		}
		else if($_GET['type'] == 'customer')		
		{
			if($row->company != '' && $row->use_company)
				$display_name = $row->company;
			else
				$display_name = $row->customer;
		
			$tmp = array("customer_id"=>$row->id, "name"=>$display_name, "tax_exempt"=>$row->tax_exempt);
			
		}
		
		$items[] = $tmp;
	
	
	}
	
	
	echo json_encode(array("items"=>$items));

	
?>