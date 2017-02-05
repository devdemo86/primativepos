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

// print an invoice


require("init.php");
require("generate_invoice.php");
require("print_pdf.php");


$action = $_GET['action'];

$ticket_id = $db->real_escape_string($_GET['ticket_id']);

list($doc, $num_lines) = generate_invoice($ticket_id);


if($action == 'print')
{
	$filename = $ticket_id . time();
	
	$html_file = $filename . ".html";
	$pdf_file = $filename . ".pdf";


		// write the file to disk
		$fp = fopen($pos->config->tmp_dir . $html_file, "w");
		fwrite($fp, $doc);
		fclose($fp);

	if($pos->config->pdf_printer == 'htmldoc' || $pos->config->pdf_printer == '')
	{
	


		//system($pos->config->print_invoice_path . " " . $filename . " " . $pos->config->tmp_dir . " " . $pos->config->pdf_printer);

		//	unlink("tmp/" . $html_file);
	}
	else
	{
//echo 'here';
		print_pdf($doc, $pos->config->tmp_dir . $filename . '.pdf');

		//echo $pos->config->print_invoice_path . " " . $filename . " " . $pos->config->tmp_dir;

		system($pos->config->print_invoice_path . " " . $filename . " " . $pos->config->tmp_dir);
		
		
	}

	//unlink("tmp/" . $pdf_file);
	
} else
{

	echo $doc1;

}

?>
