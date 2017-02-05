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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Point of Sale</title>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php

	$pos->getLibs(); 
 
?>

</head>

<body class="posbody">

<!-- Hidden Transaction variables -->

<div id="transactionVars">
<input type="hidden" id="ticket_id" value="" />
<input type="hidden" id="customer_id" value="" />
<input type="hidden" id="customer_job_id" value="" />
<input type="hidden" id="tax_exempt" value="0" />
<input type="hidden" id="allow_credit" value="0" />

<input type="hidden" id="tax_rate" value="<?php echo $pos->config->sales_tax; ?>" />
</div>

<!-- Begin POS window -->

<div id="main_container" style="height: 100%; padding: 0; margin: 0; background: #dddddd">

<div id="toolbar">
&nbsp;&nbsp; &nbsp; <img src="img/customer.png" title="Customer Info" onclick="customerdialog()" style="cursor: pointer; width: 42px; height: 42px" alt="Customer Info" /> &nbsp; <img src="img/jobs.png" title="Customer Jobs" onclick="customer_jobs_dialog()" style="cursor: pointer; width: 42px; height: 42px" alt="Customer Jobs" /> &nbsp;&nbsp; <img src="img/billing.png" title="Billing" onclick="show_billing_dialog()" style="width: 42px; height: 42px; cursor: pointer" alt="Billing" /> &nbsp;&nbsp; <img src="img/bookOrangeClear.png" style="height: 42px; cursor: pointer" title="Product Catalog" onclick="show_catalog()" alt="Product Catalog" />  &nbsp; <img src="img/payment.png" style="height: 42px; width: 42px; cursor: pointer" id="recv_payment_button" title="Receive Payment" onclick="recv_payment_screen()" alt="Receive Payments" /> &nbsp; <img src="img/exit.png" style="height: 42px; width: 42px; cursor: pointer" id="shutdown_button" title="Reconcile and Shutdown" onclick="show_shutdown_dialog()" alt="Shutdown" />

</div>

<span id="clock_container" style="color: #666666; position: absolute; top: 10px; right: 10px; width: 280px; font-size: 12pt"><?php echo date("D M j, Y"). " &nbsp; &nbsp;" . date("g:i a"); ?></span>

<!-- Ticket ID and name/job -->
<div style="margin-top: 20px">
<span style="padding-left: 30px; font-size: 180%"># </span><span style="font-size: 200%" id="ticket_display_id"></span>
<span style="padding-left: 150px; font-size: 240%; font-weight: bold; color: brown" id="customer_display_name"></span> &nbsp; &nbsp; <span style="font-size: 110%; color: #996666" id="customer_job_display_name"></span><span style="font-size: 200%; color: blue" id="refund_indicator"></span>
</div>

<!-- Totals -->

<div style="float: right; height: 300px; text-align: right; margin-top: 10px; font-size: 120%; margin-right: 5px">

  Sub Total: &nbsp; $ <span id="subtotal"></span>

    <p style="padding-top: 8px"><img id="discount_icon" src="img/sale.png" title="Discount" style="width: 40px; height: 35px; display: none; margin-top: 25px" alt="Discount" /> <span style="color: green" id="discount_display_total"></span></p>

		Tax: $ <span id="tax"></span>

		<p>
		  <span><img id="freight_icon" src="img/lorrygreen.png" title="freight charges" style="width: 40px; height: 40px; display: none" alt="Freight" /></span> &nbsp; &nbsp; <span id="freight_display_total"></span>
		</p>
		<p>
		  <span><img id="labor_icon" src="img/gears.png" title="labor charges" style="display: none; vertical-align: bottom" alt="Labor" /></span> &nbsp; &nbsp; <span id="labor_display_total"></span>
		</p>
		<p>
		  <h2>Total: </h2>$<span id="display_total"></span>
		</p>



</div>

<!-- Received by -->
<div style="text-align: center; margin-left: auto; margin-right: auto" id="recv_by_container">
<span id="recv_by_label" style="display: none">Received by:</span> 
<input id="recv_by_input" maxlength="24" size="24" style="display: none" />
 <span id="recv_by_name"></span> &nbsp; <button type="button" id="add_recv_by_button" onclick="add_recv_by()">Add received by...</button>
  &nbsp; <img src="img/loading.gif" style="display: none" id="loading_recv_by" />
