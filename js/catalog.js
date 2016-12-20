
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



function show_catalog()
{
	$('.posdlg').hide();

	$pos.barcode.prop('disabled', true);
	$catalog.dialog.show();
	$catalog.search_name.select().focus();

}

function close_catalog()
{
	$pos.barcode.removeProp('disabled');
	$catalog.dialog.hide();
	auth_cancel();
}

t= '';

function search_catalog(v)
{

	$catalog.open_record = 0; // unset the open records
	var use_ws = 0; // use wholesaler, can switch data ranges in the database if desired (catalog.php)
	

	if(v != 'go') // wait for additional input before quering server
	{
		clearTimeout(t);
		t = setTimeout("search_catalog('go')", 500);
		return false;
	}

	if($catalog.search_name.val().length < 3 && v != 'go')
		return false;

	($catalog.use_ws.prop('checked') == true) ? use_ws = 1 : use_ws = 0;


	$.get('catalog.php', { 'use_ws' : use_ws, 'q' : $catalog.search_name.val() }, function(response) {

		var rows = '';

		for(i = 0; i < response.length; i++)
		{
			if(i%2 == 0)
				stripe = " style=\"background: #dddddd\"";
			else
				stripe = '';
		
			rows += "<tr id=\"ct_" + response[i].barcode + "\"" + stripe + "><td style=\"width: 50px; padding-left: 10px\"><button type=\"button\" onclick=\"edit_cat_row($(this), " + response[i].barcode + ")\">Edit</button></td><td style=\"text-align: center; width: 65px\">" + response[i].barcode + "</td><td style=\"width: 200px; padding-left: 25px\">" + response[i].name + "</td><td style=\"width: 170px; padding-left: 30px\">" + response[i].vendor + "</td><td style=\"width: 110px; padding-left: 15px\">" + response[i].sku + "</td><td style=\"width: 140px; padding-left: 20px\">" + response[i].mft_id + "</td><td style=\"width: 58px; text-align: right; padding-right: 15px\">" + response[i].price + "</td><td style=\"width: 46px; text-align: right; padding-right: 5px\">" + response[i].qty + "</td></tr>";
		
		}

		$catalog.catalog_table.html(rows);
	
		//$('#catalog_table td').each(function() {
		
	//		$(this).css('border','1px solid #000000');
		
	//	});
	
		/*
			$("#catalog_table tr").hover(
			function()
			{
				$(this).addClass("highlight");
			},
			function()
			{			
				$(this).removeClass("highlight");
			});
		*/
	
		//$('#catalog_table td').each(function() { $(this).css('border', '1px solid #000000'); });
		//$('#catalog_headings span').each(function() { $(this).css('border', '1px solid #000000'); });
	
	}, 'json');
	

}

function add_catalog_item()
{
	var pos = $('#catalog_icon').offset(); // get position to place dialog at

	//var lpos = pos.left + 'px';
	//var tpos = pos.top - 35 + 'px';

	$catalog.add_item_dialog.show(); // css('left', lpos).css('top', tpos).
	

}

function save_new_item()
{
	var item_name = $catalog.new_item_name.val();
	var item_price = $catalog.new_item_price.val();
	var item_skn = $catalog.new_item_skn.val();
	var add_to_cart = 0;

	//var category = ;
	($('#new_item_to_cart').prop('checked') == true) ? add_to_cart=1 : add_to_cart=0;

	if(item_name == '' || isNaN(item_price))
	{
		show_note("Please enter the item name and price");
		return false;
	
	}
	
	$.post('catalog.php', { 'item_name' : item_name, 'item_price' : item_price, 'item_skn' : item_skn }, function(response) {
	
		if(response.new_id > 0)
		{
			show_note("Item Added");
		
			if(add_to_cart == 1)
			{
				$pos.barcode.val(response.new_id);
				lookup_item();
			}
			
			$catalog.add_item_dialog.hide();
			$catalog.new_item_price.val('');
			$catalog.new_item_name.val('');
		
		} else
			show_note("Could not add the item");
	
		if(response.product_id_conflict)
			alert("A duplicate item exists under this UPC");
	
	});
	

}

