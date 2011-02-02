<?php require_once BASE_PATH . '/' . SSTOOLS_BASE . '/code/FlashMessages.php'; ?>
		
<?php if ($msgs = FlashMessages::get_array()): ?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery(document)
				<?php foreach ($msgs as $msg): ?>
					.trigger('show_alert', ['<?= addslashes($msg['Message']) ?>','<?= addslashes($msg['Type']) ?>'])
				<?php endforeach; ?>
			;
		});
	</script>
<?php endif; ?>

<ul class="flash_messages" style="display:none"></ul>