</div>

<!--    Cart Headings       -->

<table style="width: 680px; border-collapse: collapse">
<tr>
	<th style="width: 50px"></th><th style="width: 50px">Qty</th><th style="width: 420px">Item</th><th style="width: 100px">Price</th><th style="width: 100px">Amount</th>
</tr>
</table>
 
<div id="cart_container" style="font-size: 80%; overflow-x: none; overflow-y: scroll; margin-left: 10px; height: 300px; background: #ffffff; border: 1px solid #000000; width: 680px">
  
 <table id="cart" style="border-collapse: collapse; width: 100%;"></table>
  
</div>
  
<table>
<tr>
<td>
<select id="open_transactions" onchange="chg_ticket(this.value)" style="padding: 5px">
<option value="">&ndash; Open Transactions &ndash;</option>
<option value="-1" disabled="disabled" style="border-top: 1px dashed #999999"></option>
<?php

$result = $db->query("SELECT ticket.*, CONCAT(customers.last_name, ', ', customers.first_name, ' ', customers.mi) AS customer, customers.first_name, company, use_company FROM ticket LEFT JOIN customers ON customers.id=ticket.customer_id WHERE payment_type IS NULL");

for($i = 0; $i < $result->num_rows; $i++)
{

	$row = $result->fetch_object();

	if($row->use_company)
		$customer = $row->company;
	else
		$customer = $row->customer;
	
	echo "<option value=\"$row->id\" style=\"padding: 5px\">#$row->display_id - $customer</option>";
	
}

?>

</select>
</td>
<td style="width: 200px; text-align: right"><button type="button" onclick="clear_pos()" id="pause_button">Pause Transaction</button></td>
<td style="width: 200px"><button type="button" id="clear_button" onclick="clear_ticket()">Void Transaction</button></td>
<td style="width: 150px; text-align: right"><button type="button" id="special_options_button" style="width: 65px; height: 30px" onclick="show_payment_specialoptions()">Special</button> <p><button type="button" id="pay_button" style="width: 80px; height: 40px" onclick="show_payment_methods()">PAY</button></p>

<!-- PAYMENT BOX -->

<div id="payment_methods" style="width: 250px; height: 175px; display:none;position: absolute; left: 44%; top: 55%; border: 1px solid #000000; padding-bottom: 4px; -moz-border-radius: 5px; background: #cccccc">

<div style="position: absolute: top: 0; right: 0; text-align: right; margin: 0; padding: 0; padding: 0">
    <a href="javascript:cancel_payment(0)"><img src="img/close.png" id="cancel_payment" style="height: 24px; width: 24px; cursor: pointer" alt="Close" /></a>
</div>
<div style="margin-top: 0px; margin-bottom: 10px; margin-right: 5px">

<label for="pay_refund">
Refund: &nbsp;<input type="checkbox" id="pay_refund" value="1" onclick="save_refund_status(this.checked)" /> &nbsp; &nbsp; &nbsp;
</label>

<!--
 <button type="button" id="cancel_payment" style="color: red;" onclick="cancel_payment(0)">Cancel</button> &nbsp;
-->
<br />
<button type="button" id="pay_cash" style="width: 85px; padding: 5px" onclick="show_payment('cash')">Cash</button>&nbsp;&nbsp;&nbsp;
<button type="button" id="pay_check" style="width: 86px; padding: 5px" onclick="show_payment('check')">Check</button>
<div style="margin-top: 4px; margin-bottom: 4px"></div>
 &nbsp; <button type="button" id="pay_cc" style="padding: 5px" onclick="show_payment('cc')">Credit Card</button>&nbsp;&nbsp;
<button type="button" id="pay_charge" style="padding: 5px" onclick="show_payment('acct')">On Account</button>
<div style="padding: 10px"></div>
<div style="margin-top: 5px" id="accts"><input type="text" value="Customer Name" style="color: #999999; padding: 4px" class="class_customer_search" id="customer_ticket_search" size="20" /><select id="pay_job_id" style="display: none; padding: 4px" onchange="choose_pay_job_id()"></select></div>

