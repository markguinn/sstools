<?php
/**
 * @author Mark Guinn <mark@adaircreative.com>
 * @package sstools
 * @date 1/11/11
 */
class RequirementsLessBackend extends Requirements_Backend
{
	static $generated_css_path = false;


	/**
	 * Takes a .less file as input, compiles it and returns the path to the css
	 * file (exactly as would be passed to requirements::css)
	 *
	 * @param  $file
	 * @return string
	 */
	function processLessFile($file) {
		$css = substr($file, 0, -5) . '.css';

		if (self::$generated_css_path) {
			$fn = str_replace(dirname($css), '', $css);
			$fold = self::$generated_css_path;
			Filesystem::makeFolder(BASE_PATH . '/' . $fold);
			$css = $fold . '/' . $fn;
		}

		lessc::ccompile(BASE_PATH . '/' . $file, BASE_PATH . '/' . $css);

		return $css;
	}


	/**
	 * Register the given stylesheet file as required.
	 *
	 * @param $file String Filenames should be relative to the base, eg, 'sapphire/javascript/tree/tree.css'
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector")
	 * @see http://www.w3.org/TR/REC-CSS2/media.html
	 */
	function css($file, $media = null) {
		if (substr($file, -5) == '.less') {
			$css = $this->processLessFile($file);
			return $this->css($css, $media);
		}

		$this->css[$file] = array(
			"media" => $media
		);
	}

	/**
	 * @param  $combinedFileName
	 * @param  $files
	 * @return bool
	 */
	function combine_files($combinedFileName, $files) {
		foreach($files as $index=>$file) {
			if (is_array($file)) {
				if (isset($file['type']) && ($file['type'] == 'css' || $file['type'] == 'less')) {
					$files[$index]['type'] = 'css';
					$files[$index]['path'] = $this->processLessFile($file['path']);
				} elseif (isset($file[1]) && ($file[1] == 'css' || $file[1] == 'less')) {
					$files[$index][1] = 'css';
					$files[$index][0] = $this->processLessFile($file[0]);
				}
			} else {
				// if it's a less file, replace it with the compiled version
				if (substr($file, -5) == '.less') {
					$files[$index] = $this->processLessFile($file);
				}
			}
		}

		return parent::combine_files($combinedFileName, $files);
	}


}
