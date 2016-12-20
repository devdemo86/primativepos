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
require("print_pdf.php");
require_once("tcpdf/tcpdf.php");


$action = $_POST['action'];
$print_tickets = $_POST['print_tickets'];

$customer_id = $db->real_escape_string($_POST['customer_id']);
$end_date = $db->real_escape_string($_POST['end_date']);

if(!is_array($customer_id)) // a request for a single customer
{
	$start_date = $db->real_escape_string($_POST['start_date']);

}


$start_timestamp = strtotime($start_date);
$end_timestamp = strtotime($end_date);

$date = strtotime($_POST['date']);


// derive the start date if not given in the batch printing 
if($start_date == '' && $end_date != '')
{
    $start_date = mktime(0, 0, 0, date("n", $end_timestamp), date("d", $end_timestamp)-30, date("Y", $end_timestamp));

    $start_timestamp = $start_date;

    $start_date = date("n/d/Y", $start_timestamp);

    $date = $end_timestamp; //strtotime($start_date);
} else if($date == '')
    $date = $end_timestamp;

//echo "s: " . date("n/d/y", $start_date) . ", e: $end_date";

$ticket_search_start_tmp = $date; // for right click print tickets and statements
$ticket_search_end = mktime(0, 0, 0, date("n", $ticket_search_start_tmp), date("j", $ticket_search_start_tmp)+1, date("Y", $ticket_search_start_tmp));

// changed on 5/22

if(!isset($_POST['start_date'])) // when printing from the entire list, it uses the end date, otherwise use the start and end dates
	$ticket_search_start = mktime(0,0,0, date("n", $ticket_search_end)-1, date("j", $ticket_search_end)-1, date("Y", $ticket_search_end));
else
{
	$ticket_search_start = strtotime($start_date);
	//echo "HERE";exit;
}

// old method
//$ticket_search_start = mktime(0,0,0, date("n", $ticket_search_end)-1, date("j", $ticket_search_end)-1, date("Y", $ticket_search_end));

//echo date(date("n/d/y",$ticket_search_start));

// convert the end timestamp to the next day so it can include the whole day of the last day specified
$end_timestamp = mktime(0, 0, 0, date("n", $end_timestamp), date("j", $end_timestamp)+1, date("Y", $end_timestamp));


