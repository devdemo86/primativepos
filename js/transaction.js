
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

function show_payment_methods()
{
	if($pos.cart.html() == '')
	{
		show_note("There are no items in the cart");
		return false;
	}

	$pos.pay_button.prop('disabled', true); //hide();
	$pos.payment_methods.show();
	$pos.customer_ticket_search.show();


}

function show_payment_specialoptions()
{

	if($pos.cart.html() == '')
	{
		show_note("There are no items in the cart");
		return false;
	}

	if($('#admin_passwd').val() == '')
	{

		authenticate('special_options');

		return false;
	}

	$pos.pause_button.prop('disabled', true);
	$pos.void_button.prop('disabled', true);	
	$pos.barcode.prop('disabled',true);
	$pos.open_transactions.prop('disabled',true);
	$pos.pay_button.prop('disabled', true); //hide();
	$pos.payment_specialoptions_dialog.show();
	$catalog.icon.hide();


}



function apply_payment_specialoptions()
{
//	is_resale : 
// freight_number : $('#freight_number'),
// discount_percentage    discount_number
// 
	var resale = 0;
	
	var discount = $pos.discount.val();
	var freight = $pos.freight.val();
	var labor = $pos.labor.val();

	if((isNaN(discount) && discount != '') || discount < 0)
	{
		show_note("Choose a value for the discount");
		return false;
	
	}
	
	if(isNaN(freight) && freight != '')
	{
		show_note("Freight is not a valid number");
		return false;
	}

	if(isNaN(labor) && labor != '')
	{
		show_note("Labor is not a valid number");
		return false;
	}
	
	/*
	if($('#discount_percentage').val() > 100 || $('#discount_percentage').val() < 0 || $('#discount_number').val() < 0)
	{
		alert("Discount % must be between 0 and 100.  Discount number must be less than subtotal.");
		return false;
	
	}
	*/


	if(discount > parseFloat($pos.subtotal.html().replace(',','')))
	{
		show_note("Discount can not exceed the subtotal!");
		return false;
	
	}
	
	// another if() to check discount_number?
	

	if($('#is_resale').prop('checked') == true)
	{
		resale = 1;
		$pos.tax.html('0.00');
	} else
	{
		resale = 0;
		var tmp_tax = parseFloat($pos.subtotal.html().replace(',','') - discount) * parseFloat($('#tax_rate').val());

		$pos.tax.html(tmp_tax.toFixed(2));
	}
	
	if(discount == '')
		$pos.discount.val('0');
	if(freight == '')
		$pos.freight.val('0');
	
		//alert($pos.tax)

	if(discount.indexOf('.') == '-1' && $pos.useAutoDecimal)
		$pos.discount.val(discount / 100);

	if(freight.indexOf('.') == '-1' && $pos.useAutoDecimal)
		$pos.freight.val(freight / 100);

	if(labor.indexOf('.') == '-1' && $pos.useAutoDecimal)
		$pos.labor.val(labor / 100);

	// save the special options given
	$.post('update_ticket.php', { 'special' : '1', 'ticket_id' : $pos.ticket_id.val(), 'discount_number' : $pos.discount.val(), 'resale' : resale, 'tax_exempt' : $pos.tax_exempt.val(), 'freight' : $pos.freight.val(), labor : $pos.labor.val(), 'subtotal' : $pos.subtotal.html().replace(',', '') }, function(data)
	{
		if(data.status)
		{
			// adjust discount
			if($pos.discount.val() > 0)
			{
				// show discount
				$pos.discount_icon.show();
				$pos.discount_display_total.html("$ -" + data.display_discount);
			
			} else
			{
				$pos.discount_icon.hide();
				$pos.discount_display_total.html('');
			}
			
			// freight
			if($pos.freight.val() > 0)
			{
				$pos.freight_icon.show();
				$pos.freight_display_total.html("$ " + data.freight);
			} else
			{
				$pos.freight_icon.hide();
				$pos.freight_display_total.html('');
			}
			
			// labor
			if($pos.labor.val() > 0)
			{
				$pos.labor_icon.show();
				$pos.labor_display_total.html("$ " + data.labor);
			} else
			{
				$pos.labor_icon.hide();
				$pos.labor_display_total.html('');
			}
		
			//var total =  parseFloat($pos.subtotal.html().replace(',','')) - parseFloat(data.discount) + parseFloat(data.freight) + parseFloat($pos.tax.html());
		

			//total = total.toFixed(2);
		

		//	$pos.subtotal.html(response.subtotal);

			$pos.tax.html(data.tax);
			$pos.display_total.html(data.total);		


			
		} else
			show_note("Problem setting special options");
	
		$catalog.icon.show();
		$pos.pause_button.removeProp('disabled');
		$pos.void_button.removeProp('disabled');
		$pos.barcode.removeProp('disabled').focus();
		$pos.open_transactions.removeProp('disabled');
		$pos.payment_specialoptions_dialog.hide();
		$pos.pay_button.removeProp('disabled');
	
	}, 'json');
	



}