</div>
</div>

<!-- PAYMENT SPECIAL OPTIONS -->
<div id="payment_specialoptions_dialog" style="width: 250px; height: 210px; display:none;position: absolute; left: 44%; top: 55%;  border: 1px solid #000000; padding: 8px; -moz-border-radius: 5px; background: #cccccc">
<!-- not sure about being able to close this
<div style="position: absolute; top: 0; right: 0; text-align: right;">
  <img src="img/close.png" onclick="closeSpecialOptions()" style="height: 32px; width: 32px; cursor: pointer" alt="Close" />
</div>
-->

<div style="margin-top: 4px; margin-bottom: 10px">
<div style="padding-bottom: 4px; text-align: center;"><b>Discount</b></div>

<!-- no need for radio button given percentage is disabled
<label for="lbl_disc_num"><input id="lbl_disc_num" type="radio" name="discount_type_selector" value="number" onclick="$pos.disc_pct.prop('disabled', true); $pos.disc_num').removeProp('disabled')" /> &nbsp; 
-->

Price: &nbsp;<input style="padding: 3px" size="7" maxlength="11" type="text" id="discount_number" value="" onkeyup="add_decimals(this, event, false)" /></label> <br />

<!-- percent is disabled
<label for="lbl_disc_pct"><input onclick="$pos.disc_num.prop('disabled', true); $pos.disc_pct.removeProp('disabled')"  id="lbl_disc_pct" type="radio" name="discount_type_selector" value="percentage" /> &nbsp; &nbsp; &nbsp; &nbsp; %:</label> <input style="padding: 3px; text-align: right" size="7" maxlength="2" type="text" id="discount_percentage" value="" onkeyup="calculate_discount_number()" />
-->
</div>
<div style="margin-top: 15px">
<div style="padding-bottom: 4px; text-align: center;"><b>Other</b></div>
&nbsp; &nbsp;<label for="is_resale"> Resale: <input type="checkbox" id="is_resale" /></label><br />

Freight: <input size="7" type="text" id="freight_number" maxlength="11" onkeyup="add_decimals(this, event, false)" />
<p style="margin-top: 5px">Labor: &nbsp;&nbsp; <input size="7" type="text" id="labor_number" maxlength="11" onkeyup="add_decimals(this, event, false)" /></p>
</div>
<div style="margin-top: 15px">
<button type="button" style="padding: 5px" onclick="apply_payment_specialoptions()" />Ok</button>
</div>
 
</div>

<!-- TRANSACTION -->

<div id="payment_take" style="width: 240px; display:none; position: absolute; left: 44%; top: 55%;  border: 1px solid #666666; padding: 8px; -moz-border-radius: 5px; background: #cccccc">
<span id="take_check" style="display: none">Check #: &nbsp;<input type="text" class="trans_info" id="check_no" size="10" maxlength="11" style="padding: 5px" /></span><br />
<span id="take_cc" style="display: none">Trans. #: &nbsp;<input type="text" class="trans_info" id="cc_trans_no" size="10" maxlength="11" style="padding: 5px" /></p><p></p></span><br />
<span id="take_cash" style="display: none">Amount Received: &nbsp;<input type="text" id="cash_given" size="5" maxlength="9" style="padding: 5px" /></span><br />

Print Receipt: <input type="checkbox" id="printReceiptChkbox" checked="checked" />
<p><button type="button" id="cancel_pay_button" onclick="window.setTimeout('cancel_payment(0)', 200)">Cancel</button>  &nbsp; <button type="button" id="postpayment_button" style="" onclick="post_transaction()">PAY</button></p>
</div>

</td>
</tr>
</table>

<table>
<tr>
<td style="padding-top: 20px; padding-left: 200px">
# <input type="text" style="padding: 3px" id="barcode" onkeyup="check_enter(this.value, event)" size="60" /> 
&nbsp; 
<img title="Add new item" src="img/addnew.gif" style="width: 18px; height: 18px; cursor: pointer" onclick="add_catalog_item()" alt="Add item to Catalog" />
</td>
<td style="padding-top: 20px; "></td><!--<button type="button" id="lookup_button">Lookup Item</button></td>-->
</tr>
</table>

