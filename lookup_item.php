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



$skn = $db->real_escape_string($_POST['skn']);
$ticket_id = $db->real_escape_string($_POST['ticket_id']);
$tax_exempt = $_POST['tax_exempt'];
$discount = $db->real_escape_string($_POST['discount']);
$freight = $db->real_escape_string($_POST['freight']);
$labor = $db->real_escape_string($_POST['labor']);

// remove, increment, or decrement item in cart
if(isset($_POST['modify_action']))
{
	modify_item($_POST['modify_action'], $ticket_id, $db->real_escape_string($_POST['item_id']), $tax_exempt, $_POST['update_qty'], $discount, $freight, $labor);

	exit;

} else if(isset($_POST['load_ticket']))
{

	load_ticket($ticket_id);
	exit;

} else if(isset($_POST['change_price']))
{
	update_price($ticket_id, $db->real_escape_string($_POST['item_id']), $db->real_escape_string($_POST['new_price']), $discount, $freight, $labor);
	
	exit;

} else
{

	// normal item scan, lookup, and add to cart

	if(is_numeric($skn))
		lookup_barcode($skn);
	else
		lookup_barcode($skn); // other function

}

//
// Scan a barcode or looked up item and add to cart
//
function lookup_barcode($skn)
{
	global $db, $ticket_id, $tax_exempt, $pos;


	if(strlen($skn) <= 6)
		$result = $db->query("SELECT * FROM catalog WHERE barcode=$skn");
	else
	{
		// i may have erroneously put too many zeros to the left of the skn when doing the import
		// so we can force pad it to 14
	
		while(strlen($skn) < 14)
			$skn = '0' . $skn;
	
		$result = $db->query("SELECT * FROM catalog WHERE product_id = '$skn'");
		
		
	}
	
	if($result->num_rows == 1)
	{
		$row = $result->fetch_object();
		
		$name = $row->name;
		$price = $row->price;

		if($ticket_id == '')
		{
			// create a new ticket
			$ticket_id = $pos->get_ticket_id();

		}	

		// in case we looked up by the long skn number, set skn to short # instead
		$skn = $row->barcode; 

		update_cart($skn, $ticket_id, $name, $price);

		list($subtotal, $cart) = getCartItems($ticket_id);

		$result = $db->query("SELECT freight, labor, discount, display_id FROM ticket WHERE id=$ticket_id");
		$row = $result->fetch_object();
	
	// function for getting final amounts
//		list($subtotal, $tax, $total) = getTransactionTotals($tax_exempt, $row->discount, $subtotal);

		$display_id = $row->display_id;

		($tax_exempt) ? $tax = '0' : $tax = ($subtotal - $row->discount) * (float) $pos->config->sales_tax;
	
		$total = number_format($tax + $subtotal - $row->discount + $row->freight + $row->labor, 2);
		$subtotal = number_format($subtotal,2);
		$tax = number_format($tax, 2);
		

		$rv = array("status"=>1, "subtotal"=>$subtotal, "tax"=>$tax, "total"=>$total, "ticket_id"=>$ticket_id, "ticket_display_id"=>$display_id, "cart"=>$cart);
		
	} else
		$rv = array("status"=>0);

	echo json_encode($rv);


}



// insert item into cart or increment the existing item in transaction_items
function update_cart($skn, $ticket_id, $name, $price)
{
	global $db;

	// first try to just update the number assuming they already have that item in the cart
	$result = $db->query("UPDATE transaction_items SET qty=qty+1, amount=qty*price WHERE item_id=$skn AND ticket_id=$ticket_id");
	
	if(!$db->affected_rows)
	{
		$name = addslashes($name);
	
		// look up the barcode to insert it into the transaction_items table
		$result = $db->query("SELECT barcode, product_id FROM catalog WHERE barcode=$skn");
		
		$row = $result->fetch_object();
		
		$db->query("INSERT INTO transaction_items(ticket_id, item_id, qty, name, price, amount, product_id) VALUES($ticket_id, $skn, 1, '$name', $price, $price,'$row->product_id')");
	
	}

}

function modify_item($action, $ticket_id, $item_id, $tax_exempt, $update_qty, $discount, $freight, $labor)
{
	global $db, $pos;
	
	if($action == 'incr')
		$result = $db->query("UPDATE transaction_items SET qty=qty+1, amount=qty*price WHERE item_id=$item_id AND ticket_id = $ticket_id");
	else if($action == 'decr')
		$result = $db->query("UPDATE transaction_items SET qty=qty-1, amount=qty*price WHERE item_id=$item_id AND ticket_id = $ticket_id");
	else if($action == 'del')
	{
		$result = $db->query("DELETE FROM transaction_items WHERE item_id=$item_id AND ticket_id = $ticket_id");
		$db->query("DELETE FROM item_descriptions WHERE barcode=$item_id AND ticket_id=$ticket_id");
	}
	else if($action == 'edit')
	{
		$update_qty = $db->real_escape_string($update_qty);
		
		$result = $db->query("UPDATE transaction_items SET qty=$update_qty, amount=qty*price WHERE item_id=$item_id AND ticket_id = $ticket_id");
	
	
	}

	
	if($result)
		$status = 1;

	$subtotal = get_subtotal($ticket_id);

	// function for getting final amounts
	//		list($subtotal, $tax, $total) = getTransactionTotals($tax_exempt, $row->discount, $subtotal);

	($tax_exempt) ? $tax = '0' : $tax = ($subtotal - $discount) * (float) $pos->config->sales_tax;
	
	$total = number_format($tax + $subtotal - $discount + $freight + $labor, 2);
	
	$subtotal = number_format($subtotal,2);
	$tax = number_format($tax, 2);
	$discount = number_format($discount, 2);

	$rv = array("status"=>$status, "subtotal"=>$subtotal, "tax"=>$tax, "total"=>$total, "ticket_id"=>$ticket_id, "display_discount"=>$discount, "cart"=>$cart);


	echo json_encode($rv);

}

