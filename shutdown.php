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

// assume that root password is the same as the database

require("init.php");

$password = $db->real_escape_string($_GET['shutdown_password']);

/*
$result = $db->query("SELECT * FROM users WHERE password=PASSWORD('$password') AND name='admin'");
if($result->num_rows == 0)
{
	header("Content-type: application/json");
	echo json_encode(array("authorized"=>0));
	exit;
}
*/

// The /home/jeremy/.profile copies the .Xauthority to .XauthorityPOS to be readable by PHP exec()

exec("export DISPLAY=:0.0 && export XAUTHORITY=/home/jeremy/.XauthorityPOS && /usr/bin/wmctrl -c 'Mozilla Firefox'", $output);

//print_r($output);

sleep(1);

header("Content-type: application/json");
echo json_encode(array("authorized"=>1));

//exec("echo $password | sudo -S /sbin/shutdown -h now");
exec("export DISPLAY=:0.0 && export XAUTHORITY=/home/jeremy/.XauthorityPOS && xfce4-session-logout --halt");

?>
