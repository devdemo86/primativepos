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

/* DYMO config in the printer options:
*
* media size:  continuous wide
* job options: characters/inch:  14
*
*/

require("init.php");
#require("/usr/share/php/php-barcode/php-barcode.php");


//$action = $_GET['action'];

$ticket_id = $db->real_escape_string($_GET['ticket_id']);

$change_returned = $_GET['change_returned'];
$payment_amount = $_GET['payment_amount'];

//$ticket_id=47;

$query = "SELECT CONCAT(customers.first_name, ' ', customers.last_name) AS customer, customers.tax_exempt, customers.credit, customers.id AS customer_id, customers.company, customers.use_company, customers.address, customers.city, customers.zip, customers.state, customer_jobs.name AS job_name, ticket.display_id, ticket.job_id, ticket.freight, ticket.labor, ticket.discount, ticket.resale, ticket.date, ticket.subtotal, ticket.total, ticket.tax, ticket.payment_type, ticket.refund, ticket.resale, ticket.check_no FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE ticket.id=$ticket_id";

$result= $db->query($query);

if($result->num_rows == 0)
	die("Ticket not found");

$row = $result->fetch_object();

$customer_id = $row->customer_id;
$ticket_date = date("M d Y g:i a", strtotime($row->date));

if($row->use_company)
	$customer_name = $row->company;
else
	$customer_name = $row->customer;

$customer_addr = $row->address;
$customer_city = $row->city;
$customer_state = $row->state;
$customer_zip = $row->zip;

$ticket_display_id = $row->display_id;

$subtotal = $row->subtotal;
$total = $row->total;
$tax = $row->tax;
$resale = $row->resale;
$discount = $row->discount;
$freight = $row->freight;
$labor = $row->labor;
$payment_type = strtoupper($row->payment_type);
$refund = $row->refund;
$check_no = $row->check_no;

$tax_state = $pos->config->tax_state;
$display_tax = $pos->config->display_tax;


// format the bottom of the ticket

//$transaction_info_length = 1496;

($payment_type == 'CHECK' || $payment_type == 'PAYMENT_CHECK') ? $show_check_no = 1 : $show_check_no = 0;

//if($show_check_no)
//	$transaction_info_length += 200; // need longer length if check number is present

//$payment_type = str_replace("_", " ", $payment_type);
if(substr($payment_type, 0, 8) == 'PAYMENT_')
{
	$tmp = str_replace("_", "  ", $payment_type);

	$payment_type = $tmp;
	if($show_check_no)
		$payment_type .= '-' . $check_no . "\n";

	$payment_type .= '  ' . $customer_name;

} else if($show_check_no)
	$payment_type .= '-' . $check_no . "\n " . $customer_name;

if($payment_type == 'ACCT')
	$payment_type = 'CHARGE';

if($payment_type == 'CASH' && $payment_amount != '')
{
	$payment_amount = number_format($payment_amount, 2);

	$transaction_info_string = <<<END
   $payment_type	($payment_amount)
 CHANGE            $change_returned-

END;

} else
	$transaction_info_string = "\n $payment_type\n Change	0.00-";


$output = $pos->config->xml_header;
$output .= "\n\n";

$result = $db->query("SELECT * FROM transaction_items WHERE ticket_id=$ticket_id ORDER BY id DESC");

$item_lines = '';

//$item_length = 750 + 159 * ($result->num_rows - 1); // have to specify size of this to printer

for($i = 0; $i < $result->num_rows; $i++)
{
	$row = $result->fetch_object();
	
	if($refund == '1')
		$row->price = '-' . $row->price;
	
	// comment out loop to print each item, so just print quantities to save paper now
	//for($j = 1; $j <= $row->qty; $j++)
	//{
	// max name = 24 i think
	// max length = 40
	
		while(strlen($row->item_id) < 6)
			$row->item_id = ' ' . $row->item_id;
	
		if(strlen($row->name) > 12)
			$row->name = substr($row->name, 0, 12);
	
		$len_price = strlen($row->price);

		// pad qty
		$qty_padding = '';
		for($j = strlen($row->qty); $j < 3; $j++)
		    $qty_padding .= ' ';

		$qty = $qty_padding . $row->qty;

		$left_side = $qty . '  ' . $row->item_id . '  ' . $row->name;

		$len_name = strlen($left_side);
		
		$num_spaces = 32 - $len_price - $len_name;
		//echo $num_spaces . "\n";
		if($num_spaces <= 0)
			$num_spaces = 1;
			
		//echo $left_side; // id, qty, name
		$output .= $left_side;

		for($k = 0; $k < $num_spaces; $k++) // pad between name and price
			$output .= " ";			
			//echo " ";
			
		//echo "$row->price\r\n";
		$output .= $row->price . "\r\n";
		
		
	//} // end loop for individual ticket listing

}

if($discount > 0)
{
	$discount_line = 'DISC.';
	$num_spaces = 30 - strlen($discount) - 4;
	for($k = 0; $k < $num_spaces; $k++)
		$discount_line .= ' ';
	$discount_line .= $discount . "\n";

} else
	$discount_line = '';

if($freight > 0)
{
	$freight_line = 'FREIGHT';
	$num_spaces = 30 - strlen($freight) - 9;
	for($k = 0; $k < $num_spaces; $k++)
		$freight_line .= ' ';
	$freight_line .= $freight . "\n";

} else
	$freight_line = '';

if($labor > 0)
{
	$labor_line = 'LABOR';
	$num_spaces = 30 - strlen($labor) - 7;
	for($k = 0; $k < $num_spaces; $k++)
		$labor_line .= ' ';
	$labor_line .= $labor ."\n";

} else
	$labor_line= '';

$doc = <<<END

 SUBTOTAL      $subtotal
 $discount_line$freight_line$labor_line
 $tax_state TAX $display_tax        $tax
              $total

 $transaction_info_string
 Ticket ID: $ticket_display_id
 $ticket_date
END;


//$bars = barcode_encode($ticket_id, 39);

//barcode_outimage($bars['text'], $bars['bars']);

//echo $doc;
$output .= $doc;

$fp = fopen($pos->config->tmp_dir . "/receipt.txt", "w");
fwrite($fp, $output);
echo $output;
fclose($fp);

//echo $pos->config->receipt_logo_path;exit;
//print_r($pos);exit;


//system("lpr -P Dymo-LabelWriter-450 -o PrintQuality=Graphics -o ppi=300 -o portrait -o ContinuousPaper=1 " . $pos->config->receipt_logo_path);
system("lpr -P Dymo-LabelWriter-450 -o ContinuousPaper=1 " . $pos->config->tmp_dir . "receipt.txt");

echo "lpr -P Dymo-LabelWriter-450 -o ContinuousPaper=1 " . $pos->config->tmp_dir . "receipt.txt";

//system("lpr -P Dymo-LabelWriter-450 -o ContinuousPaper=1 -o PrintQuality=Graphics /tmp/barcode.png");

//log_print('receipt-' . $ticket_display_id);

function log_print($path)
{

	return 0;

	$fp = fopen("c:/wamp/www/tmp/print_log.txt", "a");
	fwrite($fp, date("n/d/y H:i") . "\t$path\r\n");
	fclose($fp);

}

?>
