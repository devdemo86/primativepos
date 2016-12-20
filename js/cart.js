
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

// EDITABLE QTY FUNCTIONS
//
// change a qty input box back to a text qty
function restore_qty()
{

	$editable_item.cur_cell.html($editable_item.cur_qty);
	$editable_item.cur_item_id = '';
	$editable_item.cur_qty = '';
	$editable_item.cur_cell.removeAttr('id');
	$editable_item.cur_cell = '';
	$editable_item.edit_contents = '';


}

function edit_qty(cell_obj, item_id)
{
	// check that the cell is not a number, 
	//item id is not currently stored, and this is only editable field open
	if(isNaN($editable_item.cur_qty) || $editable_item.cur_item_id != '' || $editable_price.cur_item_id != '')
		return false;

	$editable_item.cur_qty = cell_obj.html();

	cell_obj.attr('id', 'cur_edit_cell'); // give cell an ID for keeping track of

	$editable_item.cur_cell = cell_obj; // store this for reference later

	$editable_item.edit_contents = "<input id=\"cur_edit_item\" type=\"text\" style=\"width: 20px\" value=\"" + $editable_item.cur_qty + "\" onkeyup=\"check_update_qty(event, this.value)\" />";

	cell_obj.html($editable_item.edit_contents);
	
	$('#cur_edit_item').focus().select();

	$editable_item.cur_item_id = item_id;

}

// update the cell qty we are editing on enter key press
function check_update_qty(evt, qty)
{
	if(window.event) // IE
		keynum = evt.keyCode;
	else if(evt.which) // Netscape/Firefox/Opera
		keynum = evt.which;

		if(keynum == 13)
		{
			//alert('update');
			// do update
			
			if(qty >= 1)
			{
				$editable_item.cur_qty = qty;
			
				modify_item($editable_item.cur_item_id, 'edit', qty)
			} else
			{

				show_note("Cannot set quantity less than 1.  Use the remove item button or adjust the price.");
			}
			
			restore_qty();
			$pos.barcode.focus();
		}
}

// remove, increment, decrement item in cart
function modify_item(item_id, action, new_qty)
{

	//show_note(item_id + ' and ' + action);
	$dialogs.cart_item_description_dialog.hide(); // remove in case of del
	qty = 0;

	stop_flag = 0;
//alert("look for" + item_id);return false;
	$('#cart tr').each(function() {

		// look at first cell for item_id (delete button is in this space)
	
		if($(this).find("td:first").html().indexOf("modify_item(" + item_id + ",", 0) != '-1')
		{
			// delete is easy
			if(action == 'del')
			{
			
				$(this).remove();
				restripe_rows('cart');

				// remove item descriptions
			        for(i = 0; i < $item_descriptions.length; i++)
    			        {
				    if($item_descriptions[i].barcode == item_id)
				    {
	    			        $item_descriptions[i].description = '';
					break;
				    }
			        }
				
				return false;
			}
	
			qty = $(this).find("td").eq(1).html(); //.eq(0).html());
			
			//alert($(this).find("td:first").html());return false;
			
			if(action == 'incr')
				qty++;
			else if(action == 'decr' && qty > 1)
				qty--;
			else if(action == 'edit')
				qty = new_qty;
			else
				stop_flag = 1; // prevent decrementing to zero
			
			$(this).find("td").eq(1).html(qty); // set new qty
			
			amt = $(this).find("td").eq(6).html();
			
			tmp = amt*qty;
			
			$(this).find("td").eq(7).html(tmp.toFixed(2)) // set new amt
			
			return false; // found it now quit

		}
	
	});

	if(stop_flag == 1)
		return false;


	// request to modify
	$.post('lookup_item.php', { 'ticket_id' : $pos.ticket_id.val(), 'item_id' : item_id , 'modify_action' : action, 'update_qty' : qty, 'tax_exempt' : $pos.tax_exempt.val(), discount : $pos.discount.val(), freight : $pos.freight.val(), labor : $pos.labor.val() }, function(response)
		{

			if(!response.status)
				alert("Problem modifying the item.  Please restart the transaction");
			
			$pos.subtotal.html(response.subtotal);
			$pos.tax.html(response.tax);
			$pos.display_total.html(response.total);
			
			if(response.display_discount != '0.00')
				$pos.discount_display_total.html('$ -' + response.display_discount);

			//apply_payment_specialoptions()

			
		}, 'json');
	
	

}