<!-- CUSTOMER DIALOG -->

<div id="customer_dialog" style="z-index: 100; display: none; padding: 0; margin: 0" class="posdlg">
<div style="left: 0; top: 0"><h3>Customer Database</h3></div>

<div style="position: absolute; top: 0; right: 0; text-align: right;">
  <img src="img/close.png" onclick="close_customerdialog()" style="height: 32px; width: 32px; cursor: pointer" alt="Close" />
</div>

<table>
<tr>
  <td style="height: 300px; vertical-align: top">
	<span style="font-size: 80%">
	<label for="show_inactive">Show Inactive &nbsp; <input type="checkbox" id="show_inactive" onclick="customerdialog('reload')" />
	</label>
	</span>
	
	<img src="img/addnew.gif" style="cursor: pointer" onclick="add_customer_form()" title="Add Customer" alt="Add Customer" /> <br />
	<input type="text" class="customer_search" maxlength="20" size="20" value="Search Customer" style="color: #cccccc"/><br />
	<select size="12" onchange="edit_customer_info(this.value)" id="customer_listing" style="width: 180px"></select>
	
  </td>

  <td style="vertical-align: top; padding-left: 15px" id="customer_jobs_cell">
  
	<select id="customer_job_listing" onchange="load_edit_job()"></select>
	<p><input type="text" id="customer_job_edit" maxlength="64" /></p><br />
	<p><button type="button" onclick="save_job_edit()">Save</button></p>
  </td>

<td style="vertical-align: top; padding-left: 15px" id="customer_edit_cell">

Last name<div style="padding-left: 100px; display: inline"> First name</div><div style="display: inline; padding-left: 100px"> MI</div><br />
<input type="text" id="edit_last_name" maxlength="50" />, &nbsp;<input type="text" id="edit_first_name" maxlength="50" />, <input type="text" id="edit_mi" size="3" maxlength="3" /><br />
Company<br />
<input type="text" id="edit_company" size="40" maxlength="64" />
<p>Address<br />
<input type="text" id="edit_address" size="40" maxlength="100" /><br />
<input type="text" id="edit_address2" size="40" maxlength="100" /><br />
City <span style="padding-left: 135px; display: inline"> State</span> &nbsp;&nbsp; Zip<br />
<input type="text" id="edit_city" maxlength="50" />, <input type="text" id="edit_state" size="2" maxlength="30" /> &nbsp;&nbsp;<input type="text" id="edit_zip" size="5" maxlength="10" /><br />

Phone <span style="padding-left: 120px">Ext</span><br />
 <input type="text" size="20" maxlength="42" id="phone" /> &nbsp; &nbsp;<input type="text" size="4" maxlength="4" id="phone_ext" /><br />
</p>
List by: <label for="edit_listby_company"><input type="radio" id="edit_listby_company" name="use_company" /> Company</label> <label for="edit_listby_lastname"><input type="radio" id="edit_listby_lastname" name="use_company" /> Last name</label><br />
<label for="edit_allow_credit">Has Credit: <input type="checkbox" id="edit_allow_credit" value="1" /></label><br />
<label for="edit_tax_exempt">Tax Exempt: <input type="checkbox" id="edit_tax_exempt" value="1" /></label><br />
<label for="edit_active">Active: <input type="checkbox" id="edit_active" value="1" /></label>
<input type="hidden" id="editing_customer_id" />
<p style="margin-left: auto; margin-right: auto; text-align: center">
<button type="button" id="save_customer_button" onclick="save_customer_info()">Save</button>
</p>
</td>
</tr>
</table>
</div>

<div id="recv_payment_screen" style="z-index: 80; width: 400px" class="posdlg">
<div style="position: absolute; top: 0"><h3>Payment</h3></div>
<div style="text-align: right; margin-bottom: 15px">
<img src="img/close.png" onclick="close_recv_payments()" style="cursor: pointer" alt="Close" /></div>
<input type="hidden" id="payment_recv_customer_id" />
Customer Name<br />
<input type="text" value="" style="padding: 4px" size="20" maxlength="100" id="payment_recv_search_name" /><br />
<h3 style="color: brown" id="payment_recv_display_name"></h3>
<div id="payment_recv_display_balance" style="color: brown; font-size: 14pt; padding-bottom: 15px"></div>
<select style="display: none" id="payment_recv_job_id"></select>
<p>Date <br />
<input type="text" id="payment_recv_date" size="8" maxlength="8" style="padding: 5px" value="<?php echo date("m/d/y"); ?>" /> <select id="payment_recv_hour"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option></select> :

