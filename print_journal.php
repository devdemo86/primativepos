<?php

require("init.php");


//
// print out the closing report
//

$output = '';
$date = date("n/d/y h:i a");

$output = "Closing Journal     $date\n\n";

if($_GET['printLabel'] == '1')
{
//	header("Content-type: application/octet-stream");
//	header("Content-Disposition: attachment; filename=report.label");

//	require("css/report_header.php");

}
// look up the tickets for today

$counted_checks = $_GET['checks'];
$counted_cash = $_GET['cash'];

$over_shortcash_prefix = '';

if(!(is_numeric($counted_checks) && is_numeric($counted_cash)))
{
//	exit;
}

$timestamp = mktime(0, 0, 0, date("n"), date("d"), date("Y")); // start of today

$end_of_day = mktime(0, 0, 0, date("n"), date("d")+1, date("Y"));

$query = "SELECT CONCAT(customers.first_name, ' ', customers.last_name) AS customer, customers.tax_exempt, customers.credit, customers.id AS customer_id, customers.company, customers.use_company, ticket.id as ticket_id, ticket.date, ticket.total, ticket.payment_type, ticket.refund FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE ticket.payment_type != 'VOID' AND ticket.payment_type IS NOT NULL AND UNIX_TIMESTAMP(ticket.date) > $timestamp";
//echo $query;
//exit;

$result= $db->query($query);


$item_lines = '';

//$item_length = 909 + 159 * ($result->num_rows - 1); // have to specify size of this to printer

$total_checks = 0;
$total_cash_payments = 0;
$total_cash = 0;
$total_sales = 0;

//if($_GET['printLabel'] == '1')
//	echo "	<String>\r\n";

for($i = 0; $i < $result->num_rows; $i++)
{
	$row = $result->fetch_object();


	$customer_id = $row->customer_id;
	$ticket_date = date("g:i a", strtotime($row->date));


	
	//$output .= $row->ticket_id;
	$line = $row->ticket_id;	

	// multiply the total by the R.D. and add to the total cash
	$row->refund ? $refund_determinator = -1 : $refund_determinator = 1;
	
	$pparts = explode("_", $row->payment_type); // check for payment
	
	// if a payment type
	if(count($pparts) > 1 && $pparts[1] != 'REFUND')
	{
			$payment_type = 'PYMT ' . strtoupper($pparts[1]);
	
		// refunds on account: check or cash (e.g. acct_cash)
		if($pparts[0] == 'acct')
		{
			$payment_type = 'RFND ' . strtoupper($pparts[1]);

			// this is a refund on the acct, not sure this should negate cash or not, but 
			// think so, since money must go back to them, and their acct is negated that sum
			if($pparts[1] == 'cash') // checks don't affect the totals
				$total_cash -= $row->total;

		} else if($pparts[1] == 'cash')
			$total_cash += $row->total;
		else if($pparts[1] == 'check')
			$total_checks += $row->total;
		
			
	}
	else
	{
	
		switch($row->payment_type)
		{
			case 'cash':
				$payment_type = 'CASH';
				$total_cash += $row->total * $refund_determinator;
				break;
			case 'acct':
				$payment_type = 'CHRG';
				break;
			case 'cc':
				$payment_type = 'CC';
				break;
			case 'check':
				$payment_type = 'CHCK';
				$total_checks += $row->total; // no R.D. here because checks are only received
				break;
			case 'svc_charge':
				$payment_type = 'SVC CHG';
				break;
			case 'discount':
				$payment_type = 'DISCOUNT';
				break;
			case 'VOID':
				$payment_type = 'VOID'; // the sql query is exluding this type
				break;
			case 'payment_cash':
				$payment_type = 'CASH REFUND';
				break;
		}
		
		//if($row->payment_type == 'cash')
			//$total_cash += $row->total;
		//else if($row->payment_type == 'check')
		//	$total_checks += $row->total;
		
		if(!$row->refund)
			$total_sales += $row->total;
	}
	
	if($row->refund)
		$payment_type = $payment_type . ' RFND';
	else if($payment_type == 'DISCOUNT') // this is too long
	{ 
		//$payment_type .= '    ';
	}
	//else if(!isset($pparts[1]))
	//	$payment_type = $payment_type . '          '; // pad spaces
	$line .= ' ' . $payment_type;
	
	//$output .= "  $payment_type   $row->total\n";
//	$output .= " $payment_type";

	$llen = strlen($line);
	$price_len = strlen($row->total);
	$total_len = $llen + $price_len;

	for($j = $total_len; $j < 24; $j++)
		$line .= ' ';

	$output .= $line . "$row->total\n";

/*
	for($j = 1; $j <= $row->qty; $j++)
	{
	// max name = 24 i think
	// max length = 40
	
		while(strlen($row->item_id) < 6)
			$row->item_id = ' ' . $row->item_id;
	
		if(strlen($row->name) > 14)
			$row->name = substr($row->name, 0, 14);
	
		$len_price = strlen($row->price);
		$len_name = strlen($row->item_id . '  ' . $row->name);
		
		$num_spaces = 30 - $len_price - $len_name;
		
		if($num_spaces <= 0)
			$num_spaces = 1;
			
		$row->name = str_replace("&", "&amp;", $row->name);
	
		$output .= "$row->item_id  $row->name";

		for($k = 0; $k < $num_spaces; $k++)
			$output .= " ";
			
		$output .= "$row->price\r\n";
		
		
	}*/
	

}

// COMPARE to opening balance

$result = $db->query("SELECT * FROM log WHERE UNIX_TIMESTAMP(date) > $timestamp AND UNIX_TIMESTAMP(date) < $end_of_day");


if($result->num_rows > 0)
{
	// today's sales + opening balance
	$row = $result->fetch_object();
	
	$calc_balance = (float) ($row->drawer_balance + $total_cash);
//echo "calc balance = $row->drawer_balance + $total_cash";exit;
	//$over_short = $counted_cash - $total_cash + $row->drawer_balance;
	(float) $over_short = (float) $counted_cash - (float) $calc_balance;
	
	if($over_short > 0)
		$over_shortcash_prefix = '+';
	
	$opening_drawer = $row->drawer_balance;
	
} else
	$over_short = 'ERR';

//echo "tc: $total_cash .. $row->drawer_balance and $calc_balance";exit;


$over_short = number_format($over_short, 2);
//echo "$counted_checks - $total_checks";exit;

$over_short_checks = number_format($counted_checks - $total_checks, 2);	

if($over_short_checks > 0)
	$over_shortchecks_prefix = '+';



$total_sales = number_format($total_sales, 2);
$total_cash = number_format($total_cash, 2);

// no printer just output to the screen
//if($_GET['printLabel'] == '0')
//{


$noPrinterDoc = <<<END


Cash       $total_cash
Checks     $total_checks

Opening        $opening_drawer
Total Sales    $total_sales
O/S Cash       $over_shortcash_prefix$over_short
O/S Checks     $over_shortchecks_prefix$over_short_checks

END;

if($_GET['printLabel'] == '1')
{
    $output = $output . $noPrinterDoc;

    $fp = fopen($pos->config->tmp_dir . "/journal.txt", "w");
    fwrite($fp, $output);
    echo $output;
    fclose($fp);

    system("lpr -P Dymo-LabelWriter-450 -o ContinuousPaper=1 " . $pos->config->tmp_dir . "/journal.txt");
}
else
	echo $output . $noPrinterDoc;



?>
