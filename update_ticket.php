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

$ticket_id = $db->real_escape_string($_POST['ticket_id']);
$status = 0;


if(isset($_POST['customer_id']))
	$status = update_customer($ticket_id, $db->real_escape_string($_POST['customer_id']));
else if(isset($_POST['payment_type']))
	$status = post_transaction($ticket_id);
else if(isset($_POST['job_id']))
	$status = update_customer_job($ticket_id, $db->real_escape_string($_POST['job_id']));
else if(isset($_POST['special']))
	$status = update_special($ticket_id);
else if(isset($_POST['set_refund']))
	$status = set_refund($ticket_id, $db->real_escape_string($_POST['refund']));
else if(isset($_POST['item_description']))
	$status = add_item_description($ticket_id, $db->real_escape_string($_POST['barcode']), $db->real_escape_string($_POST['description']));
else if(isset($_POST['recv_by']))
	$status = add_recv_by($ticket_id, $db->real_escape_string($_POST['recv_by']));
else
	$status = void_ticket($ticket_id);


$rv = array("status"=>$status, "ticket_id"=>$ticket_id);
echo json_encode($rv);


// functions //
///////////////


// void ticket
function void_ticket($ticket_id)
{
	global $db;

	$query = "UPDATE ticket SET payment_type='VOID', date=NOW() WHERE id=$ticket_id";

	if($db->query($query))
	{
		// update the catalog qty for each item
		// each record of transaction_items contains the quantity
		$result = $db->query("SELECT * FROM transaction_items WHERE ticket_id=$ticket_id");

		for($i = 0; $i < $result->num_rows; $i++)
		{
		    $row = $result->fetch_object();

		    $qty = $row->qty;
		    $item_id = $row->item_id;

		    $query = "UPDATE catalog SET qty = qty + $qty WHERE barcode=$item_id";
		    $db->query($query);

		}

		return (1);

	}
}


//
// add the selected customer name to the ticket
//
function update_customer($ticket_id, $customer_id)
{
	global $db, $pos;

	//$default_customer_id = $pos->config->default_customer_id;


	$result = $db->query("SELECT display_id FROM ticket WHERE id=$ticket_id");
	$row = $result->fetch_object();
	$ticket_display_id = $row->display_id;
	
	//if(!($customer_id > 1))
	//	$customer_id = $pos->config->default_customer_id;

	$query = "UPDATE ticket SET customer_id=$customer_id, job_id=NULL WHERE id=$ticket_id";

	if(!$db->query($query))
		return (0);
		
	$query = "SELECT * FROM customers WHERE id=$customer_id";
	$result = $db->query($query);

	if($result->num_rows == 1)
	{
		$row = $result->fetch_object();
		
		// for other functions we output the json at the end, but here, will do it 
		// here, but should be handled better in the future...
		$rv = array("status"=>1, "tax_exempt"=>$row->tax_exempt, "allow_credit"=>$row->credit, "ticket_display_id"=>$ticket_display_id);

		echo json_encode($rv);
		exit;
		
	} else
		return (0);
	

}

function update_customer_job($ticket_id, $job_id)
{
	global $db;

	if($job_id == 0)
		$job_id = 'NULL';

	$query = "UPDATE ticket SET job_id=$job_id WHERE id=$ticket_id";

	if($db->query($query))
		return(1);
	else
		return (0);



}

	
function update_special($ticket_id)
{
	global $db, $_POST, $pos;

	$subtotal = str_replace(",", "", $_POST['subtotal']);
	$discount_number = str_replace(",", "", $_POST['discount_number']);
//	$discount_percentage = $_POST['discount_percentage'];

	if(!is_numeric($subtotal))
		$subtotal = 0;


	if($discount_number != '')
		$discount = $discount_number;
	//else if($discount_percentage != '')
	//	$discount = number_format($discount_percentage * .01 * $subtotal, 2);
	else
		$discount = '0';
	
	$freight = $db->real_escape_string($_POST['freight']);
	$labor = $db->real_escape_string($_POST['labor']);
		
	if($freight == '')
		$freight = 0;
	
	if($labor == '')
		$labor = 0;
	
	if(!is_numeric($freight))
		$freight='NULL';
	if(!is_numeric($discount))
		$discount='NULL';
	if(!is_numeric($labor))
		$labor = 'NULL';

	$_POST['resale'] == 1 ? $resale = 1 : $resale = 0;
	$_POST['tax_exempt'] == 1 ? $tax_exempt = 1 : $tax_exempt = 0;


				// , subtotal=$subtotal-$discount
	$query = "UPDATE ticket SET discount=$discount, freight=$freight, labor=$labor, resale=$resale WHERE id=$ticket_id";
	
	if($db->query($query))
	{
		// now update the total and tax

		($resale || $tax_exempt) ?	$tax = 0 : $tax = ($subtotal - $discount) * (float) $pos->config->sales_tax;
		
		
		$db->query("UPDATE ticket SET tax=$tax WHERE id=$ticket_id");

	
//		$db->query("UPDATE ticket SET total=subtotal+tax-discount+freight WHERE id=$ticket_id");

		$total = number_format($subtotal - $discount + $freight + $labor + $tax, 2);

		$rv = array("status"=>1, "display_discount"=>number_format($discount,2), "freight"=>number_format($freight, 2), "labor"=>number_format($labor, 2), "total"=>$total, "tax"=>number_format($tax, 2));

		echo json_encode($rv);
		exit;
	} else
		return(0);



}