<input id="payment_recv_minute" type="text" maxlength="2" size="2" style="padding: 5px" />

<select id="payment_recv_ampm"><option value="am">AM</option><option value="pm">PM</option></select>
  
<p><select id="payment_recv_method">
<option value="">&ndash; Payment Type &ndash;</option>
<option value="cash">Cash</option>
<option value="check">Check</option>
<option value="cc">Credit Card</option>
</select>
</p>
<p>Amount: &nbsp; <input type="text" id="payment_recv_amt" size="11" maxlength="11" onkeyup="add_decimals(this, event, 'save_payment_recv')" /></p>
<p>Check or Trans # &nbsp; <input type="text" id="payment_recv_extra_info" size="11" maxlength="11" /></p>

<p><button type="button" onclick="save_payment_recv()">Save Payment</button></p>
</div>

<!-- Catalog Dialog -->
<div id="catalog_dialog" style="margin: 0; z-index: 195" class="posdlg">

<div style="position: absolute; top: 0"><span style="font-size: 130%; font-weight: bold; background: #ffffff; -moz-border-radius: 3px; border-radius: 3px">Catalog</span><span style="padding-left: 50px"></span>
</div>

<div style="text-align: right; margin: 0; padding: 0">
<img src="img/close.png" onclick="close_catalog()" style="cursor: pointer" alt="Close" />
</div>

<div style="margin-left: 150px; font-size: 130%">Search &nbsp; <input type="text" id="catalog_search_name" size="25" maxlength="30" onkeyup="search_catalog()" /> &nbsp;<img src="img/search.gif" onclick="search_catalog('go')" style="cursor: pointer; vertical-align: bottom" alt="Search Catalog" /> &nbsp; &nbsp; <img title="Add new item" src="img/addnew.gif" style="width: 18px; height: 18px; vertical-align: bottom; cursor: pointer" onclick="add_catalog_item()" alt="Add new item" />
<br />

<?php

if($pos->config->use_catalog_filter) { ?>

<label for="catalog_use_wholesaler">
<input type="checkbox" id="catalog_use_wholesaler" /> &nbsp; <span style="font-size: 60%">Search Principal Wholesaler Only   &nbsp;</span></label>

<?php } else
	echo "<input type=\"hidden\" id=\"catalog_use_wholesaler\" />";
?>

</div>

<div id="catalog_headings" style="padding-left: 20px; padding-right: 20px; padding-top: 20px; font-size: 100%; font-weight: bold;">
	<div style="width: 60px; padding-left: 50px; float: left">SKU</div>
	<div style="width: 170px; float: left; padding-left: 53px ">Name</div>
	<div style="width: 150px; float: left; padding-left: 50px ">Vendor</div>
	<div style="width: 105px; padding-left: 20px; float: left">Barcode</div>
	<div style="width: 147px; padding-left: 25px; float: left ">Product ID</div>
	<div style="width: 65px; padding-left: 15px; float: left">Price</div>
	<div style="width: 50px;  float: left; padding-left: 10px">Qty</div>
</div>
<br />
<div style="margin-top: 2px; height: 375px; overflow-x: none; overflow-y: scroll; border-top: 1px solid #000000">
	<table id="catalog_table" style="margin-top: 0px; font-size: 90%; cursor: default; border: 1px solid #000000; background-color: #ffffff; border-collapse: collapse">
	</table>
</div>

</div>

<!-- Add Catalog Item Dialog -->

