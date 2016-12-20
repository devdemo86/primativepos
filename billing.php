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


header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Pragma: no-cache");


$accts = array();

if(isset($_GET['basic_list']))
{
	header("Content-Type: application/json");
	show_basic_list($_GET['display_type'], $_GET['end_date'], $_GET['csv_mode']);
	
}else if(isset($_GET['customer_id']))
	view_customer_bills($db->real_escape_string($_GET['customer_id']), $_GET['sort_type']);
else if(isset($_GET['ticket_id'])) // get ticket contents
	view_ticket($db->real_escape_string($_GET['ticket_id']), $db->real_escape_string($_GET['ticket_search']), $db->real_escape_string($_GET['limit_customer_id'])); // ticket_search=0|1 to differentiate which kind of ID loads the ticket
else if(isset($_POST['special_charge']))
	save_service_charge($_POST['special_charge_type'], $db->real_escape_string($_POST['customer_id']), $db->real_escape_string($_POST['amount']), $db->real_escape_string($_POST['job_id']));
else if(isset($_POST['issue_cash_refund']))
	issue_refund($db->real_escape_string($_POST['customer_id']), $db->real_escape_string($_POST['amount']), $db->real_escape_string($_POST['refund_type']));


//
// default window when loading the billing dialog
//
function show_basic_list($display_type, $end_date, $csv_mode = 0)
{
	global $db, $accts, $no_accts;
	
	$end_date = strtotime($end_date)+86399;

	// get charges to accts
	
	$csv_output = "Accounts on " . date("n/d/Y", $end_date) . ",,\r\n";
	$csv_output .= "Name,,Balance\r\n\r\n";

	$result = $db->query("(SELECT company, id, last_name, first_name, print_statement FROM customers WHERE use_company=1 AND active=1) UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi) , id, last_name, first_name, print_statement FROM customers WHERE use_company=0 AND active=1) ORDER BY company ASC");
	

	// populate array with customers
	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();

		$ca = new customerAcct($row->id);
		
		//if($row->use_company)
			$ca->name = $row->company;
		//else
		//	$ca->name = $row->last_name . ', ' . $row->first_name . $row->mi;
		//	$ca->name = $row->last_name . ', ' . $row->first_name;
			
		$ca->print_statement = $row->print_statement;

		$accts[] = $ca;

	}

	$no_accts = count($accts);

	// sum up all debts
	$result = $db->query("SELECT ticket.customer_id, ticket.total,sum(ticket.total) AS charge from ticket where (ticket.payment_type='acct' OR ticket.payment_type='svc_charge' OR ticket.payment_type='acct_cash' OR ticket.payment_type='acct_check') AND UNIX_TIMESTAMP(ticket.date) <= $end_date AND refund=0 GROUP BY customer_id");	

	//echo "SELECT ticket.customer_id, ticket.total,sum(ticket.total) AS charge from ticket where (ticket.payment_type='acct' OR ticket.payment_type='svc_charge' OR ticket.payment_type='acct_cash' OR ticket.payment_type='acct_check') AND UNIX_TIMESTAMP(ticket.date) <= $end_date AND refund=0 GROUP BY customer_id";


	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();

		set_customer_amt($row->customer_id, 'debts', $row->charge);
		
		//echo " $row->customer_id, 'debts', $row->charge, 'pay <P>";
	}

	// sum up all payments -- NOT influenced by date selector
	$result = $db->query("SELECT customer_id, sum(total) AS payment from ticket WHERE (payment_type LIKE 'payment_%' OR payment_type='discount') AND UNIX_TIMESTAMP(ticket.date) <= $end_date GROUP BY customer_id");	


	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();

		set_customer_amt($row->customer_id, 'credits', $row->payment);
		
		//echo "set: $row->customer_id, 'credits', $row->payment <P>";
	}


	// sum up all returns -- NOT influenced by date selector
	$result = $db->query("SELECT ticket.customer_id, ticket.total,sum(ticket.total) AS charge from ticket WHERE (ticket.payment_type='acct' AND refund=1) GROUP BY customer_id");	

	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();

		set_customer_amt($row->customer_id, 'credits', $row->charge);
		
		//echo "set: $row->customer_id, 'debts', $row->charge <P>";
	}

	$json_list = ''; //array();

	$stripe = "class=\"odd\"";



	//
	// print it out
	//

	for($i = 0; $i < count($accts); $i++)
	{
		$balance = '';

		($accts[$i]->print_statement) ? $checked = " checked=\"checked\"" : $checked = "";
	
		if($accts[$i]->debts > 0 && $accts[$i]->debts > $accts[$i]->credits)
		{
			$balance = number_format($accts[$i]->get_balance(), 2);

			if($display_type == 'balances' && $balance == 0)
				continue; // skip
		
			$json_list .= "<tr id=\"printAcct_" . $accts[$i]->id . "\" $stripe onclick=\"view_customer_bills(" . $accts[$i]->id .", '', event)\"><td><input type=\"checkbox\"$checked onclick=\"set_customer_printing_status(" . $accts[$i]->id . ", $(this))\" /> " . $accts[$i]->name . "</td><td style=\"text-align: right\"><div style=\"width: 50%; display: inline\">$ </div><div style=\"width: 25%; display: inline;float: right; padding-right: 50px\"> " . $balance . "</div></td></tr>\n";

			$csv_output .= "\"" . $accts[$i]->name . "\",,\"" . $balance . "\"\r\n";
			
			($stripe == "class=\"even\"") ? $stripe = "class=\"odd\"" : $stripe = "class=\"even\"";
		


		}
		else if($accts[$i]->credits > 0)
		{
			$balance = $accts[$i]->get_balance();

			
			// for some reason negative numbers are getting in here due to some kind of 
			// floating point precision issues
			if($balance < 0)
				$balance = number_format(abs($balance), 2);
			else
				$balance = number_format($balance, 2);

			$balance == 0 ? $cr_descriptor = '' : $cr_descriptor = 'CR';

			//number_format($accts[$i]->credits, 2)

			if($display_type == 'balances' && $balance == 0)
				continue; // skip
		
			 $json_list .= "<tr id=\"printAcct_" . $accts[$i]->id . "\"  $stripe onclick=\"view_customer_bills(" . $accts[$i]->id .", '', event)\"><td><input type=\"checkbox\"$checked onclick=\"set_customer_printing_status(" . $accts[$i]->id . ", $(this))\" /> " . $accts[$i]->name . "</td><td style=\"text-align: right\"><div style=\"width: 50%; display: inline\">$cr_descriptor $ </div><div style=\"width: 25%; display: inline; float: right; padding-right: 50px\"> " . $balance . "</div></td></tr>\n";
			 
			$cr_descriptor == 'CR' ? $csv_cr_descriptor = "-" : $csv_cr_descriptor = "";
			$csv_output .= "\"" . $accts[$i]->name . "\",,\"$csv_cr_descriptor" . $balance . "\"\r\n";

			($stripe == "class=\"even\"") ? $stripe = "class=\"odd\"" : $stripe = "class=\"even\"";
			 


		} else if($display_type == 'all') // ZERO balance holders
		{
		 $json_list .= "<tr id=\"printAcct_" . $accts[$i]->id . "\"  $stripe onclick=\"view_customer_bills(" . $accts[$i]->id .", '', event)\"><td><input type=\"checkbox\"$checked onclick=\"set_customer_printing_status(" . $accts[$i]->id . ", $(this))\" /> " . $accts[$i]->name . "</td><td style=\"text-align: right\"><div style=\"width: 50%; display: inline\">$ </div><div style=\"width: 25%; display: inline;float: right; padding-right: 50px\"> 0.00</div></td></tr>\n";
	
				($stripe == "class=\"even\"") ? $stripe = "class=\"odd\"" : $stripe = "class=\"even\"";

			$csv_output .= "\"" . $accts[$i]->name . "\",," . "\"0.00" . "\"\r\n";
		}
	

		//$json_list[] = $line;

	}


