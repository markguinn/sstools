<?php
// emulate the silverstripe DB::query() function
				
class DB {
	static $conn;
	
	static function init(){
		global $databaseConfig;
		self::$conn = mysql_connect($databaseConfig["server"], $databaseConfig["username"], $databaseConfig["password"]);
		mysql_select_db($databaseConfig["database"], self::$conn);
	}
	
	static function query($sql, $errorLevel = E_USER_ERROR){
		$handle = mysql_query($sql, self::$conn);
		if (!$handle && $errorLevel) {
			die("SQL Error: " . mysql_error(self::$conn));
		}
		return new LivePubQuery($handle);
	}

}

class LivePubQuery {
	private $handle;

	public function __construct($handle) {
		$this->handle = $handle;
	}
	
	public function __destroy() {
		mysql_free_result($this->handle);
	}
	
	public function seek($row) {
		return mysql_data_seek($this->handle, $row);
	}
	
	public function numRecords() {
		return mysql_num_rows($this->handle);
	}
	
	public function nextRecord() {
		// Coalesce rather than replace common fields.
		if($data = mysql_fetch_row($this->handle)) {
			foreach($data as $columnIdx => $value) {
				$columnName = mysql_field_name($this->handle, $columnIdx);
				// $value || !$ouput[$columnName] means that the *last* occurring value is shown
				// !$ouput[$columnName] means that the *first* occurring value is shown
				if(isset($value) || !isset($output[$columnName])) {
					$output[$columnName] = $value;
				}
			}
			return $output;
		} else {
			return false;
		}
	}
}