<div class="posdlg" id="add_item_dialog" style="-moz-border-radius: 5px; border: 1px solid #000000; z-index: 196; display: none; width: 300px; height:240px; position: absolute; top: 15%; left: 20%; background: #cccccc; padding-left: 5px;" /><div style="text-align: right"><img src="img/close.png" onclick="$catalog.add_item_dialog.hide()" style="cursor: pointer" alt="Close" /></div>
<div style="top: 5px; position: absolute">
Item Name<br />
<input type="text" id="new_item_name" size="30" maxlength="30" /><br />
Price<br />
<input type="text" id="new_item_price" size="10" maxlength="7" onkeyup="add_decimals(this, event, false)" /><br />

<!--
<select id="new_item_category">
<option value="">&ndash; Choose Category &ndash;</option>
</select>
-->
Barcode<br />
<input type="text" id="new_item_skn" size="14" maxlength="14" />
&nbsp; &nbsp; &nbsp; <button type="button" onclick="save_new_item()">Save Item</button><br />
<div style="margin-top: 5px; font-size: 80%">
<label for="new_item_to_cart">Add to cart &nbsp; <input type="checkbox" id="new_item_to_cart" checked="checked" /></label>
</div>
</div>

</div>
</div>

<!-- Auth Dialog -->
<div id="auth_dialog" style="background-color: #cccccc">
<p style="font-size: 100%; background-color: #cccccc">Please enter the administrator password</p>
<p style="background-color: #cccccc"><input type="password" id="admin_passwd" onkeyup="check_auth_enterkey(event)"/> &nbsp; <button type="button" onclick="auth_return()" id="auth_confirm">OK</button> <button type="button" onclick="auth_cancel()">Cancel</button></p>

</div>

<!-- Billing Dialog -->
<div id="billing_dialog" style="z-index: 90" class="posdlg">
<div style="position: absolute; top: 0"><span style="font-size: 180%; font-weight: bold">Billing</span><span style="padding-left: 50px"><select id="billing_display_types" onchange="show_billing_dialog()">
<option value="all">All Accounts</option>
<option value="balances">Only Balances</option>
</select></span><span style="padding-left: 50px"><input type="text" size="10" maxlength="10" id="billing_list_end_date" title="Last billing date" value="<?php echo date("m/d/Y"); ?>" onchange="show_billing_dialog()" /></span> &nbsp; &nbsp; <button type="button" onclick="view_customer_bills(0, '', event)">View All Transactions</button> &nbsp; <img id="printAllStatementsCtrl" src="img/document-print.png" style="vertical-align: middle; cursor: pointer" onclick="printAllStatements()" title="Print Statements" /> <img src="img/loading.gif" style="display: none" id="printAllStatementsIndicator" /> &nbsp; <img id="showReportsCtrl" src="img/chart.png" style="vertical-align: middle; cursor: pointer; height: 30px" onclick="show_reports_dialog()" title="Show Aging Report" /> 
 &nbsp; &nbsp;<input type="text" class="customer_search" maxlength="20" size="20" value="Search Customer" style="color: #cccccc"/>
</div>
<div style="text-align: right"> &nbsp;
   <img id="asdf" src="img/close.png" onclick="close_billing_dialog()" style="cursor: pointer" alt="Close" />
</div>

<!-- headings -->
<div style="margin-top: 20px; width: 95%">
<span style="padding-left: 30px; width: 75%; font-weight: bold">Customer</span><span style="float: right; width: 18%; font-weight: bold">Amount</span>
</div>

<div id="billing_container" style="overflow-x: none; overflow-y: scroll; margin-left: 10px; height: 350px; background: #ffffff; border: 1px solid #000000; width: 95%">
<table id="billing_list" style="font-weight: bold; font-size: 90%; cursor: default; border-collapse: collapse; background: #ffffff;width: 100%"></table>
</div>

</div>

<!--
<style type="text/css">#billing_container td, th { border: 1px solid #000000 }</style>
-->
<!-- Customer tickets and billing list -->
<div id="customer_bill_dialog" style="z-index: 110;" class="posdlg">
<div style="text-align: right">
<img src="img/close.png" onclick="close_customer_bill_dialog()" style="cursor: pointer" alt="Close" /></div>