//	print_r($accts);

	$rv = array("accts"=>$json_list);

	if($csv_mode == 0)
	{
		header("Content-type: application/json");

		echo json_encode($rv);
	} else
	{
		header("Content-type: text/csv");
		echo $csv_output;
	}	


}

function set_customer_amt($customer_id, $type, $amt)
{
	global $accts, $no_accts;
	

	for($i = 0; $i < $no_accts; $i++)
	{

		if($accts[$i]->id == $customer_id)
		{
			//echo "set $customer_id - $type -- $amt<BR>";
			

			if($type == 'debts')
				$accts[$i]->debts += $amt;
			else if($type == 'credits')
				$accts[$i]->credits += $amt;
		
			break;
		}
	
	}

}


function view_customer_bills($customer_id, $sort_type, $sort_direction)
{
	global $db, $_GET;
	
	$start_date = strtotime($_GET['start_date'] . ' 00:00:00');

	$end_date = strtotime($_GET['end_date'])+86399;
	$transaction_type = $_GET['transaction_type'];

	switch ($sort_type)
	{
		case 'customer_sortimg':
			$sort = 'company';
			break;
		case 'id_sortimg':
			$sort = 'display_id';
			break;
		case 'job_sortimg':
			$sort = 'job_name';
			break;
		case 'date_sortimg':
			$sort = 'date';
			break;
		case 'amount_sortimg':
			$sort = 'total';
			break;
		case 'type_sortimg':
			$sort = 'payment_type';
			break;

		
	
	}

	if($customer_id < 1)
	{
		$condition = '';
		$condition2 = '';
	} else
	{
		$condition = "WHERE id=$customer_id";
		$condition2 = "ticket.customer_id=$customer_id AND";
	}

	$result = $db->query("SELECT customers.id, customers.company, customers.last_name, customers.first_name, mi FROM customers $condition");

	$row = $result->fetch_object();

	if($row->first_name == '')
		$name = $row->company;
	else
	{
		if($row->mi != '')
			$row->mi .= '.';
	
		$name = $row->last_name . ', ' . $row->first_name . ' ' .  $row->mi;
		
	}

	if($customer_id == 0)
		$name = "All Customers";

// look up payments, charges, and returns
//	$result = $db->query("SELECT ticket.id AS ticket_id, ticket.customer_id, date, total, payment_type, refund from ticket LEFT JOIN customers ON customers.id=ticket.customer_id WHERE payment_type is not null AND customer_id=$customer_id ORDER BY date");

	// transaction_type = all | payments | returns | paid_transactions

	if($transaction_type == 'all')
	{
		$result = $db->query("SELECT customers.company, customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=1 AND payment_type IS NOT NULL AND payment_type != 'VOID' AND payment_type NOT LIKE 'payment_%' AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date UNION (SELECT customers.company, customers.use_company, ticket.id, ticket.display_id, ticket.customer_id, ticket.date, ticket.total, 'payment', 0, customer_jobs.name AS job_name from ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=1 AND $condition2 payment_type LIKE 'payment_%' AND payment_type != 'VOID' AND UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date) UNION SELECT CONCAT(last_name, ', ', first_name, ' ' , mi), customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=0 AND payment_type IS NOT NULL AND payment_type != 'VOID' AND payment_type NOT LIKE 'payment_%' AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi), customers.use_company, ticket.id, ticket.display_id, ticket.customer_id, ticket.date, ticket.total, 'payment', 0, customer_jobs.name AS job_name from ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=0 AND $condition2 payment_type LIKE 'payment_%' AND payment_type != 'VOID' AND UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date) ORDER BY $sort DESC");


//echo "SELECT customers.company, customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=1 AND payment_type IS NOT NULL AND payment_type NOT LIKE 'payment_%' AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date UNION (SELECT customers.company, customers.use_company, ticket.id, ticket.display_id, ticket.customer_id, ticket.date, ticket.total, 'payment', 0, customer_jobs.name AS job_name from ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=1 AND $condition2 payment_type LIKE 'payment_%' AND UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date) UNION SELECT CONCAT(last_name, ', ', first_name, ' ' , mi), customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=0 AND payment_type IS NOT NULL AND payment_type NOT LIKE 'payment_%' AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi), customers.use_company, ticket.id, ticket.display_id, ticket.customer_id, ticket.date, ticket.total, 'payment', 0, customer_jobs.name AS job_name from ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=0 AND $condition2 payment_type LIKE 'payment_%' AND UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date) ORDER BY $sort DESC";exit;

	} else if($transaction_type == 'payments')
	{
		$result = $db->query("SELECT customers.company, customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, 'payment' AS payment_type, 0, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=1 AND payment_type LIKE 'payment_%' AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi), customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, 'payment' AS payment_type, 0, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=0 AND payment_type LIKE 'payment_%' AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date) ORDER BY $sort DESC");
		
	} else if($transaction_type == 'returns')
	{
		$result = $db->query("SELECT customers.company, customers.use_company,ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=1 AND refund=1 AND payment_type IS NOT NULL AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi), customers.use_company,ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=0 AND refund=1 AND payment_type IS NOT NULL AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date ) ORDER BY $sort DESC");
		
	} else if($transaction_type == 'paid_transactions')
	{
		$result = $db->query("SELECT customers.company, customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=1 AND refund=0 AND payment_type NOT LIKE 'payment_%' AND payment_type != 'acct' AND total > 0 AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi), customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=0 AND refund=0 AND payment_type NOT LIKE 'payment_%' AND payment_type != 'acct' AND total > 0 AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date) ORDER BY $sort DESC");	
	
	} else if($transaction_type == 'charges')
	{
		$result = $db->query("SELECT customers.company, customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=1 AND refund=0 AND payment_type = 'acct' AND total > 0 AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi), customers.use_company, ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=0 AND refund=0 AND payment_type = 'acct' AND total > 0 AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date) ORDER BY $sort DESC");
	
	} else if($transaction_type == 'voids')
	{
		$result = $db->query("SELECT customers.company, customers.use_company,  ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=1 AND payment_type = 'void' AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi), customers.use_company,  ticket.id AS ticket_id, ticket.display_id, ticket.customer_id, date, total, payment_type, refund, customer_jobs.name AS job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE use_company=0 AND payment_type = 'void' AND $condition2 UNIX_TIMESTAMP(date) >= $start_date AND UNIX_TIMESTAMP(date) <= $end_date) ORDER BY $sort DESC");

	}


	if(!$result || $result->num_rows == 0)
	{
		$rv = array("name"=>$name, "tickets"=>"<tr><td colspan=\"6\" style=\"width: 650px; text-align: center; margin-left: auto; margin-right: auto; font-weight: bold\">No tickets found</td></tr>");

		echo json_encode($rv);
		exit;
	}


	for($i = 0, $line = ''; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();

		if($row->refund == 1 || $row->payment_type == 'payment' || $row->payment_type == 'discount')
		{
			if($row->payment_type == 'payment' || $row->payment_type == 'discount')
				$row->total = '&ndash; $ ' . number_format($row->total, 2);
			else
				$row->total = 'R $ ' . number_format($row->total, 2);
			
		}
		else
			$row->total = '$ ' . number_format($row->total, 2);


		// mark paid IDs with a P in front
		if($row->payment_type == 'payment')
		{
			$ticket_display_id = '# ' . $row->display_id;
			$row->payment_type = 'PAYMENT';
		} else
			$ticket_display_id = '# ' . $row->display_id;
		
		$row->date = strtotime($row->date);
		
		// change 'acct' to 'charge' for display purposes
		if($row->payment_type == 'acct')
			$row->payment_type = 'CHARGE';
		else
			$row->payment_type = strtoupper($row->payment_type);
		
		// CHANGE SVC_CHARGE for display purposes
		if($row->payment_type == 'svc_charge')
			$row->payment_type = 'SVC CHG';
		
		
//		if($row->use_company)
			$customer_name = $row->company;
	//	else
		//	$customer_name = "$row->last_name, $row->first_name $row->mi";
		
		($i%2 == 0) ? $stripe="class=\"even\"" : $stripe="class=\"odd\"";
		
		//($row->payment_type != 'PAYMENT' && $row->payment_type != 'ACCT_CASH' && $row->payment_type != 'ACCT_CHECK') ?
		$onclick = "onclick=\"load_ticket_transactions($row->ticket_id, $(this))\""; // : $onclick = '';
		
		if($row->payment_type == 'ACCT_CASH')
			$row->payment_type = 'CASH REFUND';
		else if($row->payment_type == 'ACCT_CHECK')
			$row->payment_type = 'CHECK REFUND';
		
			
		$line .= "<tr id=\"printTicket_$row->ticket_id\" $onclick $stripe><td style=\"width: 120px\">$ticket_display_id</td><td style=\"width: 200px\">$customer_name</td><td style=\"width: 120px\">$row->job_name</td><td style=\"width: 140px\">" . date("n/d/y g:i a", $row->date) . "</td><td style=\"width: 100px; text-align: right\">$row->total</td><td style=\"padding-left: 70px; width: 124px\">$row->payment_type</td></tr>";
		
		
	}

	if($row->use_company || ($row->last_name == '' && $row->company != ''))
		$name = $row->company;
	else
		$name = "$row->last_name, $row->first_name $row->mi";

	$rv = array("name"=>$name, "tickets"=>$line);
	header("Content-Type: application/json");


	echo json_encode($rv);



}

