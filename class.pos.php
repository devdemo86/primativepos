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

Class pos {

	public $config;
	
	public function __construct()
	{

		$this->config = new options();

	}
	
	public function showMenu()
	{
	
	
	}
	
	public function getLibs($base_dir='')
	{
	
		if($base_dir == '')
			$base_dir = '.';
			
		$dymoFramework = $this->config->dymoFramework;
		
		// place css and javascript files in script_libraries.html
		//$included_scripts = file_get_contents("script_libraries.html");
		require("script_libraries.php");
		
		//$included_scripts = str_replace("\$base_dir", $base_dir, $included_scripts);

		//echo $included_scripts;

	
	}

	// not used
	public function print_doc($html, $title = 'report')
	{
		require_once("print_pdf.php");

		$filename = $title . time();
	
		$html_file = $filename . ".html";
		$pdf_file = $filename . ".pdf";
	

		$fp = fopen($this->config->tmp_dir . $html_file, "w");
		fwrite($fp, $html);
		fclose($fp);

		print_pdf($html, $this->config->tmp_dir . $filename . '.pdf');


	//	unlink("tmp/" . $html_file);
	//	unlink("tmp/" . $pdf_file);


	}

	// print the balance
	public function get_customer_balance($customer_id)
	{
	    global $db;
	
	$timestamp = time();

	$ca = new customerAcct($customer_id);

	// sum up all debts
	$result = $db->query("SELECT sum(ticket.total) AS charge from ticket where (ticket.payment_type='acct' OR ticket.payment_type='svc_charge' OR ticket.payment_type='acct_cash' OR ticket.payment_type='acct_check') AND UNIX_TIMESTAMP(ticket.date) <= $timestamp AND refund=0 AND ticket.customer_id=$customer_id GROUP BY customer_id");

//echo "SELECT sum(ticket.total) AS charge from ticket where ticket.payment_type='acct' AND UNIX_TIMESTAMP(ticket.date) <= $timestamp AND refund=0 AND ticket.customer_id=$customer_id GROUP BY customer_id";exit;

	$row = $result->fetch_object();
	$ca->debts = $row->charge;

	// sum up all payments -- NOT influenced by date selector
	$result = $db->query("SELECT sum(total) AS payment from ticket WHERE (payment_type LIKE 'payment_%' OR payment_type='discount') AND ticket.customer_id=$customer_id GROUP BY customer_id");	

	$row = $result->fetch_object();
	$ca->credits = $row->payment;

	// sum up all returns -- NOT influenced by date selector
	$result = $db->query("SELECT ticket.total, sum(ticket.total) AS charge from ticket WHERE (ticket.payment_type='acct' AND refund=1 AND ticket.customer_id=$customer_id) GROUP BY customer_id");	

	$row = $result->fetch_object();
	$ca->credits += $row->charge;
	
	$amount_due = number_format($ca->get_balance(), 2);
	
	return $amount_due;
	}


    //
    // create a new ticket
    //
    public function get_ticket_id($customer_id = '')
    {
	global $db;

	$default_customer_id = $this->config->default_customer_id;

//	if(!($default_customer_id > 1))
	//	die("Problem");
	
	$db->query("LOCK TABLES ticket WRITE");

	// create a default customer to assign all transactions to
	if($customer_id == '')
	    $customer_id = $default_customer_id;

	$result = $db->query("INSERT INTO ticket(customer_id) VALUES($customer_id)");
	
	$new_id = $db->insert_id;

	$db->query("UNLOCK TABLES");
	
	$result = $db->query("SELECT max(display_id)+1 AS display_id FROM ticket");
		
	if($result->num_rows > 0)
	{

		$row = $result->fetch_object();
		$display_id = $row->display_id;

		if(!$db->query("UPDATE ticket SET display_id=$display_id WHERE id=$new_id"))
			die("PROBLEM"); //return FALSE;
	}
	
	return $new_id;	
	
    }
}

Class options {

	public $sales_tax;
	public $tax_state;
	public $display_tax;
	public $catalog_limit;
	public $default_customer_id;
	
	public $invoice_header;
	public $statement_header;
	
	public $use_catalog_filter;
	
	public $print_invoice_path;
	public $root_dir;
	
	public $tmp_dir;
	public $pdf_printer;
	public $useLabelPrinter;
	public $dymoFramework;
	
	public $skuPadding;
	
	public $autoDecimal;

	public $receipt_logo_path;

	public function __construct()
	{
	
	}

}

?>
