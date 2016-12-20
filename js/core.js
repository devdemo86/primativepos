
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

$(document).ready(function(){

//$('#catalog_headings DIV').each(function() {

//	$(this).css('border', '1px solid #000000');

//});


  window.$edit_customer =
  {
	// Initialize all the queries you want to use more than once
	edit_last_name : $('#edit_last_name'),
	edit_first_name : $('#edit_first_name'),
	edit_company : $('#edit_company'),
	edit_mi : $('#edit_mi'),
	edit_address : $('#edit_address'),
	edit_address2 : $('#edit_address2'),
	edit_city : $('#edit_city'),
	edit_state : $('#edit_state'),
	edit_zip : $('#edit_zip'),
	edit_phone : $('#edit_phone'),
	editing_customer_id : $('#editing_customer_id'),
	edit_tax_exempt : $('#edit_tax_exempt'),
	edit_allow_credit: $('#edit_allow_credit'),
	edit_active : $('#edit_active'),
	edit_listby_company : $('#edit_listby_company'),
	edit_listby_lastname : $('#edit_listby_lastname'),
	save_customer_button : $('#save_customer_button'),
	customer_sel : $('#customer_listing'),
	customer_job_listing : $('#customer_job_listing'),
	customer_edit_cell : $('#customer_edit_cell'),
	customer_jobs_cell : $('#customer_jobs_cell'),
	customer_job_edit : $('#customer_job_edit'),
	phone : $('#phone'),
	phone_ext : $('#phone_ext'),
	show_inactive : 0,
	list_is_loaded : 0,
	default_customer_id : ''
	
  };

  window.$pos = 
  {
	open_transactions : $('#open_transactions'),
	special_options_button : $('#special_options_button'),
	payment_specialoptions_dialog : $('#payment_specialoptions_dialog'),
	is_resale : '0',
	freight_display_total : $('#freight_display_total'),
	labor_display_total : $('#labor_display_total'),
	discount_display_total : $('#discount_display_total'),
	discount_icon : $('#discount_icon'),
	freight_icon : $('#freight_icon'),
	labor_icon : $('#labor_icon'),
	notify : $("#notify_container"),
	barcode : $("#barcode"),
	ticket_id : $("#ticket_id"),
	customer_id : $("#customer_id"),
	customer_job_id : $("#customer_job_id"),
	customer_display_name : $("#customer_display_name"),
	ticket_display_id : $("#ticket_display_id"),
	tax_exempt : $('#tax_exempt'),
	discount : $('#discount_number'),
	freight : $('#freight_number'),
	labor : $('#labor_number'),
	subtotal : $('#subtotal'),
	tax : $('#tax'),
	display_total : $('#display_total'),
	cart : $('#cart'),
	cart_container : $('#cart_container'),
	cash_given : $('#cash_given'),
	cancel_button : $('#cancel_pay_button'),
	cc_trans_no : $('#cc_trans_no'),
	check_no : $('#check_no'),
	customer_ticket_search : $('#customer_ticket_search'),
	pay_button : $('#pay_button'),
	payment_methods : $('#payment_methods'),
	class_customer_search : $('.class_customer_search'),
	customer_dialog : $('#customer_dialog'),
	customer_search : $('.customer_search'),
	pause_button : $('#pause_button'),
	void_button : $('#clear_button'),
	refund_switch : $('#pay_refund'),
	take_check : $('#take_check'),
	pay_job_id : $('#pay_job_id'),
	customer_job_display_name : $('#customer_job_display_name'),
	allow_credit : $('#allow_credit'),
	previous_value : '', // for auto setting the decimal
	previous_decimal : 0, // binary if last value had the decimal
	closing_cash : $('#closing_cash'),
	closing_checks : $('#closing_checks'),
	authenticate_action : '',
	progress_bar_interval : 0,
	useLabelPrinter : 0,
	useAutoDecimal : 0,
	printReceiptChkbox : $('#printReceiptChkbox'),
	tax_rate : '',
	refund_indicator : $('#refund_indicator'),
	add_recv_by_button : $('#add_recv_by_button'),
	recv_by_name : $('#recv_by_name'),
	recv_by_container : $('#recv_by_container'),
	recv_by_label : $('#recv_by_label'),
	recv_by_input : $('#recv_by_input'),
	recv_by_button : $('#add_recv_by_button'),
	cart_item_description_name : $('#cart_item_description_name'),
	cart_item_description_label : $('#cart_item_description_label'),
	cart_item_description_barcode : $('#cart_item_description_barcode'),
	save_cart_item_description_button : $('#save_cart_item_description_button')
  };

  // holds a potential description for each item in the cart
  window.$item_descriptions = { };
  
  window.$catalog =
  {
  	add_item_dialog : $('#add_item_dialog'),
  	new_item_name : $('#new_item_name'),
	new_item_price : $('#new_item_price'),
	new_item_skn : $('#new_item_skn'),
	icon : $('#catalog_icon'),
	dialog : $('#catalog_dialog'),
	search_name : $('#catalog_search_name'),
	catalog_table : $('#catalog_table'),
	use_ws : $('#catalog_use_wholesaler'),
	open_record : 0,
	pre_auth_button_obj : '',
	pre_auth_barcode : ''
  };
  
  window.$payments =
  {
	payment_recv_customer_id : $('#payment_recv_customer_id'),
	payment_recv_search_name : $('#payment_recv_search_name'),
	payment_recv_method : $('#payment_recv_method'),
	payment_recv_extra_info : $('#payment_recv_extra_info'),
	payment_recv_amt : $('#payment_recv_amt'),
	payment_recv_display_name : $('#payment_recv_display_name'),
	payment_recv_date : $('#payment_recv_date'),
	payment_recv_job_id : $('#payment_recv_job_id'),
	payment_recv_button : $('#recv_payment_button'),
	payment_recv_display_balance : $('#payment_recv_display_balance'),
	payment_recv_hour : $('#payment_recv_hour'),
	payment_recv_minute : $('#payment_recv_minute'),
	payment_recv_ampm : $('#payment_recv_ampm')
  };
  
  window.$editable_item =
  {
	cur_item_id : '',
	cur_cell : '0',
	cur_qty : '',
	edit_contents : ''
  
  };

  window.$editable_price =
  {
	cur_item_id : '',
	cur_cell : '0',
	cur_price : '',
	edit_contents : '',
	pre_auth_cell : '',
	pre_auth_item_id : ''
  
  };
  
  window.$auto_decimal = 
  {
	box : null,
	count : 0,
	str : ''
  }
  
  window.$billing = 
  {
  
	dialog : $('#billing_dialog'),
	container : $('#billing_container'),
	list : $('#billing_list'),
	customer_bill_dialog : $('#customer_bill_dialog'),
	customer_bill_name : $('#customer_bill_name'),
	customer_bill_job_id : $('#customer_bill_job_id'),
	customer_tickets_container : $('#customer_tickets_container'),
	customer_tickets_list : $('#customer_tickets_list'),
	bill_start_date : $('#bill_start_date'),
	bill_end_date : $('#bill_end_date'),
	customer_bill_customer_id : $('#customer_bill_customer_id'),
	customer_bill_transaction_type : $('#customer_bill_transaction_type'),
	ticket_items_list : $('#ticket_items_list'),
	ticket_items_container : $('#ticket_items_cotainer'),
	billing_display_types : $('#billing_display_types'),
	billing_list_end_date : $('#billing_list_end_date'),
	print_statement_button : $('#print_statement_button'),
	viewStatementCtrl : $('#viewStatementCtrl'),
	service_charge_dialog : $('#service_charge_dialog'),
	service_charge_amount : $('#service_charge_amount'),
	service_charge_name : $('#service_charge_name'),
	service_charge_customer_id : $('#service_charge_customer_id'),
	service_charge_job_id : $('#service_charge_job_id'),
	service_charge_job_container : $('#service_charge_job_container'),

	printAllStatementsCtrl : $('#printAllStatementsCtrl'),
	printAllStatementsIndicator : $('#printAllStatementsIndicator'),

	special_charge_type : $('#special_charge_type'),
	statement : $('#statement_view_dialog'),
	statement_contents : $('#statement_contents'),

	reports_dialog : $('#reports_dialog'),

	cash_refund_dialog : $('#cash_refund_dialog'),
	cash_refund_display_name : $('#cash_refund_display_name'),
	cash_refund_amount : $('#cash_refund_amount'),
	cash_refund_payment_cash : $('#cash_refund_payment_cash'),
	cash_refund_payment_check : $('#cash_refund_payment_check'),
	cash_refund_customer_id : $('#cash_refund_customer_id')
  };

  window.$cmenu =
  {
	id : $('#context_menu_id'),
	type : $('#context_menu_type'),
	void_ticket_id : 0,
	row : false, // hold the row for changing the background of the open line
	row_shade : false, // to tell the striping pattern
	prev_id : false, // to see the former row's id
	obj : false

  }

  window.$dialogs = 
  {
	cart_item_description_dialog : $('#cart_item_description_dialog'),
	tmp_row : false
  }

  window.$clock = 
  {
    container : $('#clock_container'),
    currentHours : 0,
    currentMinutes : 0,
    ampm : ''
	
  }

  window.$reports = 
  {
	start_date : $('#report_start_date'),
	end_date : $('#report_end_date'),
	weekly_report_fields : $('#weekly_report_fields')

  }

  $pos.notify.notify({
		speed: 0,
		expires: 1500
  });
  
  $('#toolbar img').css('border', '1px solid #dddddd');
  
  $('#toolbar img').mouseover(function() {
  
	$(this).css('border','1px solid #999999');
  
  });
  $('#toolbar img').mouseout(function() {
  
	$(this).css('border','1px solid #dddddd');
  
  });
  
   window.setTimeout("update_clock()", 1000);

    $billing.billing_display_types.val('balances');

		
    $billing.statement.dialog({ title : 'Statement', autoOpen: false, modal : false, resizable : true, draggable : true, width: 785, height: 600 });
    $billing.reports_dialog.dialog({ title : 'Reports', autoOpen: false, modal : false, resizable : false, draggable : true, width: 850, height: 600 });

    $('.ui-dialog-titlebar').css('font-size', '10pt')

    $reports.start_date.datepicker({duration:0});
    $reports.end_date.datepicker({duration:0});

	$('#only_show_balances').prop('checked', true);

});