function post_transaction($ticket_id)
{
	global $_POST, $db;
	
	$cc_trans_no = $check_no = '';
	
	//$amount_given = $db->real_escape_string($_POST['amount_given']);
	$payment_type = $db->real_escape_string($_POST['payment_type']);
	$subtotal = $db->real_escape_string($_POST['subtotal']);
	$tax = $db->real_escape_string($_POST['tax']);
	$total = $db->real_escape_string($_POST['total']);
	$refund = $db->real_escape_string($_POST['refund']);
	$recv_by = $db->real_escape_string($_POST['recv_by']);

	$parts = explode("</b>", $recv_by); // the label is part of the value, so remove it
	$recv_by = trim($parts[1]);
	
	if($payment_type == 'check')
	{
		$check_no = $db->real_escape_string($_POST['check_no']);
		$extra_info = ", check_no='$check_no'";
	}
	else if($payment_type == 'cc')
	{
		$cc_trans_no = $db->real_escape_string($_POST['cc_trans_no']);
		$extra_info = ", cc_trans_no='$cc_trans_no'";
	}

	// remove commas from number_format()
	$tax = str_replace(",", "", $tax);
	$subtotal = str_replace(",", "", $subtotal);
	$total = str_replace(",", "", $total);
	

	

	$query = "UPDATE ticket SET payment_type='$payment_type', recv_by='$recv_by', date=NOW(), refund=$refund, subtotal='$subtotal', tax='$tax', total='$total'$extra_info WHERE id=$ticket_id";

	if($db->query($query))
	{
		// update catalog quantities
		
		$result = $db->query("SELECT * FROM transaction_items WHERE ticket_id=$ticket_id");
		
		for($i = 0; $i < $result->num_rows; $i++)
		{
			$row = $result->fetch_object();
			
			if($refund == 1)
				$qty_diff = (int) $row->qty;
			else
				$qty_diff = (int) (-1 * $row->qty);
			

			$db->query("UPDATE catalog SET qty=qty+$qty_diff WHERE barcode=$row->item_id");
	
			
		}
	
		return (1);	
		
		
	}


}

// set the refund status on a transaction
function set_refund($ticket_id, $refund)
{
    global $db;

    $result = $db->query("UPDATE ticket SET refund=$refund WHERE id=$ticket_id");

    $result ? $status = 1 : $status = 0;

    return $status;


}

// add a text description < 128 chars to an item in the cart
// only 1 description per item
function add_item_description($ticket_id,  $barcode, $description)
{
    global $db;

   // get the id for this item in the transaction_items table
    $result = $db->query("SELECT * FROM transaction_items WHERE ticket_id=$ticket_id AND item_id=$barcode");
    if($result->num_rows == 0)
	return 0;

    $row = $result->fetch_object();
    $item_id = $row->id;

    $result = $db->query("SELECT id FROM item_descriptions WHERE ticket_id=$ticket_id AND barcode=$barcode");


    if($result->num_rows == 0)
	$r = $db->query("INSERT INTO item_descriptions(ticket_id, barcode, description, item_id) VALUES($ticket_id, $barcode, '$description', $item_id)");
    else
	$r = $db->query("UPDATE item_descriptions SET description='$description' WHERE barcode=$barcode AND ticket_id=$ticket_id");

   $r ? $status = 1 : $status = 0;

   return $status;

}

// update the 'received by' field upon entry
function add_recv_by($ticket_id, $recv_by)
{
    global $db;

    $r = $db->query("UPDATE ticket SET recv_by='$recv_by' WHERE id=$ticket_id");

    $r ? $status = 1 : $status = 0;

    return $status;

}

	
?>
