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

$customer_id = $db->real_escape_string($_POST['customer_id']);
$date = $db->real_escape_string($_POST['date']);
$pay_type = $db->real_escape_string($_POST['pay_type']);
$amount = $db->real_escape_string($_POST['amount']);
$extra_info = $db->real_escape_string($_POST['extra_info']);

$job_id = $db->real_escape_string($_POST['job_id']);

$status = 0;

$cc_trans_no = 'NULL';
$check_no = 'NULL';

if($pay_type == 'cc')
{
	$cc_trans_no = "'$extra_info'";

} else if($pay_type == 'check')
{
	$check_no = "'$extra_info'";

}

$pay_type = "payment_$pay_type";

$date = strtotime($date);

// if input time is same as today, make the hours and seconds reflect the current time

//if($date == mktime(0, 0, 0, date("n"), date("d"), date("Y")))
   $date = date("y-m-d", $date) . ' ' . date("H:i:s", $date);
//else // use start of business time (e.g. 7:00)
  //  $date = date("Y-m-d", $date) . ' ' . "07:00:00";


$result = $db->query("LOCK TABLES ticket WRITE");

$result = $db->query("SELECT max(display_id) as cur_id FROM ticket");
$row = $result->fetch_object();
$display_id = $row->cur_id + 1;

$query = "INSERT INTO ticket(display_id, customer_id, total, payment_type, check_no, cc_trans_no, date, job_id) VALUES($display_id, $customer_id, '$amount', '$pay_type',$check_no, $cc_trans_no, '$date', $job_id)";

$result = $db->query($query);

$db->query("UNLOCK TABLES");

if($result)
	$status = 1;


$rv = array("status"=>$status, "ticket_id"=>$db->insert_id);

echo json_encode($rv);

?>
