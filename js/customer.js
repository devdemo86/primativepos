
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

$(window).load(function(){

	var height = screen.height - 240 + 'px';

	$('#main_container').css('height',height);

	// finalize transaction with amount given
	$('#cash_given').keyup(function(evt) {

	if(window.event) // IE
		keynum = evt.keyCode;
	else if(evt.which) // Netscape/Firefox/Opera
		keynum = evt.which;
		
		add_decimals(this, evt, false);

		if(keynum == 13)
		{
			$(this).blur(); // prevent double submitting
			
			$pos.cancel_button.prop('disabled', true);
		// .replace(/^\s+/,"")
			var display_total = parseFloat($('#display_total').html());
			//display_total = display_total.replace(/^0+/,"");
			amt_given = $(this).val().replace(/^0+/,"");
		
			//alert(amt_given + ' ad ' + display_total);
		
			if(amt_given < display_total)
			{
				$pos.cancel_button.removeProp('disabled');
				show_note("Amount given must be at least equal to the total");
			}
			else
				post_transaction();
			
			
		}
			
	});

	$pos.class_customer_search.bind('click', function()
	{
		if($(this).val() == 'Customer Name')
			$(this).val('').css('color', '#000000');
			

			
	});

	$pos.class_customer_search.bind('blur', function()
	{
		if($(this).val() == '')
			$(this).css('color', '#999999').val('Customer Name');	

	});

	
	// advance to next box on enter key
	$('#payment_take .trans_info').keyup(function(evt) {

	if(window.event) // IE
		keynum = evt.keyCode;
	else if(evt.which) // Netscape/Firefox/Opera
		keynum = evt.which;

		if(keynum == 13)
			$('#cash_given').focus();
			
	});


});

function add_customer_form()
{
	clear_customer_inputs(); // clears inputs
	$edit_customer.save_customer_button.removeProp('disabled');
	
	// unhighlight the select box so to not confuse editing the customer selected
	$('#customer_listing option:selected').removeProp('selected');
	
	$('#customer_edit_cell').show();
	$('#customer_jobs_cell').hide();

	var input_type = '';

	$('#customer_edit_cell input').each(function() {
	
		input_type = $(this).attr('type');
		
		if(input_type == 'text')
			$(this).css('background','#ffff99');
	});
	
	$edit_customer.edit_active.prop('checked', true);
	$edit_customer.editing_customer_id.val('0');
	$edit_customer.edit_last_name.focus();


}

//
// load the customer info in the input boxes for editing
//
function edit_customer_info(id)
{
	if(id == '' || id == $edit_customer.default_customer_id) // no noname
		return false;

	// this loads the job list if the job list is loaded in the right pane
	if($edit_customer.customer_jobs_cell.css('display') != 'none')
	{
		load_customer_job_list(id);
		return false;
	}

	clear_customer_inputs();

	$('#customer_edit_cell input').each(function() {
	
		$(this).css('background','#ffffff');
	
	});




	$edit_customer.edit_active.removeProp('checked');
	$edit_customer.edit_tax_exempt.removeProp('checked');
	$edit_customer.edit_allow_credit.removeProp('checked');

	// READ the customer details
	$.get('modify_customer.php', { 'customer_details' : '', 'customer_id' : id }, function(response) {
	
		//$('#edit_last_name').val(response.last_name);
		$edit_customer.edit_last_name.val(response.last_name);
		$edit_customer.edit_first_name.val(response.first_name);
		
		$edit_customer.edit_company.val(response.company);
		$edit_customer.edit_mi.val(response.mi);
		
		$edit_customer.edit_address.val(response.address);
		$edit_customer.edit_address2.val(response.address2);
		$edit_customer.edit_city.val(response.city);		
		$edit_customer.edit_state.val(response.state);
		$edit_customer.edit_zip.val(response.zip);
		$edit_customer.phone.val(response.phone);
		$edit_customer.phone_ext.val(response.phone_ext);	
	
	
	
		$edit_customer.editing_customer_id.val(id);


		if(response.allow_credit == 1)
		{
			$edit_customer.edit_allow_credit.prop('checked', true);
			

	
		}
		if(response.tax_exempt == 1)
			$edit_customer.edit_tax_exempt.prop('checked', true);	
		if(response.active == 1)
			$edit_customer.edit_active.prop('checked', true);
		if(response.use_company == 1)
			$edit_customer.edit_listby_company.prop('checked', true);
		else
			$edit_customer.edit_listby_lastname.prop('checked', true);		
		
		$edit_customer.save_customer_button.removeProp('disabled');
		
		
		
		$edit_customer.edit_first_name.focus();
	
	}, 'json');


}


