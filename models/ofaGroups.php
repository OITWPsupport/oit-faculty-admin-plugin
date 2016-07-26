<?php

/**
 * ofaGroups
 * Model class for group module
 * @author Martin Ronquillo
 * @method load(int id); load(array where); multiLoad(); multiLoad(array $where);
 */
class ofaGroups extends ofaDataObject {
	const table = 'ofaGroups';
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function ofaEmployees() {
		parent::__construct();
	}
	
	/**
	 * Return a blank object
	 * @param none
	 * @return $blank: a blank object
	 */
	public function blank() {
		return $this->blankObject(self::table);
	}
	
	/**
	 * Wrapper method to handle the loading of a single entry
	 * @param $var: record ID or array with "WHERE" parameters
	 * @return $result: the result of the query
	 */
	public function load_1($var) {
		if (is_int($var)) {
			$result = $this->get(self::table, $var);
			return $this->clean($result[0]);
		}
		elseif (is_array($var)) {
			$result = (array)$this->multiLoad_1(array($var));
			return $result[0];
		}
	}
	
	/**
	 * Load all entries
	 * @param none
	 * @return none
	 */
	public function multiLoad_0() {
		return $this->clean($this->get(self::table, -1, '', array(), array(), array('column' => 'name', 'order' => 'ASC')));
	}
	
	/**
	 * Load multiple entries using WHERE to limit results
	 * @param $where: multi-dimensional array with WHERE parameters
	 * @return $result: object with the retrieved records
	 */
	public function multiLoad_1($where) {
		return $this->clean($this->get(self::table, -1, '', $where, array(), array('column' => 'name', 'order' => 'ASC')));
	}
	
	/**
	 * Load only departments
	 * @param none
	 * @return $results: the retrieved records
	 */
	public function multiLoadDepartments() {
		$table = self::table;
		
		$query = <<<SQL
SELECT `id`, `name` FROM `%s{$table}` WHERE `groupType` = 'Department' ORDER BY `name` ASC
SQL;

		return $this->clean($this->getUsingQuery($query));
	}
	
	/**
	 * Return records via search
	 * @param $field: column name or array of column names to search
	 * @param $search: search string (does not need to be sanitized)
	 * @return $result: search results
	 */
	public function search($field, $search, $where = array()) {
		if (is_string($field))
			return $this->clean($this->getSearch(self::table, array($field), $search, $where));
		elseif (is_array($field))
			return $this->clean($this->getSearch(self::table, $field, $search, $where));
	}
	
	/**
	 * Validate form submission
	 * @param $data: object with the submitted group data
	 * @return $validated: validated/sanitized data or array of errors
	 */
	public function validate($data) {
		$validator = new ofaFormValidator();
		
		// Add rules for required fields
		$validator->addConstraint('name', ofaFormValidator::REQUIRED);
		$validator->addConstraint('groupType', ofaFormValidator::REQUIRED);
		
		// Add rules for the special elements
		$validator->addRule('siteName', ofaFormValidator::INVALID, '/');
		$validator->addRule('email', ofaFormValidator::EMAIL);
		$validator->addRule('phone', ofaFormValidator::PHONE);
		$validator->addRule('about', ofaFormValidator::HTML, '<b><i><u>');

		// Validate the data
		// Returns an object with the data on true or an array with the invalid fields on false
		return $validator->validate($data);
	}
	
	/**
	 * Save the group data
	 * @param $data: object with the group data
	 * @return $result: query results
	 */
	public function save($data) {
		$validData = (array)$this->blank();
		
		foreach ($data as $key => $item) {
			if (array_key_exists($key, $validData))
				$validData[$key] = $item;
		}

		$validData = (object)$validData;

		$id = $validData->id;
		
		if (empty($id))
			return $this->set(self::table, $validData);
		else
			return $this->set(self::table, $validData, $id);
	}
}