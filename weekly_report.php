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

$start = strtotime($db->real_escape_string($_GET['start']));
$end = strtotime($db->real_escape_string($_GET['end']));

$start_date = $start;
$end_date = mktime(0, 0, 0, date("n", $end), date("d", $end)+1, date("Y", $end));


$result = $db->query("SELECT ticket.*,customers.*, customer_jobs.name as job_name FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id LEFT JOIN customer_jobs ON customer_jobs.id=ticket.job_id WHERE UNIX_TIMESTAMP(ticket.date) >= $start_date AND UNIX_TIMESTAMP(ticket.date) <= $end_date AND (payment_type='acct' OR payment_type LIKE 'payment_%' OR payment_type='svc_charge' OR payment_type='discount')");

if($result->num_rows < 1)
    die('0');

// format the title date range with the given $end value, not the $end_date for the query that goes to the end of the day

$doc = "<!DOCTYPE html>\n<html><head><title></title></head>\n<body style=\"font-size: 8pt\">\n";
$doc .= "<p><h2 style=\"text-align: center\">Transactions from " . date("n/d/Y", $start_date) . " &nbsp;&ndash;&nbsp; " . date("n/d/Y", $end) . "</h2>\n<p></p>\n";
$doc .= "<table style=\"width: 90%\">\n";
$doc .= "<tr><th>Ticket ID</th><th>Customer</th><th>Job Name</th><th>Transaction Date</th><th>Type</th><th>Amount</th></tr>\n";

//$doc .= <<<END


for($i = 0; $i < $result->num_rows; $i++)
{
    $row = $result->fetch_object();

    if($row->use_company)
	$customer = $row->company;
    else
	$customer = "$row->last_name, $row->first_name";

    $date = date("n/d/y", strtotime($row->date));

	if($row->refund == 1)
		$row->total = '-' . $row->total;

    $doc .= "<tr><td>$row->display_id</td><td>$customer</td><td>$row->job_name</td><td>$date</td><td >$row->payment_type</td><td >$ $row->total</td></tr>\n";

}

    $doc .= "</table>\n</body>\n</html>";

    $pos->print_doc($doc, 'weekly_report');

	//print_pdf($doc, $pos->config->tmp_dir . $filename . '.pdf');

?>
