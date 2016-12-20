/**
 *  jQuery Very Simple Context Menu Plugin
 *  @requires jQuery v1.3 or 1.4
 *  http://intekhabrizvi.wordpress.com/
 *
 *  Copyright (c)  Intekhab A Rizvi (intekhabrizvi.wordpress.com)
 *  Licensed under GPL licenses:
 *  http://www.gnu.org/licenses/gpl.html
 *
 *  Version: 1.1
 *  Dated : 28-Jan-2010
 *  Version 1.1 : 2-Feb-2010 : Some Code Improvment
 */

(function($){
    jQuery.fn.vscontext = function(options){
        var defaults = {
            menuBlock: null,
            offsetX : 8,
            offsetY : 8,
            speed : 'slow',
			menuType : null
        };
        
		var options = $.extend(defaults, options);
        var menu_item = '.' + options.menuBlock;
		

        return this.each(function(){
		
            $(this).bind("contextmenu",function(e){
					return false;
			});
        
			$(this).mousedown(function(e){

						// since i don't know how many places i will place the context menu...
						
						// we'll label an ID for each row and get the ID here...
						// ...next put the id in the hidden input of the context menu along
						// with the type of action...here I did the transaction item listing as 
						// trans_listing_[id] (e.g. trans_listing_47)

						if(e.button == "2")
						{
							
						//	if($(this).hasClass('even'))
						//	    $cmenu.row_shade = 1;
						
						// permanently hightlight the row
						/*	$(this).css('background','#0099ff');
						
							$cmenu.row = $(this); // store for changing later
						*/

							$('#context_menu_id').val($(this).attr('id'));
							//$cmenu.id = $(this).attr('id');
							$cmenu.obj = $(this);
							//$('#context_menu_action').val(options.actionMethod);

							var offsetX = e.pageX  + options.offsetX;
							var offsetY = e.pageY + options.offsetY;
                            
                        			        //$(menu_item).show();
							
							// hide/show certain parts of the menu
							if(options.menuType == 'balances')
							{
								$('.ticket_cmenu_action').hide();
								$('.balances_cmenu_action').show();			
								$('.items_cmenu_action').hide();
							} else if(options.menuType == 'tickets')
							{
								$('.balances_cmenu_action').hide();
								$('.ticket_cmenu_action').show();
								$('.items_cmenu_action').hide();
							} else if(options.menuType == 'transaction_items')
							{
								$('.balances_cmenu_action').hide();
								$('.ticket_cmenu_action').hide();
								$('.items_cmenu_action').show();
							}

                            $(menu_item).css('display','block');
                            $(menu_item).css('top',offsetY);
                            $(menu_item).css('left',offsetX);
						}else {
                            $(menu_item).hide();

			    // restore the open row's striping
			
			// $cmenu.row.css({'background-color':'#ffffff','color' : '#000000'});
/*
			    if($cmenu.row_shade)
			        $cmenu.row.addClass('even').css({'background-color':'#dddddd','color' : '#000000'});
				
			    $cmenu.row_shade = 0;
			    $cmenu.prev_row = $cmenu.id.val();
			    $cmenu.row = false; // unset
			    $cmenu.id.val('');
			*/
                        }
			});
            
			// hides the menu if hovering off of it
		//	$(menu_item).hover(function(){}, function(){$(menu_item).hide();})
                
        });
    };
})(jQuery);