function modify_item_price(item_id, new_price)
{

	$.post('lookup_item.php', { 'ticket_id' : $pos.ticket_id.val(), 'item_id' : item_id , 'change_price' : '1', 'new_price' : new_price, 'tax_exempt' : $pos.tax_exempt.val(), 'discount' : $pos.discount.val(), freight : $pos.freight.val(), labor : $pos.labor.val()   }, function(response)
	{
		if(!response.status)
		{
			show_note("Could not update price");
			return false;
		}
		else
		{

			$('#cart tr').each(function() {
	
				if($(this).find("td:first").html().indexOf("modify_item(" + item_id + ",", 0) != '-1')
				{
					var qty = $(this).find("td").eq(1).html();
					var amt = new_price*qty;
					
			
					$(this).find("td").eq(7).html(amt.toFixed(2)) // set new amt

					return false;
				}

			});
			
			$pos.subtotal.html(response.subtotal);
			$pos.tax.html(response.tax);
			$pos.display_total.html(response.total);
			$pos.discount_display_total.html(response.display_discount);


		}

	});


}

function restripe_rows(table_id)
{
	var i = 0;
	
	var selector = '#' + table_id + ' tr';

	$(selector).each(function()
	{
	
		if(i%2)
			bgcolor = '#dddddd';
		else
			bgcolor = '#ffffff';

		// don't want to stripe the inner increment/decrement table
		if($(this).attr('class') != 'nostripe')
			$(this).css('background', bgcolor);	
	
		i++;
	});

}

row_counter = 0; // used for determining checkered rows

function add_to_cart(subtotal, cart)
{

	$pos.cart.html('');

	tmpcart = '';
	zeroItemWarning = 0; // warn if an item cost is zero

	for(i = 0; i < cart.length; i++)
	{

		if(i%2)
			bgcolor = '#dddddd';
		else
			bgcolor = '#ffffff';

		tmpcart += "<tr style=\"background: " + bgcolor + "\" id=\"" + cart[i].item_id + "\"><td><img onclick=\"modify_item(" + cart[i].item_id + ", 'del', $(this))\" style=\"cursor: pointer; width: 12px; height: 12px\" src=\"img/del.png\" /></td><td class=\"qty\" onclick=\"edit_qty($(this), " + cart[i].item_id + ")\">" + cart[i].qty + "</td><td><table class=\"incrctrl\"><tr class=\"nostripe\"><td><img onclick=\"modify_item(" + cart[i].item_id + ", 'incr', $(this))\" style=\"cursor: pointer;\" src=\"img/up.png\"></td></tr><tr class=\"nostripe\"><td><img onclick=\"modify_item(" + cart[i].item_id + ", 'decr', $(this))\" style=\"cursor: pointer;\" src=\"img/down.png\"></td></tr></table></td><td style=\"padding-left: 60px; width: 300px; cursor: default\">" + cart[i].name + "</td><td class =\"qty\" onclick=\"edit_price($(this), " + cart[i].item_id + ")\">" + cart[i].price + "</td><td class=\"qty\">" + cart[i].amount + "</td></tr>";
		
		if(cart[i].amount == 0 && i == 0)
			zeroItemWarning++;
		

	}

	//$pos.cart.append(row);
	$pos.cart.html(tmpcart);
	
	$('#cart tr').each(function() {
	// this is may need to be applied only to the td of the item name, not the whole row...
		
		$(this).vscontext({menuBlock: 'vs-context-menu', menuType : 'transaction_items'});

	});

	if(zeroItemWarning > 0)
		alert("There is an item priced at zero in the cart.");

	//
	//var scroll_distance = document.documentElement.scrollTop;
//alert(scroll_distance);
	//$pos.cart.top = scroll_distance;
	//$pos.cart_container.scrollTop = $pos.cart_container.scroll;
///	.,,malert($pos.cart_container.scroll);
		
}