$start_doc = <<<EOF
	<html>
	<head>
	<title></title>
	<style type="text/css">
	.invoice_border th, .invoice_border td { border: 1px solid #000000 }
	</style>
	</head>

	<body style="text-align: center; font-family: sans-serif">

EOF;


// setup loop if is array
if(is_array($_POST['customer_id']))
{
	$id_list = $_POST['customer_id'];

	$multiple_statements = 1;
} else
{
	$id_list = array();
	$id_list[] = $_POST['customer_id']; // array of 1 for consistency
}


// create new PDF document, since we will be adding multiple pages to it
// in single statement mode, we use print_pdf()
// class MYPDF at end of this file
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('test');
$pdf->SetTitle('Invoice/Statement');
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

$pdf->SetHeaderMargin(1);
$pdf->SetFooterMargin(1);	

// set auto page breaks
//$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// set font


//$print_tickets = 1;

for($i = 0; $i < count($id_list); $i++)
{
		$pdf->SetFont('dejavusans', '', 10);
		$pdf->AddPage();
		$cur_page_num = 1;

		$customer_id = $id_list[$i];

		// $length is returned, the default statement is 22 lines
		//		list($statement_text, $statement_length) = 

		// writes statement to $pdf		
		list($pdf, $cur_page_num) = get_statement($customer_id, $action, $end_timestamp, $ticket_search_start, $pdf, $cur_page_num);


//		$db->query("UPDATE customers SET print_statement=0 WHERE id=$customer_id");

		//$doc = $start_doc . $statement_text;

		// print the tickets if requested
		if($print_tickets)
		{

			//$pdf = output_statement($doc, $customer_id, $action, $ticket_search_start, $ticket_search_end, $pdf);

			$pdf = print_tickets($customer_id, $pdf, $cur_page_num);

			
		}
		
}

//Close and output PDF document
$filename = $pos->config->tmp_dir . 'group_' . time() . '.pdf';
$pdf->Output($filename, 'F');

// in case zero-balance customers were set and not showing, make sure they are cleared out
$db->query("UPDATE customers SET print_statement=0");


//	$customer_id_is_array = 1;
/*
} else // single customer
{
	$multiple_statements = 0;

	list($statement_text, $length) = get_statement($customer_id, $action, $end_timestamp, $ticket_search_start); 

	$doc = $start_doc . $statement_text;

	if(substr($action,0,5) == 'print')
		output_statement($doc, $customer_id, $action, $ticket_search_start, $ticket_search_end);


}
*/
	

// show statement and print tickets if desired
// $statement_length = number of lines in a statement if needed, to calculate page breaks

/*
function output_statement($doc, $customer_id, $action, $ticket_search_start, $ticket_search_end, $statement_length = 0)
{
	global $db, $print_tickets, $pos, $multiple_statements, $pdf, $cur_page_num;

	$statement_header = $pos->config->statement_header;

	$ts = time();

	$action == 'print_all_statements' ? $print_all_statements = 1 : $print_all_statements = 0;

	$filename = $customer_id . $ts;
	
	$html_file = $filename . ".html";
	$pdf_file = $filename . ".pdf";

	// print small if printing all tickets in a batch job
	$print_all_statements ? $font_size = 'small' : $font_size = 'normal';

	// make sure they have tickets or the printing would be skipped
	$result = $db->query("SELECT ticket.*, customer_jobs.name AS job_name FROM ticket LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE ticket.customer_id=$customer_id AND (payment_type='acct' OR payment_type LIKE 'payment_%') AND UNIX_TIMESTAMP(ticket.date) > $ticket_search_start AND UNIX_TIMESTAMP(ticket.date) <= $ticket_search_end ORDER BY ticket.date");

	
	if($print_tickets == 0 || $result->num_rows == 0)
	{
		// if printing the tickets we skip the html output
		// and append $doc1 to the tickets created in print_tickets()

		$fp = fopen($pos->config->tmp_dir . '/' . $html_file, "w");
		fwrite($fp, $doc);
		fclose($fp);
		
		if(!$multiple_statements)
		{
			print_pdf($doc . $doc, $pos->config->tmp_dir . $filename . '.pdf');
			//echo $doc1;
			$print_directive = $pos->config->print_statement_path . " " . $filename . " " . $pos->config->tmp_dir;
			system($print_directive);
		} else
			return $doc; // no printing yet, just return
		
	
			//echo $print_directive;		
	} else
	{
		// write the statement, then change font size
	
		// print the tickets, change font size

		// changed from .=
		//$cur_page_num++;
		$doc = print_tickets($customer_id, $font_size, $doc, $statement_length);
	}		
	
	// $doc is only returned if there are multiple statements

	// uncheck the print flag
	if($print_all_statements)
		$db->query("UPDATE customers SET print_statement=0 WHERE id=$customer_id");
	return $doc;

}
*/


// get customer info
function get_statement($customer_id, $action, $end_timestamp, $ticket_search_start, $pdf, $cur_page_num)
{
	global $doc, $db, $pos, $print_tickets, $start_doc;

	$statement_header = $pos->config->statement_header;

	$num_lines = 22; // .18cm/line

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

	$start_doc

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
	<TD style="width: 60%; text-align: left">$customer_name<br />
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

	// first get balance from previous 30 days
	// use the given timestamp and use that to look 30 days back

	$result = $db->query("SELECT *, unix_timestamp(date) AS ts FROM ticket WHERE customer_id=$customer_id AND UNIX_TIMESTAMP(ticket.date) < $ticket_search_start AND payment_type != 'VOID' ORDER BY DATE ASC");

	//echo "SELECT *, unix_timestamp(date) AS ts FROM ticket WHERE customer_id=$customer_id AND UNIX_TIMESTAMP(ticket.date) < $start_timestamp AND payment_type != 'VOID' ORDER BY DATE ASC";


	// || $row->payment_type='acct_cash' || $row->payment_type='acct_check'

	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();

		if($row->payment_type == 'check' && $row->refund == 1) // returned checks are not a credit
			continue;
		
		if(($row->payment_type == 'acct' && $row->refund == 0) || $row->payment_type == 'svc_charge' || $row->payment_type == 'acct_cash' || $row->payment_type == 'acct_check')
			$ca->debts += $row->total;
		else if(substr($row->payment_type, 0, 8) == 'payment_' || ($row->refund == 1 && $row->payment_type != 'cash' && $row->payment_type != 'cc') || $row->payment_type == 'discount')
			$ca->credits += $row->total;
		
	}

	// this is behaving strangely when balance forward date is later than the first ticket of the time period
	 // occurs when searching past date ranges
	//echo "HERE? " . date("n/d/y", $ticket_search_start);

	//if($ticket_search_start > 0 && $ticket_search_start <= $start_timestamp)
		$bforward_ts = $ticket_search_start;
	//else
	//	$bforward_ts = $start_timestamp;

	//echo "test: " . date("n/d/y", $bforward_ts);

	$doc1 .= "\n<TR><TD style=\"width: 20%\"><CENTER>" . date("m/d/Y", $bforward_ts) . "</CENTER></TD><TD style=\"width: 35%\"></td><td style=\"width: 20%; text-align: center\"> &nbsp; &nbsp;Balance Forward</TD><TD style=\"text-align: right; width: 25%\">" . number_format($ca->get_balance(), 2) . "</TD></TR>\r\n";



	// now look at period specified

	//$date_limits = "AND UNIX_TIMESTAMP(ticket.date) < $end_timestamp AND UNIX_TIMESTAMP(ticket.date) >= $start_timestamp";
	// changed this on 3/19
	$date_limits = "AND UNIX_TIMESTAMP(ticket.date) < $end_timestamp AND UNIX_TIMESTAMP(ticket.date) >= $bforward_ts";

	//OR payment_type='check'
	$q2 = "SELECT ticket.*, UNIX_TIMESTAMP(ticket.date) AS ts, customer_jobs.name AS job_name FROM ticket LEFT JOIN customer_jobs ON ticket.job_id=customer_jobs.id WHERE ticket.customer_id=$customer_id $date_limits AND payment_type != 'VOID' AND (payment_type = 'ACCT' OR payment_type LIKE 'PAYMENT_%' OR payment_type LIKE 'svc_charge' OR payment_type LIKE 'discount'  OR payment_type='acct_cash' OR payment_type='acct_check') ORDER BY ticket.date ASC";
	//echo $q2;exit;
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

		$num_lines++; 

		if($num_lines >= 53 && $i < $result->num_rows - 1) // handle multiple pages, is 55 right?
		{
			$pdf->WriteHTML($doc1 . "</table></body></html>");
			$pdf->Cell(0, 10, 'Page ' . $cur_page_num, 0, false, 'C', 0, '', 0, false, 'T', 'M');
			//$pdf->SetFont('dejavusans', '', 8);
			$pdf->endPage();
			$pdf->startPage();

			$cur_page_num++;
			$num_lines = 0;
			$doc1 = $start_doc;
			$doc1 .= "<TABLE BORDER=0 CELLSPACING=\"3px\" align=\"left\" style=\"width: 100%\">";
		}

	}

	$doc1 .= "</table>";

	while($num_lines < 56)
	{
		$doc1 .= "<br>";
		$num_lines++;

	}

	$pdf->WriteHTML($doc1 . "</body></html>");
	$pdf->Cell(0, 10, 'Page ' . $cur_page_num, 0, false, 'C', 0, '', 0, false, 'T', 'M');

	$pdf->endPage();


	$cur_page_num++;
	

