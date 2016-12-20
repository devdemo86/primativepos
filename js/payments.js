
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

function recv_payment_screen()
{
	$('.posdlg').hide();

	$('#recv_payment_screen').show();
	//$pos.open_transactions.prop('disabled',true);
	$pos.pause_button.prop('disabled',true);
	$pos.pay_button.prop('disabled',true);

	$payments.payment_recv_hour.val($clock.currentHours);
	$payments.payment_recv_minute.val($clock.currentMinutes);
	$payments.payment_recv_ampm.val($clock.ampm);

	$pos.barcode.prop('disabled',true);
	$payments.payment_recv_search_name.focus();

}

function close_recv_payments()
{
	$('#recv_payment_screen').hide();
	$payments.payment_recv_search_name.show().val('');
	$payments.payment_recv_display_name.html('');
	$payments.payment_recv_display_balance.html('');
	$payments.payment_recv_customer_id.val('');
	$pos.open_transactions.removeProp('disabled');
	$pos.pause_button.removeProp('disabled');
	$pos.pay_button.removeProp('disabled');	
	$payments.payment_recv_method.val('');
	$payments.payment_recv_extra_info.val('');
	$payments.payment_recv_amt.val('');
	$pos.barcode.removeProp('disabled');


}


$(window).load(function(){

	$payments.payment_recv_customer_id.val('')

	$( "#payment_recv_search_name" ).autocomplete({ minLength: 3, delay : 1200, source: 
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
				$('.ui-autocomplete li').css('font-size','60%');
				//$('.ui-autocomplete ul').css('height','100px');
				
		}, select: function( event, ui ) {


				$payments.payment_recv_customer_id.val(ui.item.value);
				$payments.payment_recv_search_name.val(ui.item.label);

				$payments.payment_recv_search_name.hide();

				$payments.payment_recv_display_name.html(ui.item.label);

				//ui.item.value = ''; // replace ID var with label so it goes in the box instead

				$.get('modify_customer.php', { 'get_customer_jobs' : '1', 'show_balance' : 1, 'job_cust_id' : ui.item.value }, function(response) 
				{
					var payment_job_html = "<option value=\"0\">- Choose Job -</option>";

				
					for(i = 0; i < response.jobs.length; i++)
					{
						payment_job_html += "<option value=\"" + response.jobs[i].id + "\">" + response.jobs[i].name + "</option>";
					}
					
					$payments.payment_recv_display_balance.html("<i>Balance:</i> &nbsp; $ " + response.cur_balance + "");
					
					$payments.payment_recv_job_id.html(payment_job_html);					
					$payments.payment_recv_job_id.show();
				}, 'json');
				
		
			//alert($payments.payment_recv_search_name.val());

		}
		
	});
	
});


function save_payment_recv()
{

    var dateregex=/((0[0-9])|(1[012]))\/((0[1-9])|([12][0-9])|(3[01]))\/[0-9]{2}/;
	
	var date_paid = $payments.payment_recv_date.val();
	var pay_type = $payments.payment_recv_method.val();
	var extra_info = $payments.payment_recv_extra_info.val();
	var amount = $payments.payment_recv_amt.val();
	var customer_id = $payments.payment_recv_customer_id.val();

	amount = amount.replace(/^0+/,""); // remove preceding zeros if given
	amount = amount.replace(',','');

	if(amount.indexOf('.') == '-1' && $pos.useAutoDecimal)
		amount /= 100;

	if(!date_paid.match(dateregex))
	{
		show_note("Date must be in format: mm/dd/yy");
		return false;
	}
	
	if(pay_type == '')
	{
		show_note("Please enter the type of payment");
		return false;
	}
	
	if(customer_id == '')
	{
		show_note("Please choose a customer");
		return false;
	}

/*
	if(amount.length - amount.indexOf('.') != 3 || isNaN(amount))
	{
		show_note("Price is not in correct format.  Must include 2 digits beyond the decimal point");
		return false;
	}*/

	tmp_min = $payments.payment_recv_minute.val().toString();
	if(tmp_min.length != 2 || isNaN($payments.payment_recv_minute.val()))
	{
	    show_note("Minutes has not been given");
	    return false;
	}

	date_paid = date_paid + ' ' + $payments.payment_recv_hour.val() + ':' + $payments.payment_recv_minute.val() + ' ' + $payments.payment_recv_ampm.val();

	
	$.post('save_payment.php', { 'customer_id' : customer_id, 'date' : date_paid, 'pay_type' : pay_type, 'amount' : amount, 'extra_info' : extra_info, 'job_id' : $payments.payment_recv_job_id.val() }, function(response) {
	
		if(response.status)
		{
			show_note("Payment Received");
			
			$payments.payment_recv_amt.val('');
			
			/*
			cash_back = amt_given - total_sale;
			cash_back = cash_back.toFixed(2)
			if(cash_back > 0)
				alert("Cash back: " + cash_back);
			*/
			
			print_receipt(response.ticket_id, amount, '');
			
			close_recv_payments();
			
		} else
			show_note("Could not save payment.  Verify the form was completed correctly.");
	});
	


}
