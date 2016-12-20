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

if(isset($_POST['job_id']))
{
	save_customer_job();

}else if(isset($_GET['get_customer_jobs']))
	get_customer_jobs($_GET['job_cust_id'], $_GET['show_balance']);
else if(isset($_GET['get_customers']))
	get_customers();
else if(isset($_GET['customer_details']))
	get_customer_details($db->real_escape_string($_GET['customer_id']), $_GET['show_jobs']);
else if(isset($_POST['set_printing_status']))
	edit_printing_status($_POST['customer_id'], $_POST['chk_status']);
else if(isset($_POST['customer_id']))
	save_customer_info($db->real_escape_string($_POST['customer_id']));



//
// show the customer list in the select-list
//
function get_customers()
{
	global $db, $_GET;

	if($_GET['show_inactive'] == 0)
		$result = $db->query("(SELECT company, id, last_name, first_name FROM customers WHERE use_company=1 AND active=1) UNION (SELECT CONCAT(last_name, ', ', first_name, ' ', mi), id, last_name, first_name FROM customers WHERE use_company=0 AND active=1) ORDER BY company ASC");
	
	//	$result = $db->query("SELECT id, company, first_name, last_name FROM customers WHERE active=1 ORDER BY company ASC");
	else
		$result = $db->query("(SELECT company, id, last_name, first_name FROM customers WHERE use_company=1) UNION (SELECT CONCAT(last_name, ', ', first_name, ' ', mi), id, last_name, first_name FROM customers WHERE use_company=0) ORDER BY company ASC");
//		$result = $db->query("SELECT id, company, first_name, last_name FROM customers ORDER BY company ASC");	

	$customers = array();

	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();
		
		//if($row->company != '' && $row->last_name == '')
		//$row->last_name = $row->company;
		
		//if($row->company == $row->last_name)
		//	$row->company .= ", " . $row->first_name;


		$tmp = array("id"=>$row->id, "c"=>$row->company);
		$customers[] = $tmp;

	}

	$rv = array("customers"=>$customers);

	echo json_encode($rv);
	
	exit;
}

function get_customer_details($customer_id, $show_jobs)
{
	global $db;

	$result = $db->query("SELECT * FROM customers WHERE id=$customer_id");

	$row = $result->fetch_object();

	if($show_jobs)
	    $jobs = get_customer_jobs($customer_id, 0, 'array'); // any 3rd parameter is fine

	$rv = array("last_name"=>$row->last_name, "first_name"=>$row->first_name, "mi"=>$row->mi, "company"=>$row->company, "address"=>$row->address, "address2"=>$row->address2, "city"=>$row->city, "state"=>$row->state, "zip"=>$row->zip, "tax_exempt"=>$row->tax_exempt, "allow_credit"=>$row->credit, "active"=>$row->active, "use_company"=>$row->use_company,"phone"=>$row->phone,"phone_ext"=>$row->phone_ext, "jobs"=>$jobs);

	echo json_encode($rv);
	
	exit;
}