function edit_cat_row(button_obj, barcode)
{
	// save an opened row or open a row for editing
	// 
	var save = 0;
	
	if($('#admin_passwd').val() == '' && $catalog.open_record != 1)
	{
		$catalog.pre_barcode = barcode;
		$catalog.pre_auth_button_obj = button_obj;
	
		authenticate('edit_catalog');

		return false;
	}


	if(button_obj.html() == 'Save')
	{
	
		data = new Object();
	
		data.barcode = $('#cat_barcode').val();
		data.name = $('#cat_name').val();
		data.vendor = $('#cat_vendor').val();
		data.sku = $('#cat_sku').val();
		data.manufacturer_id = $('#cat_manufacturer_id').val();
		data.price = $('#cat_price').val();
		data.qty = $('#cat_qty').val();
		data.edit_item = 1;
		
		if(data.price != '')
		{

			if(!(data.price.indexOf('.') == data.price.length - 1 - 2))
			{
				alert("Please include the decimal and cents for the item");
				return false;
			}
		
			if(isNaN(data.price))
			{
				alert("Please enter a valid number with decimal point and cents");
				return false;
			}
			
			if(data.price.indexOf('.') == 0) // add preceding zero to decimal
				$('#cat_price').val('0' + data.price);
		
		}
		
		$.post('catalog.php', data, function(response) {
		
			if(response.status == 0)
				show_note("Could not save item!");
			else
			{	// update values that are formated on the server

				//$('#cat_sku').val(response.skn);
				//$('#cat_price').val(response.price);
			
			}
		
			if(response.product_id_conflict)
				alert("A duplicate item exists under this UPC");
		
		}, 'json');
	
		save = 1;
	
		$catalog.open_record = 0;
	}



	if($catalog.open_record == 1)
		return false;

	if(!save)
		$catalog.open_record = 1;

	var row_id = button_obj.parent().parent().attr('id');
	
	if(!save)
		button_obj.html('Save')
	else
	{
		button_obj.html('Edit');

	}
	

	cell = $('#' + row_id).find("td").eq(1);

	if(save)
		cell.html($('#cat_barcode').val());
	else
	{
		tmp = cell.html();
		cell.html("<input style=\"width: 90%\" type=\"text\" id=\"cat_barcode\" />");
		$('#cat_barcode').val(tmp).prop('disabled', true);
	}

	cell = $('#' + row_id).find("td").eq(2);
	
	if(save)
		cell.html($('#cat_name').val());
	else
	{
		tmp = cell.html();
		cell.html("<input style=\"width: 90%\" type=\"text\" id=\"cat_name\" maxlength=\"30\" />");
		$('#cat_name').val(tmp);
	}
		
	cell = $('#' + row_id).find("td").eq(3);
	
	if(save)
		cell.html($('#cat_vendor').val());
	else
	{
		tmp = cell.html();
		cell.html("<input style=\"width: 90%\" type=\"text\" id=\"cat_vendor\" maxlength=\"20\" />");
		$('#cat_vendor').val(tmp);
	}

	cell = $('#' + row_id).find("td").eq(4);

	if(save)
		cell.html($('#cat_sku').val());
	else
	{
		tmp = cell.html(); // product_id in the database
		cell.html("<input style=\"width: 90%\" type=\"text\" id=\"cat_sku\" maxlength=\"24\" />");
		$('#cat_sku').val(tmp);
	}

	cell = $('#' + row_id).find("td").eq(5);
	
	if(save)
		cell.html($('#cat_manufacturer_id').val());
	else
	{
		tmp = cell.html();
		cell.html("<input style=\"width: 90%\" type=\"text\" id=\"cat_manufacturer_id\" maxlength=\"24\" />");
		$('#cat_manufacturer_id').val(tmp);
	}

	cell = $('#' + row_id).find("td").eq(6);
	
	if(save)
		cell.html($('#cat_price').val());
	else
	{
		tmp = cell.html();
		cell.html("<input style=\"text-align: right\" size=\"5\" type=\"text\" id=\"cat_price\" maxlength=\"10\" />");
		$('#cat_price').val(tmp);
	}

	cell = $('#' + row_id).find("td").eq(7);

	if(save)
		cell.html($('#cat_qty').val());
	else
	{
		tmp = cell.html();
		cell.html("<input size=\"3\" style=\"text-align: right\" type=\"text\" id=\"cat_qty\" maxlength=\"11\" />");
		$('#cat_qty').val(tmp);
	}

}