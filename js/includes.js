
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

$(document).ready(function() {

	//$('#ticket_display_id').html($('#ticket_id').val());

	$("#show_inactive").removeProp('checked'); // don't want this in clear_pos() i don't think since

	clear_pos();

	$pos.barcode.focus();

	// setup ticket heading sorting
	$('#ticket_heading_sort_row img').each(function() { 
		if($(this).attr('id') != 'date_sortimg')
			$(this).css('display', 'none');
	});

	// force focus back to barcode
	$(document).click(function(evt) {
	
		if($editable_item.cur_item_id != '') // remove editable item in cart
		{
			if(evt.target.id != 'cur_edit_cell') // only restore in an onblur to the cell
				restore_qty();
			else
				return false;
		} else if($editable_price.cur_item_id != '') // remove editable item in cart
		{
			if(evt.target.id != 'cur_edit_cell_price' && evt.target.id != 'auth_confirm') // only restore in an onblur to the cell
				restore_price();
			else
				return false;

		}

		if(evt.which != 3)
		{
		    $('.vs-context-menu').hide();

		  //  if($cmenu.row){
/*
		    $cmenu.row.css({'background-color':'#ffffff','color' : '#000000'});

		    if($cmenu.row_shade)
			 $cmenu.row.addClass('even').css({'background-color':'#dddddd','color' : '#000000'});
				
		    $cmenu.row_shade = 0;
		    $cmenu.prev_row = $cmenu.id.val();
		    $cmenu.row = false; // unset
		    $cmenu.id.val('');	
*/
		}

		//alert($('.vs-context-menu').css('display'));
		// restore the striping to the open context menu row
		//alert($cmenu.id.val());
		//if($('.vs-context-menu').css('display') != 'block' || ($cmenu.id.val() == '' && $cmenu.row) || ($cmenu.prev_id == $cmenu.id.val() && $cmenu.row) && $cmenu.row)
		//{

		//}

		if(evt.target.type != 'select-one' && evt.target.type != 'text')
		{
			if(!(evt.target.value == '' || evt.target.value > 0))
				$pos.barcode.focus();
		}
	});

	
	//
	//	AUTOCOMPLETE -- BARCODE
	//
	$('.ui-autocomplete ul').css('height','100px'); // fix height

	$( "#barcode" ).autocomplete({ delay: 300, minLength: 3, source: 
		function(request, response)
		{
			if(!isNaN(request.term)) //avoid lookups on numbers for now
				return;
		
			$.get('ac_search.php', { 'q' : request.term, 'type' : 'catalog' }, 
			function(cdata)
			{
			//	response( $.map( data.items, function( item ) {
				//		return { label: item.name, value: item.barcode	}
						
					//	}));
					


				try {
						myarr = new Array(cdata.items.length);
						
					} catch(e){ alert('Error communicating with server'); return false; }
				
				for(i = 0; i < cdata.items.length; i++)
				{
					tmpobj = new Object();
			
					tmpobj.label = cdata.items[i].name;
					tmpobj.value = cdata.items[i].barcode;
					tmpobj.extra = cdata.items[i].extra;
				
					myarr[i] = tmpobj;
				}
	
				response(myarr);
			
			}, 'json');
			

		}, 	open: function() {
	
			$('.ui-autocomplete li').css('font-size','60%');
	
		}, select: function( event, ui ) {

			//alert(event.which);

			$pos.barcode.val(ui.item.value);

		//	event.stopPropagation(); // so enter key doesn't fire evet twice!

		//console.log(event.which);
			// don't need for 13 since check_enter() already handles that
			if(event.which == 1 || event.which == 0 ) //|| event.which == 13) // m
				lookup_item();


		}, focus: function( event, ui ) {

			var sel_item = event.target;

			$('.ac_extra').remove();

			$('.ui-autocomplete li a').each(function() {
			
			//alert($(this).html());

			
				if($(this).html() == ui.item.label)
					$(this).parent().append("<div class=\"ac_extra\" style=\"font-size: 90%; color: #666666\">" + ui.item.extra +"</div>");
			
				//$(this).html(ui.item.label + "<BR>" + ui.item.extra);
			
			
			});
			
			//alert(event.target.id);
			//(ui.item.extra);

		}
		
	});
	
	$('.customer_search').autocomplete({ minLength: 3, source:
		function(request, response)
		{
		
			$.get('ac_search.php', { q : request.term, type : 'customer' },
			function(cdata)
			{
				myarr = new Array(cdata.items.length);
				
				for(i = 0; i < cdata.items.length; i++)
				{
					tmpobj = new Object();
			
					tmpobj.label = cdata.items[i].name;
					tmpobj.value = cdata.items[i].customer_id;
				
					myarr[i] = tmpobj;
				}
	
				response(myarr);

			}, 'json');
			
			

		}, 	open: function() {
				
				$('.ui-autocomplete li').css('font-size','60%');
				//$('.ui-autocomplete ul').css('height','100px');
				
		}, select: function( event, ui ) {

			if($pos.customer_dialog.css('display') == 'block')
			{
				$edit_customer.customer_sel.val(ui.item.value);
				edit_customer_info(ui.item.value);
				$(this).css('color', '#cccccc').val('Search Customer');

			} else if($billing.dialog.css('display') == 'block')
			{
				view_customer_bills(ui.item.value);
				$(this).css('color', '#cccccc').val('Search Customer');
			}
		}
	}).click(function() { $(this).val('').css('color', '#000000'); }).blur(function() { $(this).val('Search Customer').css('color', '#cccccc'); });
			

	$('.ticket_search').click(function() { $(this).val('').css('color', '#000000'); }).blur(function() { $(this).val('Search Ticket').css('color', '#cccccc'); });
	
	// used for selecting the name on the ticket
	$( "#customer_ticket_search" ).autocomplete({ minLength: 3, source: 
		function(request, response)
		{
		
			$.get('ac_search.php', { 'q' : request.term, 'type' : 'customer' }, 
			function(cdata)
			{

				myarr = new Array(cdata.items.length);
				
				for(i = 0; i < cdata.items.length; i++)
				{
					tmpobj = new Object();
			
					tmpobj.label = cdata.items[i].name;
					tmpobj.value = cdata.items[i].customer_id;
				
					myarr[i] = tmpobj;
				}
	
				response(myarr);

			}, 'json');
			

		}, 	open: function() {
				$('.ui-autocomplete li').css('font-size','70%');
				//$('.ui-autocomplete ul').css('height','100px');
				
		}, select: function( event, ui ) {
		
		
				// set customer name on the db entry
				$.post('update_ticket.php', { 'ticket_id' : $('#ticket_id').val(), 'customer_id' : ui.item.value }, function(response)
				{

		
					if(response.status)
					{

						$pos.customer_display_name.html(ui.item.label);
						$pos.customer_job_display_name.html('').hide(); // hide job in case changed customer
						$pos.customer_id.val(ui.item.value);
						$pos.tax_exempt.val(response.tax_exempt);
						$pos.allow_credit.val(response.allow_credit);
						$pos.customer_ticket_search.val('');
						$pos.customer_ticket_search.hide();
						
	
						// reset tax depending on tax exempt


						if(response.tax_exempt == 1)
						{
							
							// get total, remove comma and parse as float
							var tmp = $pos.display_total.html();
							cur_total = parseFloat(tmp.replace(',', ''));
							
							tmp = $pos.tax.html();
							cur_tax = parseFloat(tmp.replace(',', ''));
							
							// subtract tax from total
							new_total = cur_total - cur_tax;
							new_total = new_total.toFixed(2);

							// tax to 0 and display new total
							$pos.tax.html('0.00');
							$pos.display_total.html(new_total);
	
						}
						
						// update the change-ticket select box with customer's name
						
						var new_label = "#" + response.ticket_display_id + " - " + ui.item.label;
						$('#open_transactions option').each(function() {
			
							if($(this).val() == $pos.ticket_id.val())
								$(this).html(new_label);
						});					

						//
						// show job listings if available
						//
						
						// populate the job box
						$.get('modify_customer.php', { 'get_customer_jobs' : '', 'job_cust_id' : ui.item.value }, function(response) 
						{
						
							htmlline = "<option value=\"\"> - Choose Job -</option>";
							htmlline += "<option value=\"\" disabled=\"disabled\"></option>";
							htmlline += "<option value=\"0\">No Job Specified</option>";
							
							for(i = 0; i < response.jobs.length; i++)
							{
								htmlline += "<option value=\"" + response.jobs[i].id + "\">" + response.jobs[i].name + "</option>";
							}
							
							if(response.jobs.length > 0)
								$pos.pay_job_id.html(htmlline).show();
							else // if no jobs, then let the search box re-appear
							{
								$pos.customer_ticket_search.val('Customer Name').css('color', '#999999').show();

							}

						}, 'json');
						
						//$pos.pay_job_id.show();
						
						
						
					} else
						show_note("Could not set customer!");
					
				}, 'json');
		}
		
	});

	$('#shutdown_dialog').dialog({ title : 'POS Shutdown', autoOpen: false, modal : true, resizable : false, draggable : false, width: 330, height: 400, open : function() {
	$('.ui-button').css({'font-size' : '80%', 'padding' : '3px'});
	$('.ui-dialog-titlebar').css('font-size', '80%');
	//$('#closing_cash').focus();

	}
	});


	// Additional display fixes

	$payments.payment_recv_date.datepicker({'duration' : 0});
	$billing.bill_start_date.datepicker({'duration' : 0});
	$billing.bill_end_date.datepicker({'duration' : 0});
	$('#ui-datepicker-div').css('font-size','80%');


	$('button').addClass('ui-state-default ui-corner-all');
	
	// add hover events
	$('.ui-state-default').hover(
		function(){ 
			$(this).addClass("ui-state-hover"); 
		},
		function(){ 
			$(this).removeClass("ui-state-hover"); 
		}
	).css('cursor','pointer');
	
	$('textarea, select, input[type=text], input[type=password]').addClass('ui-corner-all');


});



