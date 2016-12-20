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

	require("config/options.cfg.php");
	require("class.pos.php");
	require("class.customeracct.php");


	$pos = new pos();
	
	$pos->config->sales_tax = $sales_tax;
	$pos->config->tax_state = $tax_state;
	$pos->config->catalog_limit = $catalog_limit;
	$pos->config->default_customer_id = $default_customer_id;

	$pos->config->use_catalog_filter = $use_catalog_filter;
	
	$pos->config->print_invoice_path = $print_invoice_path;
	$pos->config->print_statement_path = $print_statement_path;
	$pos->config->print_small_invoice_path = $print_small_invoice_path;	
	
	$pos->config->receipt_logo_path = $receipt_logo_path;

	// used in generate_invoice.php...contains the business name/address/phone
	// use only regular non-css HTML because that's all HTMLDOC supports
	$pos->config->invoice_header = "$company_name<BR>$company_address<BR><BR>$company_city, $company_state &nbsp;$company_zip<BR></B><P><B>Phone: $company_phone</B></P>";
	
	// used in print_statements.php
	$pos->config->statement_header = "$company_name<BR>$company_address<BR>$company_city, $company_state &nbsp;$company_zip<BR>Phone: $company_phone";
	
	// used in print_receipt.php to point to css/receipt_header.xml.php
	$pos->config->xml_header = " $company_name\n $company_address\n $company_city, $company_state  $company_zip\n $company_phone";
	
	// points to receipt_header.xml in print_receipt.php
	$pos->config->root_dir = $root_dir;
	
	$pos->config->tmp_dir = $tmp_dir;
	
	$pos->config->pdf_printer = $pdf_printer;
	
	$pos->config->dymoFramework = $dymoFramework;
	$pos->config->useLabelPrinter = $useLabelPrinter;
	
	$pos->config->skuPadding = $skuPadding;
	
	$pos->config->autoDecimal = $auto_decimal;
	
	/************************************************************/

	$db = new mysqli($dbhostname, $dbusername, $dbpassword, $dbdatabase);

	if($db->connect_errno)
		die("Could not connect to the database.  Check that config/options.cfg.php contains the username and password for the database");


	// construct month ago and week ago timestamps
	$matime = mktime(0, 0, 0, date("n")-1, date("d"), date("Y"));
	$watime = mktime(0, 0, 0, date("n"), date("d")-7, date("Y"));
	

	$month_ago = date("m/d/Y", $matime);
	$week_ago = date("m/d/Y", $watime);
	
	$pos->config->display_tax = $pos->config->sales_tax * 100;
	
	// prevent caching of pages
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	header("Pragma: no-cache");

?>
