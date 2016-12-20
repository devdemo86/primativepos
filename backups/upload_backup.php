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

// open some file for reading


header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 
header("Pragma: no-cache");


require("/var/www/config/ftp.cfg.php"); // holds username/password for FTP site

set_time_limit($backup_timeout);


if($_GET['type'] == 'full_backup')
{
	if($_GET['action'] == 'backup')
	{

		exec('./backup');

		// check that file was backed up sucessfully by looking at file access time
		$date_modified = filemtime("$tmp_dir/backup.7z");
		$file_size = filesize("$tmp_dir/backup.7z");
		
		$status = time() - $date_modified;
		
		// get file size in megabytes
		$file_size = $file_size/1048576;
		
		// mega-bits / upload speed in mega-bits
		$est_time = ($file_size*8) / $upload_speed;
		$est_time = round($est_time, 0);
		
		//header("Content-type: application/json");
		echo json_encode(array("time_diff"=>$status, "file_size"=>round($file_size, 2), "est_time"=>$est_time));
		
		exit;
	}

	$upload_filename = "full_backup" . date("Ymd-Hi") . '.7z';
	$local_file = "$tmp_dir/backup.7z";
}
else
{
	exec('/var/www/backups/quick_backup');
	$upload_filename = "micro_backup.7z";// . date("Ymd-Hi") . '.7z';
	$local_file = "$tmp_dir/micro_backup.7z";
	
}



// set up basic connection
$conn_id = ftp_connect($ftp_host);

if($conn_id === FALSE)
	die("Could not connect to the host $ftp_host");



// login with username and password
$login_result = ftp_login($conn_id, $ftp_username, $ftp_passwd);

if($login_result === FALSE)
	die("Could not login to host");

ftp_pasv($conn_id, true); // passive required by drivehq.com

// upload file
if (ftp_put($conn_id, "/$upload_filename", $local_file, FTP_BINARY)) {
    echo "Successfully uploaded";
} else {
    echo "There was a problem while uploading\n";
}

// close the connection and the file handler
ftp_close($conn_id);


?>