function show_note(msg)
{
	$pos.notify.notify("create", {
			title: msg, speed: 0, expires: 2000
	});	


}



function chg_ticket(ticket_id)
{
	
	$.post('lookup_item.php', { 'load_ticket' : '1', 'ticket_id' : ticket_id }, function(response) {

			if(response.status)
			{

				cancel_payment(1);
				
				$pos.subtotal.html(response.subtotal);
				$pos.tax.html(response.tax);
				$pos.display_total.html(response.total);
				
				var tmp_subtotal = response.subtotal.replace(',', '');
				
				add_to_cart(tmp_subtotal, response.cart);
				
				$pos.ticket_id.val(response.ticket_id);
				$pos.ticket_display_id.html(response.ticket_display_id);
				$pos.pay_job_id.val(response.job_id)
				$pos.customer_job_display_name.html(response.job_name);
				
				$pos.open_transactions.val('');
				
				// set the inputs used to modify the discount/freight/resale
				/*
				if(response.discount > 0)
					var tmppct = 100 - ((parseFloat(tmp_subtotal) - parseFloat(response.discount))/parseFloat(tmp_subtotal)) * 100;
				else
					var tmppct = 0;
					
				//	alert(parseFloat(response.subtotal) - parseFloat(response.discount));
		//alert(tmppct);

				
				if(tmppct == 100)
					tmppct = '';
				else
					tmppct = tmppct.toFixed(0);

				$('#discount_percentage').val(tmppct);
				*/
		
				$pos.discount.val(response.discount.replace(',', ''));
				
				$('#freight_number').val(response.freight);
				$('#labor_number').val(response.labor);

				// show discount in main totals				
				if(response.discount.replace(',', '') > 0)
				{

					$pos.discount_icon.show();
					$pos.discount_display_total.html("$ -" + response.discount);
			
				}
			
				if(response.freight > 0)
				{
					$pos.freight_icon.show();
					$pos.freight_display_total.html("$ " + response.freight);
				}
				if(response.labor > 0)
				{
					$pos.labor_icon.show();
					$pos.labor_display_total.html("$ " + response.labor);
				}
				
				if(response.resale == 1)
					$('#is_resale').prop('checked', true);
				else
					$('#is_resale').removeProp('checked');

				if(response.refund == 1)
				{
				    $pos.refund_switch.prop('checked', true);
				    $pos.refund_indicator.html('- Refund');
				} else
 			 	    $pos.refund_indicator.html('');

				// these will be set from the db eventually i think
				$pos.customer_display_name.html(response.customer);
				$pos.customer_id.val(response.customer_id);
				$pos.tax_exempt.val(response.tax_exempt);
				$pos.allow_credit.val(response.allow_credit);
				$pos.cash_given.val('');
				$pos.check_no.val('');
				$pos.cc_trans_no.val('');
				$pos.add_recv_by_button.removeProp('disabled');
				$pos.recv_by_label.hide();
				$pos.recv_by_name.html('');
				$pos.recv_by_input.hide().val('');
				$pos.recv_by_button.html("Add received by...");

				$item_descriptions = response.item_descriptions;

				if(response.recv_by != '' && response.recv_by != null)
				{
				    $pos.recv_by_name.html(response.recv_by).show();
				    $pos.recv_by_label.show();
				}
			} else
				show_note("Cannot load ticket");

			$pos.barcode.focus();
		}, 'json');	
	
	//add_to_cart(subtotal, cart);
	
	//$('#barcode').focus(); // seems to get focus back on its own...

}




	
function lookup_item() {
	
	
	if($pos.barcode.val() == '')
		return false;
		

	$pos.add_recv_by_button.removeProp('disabled');
	//  barcode lookup

	$.post('lookup_item.php', { 'skn' : $pos.barcode.val(), 'ticket_id' : $pos.ticket_id.val(), 'tax_exempt' : 	$pos.tax_exempt.val() }, function(response) {

			if(response.status)
			{
			
				$pos.subtotal.html(response.subtotal);
				$pos.tax.html(response.tax);
				$pos.display_total.html(response.total);
			
				add_to_cart(response.subtotal, response.cart);
				
				if($pos.ticket_id.val() == '')
				{
					// new ticket
					$pos.ticket_id.val(response.ticket_id);
					$pos.ticket_display_id.html(response.ticket_display_id);

					option_row = "<option value=\"" + response.ticket_id + "\">#" + response.ticket_display_id + "</option>";
						
					$pos.open_transactions.append(option_row);
						
				} else if($pos.ticket_id.val() != response.ticket_id)
					alert("Exception 0001:  Ticket ID has changed/Uknown error.  You should cancel the current transaction and start over.\r\n\r\nsys var: " + $pos.ticket_id.val() + "...retrieved: " + response.ticket_id);

				$pos.barcode.val('');

			} else
				show_note("Item not found");

		}, 'json');
		
}

