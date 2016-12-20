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
	


 auth.php
 
		authenticates admin functions, such as:
			*editing the catalog
			*changing price in a transaction

 		The admin password can be set only under MySQL using the PASSWORD()
		function
		
*/	
require("init.php");
header("Content-Type: application/json");

$pw = $db->real_escape_string($_POST['passwd']);

$result = $db->query("SELECT * FROM users WHERE password=PASSWORD('$pw')");

if($result->num_rows > 0)
	$result = 1;
else
	$result = 0;
	
echo json_encode(array("auth"=>$result));

?>
