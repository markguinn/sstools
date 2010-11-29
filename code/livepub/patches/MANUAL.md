Manual Core Modifications for LivePub
=====================================

cms/code/staticpublisher/FilesystemPublisher.php
------------------------------------------------

In publishPages function:

	LivePubHelper::init_pub();
	foreach($urls as $url => $path) {
		LivePubHelper::clear_init_code();
		...
	}
	LivePubHelper::stop_pub();

In the generatePHPCacheFile function:

	return str_replace(
			array('**MAX_AGE**', '**LAST_MODIFIED**', '**CONTENT**'),
	//		array((int)$age, $lastModified, $content),
			array((int)$age, $lastModified, LivePubHelper::get_init_code() . $content), 
			$template);
