<?php
/**
 * These are tasks which augment core functionality (like cleanly wiping the cache)
 */ 
class SSTasks extends Controller {

	function clearCache(){
		$dirs = array('.', 'cache');
		echo "<pre>";
		foreach ($dirs as $dirname) {
			$dirpath = TEMP_FOLDER . '/' . $dirname;
			echo "clearing $dirpath\n";
			if ($dir = opendir($dirpath)) {
			    while (false !== ($file = readdir($dir))) {
			    	if ($file != '.' && $file != '..' && $file != '.svn' && !is_dir($file)) {
				        echo "  $file\n";
				        unlink($dirpath . '/' . $file);
			    	}
			    }	
			    
			    closedir($dir);
			}		
		}
		exit;
	}

}
