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
require("generate_invoice.php");


$customer_id = $db->real_escape_string($_POST['customer_id']);

$end_date = $db->real_escape_string($_POST['end_date']);
$start_date = $db->real_escape_string($_POST['start_date']);

$start_timestamp = strtotime($start_date);
$end_timestamp = strtotime($end_date);

//echo "s: " . date("n/d/y", $start_date) . ", e: $end_date";

$ticket_search_start = $start_timestamp;

$end_timestamp = mktime(0, 0, 0, date("n", $end_timestamp), date("j", $end_timestamp)+1, date("Y", $end_timestamp));


$multiple_statements = 0;

list($statement_text, $length) = get_html_statement($customer_id, $action, $end_timestamp, $ticket_search_start); 


echo $statement_text;	



// get customer info
function get_html_statement($customer_id, $action, $end_timestamp, $ticket_search_start)
{
	global $db, $pos; // only used for the basic get request I think

	$statement_header = $pos->config->statement_header;


	$query = "SELECT CONCAT(customers.first_name, ' ', customers.last_name) AS customer, customers.tax_exempt, customers.credit, customers.id AS customer_id, customers.company, customers.use_company, customers.address, customers.address2, customers.city, customers.zip, customers.state FROM customers WHERE id=$customer_id";

	$result= $db->query($query);

	if($result->num_rows == 0)
		die("Customer not found");

	$row = $result->fetch_object();


	($row->use_company) ? $customer_name = $row->company : $customer_name = $row->customer;

	$customer_addr = $row->address;
	$customer_addr2 = $row->address2;
	$customer_city = $row->city;
	$customer_state = $row->state;
	$customer_zip = $row->zip;

	// determine balance


	$ca = new customerAcct($customer_id);

	$date_limits = "AND UNIX_TIMESTAMP(ticket.date) < $end_timestamp"; // AND UNIX_TIMESTAMP(ticket.date) >= $start_timestamp";
	//echo "d: " . date("n/d/y", $end_timestamp);exit;

	$date = date("m/d/Y", mktime(0,0,0, date("n", $end_timestamp), date("d", $end_timestamp) - 1, date("Y", $end_timestamp))); // for display on statement

	if($customer_addr2 != '')
		$customer_addr .= "<br /> &nbsp; " . $customer_addr2;

	// sum up all debts
	$result = $db->query("SELECT sum(ticket.total) AS charge from ticket where (ticket.payment_type='acct' or ticket.payment_type='svc_charge'  or payment_type='acct_cash' or payment_type='acct_check') $date_limits AND refund=0 AND ticket.customer_id=$customer_id GROUP BY customer_id");


	$row = $result->fetch_object();
	$ca->debts = $row->charge;

	// sum up all payments
	$result = $db->query("SELECT sum(total) AS payment from ticket WHERE (payment_type LIKE 'payment_%' OR payment_type='discount') $date_limits AND ticket.customer_id=$customer_id GROUP BY customer_id");	

	$row = $result->fetch_object();
	$ca->credits = $row->payment;

	// sum up all returns
	$result = $db->query("SELECT ticket.total, sum(ticket.total) AS charge from ticket where ticket.payment_type='acct' AND refund=1 $date_limits AND ticket.customer_id=$customer_id GROUP BY customer_id");	

	$row = $result->fetch_object();
	$ca->credits += $row->charge;
	//	echo $ca->credits . " + " . $ca->debts;
	$amount_due = number_format($ca->get_balance(), 2);

	//767.43 + 841.0

	$doc1 = <<<END


	<table style="width: 100%">
	<tr>
	<td style="width: 30%; text-align: left">
		$statement_header
	</td>

	<td style="width: 70%; text-align: right">
	<H2>Statement</H2>
	<P>Date</P>
	$date
	</td>
	</tr>
	</table>

	<table>
	<tr>
	<TD style="width: 15%; text-align: left"></TD>
	<td style="width: 60%; text-align: left">To:</td>
	<td style="width: 25%"></td>
	</TR>
	<TR>
	<td style="width: 15%"></td>
	<TD style="width: 60%; text-align: left">
	$customer_name<br />
	$customer_addr<br />
	$customer_city, &nbsp;$customer_state &nbsp; $customer_zip
	</TD>
	<td style="width: 25%"></td>
	</TR>
	</TABLE>

	<PRE>




	</PRE>

	<TABLE BORDER=0 CELLSPACING="3px" align="left" style="width: 100%">
	<TR>
	   <TD width="40%" colspan="2"></TD>
	   <TD width="30%">Terms</TD>
	   <TD width="30%">Amount Due</TD>
	</TR>
	<TR>
	  <TD width="40%" colspan="2"></TD>
	  <TD width="30%">Net 30</TD>
	  <TD width="30%" align="right"> $amount_due</TD>
	</TR>

	<TR>
	  <TD >Date</TD>
	  <TD ></TD>
	  <TD align="right">Amount</TD>
	  <TD align="right">Balance</TD>
	</TR>
END;

	$ca = new customerAcct($customer_id);

	// first get balance from previous 30 days (balance forward)
	// use the given timestamp and use that to look 30 days back
	//echo $end_timestamp;exit;


	$result = $db->query("SELECT *, unix_timestamp(date) AS ts FROM ticket WHERE customer_id=$customer_id AND UNIX_TIMESTAMP(ticket.date) < $ticket_search_start AND payment_type != 'VOID' ORDER BY DATE ASC");

	//$item_lines = '';

	// || $row->payment_type='acct_cash' || $row->payment_type='acct_check'

	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();

	//	if($row->payment_type == 'cc')
		//	continue;


		if($row->payment_type == 'check' && $row->refund == 1) // returned checks are not a credit
			continue;
		
		if(($row->payment_type == 'acct' && $row->refund == 0) || $row->payment_type == 'svc_charge' || $row->payment_type == 'acct_cash' || $row->payment_type == 'acct_check')
		{

			$ca->debts += $row->total;
		}
		else if(substr($row->payment_type, 0, 8) == 'payment_' || ($row->refund == 1 && $row->payment_type != 'cash' && $row->payment_type != 'cc') || $row->payment_type == 'discount')
		{
			$ca->credits += $row->total;
		}
		
	}

	// this is behaving strangely when balance forward date is later than the first ticket of the time period
	 // occurs when searching past date ranges

	$bforward_ts = $ticket_search_start;
	//print_r($ca);

	$doc1 .= "\n<TR><TD style=\"width: 20%\"><CENTER>" . date("m/d/Y", $bforward_ts) . "</CENTER></TD><TD style=\"width: 35%\"></td><td style=\"width: 20%; text-align: center\"> &nbsp; &nbsp;Balance Forward</TD><TD style=\"text-align: right; width: 25%\">" . number_format($ca->get_balance(), 2) . "</TD></TR>\r\n";



	// now look at period specified

	//$date_limits = "AND UNIX_TIMESTAMP(ticket.date) < $end_timestamp AND UNIX_TIMESTAMP(ticket.date) >= $start_timestamp";
	// changed this on 3/19
	$date_limits = "AND UNIX_TIMESTAMP(ticket.date) < $end_timestamp AND UNIX_TIMESTAMP(ticket.date) >= $bforward_ts";

	//OR payment_type='check'
	$q2 = "SELECT ticket.*, UNIX_TIMESTAMP(ticket.date) AS ts, customer_jobs.name AS job_name FROM ticket LEFT JOIN customer_jobs ON ticket.job_id=customer_jobs.id WHERE ticket.customer_id=$customer_id $date_limits AND payment_type != 'VOID' AND (payment_type = 'ACCT' OR payment_type LIKE 'PAYMENT_%' OR payment_type LIKE 'svc_charge' OR payment_type LIKE 'discount'  OR payment_type='acct_cash' OR payment_type='acct_check') ORDER BY ticket.date ASC";