// SAVE INFO
//
function save_customer_info()
{
	var data = new Object();
	var tmp_label = '';

	($edit_customer.edit_active.prop('checked') == true) ? data.active=1 : data.active=0;
	($edit_customer.edit_tax_exempt.prop('checked') == true) ? data.tax_exempt=1 : data.tax_exempt=0;
	($edit_customer.edit_allow_credit.prop('checked') == true) ? data.allow_credit=1 : data.allow_credit=0;
	

	if($edit_customer.edit_listby_company.prop('checked'))
		data.use_company = 1;
	else
		data.use_company = 0;
	
	data.customer_id = $edit_customer.editing_customer_id.val();
	data.last_name = $edit_customer.edit_last_name.val();
	data.first_name = $edit_customer.edit_first_name.val();
	data.mi = $edit_customer.edit_mi.val();
	data.company = $edit_customer.edit_company.val();
	
	data.address = $edit_customer.edit_address.val();
	data.address2 = $edit_customer.edit_address2.val();	
	data.city = $edit_customer.edit_city.val();
	data.state = $edit_customer.edit_state.val();
	data.zip = $edit_customer.edit_zip.val();
	
	data.phone = $edit_customer.phone.val();
	data.phone_ext = $edit_customer.phone_ext.val();

	// regex the names, basically to avoid spaces
	var name_regex = /^[A-Za-z]+/;
	var company_regex = /^[A-Za-z0-9]/;
	
	var tmpname = data.last_name;
	var tmpco = data.company;

	if((!tmpname.match(name_regex) && data.use_company == 0 ) || (!tmpco.match(company_regex) && data.use_company == 1))
	{
		alert("You must give the customer a name");
		return false;
	}


	$.post('modify_customer.php', data, function(response) {
	
		if(response.status)
		{
			show_note("Information Saved");
		
			// if the current customer has an open ticket, reload the ticket
			if($pos.customer_id.val() == data.customer_id)
			{
				var tmpticket = $pos.ticket_id.val();
			
				clear_pos(tmpticket);
				chg_ticket(tmpticket);
				//$pos.allow_credit = data.allow_credit;
				
				//$pos.tax_exempt = data.tax_exempt
			
			
			}
			
			if(response.reorder)
				load_customer_list();
			
		}
		else
			show_note("Error!");
			
		// set editing id in the case of a new customer
		// and add to select box and set selected
		if(response.customer_id == 0) // new customer
		{
			var listing_obj = document.getElementById('customer_listing');
		
			if(response.company == '')
				tmp_label = data.last_name + ", " + data.first_name;
			else
				tmp_label = data.company;
		
			var new_option = "<option value=\"" + response.customer_id + "\">" + tmp_label + "</option>";
		
			$edit_customer.customer_sel.append(new_option);			

			
			listing_obj.selectedIndex = listing_obj.length - 1;
		
		}
		
		$edit_customer.editing_customer_id.val(response.customer_id);
	
	});
	
	
	$('#customer_edit_cell input').each(function() {
	
		$(this).css('background','#ffffff');
	
	});
	
	//alert(response.active);


}

function customerdialog(options)
{
	if(options == 'reload')
	{
		load_customer_list(options);
		return false;
	}
	
	$('#customer_listing option:selected').removeProp('selected');

		//$pos.open_transactions.prop('disabled',true);
		$pos.barcode.prop('disabled',true);
		$('#customer_jobs_cell').hide();
		$('#customer_edit_cell').show();

		$('.posdlg').hide();
		
		$edit_customer.editing_customer_id.val('');

		document.getElementById('customer_dialog').style.display = 'block';

		if($edit_customer.editing_customer_id.val() == '')
			$edit_customer.save_customer_button.prop('disabled',true);
	

		if($edit_customer.list_is_loaded == 0 || options == 'reload')
			load_customer_list(options);
		
		//else
		//	document.getElementById('customer_dialog').style.display = 'block';
		


}

function customer_jobs_dialog()
{
	$('.posdlg').hide();

	document.getElementById('customer_dialog').style.display = 'block';

	var cur_cust_id = $edit_customer.customer_sel.val();

	if($edit_customer.list_is_loaded == 0) // || options == 'reload')
		load_customer_list();
	
	$('#customer_edit_cell').hide();
	$('#customer_jobs_cell').show();
	
	if(cur_cust_id > 0)
		load_customer_job_list(cur_cust_id);


}