// EDITABLE PRICE FUNCTIONS
//
// change a qty input box back to a text qty
function restore_price()
{

	$editable_price.cur_cell.html($editable_price.cur_price);
	$editable_price.cur_item_id = '';
	$editable_price.cur_price = '';
	$editable_price.cur_cell.removeAttr('id');
	$editable_price.cur_cell = '';
	$editable_price.edit_contents = '';


}

//
// edit_price of items in the cart
//
function edit_price(cell_obj, item_id)
{
	// first time, just store the cell and item_id
	if($('#admin_passwd').val() == '')
	{
		$editable_price.pre_auth_cell = cell_obj;
		$editable_price.pre_auth_item_id = item_id;
	
		//$('#auth_confirm').click("");
	
		authenticate('editable_price');

		//$('#auth_confirm').click(edit_price(cell_obj, item_id, $('admin_passwd').val()));
		return false;
	}


			
		// check that the cell is not a number,
		// item id is not current stored, and this is only editable field open
	if(isNaN($editable_price.cur_price) || $editable_price.cur_item_id != '' || $editable_item.cur_item_id != '')
		return false;

	$editable_price.cur_price = cell_obj.html();
	
	cell_obj.attr('id', 'cur_edit_cell_price'); // give cell an ID for keeping track of

	$editable_price.cur_cell = cell_obj; // store this for reference later

	$editable_price.edit_contents = "<input id=\"cur_edit_item_price\" maxlength=\"11\" type=\"text\" style=\"width: 50px\" value=\"" + $editable_price.cur_price + "\" onkeyup=\"add_decimals(this, event, false); check_update_price(event, this.value)\" />";

	cell_obj.html($editable_price.edit_contents);
	
	$('#cur_edit_item_price').focus().select();

	$editable_price.cur_item_id = item_id;			
			

	
}

// update the cell qty we are editing on enter key press
function check_update_price(evt, price)
{
	if(window.event) // IE
		keynum = evt.keyCode;
	else if(evt.which) // Firefox/Opera
		keynum = evt.which;

	if(keynum == 13)
	{

		// so event doesn't propogate on 'enter key'
		$('#cur_edit_item_price').blur();

		if(isNaN(price))
		{
			show_note("Not a valid number");
			restore_price();
			return false;
		
		}

		if(price.indexOf('.') == '-1' && $pos.useAutoDecimal)
			price /= 100;

		
		price = parseFloat(price);

		//var password = prompt("Enter the administrator password");
		//if($('#auth_dialog').css('display') == 'none')
		//	alert($('#auth_dialog').css('display'));


		$editable_price.cur_price = price.toFixed(2);
			
		modify_item_price($editable_price.cur_item_id, $editable_price.cur_price) 


		restore_price();
		$pos.barcode.focus();
			


	}
}

function save_cart_item_description()
{
	$pos.save_cart_item_description_button.prop('disabled', true);

    var barcode = $pos.cart_item_description_barcode.val();
    var description = $pos.cart_item_description_name.val();

    $.post('update_ticket.php', { item_description : 1, ticket_id : $pos.ticket_id.val(), barcode : barcode, description : description }, function(response) {



	if(response.status)
	{
	    show_note("Description saved");
	    $dialogs.cart_item_description_dialog.hide();
	  //  $dialogs.cart_item_description_name.hide();

	    var found = 0; // see if we need to store this description entry or if it already exists

	    for(i = 0; i < $item_descriptions.length; i++)
 	    {
		if($item_descriptions[i].barcode == barcode)
		{
		    $item_descriptions[i].description = description;
		    found = 1;
		    break;
		}
    	     }

	    if(!found)
		$item_descriptions.push({'barcode' : barcode, 'description' : description});

	} else
	    alert("Could not save the description");

	$pos.cart_item_description_name.val('');
	$pos.save_cart_item_description_button.removeProp('disabled');

    });

}