//
// show the ticket items in the billing page
//
function view_ticket($ticket_id, $ticket_search, $limit_customer_id)
{
	global $db;

	if($limit_customer_id > 0)
	{
		$customer_limit = "AND ticket.customer_id=$limit_customer_id";
		$customer_limit_notification = ' under given customer';
	} else
		$customer_limit_notification = ''; // give extra notice that ticket could be under a different customer

	if($ticket_search == '1')
	{
		$ticket_id = trim($ticket_id, '#');
		
		$result = $db->query("SELECT ticket.*, ticket.id AS ticket_id, ticket.display_id AS display_id, customers.*, customer_jobs.* FROM ticket LEFT JOIN customers ON ticket.customer_id=customers.id LEFT JOIN customer_jobs ON ticket.job_id=customer_jobs.id WHERE ticket.display_id=$ticket_id $customer_limit"); 


		if(!$result || $result->num_rows == 0)
		{
			$rv = array("ticket_items"=>'', "ticket_headings"=>"<tr><td colspan=\"6\" style=\"width: 650px; text-align: center; margin-left: auto; margin-right: auto; font-weight: bold\">Ticket not found $customer_limit_notification</td></tr>");

			echo json_encode($rv);
			exit;
		}
	
		$row = $result->fetch_object();
		
		$ticket_display_id = '# ' . $row->display_id;
		
		if($row->use_company)
			$customer_name = $row->company;
		else
			$customer_name = "$row->last_name, $row->first_name $row->mi";
		
		$row->date = strtotime($row->date);
	
		if($row->refund)
			$refund_indicator = 'R';
		else
			$refund_indicator = '';
		
		$ticket_headings .= "<tr id=\"printTicket_$row->ticket_id\" class=\"even\" style=\"font-weight: bold\"><td style=\"width: 120px\">$ticket_display_id</td><td style=\"width: 200px\">$customer_name</td><td style=\"width: 120px\">$row->job_name</td><td style=\"width: 140px\">" . date("n/d/y g:i a", $row->date) . "</td><td style=\"width: 100px; text-align: right\">$refund_indicator $ $row->total</td><td style=\"padding-left: 70px; width: 124px\">$row->payment_type</td></tr>";
	
		$ticket_id = $row->ticket_id; // switch to the db ID

	}

	$add_border = '';

	$result = $db->query("SELECT ticket.freight, ticket.labor, ticket.discount, ticket.tax, ticket.subtotal, ticket.recv_by,  transaction_items.*  FROM ticket LEFT JOIN transaction_items ON transaction_items.ticket_id=ticket.id WHERE transaction_items.ticket_id=$ticket_id");
	//echo "SELECT ticket.freight, ticket.labor, ticket.discount, ticket.tax, ticket.subtotal, ticket.recv_by,  transaction_items.*, item_descriptions.description FROM ticket LEFT JOIN transaction_items ON transaction_items.ticket_id=ticket.id LEFT JOIN item_descriptions ON item_descriptions.item_id=transaction_items.id WHERE transaction_items.ticket_id=$ticket_id";
	
	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();
		
		if($i == $result->num_rows - 1)
			$add_border = "style=\"border-bottom: 1px solid #cccccc\""; // add bottom border;
	
		
		$line .= "<tr $add_border><td style=\"width: 50px; text-align: right\">$row->item_id</td><td style=\"width: 60px; text-align: right\">$row->qty</td><td style=\"padding-left: 50px; width: 240px\">$row->name</td><td style=\"width: 100px; text-align: right\">$ $row->price</td><td style=\"padding-left: 40px; width: 94px; text-align: right; padding-right: 60px;\">$ $row->amount</td></tr>";
		
		if($row->description != '')
		    $line .= "<tr><td></td><td></td><td style=\"padding-left: 50px; width: 240px\">&ndash; $row->description</td><td></td><td></td></tr>";
		
	}
	
	$freight_value = $row->freight;
	$discount_value = $row->discount;
	$labor_value = $row->labor;
	$recv_by = $row->recv_by;

	// subtotal
	$line .= "<tr><td style=\"width: 50px; text-align: right\"></td><td style=\"width: 60px; text-align: right\"></td><td style=\"padding-left: 50px; width: 240px\">SUBTOTAL</td><td style=\"width: 100px; text-align: right\"></td><td style=\"padding-left: 40px; width: 94px; text-align: right; padding-right: 60px;\">$ $row->subtotal</td></tr>";

	// discount
	if($discount_value > 0)
		$line .= "<tr><td style=\"width: 50px; text-align: right\"></td><td style=\"width: 60px; text-align: right\"><img src=\"img/sale.png\" style=\"width: 14px; height: 14px\" /></td><td style=\"padding-left: 50px; width: 240px\">DISCOUNT</td><td style=\"width: 100px; text-align: right\"></td><td style=\"padding-left: 40px; width: 94px; text-align: right; padding-right: 60px;\">$ $discount_value</td></tr>";
		
	// tax
	$line .= "<tr><td style=\"width: 50px; text-align: right\"></td><td style=\"width: 60px; text-align: right\"></td><td style=\"padding-left: 50px; width: 240px\">TAX</td><td style=\"width: 100px; text-align: right\"></td><td style=\"padding-left: 40px; width: 94px; text-align: right; padding-right: 60px;\">$ $row->tax</td></tr>";

	if($freight_value > 0)
		$line .= "<tr><td style=\"width: 50px; text-align: right\"></td><td style=\"width: 60px; text-align: right\"><img src=\"img/lorrygreen.png\" style=\"width: 14px; height: 14px\" /></td><td style=\"padding-left: 50px; width: 240px\">FREIGHT</td><td style=\"width: 100px; text-align: right\"></td><td style=\"padding-left: 40px; width: 94px; text-align: right; padding-right: 60px;\">$ $freight_value</td></tr>";

	if($labor_value > 0)
		$line .= "<tr><td style=\"width: 50px; text-align: right\"></td><td style=\"width: 60px; text-align: right\"><img src=\"img/gears.png\" style=\"width: 10px; height: 15px\" /></td><td style=\"padding-left: 50px; width: 240px\">LABOR</td><td style=\"width: 100px; text-align: right\"></td><td style=\"padding-left: 40px; width: 94px; text-align: right; padding-right: 60px;\">$ $labor_value</td></tr>";

	if($recv_by != '')
		$line .= "<tr><td style=\"width: 50px; text-align: right\"></td><td style=\"width: 60px; text-align: right\"></td><td style=\"padding-left: 50px; width: 240px\">RECEIVED BY</td><td style=\"width: 100px; text-align: right\"></td><td style=\"padding-left: 40px; width: 94px; text-align: right; padding-right: 60px;\">$recv_by</td></tr>";

	$rv = array("name"=>$name, "ticket_items"=>$line, "ticket_headings"=>$ticket_headings);

	header("Content-Type: application/json");

	echo json_encode($rv);

}

