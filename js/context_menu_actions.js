function contextmenu_add_cart_item_description()
{

    // add the label of the item to confirm we right clicked the right line
    item_line = $cmenu.obj.find('td').eq(5).html();
    var barcode = $cmenu.id.val();
    var description = '';

    $pos.cart_item_description_barcode.val(barcode);
    $pos.cart_item_description_label.html(item_line);

    for(i = 0; i < $item_descriptions.length; i++)
    {
	if($item_descriptions[i].barcode == barcode)
	{
	    description = $item_descriptions[i].description;
	    break;
	}
    }

    $dialogs.cart_item_description_dialog.show();
    $pos.cart_item_description_name.val(description).focus();
  
  // alert($cmenu.id.val());

}



function contextmenu_print_receipt(rtype)
{
	var ticket_ident = $cmenu.id.val();
	
	var tmp = ticket_ident.split("_");
	
	var pticket_id = tmp[1];
	
	if(rtype == 'invoice')
	{
	
		$.get('print.php', {'ticket_id' : pticket_id, 'action' : 'print' }, 
			function(response) { }
		);
	} else if(rtype == 'receipt')
	{
		print_receipt(pticket_id, '', '');
	}

}


function contextmenu_void_receipt(auth)
{
	// warn with ticket id
	var tmp = $cmenu.id.val();
	var parts = tmp.split('_');
	var ticket_id = parts[1];

	// the id of the row is the same as the real id, however, we 
	// need to reference it by the display id
	$('#customer_tickets_list tr').each(function() {
		
		if($(this).attr('id') == 'printTicket_' + ticket_id)
			{
			display_ticket_id = $(this).find('td').eq(0).html();
			return;
			}
	})
	
	if(confirm("Void ticket " + display_ticket_id + "?"))
	{

		if(auth == null)
		    authenticate('void_ticket');
	
		var ticket_ident = $cmenu.id.val();
	
		var tmp = ticket_ident.split("_");
	
		// store this for later because auth_return() will need it
		$cmenu.void_ticket_id = tmp[1];
	}

}