function clear_ticket(ticket_id)
{
	var clear_pos_vars = 0;

	if(ticket_id == null && $pos.ticket_id.val() == '')
		return false;
	
	// ticket_id is the currently open ticket, as opposed to voiding through the billing page
	if(!(ticket_id > 0))
	{
		ticket_id = $pos.ticket_id.val();
		clear_pos_vars = 1;
	}

	$.post('update_ticket.php', { 'ticket_id' : ticket_id }, function(response) {
		
		// clear display
		if(!response.status)
		{
			show_note("Cannot void ticket!");
			return false;
			
		} else
			show_note("Ticket Voided");
			
		// remove from select box
		$('#open_transactions option').each(function() {
			
			if($(this).val() == $pos.ticket_id.val())
				$(this).remove();
		});

		if(clear_pos_vars == 1)
			clear_pos();
		
	}, 'json');

	
}



function authenticate(action)
{

	if(action == null)
	{
		alert("Error: no action given");
		return false;
	}

	$pos.authenticate_action = action;
		
	$('#auth_dialog').show();
	$pos.barcode.prop('disabled',true);
	$('#admin_passwd').focus();

}

// after entering password, return to action
function auth_return()
{
	//alert($pos.authenticate_action);
	$.post('auth.php', { 'passwd' : $('#admin_passwd').val() }, function(response) {
		
		if(response.auth)
		{
			if($pos.authenticate_action == 'editable_price')
				edit_price($editable_price.pre_auth_cell, $editable_price.pre_auth_item_id);
			else if($pos.authenticate_action == 'void_ticket')
				clear_ticket($cmenu.void_ticket_id);
			else if($pos.authenticate_action == 'special_options')
				show_payment_specialoptions();
			else if($pos.authenticate_action == 'edit_catalog')
				edit_cat_row($catalog.pre_auth_button_obj, $catalog.pre_auth_barcode)
				
		} else
		{
			show_note("Incorrect password");
		}
		
		auth_cancel();
		
	}, 'json');
	
}