function save_refund_status(status)
{
    status ? refund = 1 : refund = 0;

    $.post('update_ticket.php', { 'set_refund' : '1', 'ticket_id' : $pos.ticket_id.val(), refund : refund }, function(data)
    {
	if(!data.status)
	{
	    alert("Could not change the refund status.  Please pause the transaction and return to it and try again");

	    $pos.refund_switch.prop('checked') ? $pos.refund_switch.removeProp('checked') : $pos.refund_switch.prop('checked', true);

	}

	if(refund && data.status)
	    $pos.refund_indicator.html('- Refund');
	else if(!refund && data.status)
	    $pos.refund_indicator.html('');
    });

}

// AUTOMATICALLY SET THE DISCOUNT PERCENT WHILE TYING IN THE NUMBER BOX
// not used now since percentage is removed for now
/*
function calculate_discount_percentage()
{
	var dn = $('#discount_number');
	var dp = $('#discount_percentage');
	
	var tmp_subtotal = parseFloat($pos.subtotal.html().replace(',',''));

	if(dn.val() > 0)
	{
		// (data.subtotal - data.discount)/data.subtotal * 100;
		var new_pct = 100 - ((tmp_subtotal - dn.val())/tmp_subtotal) * 100;
		
		if(((tmp_subtotal - dn.val())/tmp_subtotal) * 100 < 1)
			new_pct = '';
		else
			new_pct = new_pct.toFixed(0);
	} else
		var new_pct = '0';
	
	dp.val(new_pct);
	

//alert
}


// AUTOMATICALLY SET THE DISCOUNT AMT WHILE TYPING IN THE PERCENTAGE BOX
function calculate_discount_number()
{
	var dn = $('#discount_number');
	var dp = $('#discount_percentage');
	
	var tmp_subtotal = parseFloat($pos.subtotal.html());

	
	//alert(tmp_subtotal);
	
	var new_number = tmp_subtotal * dp.val() * .01;
	new_number = new_number.toFixed(2);
	
	dn.val(new_number);


}
*/

function choose_pay_job_id()
{
	// need to save this to the server when selected as well as show up on the screen

	var option = document.getElementById('pay_job_id');
	
	var job_name = option.options[option.selectedIndex].text;
	var job_id = option.value;
	
	if(job_id == '')
		return false;

	// save the chosen job id
	$.post('update_ticket.php', { 'ticket_id' : $pos.ticket_id.val(), 'job_id' : job_id }, function(response)
	{
		if(response.status)
		{
		

			if(job_id > 0) // job_id = 0 indicates removal of currently set job name
			{
				$pos.customer_job_id.val(job_id);
				$pos.customer_job_display_name.html(job_name).show();
			} else
			{
				$pos.customer_job_id.val('');
				$pos.customer_job_display_name.html('');
			
			}
		} else
			show_note("Problem setting job name");
	
	}, 'json');


}

