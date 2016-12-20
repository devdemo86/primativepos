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


$accts = array();

$type = $_POST['type'];
$amount = $db->real_escape_string($_POST['amount']);

header("Content-type: application/json");


// save the opening balance in the drawer
if($type == 'open')
{

	$result = $db->query("INSERT INTO log(action, drawer_balance) VALUES('open', $amount)");

	$result ? $status = 1 : $status = 0;

	echo json_encode(array("status"=>$status));
}

?>