function auth_cancel()
{
	$('#auth_dialog').hide();
	$('#admin_passwd').val('');
	$pos.barcode.removeProp('disabled');
	$pos.authenticate_action = '';

}

// check enter key on auth keypress
function check_auth_enterkey(evt)
{
	if(window.event) // IE
		keynum = evt.keyCode;
	else if(evt.which) // Netscape/Firefox/Opera
		keynum = evt.which;

	
	if(keynum == 13)
		auth_return();

}

// print_tickets = 1|0 to print tickets along with statement
function print_customer_statement(print_tickets)
{
	$('#customer_activity_indicator').show();
	
	if($billing.customer_bill_customer_id.val() > 0)
		id = $billing.customer_bill_customer_id.val();
	else
	{
		var customer_ident = $cmenu.id.val();
		
		var tmp = customer_ident.split("_");
		
		id = tmp[1];
		
	}
	
	// encode date slashes
	var tmpdate = $billing.billing_list_end_date.val();
	date = tmpdate.replace(/\//g, "%2F");
	
	//'date' : $billing.bill_end_date.val()

	$.post('print_statements.php', {'customer_id' : id, 'start_date' : $billing.bill_start_date.val(), 'end_date' : $billing.bill_end_date.val(), 'action' : 'print', 'print_tickets' : print_tickets, 'rnd' : Math.random() }, function() {
		
		$('#customer_activity_indicator').hide();
		
	
	});
	
}


function print_end_report()
{

	
	var counted_cash = $pos.closing_cash.val();
	var counted_checks = $pos.closing_checks.val();
	
	if(counted_cash.indexOf('.') == '-1' && $pos.useAutoDecimal && counted_cash != 0)
		counted_cash /= 100;
		
	if(counted_checks.indexOf('.') == '-1' && $pos.useAutoDecimal && counted_checks != '0')
		counted_checks /= 100;
	

	if((isNaN(counted_cash) || isNaN(counted_checks)) || counted_cash == '' || counted_checks == '')
	{
		alert("Please give the amount of checks and cash");
		return false;
	}

	$.get('print_journal.php', { 'cash' : counted_cash, 'checks' : counted_checks, 'printLabel' : 1 }, function(data_xml) {
	
			$pos.closing_checks.blur();
	
			alert(data_xml);
			
			$('#create_backup_button').removeProp('disabled');

	
	});

}


var t;
function add_decimals(box, evt, callback)
{
	var str = box.value;
	
	if(window.event) // IE
	{
		keynum = evt.keyCode;
		if(keynum == 13 && callback)
			window[callback]();
	}
	else if(evt.which == 13 && callback)			//alert(evt.target);
	{

		//if(callback == 'post_transaction')
		//	return false;
		
		window[callback]();
		//save_opening_balance();

	}

	if(!$pos.useAutoDecimal)
		return;

	$auto_decimal.box = box;
	$auto_decimal.count++;
	$auto_decimal.str = str;
	
	count = $auto_decimal.count;
	
	clearTimeout(t);
	t = setTimeout("add_decimals_go(count)", 400);

}

function add_decimals_go(cur_count)
{

	// let delay prevent firing in rapid keystrokes
	if($auto_decimal.count != cur_count)// || $auto_decimal.count == 0)
		return;
//	alert($auto_decimal.count + ' and ' + cur_count);	
	$auto_decimal.count = 0;
	
	//box = $auto_decimal.box;
	str = $auto_decimal.str;
//	alert(str);alert(box);
//alert('hi');
	var box = '';

	if(isNaN(str))
	{
		if(str.length > 1)
			box = str.substr(0, str.length-1);
		else
			box = '';
			
		return;
	}
	
	str = str.replace(".", "");
	
	var origvalue = str;
	var offset = 0;
	
//alert(str);
	//$('#debug_container').html(str.length + ' and ' + $pos.previous_value.length);

	if(str.length == 2 )//&& $pos.previous_value.length == 3 && $pos.previous_decimal)
	{
		//alert('replace');
		box = str.replace(".", "");
		//box.value = str;
		//$pos.previous_value--; // remove the decimal
	offset = -1;
	} else if(str.length == 3 ) //&& ($pos.previous_value.length == 2 || $pos.previous_value.length == 4))
	{
		left_side = str.substr(0, 1);
		right_side = str.substr(1);
		box = left_side + '.' + right_side;
		//	alert('2');
		//$pos.previous_value++; // since we add the decimal
		//alert(left_side);
		$pos.previous_decimal = 1;
		offset = 1;
	} else if(str.length > 3)
	{
		left_side = str.substr(0, str.length-2);
		right_side = str.substr(str.length-2) 
//	alert('3');
		box = left_side + '.' + right_side;
		$pos.previous_decimal = 1;
	} else
		box = str;
		
	$pos.previous_value = origvalue;// + offset;

	$auto_decimal.box.value=box;
}

function shutdown_pos()
{
	if(!confirm("Are you sure you want to shut down the POS Computer?"))
		return false;

	var shutdown_password = $('#shutdown_passwd').val();

	if(shutdown_password == '')
	{
		alert("You must enter your password");
		return false;
    }

	$('#shutdown_indicator').show();

	$.get('shutdown.php', { 'shutdown_password' : shutdown_password }, function(response) {
	
		if(response.authorized == '0')
		{
			$('#shutdown_indicator').hide();
			alert("Incorrect password, please try again.");
		} else if(response.authorized == '1')
			$('#shutdown_started').show();

	}, 'json');

}

function show_shutdown_dialog()
{
	$('.posdlg').hide();
	$(".ui-dialog-titlebar-close").show();
	$('#shutdown_dialog').dialog('open'); 

}

function create_backup()
{
	$('#backup_progress').show();

	$.get('backups/upload_backup.php', { type : 'full_backup', 'action' : 'backup' }, function(response) {
	
		//$('#shutdown_status').html('Shutting down . . .');
				//	alert(response);return false
			// if access time is too far in the past
			if(response.time_diff > 960)
			{
				alert("Backup could not complete.  Last backup was made " + Math.round(response.time_diff/60) + " minutes ago");
				$('#backup_progress').html("Backing up and encrypting database . . .<span style=\"color: red\"> FAIL</span>");
				return false;
			}
			
			$('#backup_progress').html("Backing up and encrypting database . . . Done");

			var est_time = response.est_time;
			
			// time x 2 since we update the progress bar every 500 milliseconds (1/2 a second)
			$pos.progress_bar_interval = 60 / est_time;
			
			$('#upload_progress').show();
		
			var progress_bar = $("#progressbar");
			
			progress_bar.progressbar({ value: 0 });
			setTimeout(updateProgress, 500);

			//return false;
			
			$.get('backups/upload_backup.php', { type : 'full_backup' }, function(response) {
	
				progress_bar.progressbar("option", "value", 100);

				if(response == 'Successfully uploaded')
					$('#upload_progress').html("Uploading database offsite . . . Done");
				else
				{
					$('#upload_progress').html("<span style=\"color: red\"> UPLOAD FAILED</span>");
					alert("Upload could not be completed: " + response);
				}
				
				$("#progressbar").hide();
				
				$('#shutdown_pc_button').removeProp('disabled');
			});

	
	}, 'json');

}

function updateProgress(coeff) {

  var progress;

  progress = $("#progressbar").progressbar("option","value");

  if (progress < 100) {
  
      $("#progressbar").progressbar("option", "value", progress + $pos.progress_bar_interval);
      setTimeout(updateProgress, 500);
	  
  }
  
}

function print_weekly_report()
{
    if($reports.start_date.val() == '' || $reports.end_date.val() == '')
    {
		$reports.weekly_report_fields.show();
		return;
    }


    $.get('weekly_report.php', { start : $('#report_start_date').val(), end : $('#report_end_date').val() }, function(response) {

	

    });
}
