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

// Set the user name and password for the FTP upload site
// Set the upload speed for progress bar estimation
// Set the temporary directory were the backups will be generated

//$ftp_username = '';
//$ftp_passwd = '';
$ftp_username = '';
$ftp_passwd = '';

$ftp_host = "";

$tmp_dir = "/wamp/tmp"; // no trailing slash

$upload_speed = .66; // upload speed in mbps


// maximum number of seconds to allow the backup script to run
// this may need to be much higher depending on your database size and connection
$backup_timeout = 400; 


?>