<?php
/**
 * general utility functions
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @package sstools
 */
class SSTools{

	/**
	 * builds a dropdown from all the files of a certain type in a certain directory
	 * @param string $path - path to the folder
	 * @param string|array $extensions [optional] - limit to certain file extensions
	 * @return array
	 */
	static function file_list_dropdown($path, $extensions = '\w+') {		
		if (is_array($extensions)) $extensions = implode('|', $extensions);

		$dir = opendir(Director::getAbsFile($path));
		$list = array();

		while (($file = readdir($dir)) !== false) {
			if (preg_match('/\.(' . $extensions . ')$/i', $file)) {
				$list[$file] = $file;
			}
		}
		
		closedir($dir);
		
		return $list;
	}

}