// edit customer jobs available
function load_customer_job_list(customer_id)
{
	$edit_customer.customer_job_edit.val('').prop('disabled', true);
	$edit_customer.customer_job_edit.css('background', '#ffffff');

	$.get('modify_customer.php', { 'get_customer_jobs' : '', 'job_cust_id' : customer_id }, function(response) 
	{
		customer_html = "<option value=\"\"> - Choose Job -</option>";

				
		for(i = 0; i < response.jobs.length; i++)
		{
			customer_html += "<option value=\"" + response.jobs[i].id + "\">" + response.jobs[i].name + "</option>";
		}

		customer_html += "<option value=\"\" disabled=\"disabled\"></option>";
		customer_html += "<option value=\"new\">Add New . . .</option>";
					
		$edit_customer.customer_job_listing.html(customer_html);

	
	}, 'json');

}

function load_edit_job()
{
	var option = document.getElementById('customer_job_listing');

	if(option.value == '')
		return;
	else if(option.value == 'new')
	{
		$edit_customer.customer_job_edit.css('background','#ffff99').removeProp('disabled').val('');

	} else
	{
		$edit_customer.customer_job_edit.val(option.options[option.selectedIndex].text);
		$edit_customer.customer_job_edit.css('background','#ffffff').removeProp('disabled');

	}

	$edit_customer.customer_job_edit.focus();

}

function save_job_edit()
{
	var job_name = $edit_customer.customer_job_edit.val();

	var cust_id = $edit_customer.customer_sel.val();
	
	if(job_name == 'No Job Specified')
	{
		alert("This name cannot be used as a job name");
		return false;
	}
	
	$.post('modify_customer.php', { customer_id : cust_id, job_name : job_name, job_id : $edit_customer.customer_job_listing.val() }, function(response) {
	
		if(response.status)
		{

			show_note("Job Saved");
			
			// reload the list
			load_customer_job_list(cust_id);
		
			$edit_customer.customer_job_edit.val('').css('background','#ffffff');
		}
	
	
	}, 'json');

}

function close_customerdialog()
{
	clear_customer_inputs();
	document.getElementById('customer_dialog').style.display = 'none';
//	$pos.open_transactions.removeProp('disabled');
	$pos.barcode.removeProp('disabled');
	
	$edit_customer.editing_customer_id.val('');

	// clear the value give for a new entry, but other IDs can stay when we close the screen
	if($edit_customer.editing_customer_id.val() == '0')
		$edit_customer.editing_customer_id.val('');

	// make inputs white if yellowed from clicking "add new customer"
	$('#customer_edit_cell input').each(function() {
	
		input_type = $(this).attr('type');	
		
		if(input_type == 'text')
			$(this).css('background','#ffffff');
	});

}

function clear_customer_inputs() 
{
	$edit_customer.edit_last_name.val('');
	$edit_customer.edit_first_name.val('');
		
	$edit_customer.edit_company.val('');
	$edit_customer.edit_mi.val('');
		
	$edit_customer.edit_address.val('');
	$edit_customer.edit_address2.val('');
	$edit_customer.edit_city.val('');		
	$edit_customer.edit_state.val('');
	$edit_customer.edit_zip.val('');

	$edit_customer.edit_active.removeProp('checked');
	$edit_customer.edit_tax_exempt.removeProp('checked');
	$edit_customer.edit_allow_credit.removeProp('checked');
	


}

function load_customer_list(options)
{
		$edit_customer.customer_sel.html("<option value=\"\">Loading . . .</option>");

			// toggle
			if($edit_customer.show_inactive == 0 && options == 'reload')
				$edit_customer.show_inactive = 1;
			else if($edit_customer.show_inactive == 1 && options == 'reload')
				$edit_customer.show_inactive = 0; 
		
			$.get('modify_customer.php', { 'get_customers' : '', 'show_inactive' : $edit_customer.show_inactive }, function(response) 
			{
				document.getElementById('customer_dialog').style.display = 'block';
				
				customer_html = '';

			//	customer_html = "<option value=\"\">&ndash; Choose Customer &ndash;</option>";
			//	customer_html += "<option value=\"\" disabled=\"disabled\"></option>";
				
				for(i = 0; i < response.customers.length; i++)
				{

						customer_html += "<option value=\"" + response.customers[i].id + "\">" + response.customers[i].c + "</option>";
						

				}
					
				$edit_customer.customer_sel.html(customer_html);
				$edit_customer.customer_sel.show();			

				//$edit_customer.customer_sel.val($edit_customer.editing_customer_id.val());
				$edit_customer.list_is_loaded = 1;
	
			}, 'json');

}