function clear_pos() {


		$pos.cart.html('');
		$pos.subtotal.html('');
		$pos.tax.html('');
		$pos.ticket_display_id.html('');
		$pos.ticket_id.val('');
		$pos.customer_display_name.html('');
		$pos.customer_id.val('');
		$pos.display_total.html('');
		$pos.cash_given.val('');
		$pos.check_no.val('');
		$pos.cc_trans_no.val('');
		$pos.tax_exempt.val('0');
		$pos.pay_button.removeProp('disabled');
		$pos.cancel_button.removeProp('disabled');
		$pos.refund_switch.removeProp('checked');
		$pos.open_transactions.val('').removeProp('disabled');
		$pos.check_no.removeProp('disabled');
		$edit_customer.customer_job_edit.val('');
		$pos.customer_ticket_search.val('Customer Name').css('color', '#999999');
		$pos.allow_credit.val('0');
		$pos.discount.val('0');
		$pos.freight.val('0');
		$pos.labor.val('0');
		
		$pos.pay_job_id.val('');
		$pos.customer_job_display_name.html('');

		$catalog.add_item_dialog.hide();
		$catalog.new_item_name.val('');
		$catalog.new_item_price.val('');
		
		$pos.printReceiptChkbox.prop('checked', true);
		$pos.refund_indicator.html('');
		$pos.add_recv_by_button.prop('disabled', true);
		$pos.recv_by_name.html('').hide();
		$pos.recv_by_input.val('').removeProp('disabled');
		$pos.recv_by_label.hide();
		$pos.recv_by_button.html('Add received by...');
		$pos.pause_button.removeProp('disabled');

		cancel_payment(1);

		$pos.barcode.focus();
	//alert($pos.customer_display_name.html());	
	
}