<span id="customer_bill_name" style="font-size: 16pt"></span> &nbsp; <img src="img/loading.gif" id="customer_activity_indicator" style="display: none" /><br />
<select id="customer_bill_job_id">
<option value="">&ndash; Choose Job &ndash;</option>
</select> &nbsp; &nbsp; &nbsp; 
<input type="text" id="bill_start_date" size="10" onchange="view_customer_bills($billing.customer_bill_customer_id.val())" value="<?php echo $month_ago; ?>" /> &nbsp; to &nbsp; <input type="text" id="bill_end_date" size="10" value="<?php echo date("m/d/Y"); //$week_ago; ?>" onchange="view_customer_bills($billing.customer_bill_customer_id.val())" /> &nbsp; 
<select id="customer_bill_transaction_type" onchange="view_customer_bills($billing.customer_bill_customer_id.val())">
<option value="all" selected="selected">All Transactions</option>
<option value="payments">Payments</option>
<option value="returns">Returns</option>
<option value="charges">Charges</option>
<option value="paid_transactions">Cash/Check/CC</option>
<option value="voids">Voids</option>
</select> &nbsp;
<img id="print_statement_button" src="img/document-print.png" style="vertical-align: middle; cursor: pointer" onclick="print_customer_statement(0)" title="Print Statement" /> &nbsp;

 <img id="viewStatementCtrl" src="img/CCBill-20120401.png" style="width: 30px; height: 30px; vertical-align: middle; cursor: pointer" onclick="view_customer_statement()" title="View Statement" /> 


<!-- <button type="button" onclick="print_customer_statement()" id="print_statement_button">Print Statement</button>-->
&nbsp; &nbsp; <input type="text" class="ticket_search" maxlength="15" size="15" value="Find Ticket #" style="color: #cccccc" onkeyup="viewTicket(this.value, event)" />
<input type="hidden" id="customer_bill_customer_id" />
<!-- headings -->
<div style="margin-top: 20px; width: 95%; font-size: 90%">

<table class="ticket_heading">
<tr id="ticket_heading_sort_row">
 <td style="font-weight: bold; padding-left: 10px; width: 100px;"><a href="javascript:view_customer_bills('-1', 'id_sortimg')"><img src="img/arrow_down.gif" id="id_sortimg" />Ticket ID</a></td>
 <td style="float: left; width: 160px; font-weight: bold"><a href="javascript:view_customer_bills('-1', 'customer_sortimg')"><img src="img/arrow_down.gif" id="customer_sortimg" />Customer Name</td>
 <td style="text-align: center; width: 50px; font-weight: bold"><a href="javascript:view_customer_bills('-1', 'job_sortimg')"><img src="img/arrow_down.gif" id="job_sortimg" />Job</td>
 <td style="padding-left: 105px; width: 140px; font-weight: bold"><a href="javascript:view_customer_bills('-1', 'date_sortimg')"><img src="img/arrow_down.gif" id="date_sortimg" />Date</td>
 <td style="float: left; width: 65px; font-weight: bold"><a href="javascript:view_customer_bills('-1', 'amount_sortimg')"><img src="img/arrow_down.gif" id="amount_sortimg" />Amount</td>
 <td style="float: left; padding-left: 60px; width: 65px; font-weight: bold"><a href="javascript:view_customer_bills('-1', 'type_sortimg')"><img src="img/arrow_down.gif" id="type_sortimg" />Type</td>
</tr>
</table>

</div>

<div id="customer_tickets_container" style="overflow-x: none; overflow-y: scroll; margin-left: 10px; height: 200px; background: #ffffff; border: 1px solid #000000; width: 95%; margin-top: 5px">
<table id="customer_tickets_list" style="font-size: 90%; cursor: default; border-collapse: collapse; background: #ffffff; width: 100%"></table>
</div>

<!-- individual ticket headings -->
<div style="margin-top: 10px; width: 95%; font-size: 85%">
<table style="border-collapse: collapse" class="ticket_heading">
<tr><td style="font-weight: bold; padding-left: 20px; width: 50px;">Item ID</td><td style="width: 100px; font-weight: bold; padding-left: 15px">Quantity</td><td style="width: 210px; font-weight: bold; padding-left: 30px">Item Description</td><td style="padding-left: 105px; width: 130px; font-weight: bold">Price</td><td style="padding-left: 10px; width: 100px; font-weight: bold">Total</td></tr>
</table>
</div>


