<?php

/**
 * ofaWpDatabase
 * A wrapper class to interace with the WPDB class
 * @author Martin Ronquillo
 */
class ofaWpDatabase {
	private $prefix;
	
	/**
	 * The constructor
	 * @param none
	 * @return none
	 */
	public function ofaWpDatabase() {
		// Grab the DB prefix
		global $wpdb;
		$this->prefix = $wpdb->base_prefix;
	}
	
	/**
	 * Query the WP database
	 * @param $query: the query to be executed
	 * @return $result: An object with the retrieved data
	 */
	public function getResults($query) {
		global $wpdb;
		$result = $wpdb->get_results($query);
		return $result;
	}
	
	/**
	 * Query the WP database (will not return data)
	 * Use for CUD (Create, Update, Delete) operations
	 * @param $query: the query to be executed
	 * @return $result: number of affected rows or false on error
	 */
	public function query($query) {
		global $wpdb;
		$result = $wpdb->query($query);
		return $result;
	}
	
	/**
	 * Escape a value to sanitize it
	 * @param $value: the value to escape
	 * @return $escaped: the escaped value
	 */
	public function escape($value) {
		global $wpdb;
		return $wpdb->escape($value);
	}
	
	/**
	 * Get the WPDB prefix
	 * @param none
	 * @return $prefix: the WPDB prefix
	 */
	public function getPrefix() {
		global $wpdb;
		return $wpdb->base_prefix;
	}
	
	/**
	 * Execute several queries at once
	 * @param $queries: an array with the queries to execute
	 * @return: $count: the number of successful queries
	 */
	public function queries($queries) {
		$count = 0;
		
		foreach ($queries as $query) {
			$result = $this->query($query);
			
			if ($result != false)
				$count++;
		}
		
		return $count;
	}
	
	/**
	 * Retrieve an option value from the ofaOptions table
	 * @param $option: The option to retrieve
	 * @return $optionValue: The option value
	 */
	public function getOption($option) {
		$prefix = $this->prefix;
		$option = $this->escape($option);
		
		$query = "SELECT `optionValue` FROM `{$prefix}ofaOptions` WHERE `optionType` = '{$option}'";
		$result = $this->getResults($query);
		return $result[0]->optionValue;
	}
	
	/**
	 * Add or update an option in the ofaOptions table
	 * @param $option: option name
	 * @param $value: option value
	 * @return true|false|message
	 */
	public function setOption($option, $value) {
		$prefix = $this->prefix;
		$option = $this->escape($option);
		$value = $this->escape($value);
		
		$checkQuery = "SELECT `optionValue` FROM `{$prefix}ofaOptions` WHERE `optionType` = '{$option}'";
		$optionCheck = $this->getResults($checkQuery);

		if(empty($optionCheck)) {
			$entryQuery = "INSERT INTO `{$prefix}ofaOptions` (`optionType`, `optionValue`) VALUES ('{$option}', '{$value}')";
			$entry = $this->query($entryQuery);
			
			if ($entry != false)
				return true;
			else
				return "Option '{$option}' could not be saved to the database";
		}
		else {
			$updateQuery = "UPDATE `{$prefix}ofaOptions` SET `optionValue` = '{$value}' WHERE `optionType` = '{$option}'";
			$update = $this->query($updateQuery);
			
			if ($update != false)
				return true;
			else
				return "Option '{$option}' could not be saved to the database";
		}
	}
}