// this is called during autocomplete selects with the enter key...need to find a way 
// to avoid this, since this function will be needed if a barcode scanner is hooked up
function check_enter(sku, evt)
{
	if(window.event) // IE
	{
		keynum = evt.keyCode;
		if(keynum == 13)
			lookup_item();
	}
	else if(evt.which == 13)			//alert(evt.target);
		lookup_item();
	
		return false;
}

function save_opening_balance()
{
	amt = $('#open_cash').val();
	tmpamt = amt.toString();

	
	if(tmpamt.indexOf('.') == '-1' && $pos.useAutoDecimal)
		amt /= 100;
		

	$.post('journal.php', { type : 'open', amount : amt }, function(data) {
		
			if(data)
			{
				$.get('print_opening_journal.php', { amount : amt, printLabel : $pos.useLabelPrinter }, function(data_xml) {
				
					if($pos.useLabelPrinter) // print is done internally
					{
						//var printers = dymo.label.framework.getPrinters();
						//printer_index = find_dymo_printer();
					
						//label = dymo.label.framework.openLabelXml(data_xml);
			
						//label.print(printers[printer_index].name);
					} else
						alert(data_xml);			
				
				});
				
				
				$('#startup_dialog').dialog('close');
				$(".ui-dialog-titlebar-close").show(); // add back the close button
			}
			else
			{
				// blur so add_decimals() doesn't fire again if enter key is pressed after the error message
				$('#open_cash').blur();
				alert("could not save the opening balance");
				
				window.setTimeout(function() {
					$('#open_cash').focus();
				}, 100);
			
			}
			
		}, 'json');

}

