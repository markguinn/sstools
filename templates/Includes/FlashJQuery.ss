<% require javascript(flash_messages/javascript/flash_messages.js) %>

<% if FlashMessages %>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery(document)
				<% control FlashMessages %>
					.trigger('show_alert', ['$JS_Message','$JS_Type'])
				<% end_control %>
			;
		});
	</script>
<% end_if %>

<ul class="flash_messages" style="display:none"></ul>