<div id="ticket_items_container" style="overflow-x: none; overflow-y: scroll; margin-left: 10px; height: 170px; background: #ffffff; border: 1px solid #000000; width: 95%; margin-top: 5px">
<table id="ticket_items_list" style="overflow-x: none; overflow-y: scroll; font-size: 85%; cursor: default; border-collapse: collapse; background: #ffffff; width: 100%"></table>
</div>

</div>

<?php

require("dialogs.html");

?>

<div id="notify_container" style="z-index: 2000; display: none;position: absolute; top: 20%; left: 40%;">
	<div id="default_container" style="font-size: 140%; text-align: center" >
		<h1>#{title}</h1>
		<p>#{text}</p>
	</div>
</div>

    <div class="vs-context-menu" style="border-radius: 4px">

		<input type="hidden" id="context_menu_type" />
		<input type="hidden" id="context_menu_id" />
        <ul>
            <li class="balances_cmenu_action"><a href="javascript:print_customer_statement()">Print Statement</a></li>
  	    <li class="balances_cmenu_action"><a href="javascript:print_customer_statement(1)">Print Statement & Tickets</a></li>
            <!--<li class="balances_cmenu_action"><a href="javascript:customerdialog();edit_customer_info(1127);" >Edit Contact</a></li>-->
	    <li class="balances_cmenu_action"><a href="javascript:alert('not implemented');" >Edit Contact</a></li>
	    <li class="balances_cmenu_action"><a href="javascript:show_service_charge_dialog('svc_charge')" >Add Service Charge...</a></li>
	    <li class="balances_cmenu_action"><a href="javascript:show_service_charge_dialog('discount')" >Add Discount...</a></li>
	    <li class="balances_cmenu_action"><a href="javascript:issue_cash_refund('discount')" >Issue Cash Refund...</a></li>
		    
	    <li class="ticket_cmenu_action"><a href="javascript:contextmenu_print_receipt('receipt');">Print Receipt</a></li>
            <li class="ticket_cmenu_action"><a href="javascript:contextmenu_print_receipt('invoice');">Print Invoice</a></li>
            <li class="ticket_cmenu_action"><a href="javascript:contextmenu_void_receipt()">Void Transaction</a></li>
	   
	    <li class="items_cmenu_action"><a href="javascript:contextmenu_add_cart_item_description()">Add description...</a></li>
        </ul>
    </div>

<?php

$timestamp = mktime(0, 0, 0, date("n"), date("d"), date("Y"));

$result = $db->query("SELECT * FROM log WHERE unix_timestamp(date) > $timestamp AND action='open'");

if($result->num_rows == 0)
{
?>
<script type="text/javascript">

	$(document).ready(function() {
		
		// startup dialog for recording the opening balance

		
		$('#startup_dialog').dialog({ title : 'POS Startup', autoOpen: true, modal : true, resizable : false, draggable : false, width: 300, height: 250, buttons : { 'OK' : function() {
		
		var open_val = $('#open_cash').val(); 
		
		// check input is valid
		if(isNaN(open_val) || open_val == '' || open_val < 0)
		{
			alert("Please enter the opening cash value");
			return false;
		}
		
		save_opening_balance();
		
		}}, open: function(event, ui) {
				$(".ui-dialog-titlebar-close").hide();
				$(".ui-button ").css('margin-left' , 'auto').css('margin-right','auto').css('text-align','center');
				$('.ui-dialog-titlebar').css('font-size', '80%');
				$('.ui-button').css({'font-size' : '60%', 'padding' : '1px'});
				
			}
		});
		

	});
</script>
<?php

}

?>

<script type="text/javascript">

$(document).ready(function() {

	// enable label printer
	$pos.useLabelPrinter = <?php echo $pos->config->useLabelPrinter; ?>;
	$pos.useAutoDecimal = <?php echo $pos->config->autoDecimal; ?>;
	$edit_customer.default_customer_id = <?php echo $pos->config->default_customer_id; ?>;
	$pos.tax_rate = <?php echo $pos->config->sales_tax; ?>;
});
		
</script>

</body>
</html>
