/**
 * Error and info notificitions			 
 * some global custom events that allow us to display notifications and error messages
 *
 * @author Mark Guinn
 * @package sstools
 */
jQuery(document).bind('show_alert', function(event, msg, type){
	if (!msg) return;

	var el = jQuery('<li></li>')
		.addClass(type)
		.html(msg)
		.click(function(){
			jQuery(this).slideUp(500, function(){
				var me = jQuery(this);
				var p = me.parent();
				if (p.children().length == 1) p.animate({marginTop:-100}, 500, 'swing');
				me.remove();
			});
		})
	;
	
	jQuery('.flash_messages')		
		.css('margin-top', -100)
		.show()
		.append(el)
		.animate({marginTop:0}, 500, 'swing')
	;
				
	setTimeout(function(){ el.trigger('click'); }, 10000);
});
