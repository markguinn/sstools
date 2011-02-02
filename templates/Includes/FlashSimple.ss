<% if FlashMessages %>
	<ul class="flash_messages">
		<% control FlashMessages %>
			<li class="$Type" onclick="this.style.display='none'">$Message</li>
		<% end_control %>
	</ul>
<% end_if %>