// $charge_type = 'Service Charge' | 'Discount'
function save_service_charge($charge_type, $customer_id, $amount, $job_id)
{
	global $db, $pos;
	
	$db->query("LOCK TABLES ticket WRITE");
	$result = $db->query("SELECT MAX(display_id) FROM ticket");
	
	$row = $result->fetch_row();
	
	$ticket_id = $row[0] + 1;
	
	$charge_type == 'Discount' ? $payment_type = 'discount' : $payment_type = 'svc_charge';

	if($job_id == '')
	    $job_id = 'NULL';

	$result = $db->query("INSERT INTO ticket(display_id, payment_type, customer_id, total, date, job_id) VALUES($ticket_id, '$payment_type', $customer_id, $amount, NOW(), $job_id)");
	
	$db->query("UNLOCK TABLES");

	if($result)
		$status = 1;
	else
		$status = 0;

	$new_balance = $pos->get_customer_balance($customer_id);
	

	$rv = array("status"=>$status, "new_balance"=>$new_balance);

	header("Content-Type: application/json");

	echo json_encode($rv);
}

function issue_refund($customer_id, $amount, $type)
{
    global $db, $pos;

    $status = 0;

    if(($type == 'cash' || $type == 'check') && $amount > 0)
    {

	$ticket_id = $pos->get_ticket_id($customer_id);

	$type = 'acct_' . $type;

	// this is ambiguous, refund=0, but payment_type='acct_cash', so be 
	// aware of the two different types of refunds. This does not credit their account, 
	// so is really a debit
	$query = "UPDATE ticket SET payment_type='$type', date=NOW(), refund=0, subtotal='$amount', total='$amount' WHERE id=$ticket_id";

	$result = $db->query($query);
	if($result)
		$status = 1;

	$new_balance = $pos->get_customer_balance($customer_id);

    }

    $rv = array("status"=>$status, "new_balance"=>$new_balance);

    header("Content-Type: application/json");

    echo json_encode($rv);

}

?>