function get_subtotal($ticket_id)
{
	global $db;

	$result = $db->query("SELECT amount FROM transaction_items WHERE ticket_id=$ticket_id");
		
	$subtotal = 0.0;
		
	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();
		
		$subtotal += $row->amount;
		
	}


	return $subtotal;

}

function load_ticket($ticket_id)
{
	global $db, $pos;

	$result = $db->query("SELECT CONCAT(customers.first_name, ' ', customers.mi, ' ', customers.last_name) AS customer, customers.tax_exempt, customers.credit, customers.company, customers.id AS customer_id, customers.use_company, customers.mi, customer_jobs.name AS job_name, ticket.job_id, ticket.display_id, ticket.freight, ticket.labor, ticket.discount, ticket.resale, ticket.payment_type, ticket.refund, ticket.recv_by FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE ticket.id=$ticket_id");
	
	$row = $result->fetch_object();
	
	// prevent loading tickets that are complete
	if($row->payment_type != '')
	{
		echo json_encode(array("status"=>0));
		exit;	
	}
	
	if(!$row->use_company)
	{
		$customer_name = $row->customer;
	}
	else
		$customer_name = $row->company;
	
	($row->tax_exempt != 1) ? $tax_exempt = 0 : $tax_exempt = 1;
	$row->customer_id == '' ? $customer_id = '' : $customer_id = $row->customer_id;
	$row->credit ? $allow_credit = 1 : $allow_credit = 0;
	
	$display_id = $row->display_id;
	
	$job_name = $row->job_name;
	$job_id = $row->job_id;
	
	$discount = $row->discount;
	$freight = $row->freight;
	$labor = $row->labor;
	$resale = $row->resale;
	$refund = $row->refund;
	$recv_by = $row->recv_by;

	list($subtotal, $cart) = getCartItems($ticket_id);
	

	($tax_exempt || $resale) ? $tax = '0' : $tax = ($subtotal - $discount) * (float) $pos->config->sales_tax;
	
	$total = number_format($tax + $subtotal + $freight + $labor - $discount,2);
	$subtotal = number_format($subtotal,2);
	$tax = number_format($tax, 2);

	// get existing item descriptions if available
	$descriptions = array();
	$result = $db->query("SELECT * FROM item_descriptions WHERE ticket_id=$ticket_id");
	for($i = 0; $i < $result->num_rows; $i++)
	{
	    $row = $result->fetch_object();

	    $descriptions[] = array('barcode'=>$row->barcode, 'description'=>$row->description);
	}


	$rv = array("status"=>1, "customer"=>$customer_name, "customer_id"=>$customer_id, "resale"=>$resale, "tax_exempt"=>$tax_exempt, "allow_credit"=>$allow_credit, "subtotal"=>$subtotal, "tax"=>$tax, "total"=>$total, "freight"=>$freight, "labor"=>$labor, "discount"=>number_format($discount, 2), "ticket_id"=>$ticket_id, "ticket_display_id"=>$display_id,"job_id"=>$job_id, "job_name"=>$job_name, "refund"=>$refund, "recv_by"=>$recv_by, "cart"=>$cart, "item_descriptions"=>$descriptions);
		
	echo json_encode($rv);

}

// update price from an editable price cell
function update_price($ticket_id, $item_id, $new_price, $discount, $freight, $labor)
{
	global $db, $tax_exempt, $pos;

	$result = $db->query("UPDATE transaction_items SET price=$new_price, amount=qty*price WHERE item_id=$item_id AND ticket_id = $ticket_id");
	
	if(!$result)
		$rv = array("status"=>0);
	else
	{
		$subtotal = get_subtotal($ticket_id);

		($tax_exempt) ? $tax = '0' : $tax = ($subtotal - $discount) * (float) $pos->config->sales_tax;
	
		$total = number_format($tax + $subtotal - $discount + $freight + $labor,2);
		$subtotal = number_format($subtotal,2);
		$tax = number_format($tax, 2);
	
		$rv = array("status"=>1,"subtotal"=>$subtotal,"tax"=>$tax,"total"=>$total);
	}
	
	echo json_encode($rv);
	exit;
	

}

// get items in the transaction_items table associated with the given ticket id
function getCartItems($ticket_id)
{
	global $db;
	
	$result = $db->query("SELECT * FROM transaction_items WHERE ticket_id=$ticket_id ORDER BY id DESC");
		
	$cart = array();
	$subtotal = 0.0;
	
	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();
		
		$item = array("item_id"=>$row->item_id, "name"=>$row->name . ' - ' . $row->item_id, "qty"=>$row->qty, "price"=>$row->price, "amount"=>$row->amount);
		$cart[] = $item;
			
		$subtotal += $row->amount;
		
	}

	return array($subtotal, $cart);
}

?>