//	echo $q2;exit;


	$result = $db->query($q2);


	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();


		if(substr($row->payment_type, 0, 8) == 'payment_' || ($row->refund == 1 && $row->payment_type != 'cash') || $row->payment_type == 'discount' )
			$ca->credits += $row->total;
		else if(($row->payment_type == 'acct' && $row->refund == 0) || $row->payment_type == 'svc_charge' || $row->payment_type == 'acct_cash' || $row->payment_type == 'acct_check')
		{
			// only returns and payments can be applied to the running balance if occuring after the end date
			//if($row->ts > $timestamp)
			//	continue;
	
			$ca->debts += $row->total;
		
		}

		if(substr($row->payment_type, 0, 8)  == 'payment_')
		{
			$transaction_type = 'PMT #' . $row->display_id;
			$total = -1 * $row->total;
		} else if($row->payment_type == 'svc_charge')
		{
			$transaction_type = "SVC CHG #" . $row->display_id;
			$total = $row->total;
		} else if($row->payment_type == 'discount')
		{
			$transaction_type = "DISCOUNT #" . $row->display_id;
			$total = -1 * $row->total;
		}
		else if($row->payment_type == 'acct_cash' || $row->payment_type == 'acct_check')
		{
			$parts = explode("_", $row->payment_type);
		
			$transaction_type = strtoupper($parts[1]) . " REF #" . $row->display_id;
			$total = -1 * $row->total;
		}
		else
		{
			$transaction_type = "INV #" . $row->display_id;
			$total = $row->total;
		}

		if($row->refund == '1')
			$total = -1 * $row->total; // mark negative for return/refund

		$row->job_name != '' ? $job_name = "&ndash; $row->job_name" : $job_name = '';

		$doc1 .= "\n<TR><TD style=\"width: 20%\"><CENTER>" . date("m/d/Y", strtotime($row->date)) . "</CENTER></TD>";
		$doc1 .= "<TD style=\"width: 35%\">&nbsp; &nbsp; &nbsp;$transaction_type $job_name</TD>";
		$doc1 .= "<TD  ALIGN=\"RIGHT\" style=\"width: 20%\">" . number_format($total, 2) . "</TD><TD align=\"right\" style=\"width: 25%\">" . number_format($ca->get_balance(), 2) . "</TD></TR>\r\n";

	}
	
	$doc1 .= "</table>";


	return array($doc1, $num_lines);

}


?>
