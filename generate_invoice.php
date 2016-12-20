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

// $show_heading will avoid printing the company name on the ticket
// $mode, if set to 'statement' will skip the customer's billing info
// $basic_html includes the <html><body></body></html>
function generate_invoice($ticket_id, $show_heading = 1, $mode = 'simple', $basic_html = 1)
{
	global $db, $pos;
	
	if($show_heading)
		$invoice_header = $pos->config->invoice_header;
	else
		$invoice_header = '';

$query = "SELECT CONCAT(customers.first_name, ' ', customers.last_name) AS customer, customers.tax_exempt, customers.credit, customers.id AS customer_id, customers.company, customers.use_company, customers.address, customers.city, customers.zip, customers.state, customer_jobs.name AS job_name, ticket.job_id, ticket.freight, ticket.labor, ticket.discount, ticket.resale, ticket.date, ticket.subtotal, ticket.total, ticket.tax, ticket.payment_type, ticket.refund, ticket.resale, ticket.recv_by, ticket.check_no, ticket.display_id FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE ticket.id=$ticket_id";


$result= $db->query($query);

if($result->num_rows == 0)
	die("Ticket not found");

$row = $result->fetch_object();

$customer_id = $row->customer_id;
$ticket_date = date("m/d/y", strtotime($row->date));
$ticket_time = date("g:i a", strtotime($row->date));

if($row->use_company)
	$customer_name = $row->company;
else
	$customer_name = $row->customer;

$customer_addr = $row->address;
$customer_city = $row->city;
$customer_state = $row->state;
$customer_zip = $row->zip;

$subtotal = $row->subtotal;
$total = $row->total;
$tax = $row->tax;
$resale = $row->resale;
$discount = $row->discount;
$freight = $row->freight;
$labor = $row->labor;
$payment_type = $row->payment_type;
$check_no = $row->check_no;

$ticket_display_id = $row->display_id;

$recv_by = $row->recv_by;


$job_name = $row->job_name;

$row->refund ? $refund_indicator = '-' : $refund_indicator = '';

if($basic_html)
{
$doc1 = <<<END
<html>
<head>
<title></title>
<style type="text/css">
.invoice_border th, .invoice_border td { border: 1px solid #000000 }

</style>
</head>

<body style="text-align: center; font-family: sans-serif">
<BASEFONT FACE="arial" SIZE=1>

<CENTER><B>
$invoice_header

</CENTER>
END;
} else
	$doc1 = "";

$doc1 .= <<<END

<P></P>

<CENTER>
<table width="90%" BORDER=1 class="invoice_heading_table" style="margin-left: auto; margin-right: auto; border-collapse: collapse; border: 1px solid #000000">
<tr class="invoice_border"><th>Customer No.</th><th>Job Name</th><th>Invoice No.</th><th>Received by</th><th style="width: 80px">Date</th><th style="width: 80px">Time</th></tr>
<tr class="invoice_border"><td><center>$customer_id</center></td><td><center>$job_name</center></td><td><CENTER>$ticket_display_id</CENTER></td><td><center>$recv_by</center></td><td><CENTER>$ticket_date</CENTER></td><td><CENTER>$ticket_time</CENTER></td></tr>
</table>
</CENTER>

<P></P>
END;


if($mode != 'statement')
{

$doc1 .= <<<END

<CENTER>
<table BORDER=0 style="width: 100%; text-align: center; margin-left: auto; margin-right: auto; margin-top: 15px;  border: none;">
<tr>
<td style="width: 37%"></td>

<td style="width: 26%">

	<table border="1" style="width:100%">
	<tr>
	<td style="padding: 5px">
	<b>Sold To</b>

	<br>
	 &nbsp; $customer_name<br />
	 &nbsp; $customer_addr<br />
	 &nbsp; $customer_city, &nbsp;$customer_state &nbsp; $customer_zip
	</td>
	</tr>
	</table>
</td>
<td style="width: 37%"></td>
</TR>
</TABLE>

</CENTER>
END;

}

if($basic_html == 1) // make it smaller when printing multiple
	$doc1 .= "<p></p>";

$doc1 .= <<<END

<CENTER>
<table WIDTH="95%" BORDER=0 CELLPADDING="4px" class="items_list" style="margin-top: 15px; margin-left: auto; margin-right: auto; border: none">
<tr><th>Quantity</th><th>SKU</th><th>Description</th><th align="center">Price</th><th align="center">Amount</th></tr>
END;

$result = $db->query("SELECT * FROM transaction_items WHERE ticket_id=$ticket_id ORDER BY id DESC");

$item_lines = '';

$num_lines = $result->num_rows; // for keeping track of for batch jobs

for($i = 0; $i < $result->num_rows; $i++)
{
	$row = $result->fetch_object();

	if(strlen($row->name) >= 17)
		$num_lines++; // the item description wraps over the line

	$item_lines .= <<<END

	<tr><td style=""><center>$row->qty</center></td>
	<td style=""><center>$row->item_id</center></td>
	<td class="item_name"><CENTER>$row->name</CENTER></td>
	<td align="right" style="text-align: right; padding-right: 10px;">$refund_indicator$row->price</td>
	<td align="right" style="text-align: right; padding-right: 10px;">$refund_indicator $row->amount</td>
	</tr>
END;

}

$doc1 .= $item_lines;

$tmp = <<<END
<tr class="totals_row"><td colspan="3" style="border-right: none"></td>
<td align="right" style="border-left: none; text-align: right; padding-right: 10px">Subtotal <br />
END;

$doc1 .= $tmp;

if($discount > 0)
	$doc1 .= "Discount<br />";

if($tax != '')
$doc1 .= "Tax<br />";

if($freight > 0)
	$doc1 .= "Freight <br />";

if($labor > 0)
	$doc1 .= "Labor <br />";

$tmp = <<<END
<p><FONT FACE="arial">Total Amount</FONT></p>
<p><FONT FACE="arial">Payment Type</FONT></p>
</td><td align="right" style="text-align: right; padding-right: 10px; border-bottom: 1px solid #000000">
$refund_indicator$subtotal<br />
END;

$doc1 .= $tmp;

if($discount > 0)
	$doc1 .= " -" . $discount . "<br />";
	
if($tax != '')
$doc1 .= "$refund_indicator$tax<br />";

if($freight > 0)
{
	$doc1 .= "$freight <br />";

	//$total += $freight;
	//$total = number_format($total, 2);

}

if($labor > 0)
{
	$doc1 .= "$labor <br />";

//	$total += $labor;
}

$total = number_format($total, 2);


$payment_type = strtoupper($payment_type);

if($payment_type == 'CHECK' || $payment_type == 'PAYMENT_CHECK')
    $payment_type .= ' - ' . $check_no;


$tmp = <<<END
<p><FONT FACE="arial">$refund_indicator$total</FONT></p>
<p><FONT FACE="arial">$payment_type</FONT></p>

</td></tr>

</table>
</CENTER>

END;

if($basic_html)
{
	$tmp .= <<<END
</body>
</html>
END;
}

$doc1 .= $tmp;

return array($doc1, $num_lines);

}

?>