//	$doc1 .= $item_lines;
/*
	$doc1 .= <<<END
	</TABLE>
	<!--stop unless using footer table-->
	<PRE>







	</PRE>
END;
*/
	/*
	<!--
	<TABLE BORDER=0 WIDTH="90%" CELLPADDING="5px">
	<TR><TD>CURRENT</TD><TD>1-30 DAYS PAST DUE</TD><TD>31-60 DAYS PAST DUE</TD><TD>61-90 DAYS PAST DUE</TD><TD>OVER 90 DAYS PAST DUE</TD><TD>Amount Due</TD></TR>
	<TR><TD COLSPAN="6"></TD></TR>
	<TR><TD>$current</TD><TD>$pdue_1</TD><TD>$pdue_31</TD><TD>$pdue_61</TD><TD>$pdue_90</TD></TR>
	</TABLE>
	-->
	*/



	if(!$print_tickets && $action != 'view') 
		$tmp .= "</BODY></HTML>";


	$doc1 .= $tmp;

	return array($pdf, $cur_page_num);
	//return array($doc1, $num_lines);

}


// customer_id, font size='small|normal', doc1 = statement html
// $statement_length given to find page breaks
function print_tickets($customer_id, $pdf, $cur_page_num)
{

	global $thirty_days_ago, $db, $timestamp, $pos, $ticket_search_start, $ticket_search_end, $multiple_statements, $start_doc;
	
	$page_no = 1; // keeping track of pages
	$num_lines = 0;

	$doc = $start_doc; // the <html><body> and some styles

// max number of lines per ticket (per half page):  19
// therefore if starting at top of page we can do 40 lines or so

//we search all the tickets in the last month

// single ticket: if over 40 lines split up

	//$result = $db->query("SELECT ticket.*, customer_jobs.name AS job_name FROM ticket LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE ticket.customer_id=$customer_id AND payment_type='acct' AND UNIX_TIMESTAMP(ticket.date) > $thirty_days_ago AND UNIX_TIMESTAMP(ticket.date) <= $timestamp ORDER BY ticket.date");
	$result = $db->query("SELECT ticket.*, customer_jobs.name AS job_name FROM ticket LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE ticket.customer_id=$customer_id AND (payment_type='acct' OR payment_type LIKE 'payment_%') AND UNIX_TIMESTAMP(ticket.date) > $ticket_search_start AND UNIX_TIMESTAMP(ticket.date) <= $ticket_search_end ORDER BY ticket.date");

//echo "SELECT ticket.*, customer_jobs.name AS job_name FROM ticket LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE ticket.customer_id=$customer_id AND (payment_type='acct' OR payment_type LIKE 'payment_%') AND UNIX_TIMESTAMP(ticket.date) > $ticket_search_start AND UNIX_TIMESTAMP(ticket.date) <= $ticket_search_end ORDER BY ticket.date";exit;

	if($result->num_rows == 0)
		return $pdf;

	//$pdf->WriteHTML(
	//$pdf->SetFont('dejavusans', '', 10);
	$num_lines = 0; // keep track of how much page space we have
	//$cur_page = 2; // could be more if the statement is long

    $pdf->SetFont('dejavusans', '', 6);
	$pdf->startPage();

	for($i = 0; $i < $result->num_rows; $i++)
	{
		$row = $result->fetch_object();
		
		// don't show the heading (business address) if multiple invoices
		$i == 0 ? $show_heading = 1 : $show_heading = 0;
		
		$prev_doc_html = $doc; // keep a copy of the previous, because if the next invoice is too long, we have to break
		$prev_num_lines = $num_lines;

		// $tmpnum is the number of items on the invoice
		list($tmpdoc, $tmpnum) = generate_invoice($row->id, $show_heading, 'statement', 0);
	//	echo $num_lines . "\n";

		$doc .= $tmpdoc;
		$num_lines += $tmpnum + 14; // 14 is the basic minimum
		

		if($num_lines >= 82) // break before printing out the last invoice
		{
			// this will mess up the page numbers if a single ticket is over 85 lines

			// add line breaks to reach the end of the page

			while($prev_num_lines < 82) 
			{
				$prev_doc_html .= "<br>&nbsp;"; // tcpdf was ignoring <br>, but nbsp; fixed it
				$prev_num_lines++;

			}
			//echo $prev_doc_html;exit;

			$pdf->WriteHTML($prev_doc_html . "</body></html>");
			$pdf->Cell(0, 10, 'Page ' . $cur_page_num, 0, false, 'C', 0, '', 0, false, 'T', 'M');
			$pdf->endPage();
			$pdf->startPage();
			
			$doc = $start_doc . $tmpdoc; // set the current output to the last invoice

			$num_lines = $tmpnum + 14; 

			$cur_page_num++;
			
			echo "next line ($num_lines):\n" . $doc;

//echo "new:" . $doc;exit;
		} else
			echo "cur output ($i):\n\n $doc";


//	echo "gen: $row->id";
	}

	// add lines to reach the end of the page
	while($num_lines < 82)
	{
		$doc .= "<br>";
		$num_lines++;		
	}

	$doc .= "</body></html>";
//echo $doc;exit;
	$pdf->writeHTML($doc);	
	$pdf->Cell(0, 10, 'Page ' . $cur_page_num, 0, false, 'C', 0, '', 0, false, 'T', 'M');

	$pdf->endPage();

	return $pdf;

	//echo $pdf_file;
	//unlink("tmp/" . $html_file);
	//unlink("tmp/" . $pdf_file);


}

function log_print($path)
{

	$fp = fopen("c:/wamp/www/tmp/print_log.txt", "a");
	fwrite($fp, date("n/d/y H:i") . "\t$path\r\n");
	fclose($fp);

}



?>
