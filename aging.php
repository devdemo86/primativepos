<?php

require("init.php");

$print_icon = "<img title=\"Print Aging Report\" onclick=\"print_aging_report()\" style=\"vertical-align: middle; cursor: pointer\" src=\"img/document-print.png\" id=\"printAllStatementsCtrl\">";

if(isset( $_GET['print']))
{
    if($_GET['print'] == 1)
    {
	$print = 1;
	$print_icon = '';
    }
    else
	$print = 0;

} else
    $print = 0;

$only_show_balances = 0;
$checked = '';
if(isset($_GET['only_show_balances']))
{
	if($_GET['only_show_balances'] == 1)
	{
		$only_show_balances = 1;
		$checked = "checked=\"checked\"";
	}
}


$end_date = strtotime($_GET['end_date']);

$todays_date = date("n/d/y", $end_date);

$doc = <<<END

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Point of Sale Aging Report</title>
</head>

<body class="posbody" style="font-family: sans-serif">
<style type="text/css">

.reports_table td, .reports_table th { border: 1px solid #000000; padding: 3px }

</style>

<div style="font-size: 14pt; font-weight: bold; margin-left: auto; margin-right: auto; text-align: center">
Aging Summary as of $todays_date 
$print_icon &nbsp; <label><input type="checkbox" id="only_show_balances" onclick="show_reports_dialog()" $checked> Balances only</label>
</p>
</div>
END;


$query = "(SELECT company, id as customer_id, last_name, first_name FROM customers WHERE use_company=1 AND active=1) UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi) , id as customer_id, last_name, first_name FROM customers WHERE use_company=0 AND active=1) ORDER BY company ASC";

//$query = "(SELECT company, id as customer_id, last_name, first_name FROM customers WHERE use_company=1) UNION (SELECT CONCAT(last_name, ', ', first_name, ' ' , mi) , id as customer_id, last_name, first_name FROM customers WHERE use_company=0) ORDER BY company ASC";


$result= $db->query($query);



$days_ago_30 = mktime(0, 0, 0, date("m", $end_date), date("d", $end_date) - 30, date("Y", $end_date));
$days_ago_60 = mktime(0, 0, 0, date("m", $end_date), date("d", $end_date) - 60, date("Y", $end_date));
$days_ago_90 = mktime(0, 0, 0, date("m", $end_date), date("d", $end_date) - 90, date("Y", $end_date));
$days_ago_120 = mktime(0, 0, 0, date("m", $end_date), date("d", $end_date) - 120, date("Y", $end_date));
$days_ago_150 = mktime(0, 0, 0, date("m", $end_date), date("d", $end_date) - 150, date("Y", $end_date));

$doc .= <<<END

<table border=1 style="border: 1px solid #000000; border-collapse: collapse" class="reports_table">
  <tr>
    <th align="center">Customer</th>
    <th>Current</th>
    <th>1-30 days</th>
    <th>30-60 days</th>
    <th>60-90 days</th>
    <th>&gt; 90 days</th>
    <th>Total</th>
  </tr>
END;

if($result->num_rows == 0)
{
    $doc .= "No customers found</tr></table>";

}

$date_limit = "UNIX_TIMESTAMP(ticket.date)";

// put each date limit into an array for each of the 5 iterations
$query_params = array("AND $date_limit > $days_ago_30", // current
			"AND $date_limit >= $days_ago_60 AND $date_limit < $days_ago_30", // 1-30
			"AND $date_limit >= $days_ago_90 AND $date_limit < $days_ago_60",
			 "AND $date_limit >= $days_ago_120 AND $date_limit < $days_ago_90",
			 "AND $date_limit >= $days_ago_150 AND $date_limit < $days_ago_120",
			 ""	 
		   );

$column_totals = array(0,0,0,0,0,0); // column totals

// loop through all customers
for($i = 0; $i < $result->num_rows; $i++)
{

    $crow = $result->fetch_object();

    $customer_id = $crow->customer_id;

    $customer_row = "<tr>\n\t<td>$crow->company</td>";
//	$customer_row = '';
	$customer_has_balance = 1; // exclude if their first column (current) is $0

    // loop through 6 billing period columns
    for($j = 0; $j < 6; $j++)
    {
		$params = $query_params[$j];

		$q = "SELECT *, unix_timestamp(date) AS ts FROM ticket WHERE payment_type != 'VOID' AND payment_type != 'cash' and payment_type != 'check' AND payment_type != 'cc' AND customer_id=$customer_id $params";
		//echo $q;exit;
        
		$tmp_result = $db->query($q);

		$ca = new customerAcct($customer_id);


		// loop through tickets of chosen period
        for($k = 0; $k < $tmp_result->num_rows; $k++)
        {
	    	$row = $tmp_result->fetch_object();

	    	if(substr($row->payment_type, 0, 8) == 'payment_' || $row->refund == 1 || $row->payment_type == 'discount')
				$ca->credits += $row->total;
	    	else if(($row->payment_type == 'acct' && $row->refund == 0) || $row->payment_type == 'svc_charge' || $row->payment_type == 'acct_cash' || $row->payment_type == 'acct_check')
				$ca->debts += $row->total;
//else echo "$row->id::: wtf: $row->payment_type, $row->refund<br>";
//$doc .= $row->total . ",";
		}
//$doc .=  "<br>";


		$total = $ca->get_balance();

		if($total == 0 && $j == 5 && $only_show_balances)
		{
			$customer_has_balance = 0;
			// don't break since it will affect the totals			
			//break;
		}
			$column_totals[$j] += $total;

		$total =  number_format($total, 2);

		$customer_row .= "<td align=\"right\">$ $total</td>";

    }

	if(($customer_has_balance && $only_show_balances) || $only_show_balances == 0)
	    $doc .= $customer_row . "\n<tr>\n";


}


$doc .= "<tr>\n\t<th>Totals</th><th>$ " . number_format($column_totals[0], 2) . "</th><th>$ " . number_format($column_totals[1], 2) . "</th><th>$  " . number_format($column_totals[2], 2) . "</th><th>$ " . number_format($column_totals[3], 2) . "</th><th>$ " . number_format($column_totals[4], 2) . "<th>$ " . number_format($column_totals[5], 2) . "</th>\n</tr>\n";

$doc .= "</body>\n</html>";

if(!$print)
    echo $doc;
else
    $pos->print_doc($doc);


?>