function show_payment(type)
{
	if($pos.display_total.html() == '')
	{
		show_note("There is nothing listed on the ticket");
		return false;
	}

	if($pos.customer_id.val() == '' && (type != 'cash'))
	{
		show_note("Please enter the customer's name");
		return false;
	} else if(type == 'acct' && $pos.allow_credit.val() == 1)
	{
		// only  time a variable is passed to post_transaction
		post_transaction('acct');
		
	} else if(type == 'acct')
	{
		show_note("The customer is not setup for credit");
		return false;

	}

	$pos.barcode.prop('disabled',true); // interferes with the focus

	//$('#postpayment_button').show();
	$('#payment_methods').hide();
	$('#payment_take').show();
	
	if(type == 'cash' || type == 'check')
	{
		if($pos.refund_switch.prop('checked')) // no check # for return
			$pos.cash_given.val($pos.display_total.html().replace(',', ''));
	
		$('#take_cash').show();
		$('#cash_given').focus();
	}
	
	if(type == 'check')
	{
		$('#take_check').show()
		
		if($pos.refund_switch.prop('checked')) // no check # for return
			$pos.check_no.prop('disabled', true);

		$('#take_cash').show();
		$pos.check_no.focus();
	}
		
	if(type == 'cc')
	{
		$pos.cc_trans_no.val('');
		$pos.cc_trans_no.focus();
		$('#take_cc').show();
		$('#take_cash').show();
	}
		


}

// accepts argument whether to clear the special options
function cancel_payment(clear_special)
{
	$('#payment_methods').hide();
	$('#payment_take').hide();
	$('#take_cash').hide();
	$('#take_check').hide();
	$('#take_cc').hide();

	// these 3 get hidden when doing charge
	$('#pay_cc').show();
	$('#pay_check').show();
	$('#pay_cash').show();
	
	$pos.pay_job_id.hide().html('');
	$pos.customer_ticket_search.val('Customer Name').css('color', '#999999');
	
	$dialogs.cart_item_description_dialog.hide();

	// clear inputs
	$pos.cash_given.val('');
	$pos.check_no.val('');	
	$pos.cc_trans_no.val('');
	
	// charge inputs
	$('#accts').show();
	$('#cancel_payment').show();
	
	$pos.pay_button.removeProp('disabled'); //show();

	$pos.barcode.removeProp('disabled');

	if(clear_special)
	{
		$pos.tax_exempt.val('0');

		$pos.refund_switch.removeProp('checked'); //show();
		$pos.discount_icon.hide();
		$pos.discount_display_total.html('');
		$pos.freight_icon.hide();
		$pos.freight_display_total.html('');
		$pos.labor_icon.hide();
		$pos.labor_display_total.html('');
	}

}

function post_transaction(type)
{
	var check_no = $pos.check_no.val();

	if($('#tmp_recv_by').length)
	{
	    alert("Please save the received by field by clicking the button next to it");
	    return false;
	}
	
	var amt_given_str = $pos.cash_given.val();
	amt_given_str = amt_given_str.replace(/^0+/,""); // no 0.xx

	var amt_given = 0.0;
	
	var cash_back = '';
	var refund = 0;
	var payment_type = '';
	
	var total_sale_str = $pos.display_total.html().replace(/^\s+/,""); // remove spaces
	total_sale_str = total_sale_str.replace(',', '');
	total_sale_str = total_sale_str.replace(/^0+/,"");
	
	var total_sale = parseFloat(total_sale_str);
	
	var recv_by = $pos.recv_by_name.html();
	
	//alert(total_sale);
//	alert($pos.refund_switch.prop('checked'));
	
	$pos.refund_switch.prop('checked') ? refund = '1' : refund = 0;


	if(amt_given_str.indexOf('.') == '-1' && $pos.useAutoDecimal)
		amt_given = parseFloat(amt_given_str) / 100;
	else
		amt_given = parseFloat(amt_given_str);

	// verify amount received for refund
	if(amt_given != total_sale)
	{
		if(refund == 1 && type != 'acct') // 
		{
			$pos.cancel_button.removeProp('disabled');
			show_note("Amount given must equal sale total");
			return false;
		}
	}

	if(total_sale >= 1000000)
	{
		alert("The total sale must be under $1,000,000.  If you need a higher limit you must enlarge the total field in the database table.");
		$pos.cancel_button.removeProp('disabled');
		return false;
	
	}

	// determine payment type
	if($('#take_cc').css('display') == 'inline') // CC
	{
		if(amt_given != total_sale)
		{
			show_note("Amount charged must equal sale total");
			$pos.cancel_button.removeProp('disabled');
			return false;
		}
	
		//show_note("cc");
		payment_type = 'cc';
	
	} else if($('#take_check').css('display') == 'none' && type != 'acct') // cash
	{
		// add decimals if only a two digit number'

	
		if(isNaN(amt_given))
		{
			show_note("Invalid amount");
			$pos.cancel_button.removeProp('disabled');			
			return false;
		} else if(amt_given < total_sale)
		{
			show_note("Amount given is less than the total"); // + ' ' + amt_given + ' and  ' + total_sale);
			$pos.cancel_button.removeProp('disabled');
			return false;
		
		}
		
		cash_back = amt_given - total_sale;
		cash_back = cash_back.toFixed(2)
		
		payment_type = 'cash';
		// insert into document, the cash back
		//show_note(cash_back);
	
	
	} else if($('#take_check').css('display') == 'inline') // check
	{
		if(amt_given != total_sale)
		{
			show_note("Check amount must equal sale total");
			$pos.cancel_button.removeProp('disabled');
			return false;
		}
		
		
		if(check_no == '' && !refund)
		{
			show_note("Please enter the check number");
			$pos.cancel_button.removeProp('disabled');
			return false;
		}
	
	
		if(refund)
			check_no = 0;
	
		payment_type = 'check';
	
	} else if(type == 'acct')
	{
		payment_type = 'acct';	
	}
	


	
	$.post('update_ticket.php', { 'ticket_id' : $pos.ticket_id.val(), 'amount_given' : amt_given, 'check_no' : check_no, 'cc_trans_no' : $pos.cc_trans_no.val(), 'payment_type' : payment_type, 'subtotal' : $pos.subtotal.html(), 'tax' : $pos.tax.html(), 'total' : total_sale, 'refund' : refund, recv_by : recv_by }, function(data) {
	
		if(data.status)
		{
			if(cash_back > 0)
				alert("Cash back: " + cash_back);
				
			if(refund && payment_type != 'acct') // if refund to acct, say nothing
				alert("Customer Refund: " + total_sale);
		
			show_note("Transaction Complete!");

			// print receipt
			if($pos.printReceiptChkbox.prop('checked') && payment_type != 'acct')
				print_receipt($pos.ticket_id.val(), amt_given, cash_back);

			// remove from open tickets
			$('#open_transactions option').each(function() {
			
				if($(this).val() == $('#ticket_id').val())
					$(this).remove();
			});
			
			clear_pos();
			//$('#pause_button').click(); // clear the cart and totals
		
		
		} else
			show_note("Could not finalize the transaction");
	
	
	}, 'json');

}