function save_customer_info($customer_id)
{
	global $db, $_POST;
	
	$ln = ucfirst($db->real_escape_string($_POST['last_name']));
	$fn = ucfirst($db->real_escape_string($_POST['first_name']));
	$mi = $db->real_escape_string($_POST['mi']);
	$co = $db->real_escape_string($_POST['company']);
	
	$addr = $db->real_escape_string($_POST['address']);
	$addr2 = $db->real_escape_string($_POST['address2']);
	$city = $db->real_escape_string($_POST['city']);
	$state = $db->real_escape_string($_POST['state']);
	$zip = $db->real_escape_string($_POST['zip']);
	
	$credit = $db->real_escape_string($_POST['allow_credit']);
	$tax_exempt = $db->real_escape_string($_POST['tax_exempt']);
	$active = $db->real_escape_string($_POST['active']);

	$phone = $db->real_escape_string($_POST['phone']);
	$phone_ext = $db->real_escape_string($_POST['phone_ext']);
	
	($_POST['use_company'] == 1) ? $use_company = 1 : $use_company = 0;
	
	//if($co == '')
	//	$co = $ln; // company is the search term, so it must contain the last name if blank
	if($customer_id == 1126) // no editing 'noname'
		die("{\"status\":0}");
		
		
	// check that the names or company has changed and signal that the list should be reloaded if so
	$result = $db->query("SELECT * FROM customers WHERE id=$customer_id");
	$row = $result->fetch_object();
	if(($ln != $row->last_name && $row->use_company == 0) || ($co != $row->company && $row->use_company == 1) || $use_company != $row->use_company || $active == 0)
		$reorder_status = 1;
	else
		$reorder_status = 0;

	
	if($customer_id > 0)
	{
		$status = $db->query("UPDATE customers SET last_name='$ln', first_name='$fn', mi='$mi', company='$co', address='$addr', address2='$addr2', city='$city', state='$state', zip='$zip', credit=$credit, tax_exempt=$tax_exempt, active=$active, use_company=$use_company, phone='$phone', phone_ext='$phone_ext' WHERE id=$customer_id");
		

	}
	else
	{
		$status = $db->query("INSERT INTO customers(company,last_name,first_name,mi,address,address2,city,state,zip,credit,tax_exempt,active, use_company) VALUES('$co', '$ln', '$fn', '$mi',  '$addr', '$addr2', '$city', '$state', '$zip', '$credit', '$tax_exempt', '1', $use_company)");
	
		$customer_id = $db->insert_id;	
	}
	
	
	$status ? $status = 1 : $status = 0;
	
	$rv = array("status"=>$status, "customer_id"=>$customer_id, "reorder"=>$reorder_status);
	echo json_encode($rv);
	
	exit;
}

function get_customer_jobs($customer_id, $show_balance = 0, $output_type = 'screen')
{
	global $db, $pos;
	
	$id = $customer_id;
	
	// for some reason it may get turned ito an array
	if(is_array($customer_id))
		$id = $customer_id[0];
	
	if($customer_id < 0)
	{
		$rv = array("jobs"=>'');
		echo json_encode($rv);
		exit;	
	}

	$result = $db->query("SELECT * FROM customer_jobs WHERE customer_id=$id");
	
	$jobs = array();
	
	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();
		$tmp = array("id"=>$row->id, "name"=>$row->name);
		
		$jobs[] = $tmp;
	}
	
	if($show_balance == '1')
		$cur_balance = $pos->get_customer_balance($id);

	$rv = array("jobs"=>$jobs, "cur_balance"=>$cur_balance);

	if($output_type == 'screen')	
		echo json_encode($rv);
	else
		return array("jobs"=>$jobs);	

	exit;

}

function save_customer_job()
{

	global $db, $_POST;

	$id = $db->real_escape_string($_POST['job_id']);
	$name = $db->real_escape_string($_POST['job_name']);
	$customer_id = $_POST['customer_id'];

	if(is_array($customer_id))
		$customer_id = $db->real_escape_string($customer_id[0]);
	else
		$customer_id = $db->real_escape_string($customer_id);

	
	if($id == 'new')
		$query = "INSERT INTO customer_jobs(customer_id, name, active) VALUES($customer_id, '$name', 1)";
	else
		$query = "UPDATE customer_jobs SET name='$name' WHERE id=$id";

	$result = $db->query($query);
	
	if($result)
		$status = 1;
	else
		$status = 0;


	$rv = array("status"=>$status,"save_id"=>$id);
	echo json_encode($rv);
	
	exit;	
	
	
}



// set whether statements are printed in bulk for this customer
function edit_printing_status($customer_id, $chk_status)
{
    global $db;

    $customer_id = $db->real_escape_string($customer_id);
    $chk_status = $db->real_escape_string($chk_status);

    $query = "UPDATE customers SET print_statement=$chk_status WHERE id=$customer_id";
    //echo $query;
    $result = $db->query($query);

    $result ? $status = 1 : $status = 0;

    header("Content-type: application/json");
    echo json_encode(array("status"=>$status));

    exit;
}

?>