// start the function by delaying the printing so the indicator can load
// and the control can be hidden
function printAllStatements()
{
    $billing.printAllStatementsIndicator.show();
    $billing.printAllStatementsCtrl.hide();

    window.setTimeout("printAllStatementsStart()", 200);

}

function printAllStatementsStart()
{
	i = 0;
	print_ids = new Array();

    $('#billing_list tr').each(function() {

		var tmp = $(this).attr('id');
		var parts = tmp.split('_');

		try {
			if(!parts[1])
				return false;
			
			} catch(e) { return false; }

		var customer_id = parts[1];

		tmp = $(this).find('input:checkbox');


		if(tmp.prop('checked'))
		{

			print_ids.push(customer_id);
		}

		// we should get the response back from the server and deselect then
		tmp.removeProp('checked');

		i = i + 2000;
    });

	
	$.post('print_statements.php', { action : 'print_all_statements', print_tickets : 1, end_date : $billing.billing_list_end_date.val(),  customer_id : print_ids, 'rnd' : Math.random() }, function(response) {

//		tmp.removeProp('checked');
		
	});

	//console.log(print_ids);
	

    $billing.printAllStatementsIndicator.hide();
    $billing.printAllStatementsCtrl.show();
 
}

function update_clock()
{

  var currentTime = new Date();

  var currentHours = currentTime.getHours();
  var currentMinutes = currentTime.getMinutes();
  var currentSeconds = currentTime.getSeconds();

  var currentYear = currentTime.getFullYear();
  var currentMonth = currentTime.getMonth() + 1;
  var currentDay = currentTime.getDate();

  var currentDayName = currentTime.getDay();

  var day_of_week = new Array('Sun','Mon','Tue','Wed','Thurs','Fri','Sat');
  var mo_of_year = new Array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sept','Oct','Nov','Dec');


  // Pad the minutes and seconds with leading zeros, if required
  currentMinutes = (currentMinutes < 10 ? "0" : "") + currentMinutes;
  currentSeconds = (currentSeconds < 10 ? "0" : "") + currentSeconds;
  currentMonth = (currentMonth < 10 ? "0" : "") + currentMonth;

  var timeOfDay = (currentHours < 12) ? "am" : "pm";

  currentHours = (currentHours > 12) ? currentHours - 12 : currentHours;

  currentHours = (currentHours == 0) ? 12 : currentHours;

  // store in global variables for the payments screen
   $clock.currentHours = currentHours;
   $clock.currentMinutes = currentMinutes;
   $clock.ampm = timeOfDay;

  // Compose the string for display
  var currentTimeString = currentHours + ":" + currentMinutes + " " + timeOfDay;
  var currentDateString =  day_of_week[currentDayName] + ' ' + mo_of_year[currentMonth-1] + " " + currentDay + ", "+ currentYear;

  // Update the time display

	$clock.container.html(currentDateString + ' &nbsp; &nbsp;' + currentTimeString);
	
	window.setTimeout("update_clock()", 60000);
	
}