function print_receipt(id, amt_given, cash_back)
{
	if(!$pos.useLabelPrinter)
		return false;
	
	//var printers = dymo.label.framework.getPrinters();
	//printer_index = find_dymo_printer();

	$.get('print_receipt.php', { 'ticket_id' : id, 'payment_amount' : amt_given, 'change_returned' : cash_back }, function(data) {
	
/*
			label = dymo.label.framework.openLabelXml(data);

			label.print(printers[printer_index].name);
	*/
	});

}

function find_dymo_printer()
{
		var printers = dymo.label.framework.getPrinters();

		if (printers.length == 0)
			throw "No DYMO printers are installed. Please install the DYMO print drivers and development SDK";

		for (var j = 0; j < printers.length; ++j)
		{
			if (printers[j].printerType == "LabelWriterPrinter")
				return j;
		}
}

function add_recv_by()
{
    var tmpname = $pos.recv_by_name.html();
    
	// set name, hide box
    if($pos.recv_by_input.css('display') != 'none') 
    {
        $('#loading_recv_by').show();
    	
    	$pos.recv_by_input.prop('disabled', true);
 
    	recv_by = $pos.recv_by_input.val();

    	$.post('update_ticket.php', { ticket_id : $pos.ticket_id.val(), recv_by : recv_by }, function(response) {

    		$pos.recv_by_input.removeProp('disabled').val('').hide();
    		$('#loading_recv_by').hide();
       		$pos.recv_by_button.html('Add received by...');
    		
    		if(response.status == 0)
    		{
    			alert("Could not save received by information.");
    			return false;
    		}
    		
    		if(recv_by != '')
    		{    			 
    	    	$pos.recv_by_name.show().html(recv_by);
    		}
    		else
    			$pos.recv_by_label.hide();
    		

    	});

    } else
    {

    	//$pos.recv_by_container.prepend(ibox);
    	$pos.recv_by_button.html('Save received by');
		$pos.recv_by_name.hide();
		$pos.recv_by_label.show();
		$pos.recv_by_input.show().val(tmpname).focus();

    }   

}
