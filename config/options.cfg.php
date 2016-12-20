<?php

/////////////////////////////////////////////
// database options, do not modify unless you know you have to

	$dbhostname = 'localhost';
	$dbusername = 'pos';
	$dbpassword = 'pos';
	$dbdatabase = 'pos';

////////////////////////////////////////////

	$sales_tax = .0725;
	
	$tax_state = 'IL';

	$catalog_limit = 500; // limit the catalog search to defined number

	// this is the 'noname' account that gets created initially by the install sql file
	// this value should be fine
	$default_customer_id = 1;

	// set your company's information
	$company_name = "my company";
	$company_address = "address";
	$company_city = "city";
	$company_state = "state";
	$company_zip = "zip";
	$company_phone = "(777) 777-7777";
	
	// this treats items with barcodes < 100000 differently
	// set to 1 or 0
	$use_catalog_filter = 1;
	
	// Add 0's to the front of sku numbers to make all the same length
	// set this to the maximum length SKU numbers you use (database max is 24)
	// set to zero to not pad
	$skuPadding = 14; 
	
	// DYMO framework not used anymore
	//After installing the DYMO SDK framework for receipt printing, copy the javascript library into the /js 
	//folder and set the name of the file below
	$useLabelPrinter = 1;
	//$dymoFramework = "DYMO.Label.Framework.1.2.4.js";

	$receipt_logo_path = "/var/www/primativepos/css/houselmbr_graphic.gif";
	
	// File paths
	// Do not use spaces in path names
	
	// use double backslash on windows for the following three variables (e.g. c:\\wamp\\apache\\htdocs\\pos\\print.bat)
	$print_invoice_path = "/var/www/primativepos/print_invoice.bat";
	$print_statement_path = "/var/www/primativepos/print.bat";
	
	// path for smaller fonts
	$print_small_invoice_path = "/var/www/primativepos/printsmall.bat";

	// use in print_receipt.php to retrieve an xml header
	$root_dir = "http://127.0.0.1/primativepos/"; 
	
	// location of directory to store temporary files to be printed
	$tmp_dir = "/var/www/primativepos/tmp/";
	
	// guesses where to place the decimal on input boxes, but is somewhat buggy
	// set to 1 to turn on or 0 to turn off
	$auto_decimal = 0;
	
	// not implemented yet, either 'tcpdf' or 'htmldoc'
	$pdf_printer = "tcpdf";
	
?>
