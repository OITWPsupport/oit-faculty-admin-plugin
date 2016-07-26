<?php

/**
 * ofaCourses
 * Model class to manage the ofaCourses table
 * @author Martin Ronquillo
 */
class ofaCourses extends ofaDataObject {
	const table = 'ofaCourses';
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function ofaCourses() {
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
	 * @param $var: record id or array with WHERE parameters
	 * @return $result: object with the retrieved record
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
	 * Load all personnel entries
	 * @param none
	 * @return none
	 */
	public function multiLoad_0() {
		return $this->clean($this->get(self::table, -1, '', array(), array(), array('column' => 'course', 'order' => 'ASC')));
	}
	
	/**
	 * Load multiple records using WHERE
	 * @param $where (column|value): multi-dimensional array with WHERE parameters
	 * @return $result: an object of retrieved records
	 */
	public function multiLoad_1($where) {
		return $this->clean($this->get(self::table, -1, '', $where, array(), array('column' => 'course', 'order' => 'ASC')));
	}
	
	/**
	 * Load courses with the `id` & `title` columns
	 * @param none
	 * @return $results: the retrieved records
	 */
	public function multiLoadTitles() {
		$table = self::table;
		
		$query = <<<SQL
SELECT `id`, `course`, `number`, `title` FROM `%s{$table}` ORDER BY `title` ASC
SQL;

		return $this->clean($this->getUsingQuery($query));
	}
	
	/**
	 * Retrieve records by performing a search
	 * @param $like: array of columns to search
	 * @param $search: search string (does not need to be validated/sanitized)
	 * @param $where (optional): array of WHERE parameters
	 * @return $result: object of retrieved records
	 */
	public function search($like, $search, $where = array()) {
		return $this->clean($this->getSearch(self::table, $like, $search, $where));
	}
	
	/**
	 * Validate data from a form
	 * @param $data: object with data
	 * @return: validated/sanitized data or array with incorrect fields
	 */
	public function validate($data) {
		$validator = new ofaFormValidator();
		
		// Add rules for required fields
		$validator->addConstraint('departmentId', ofaFormValidator::REQUIRED);
		$validator->addConstraint('course', ofaFormValidator::REQUIRED);
		$validator->addConstraint('number', ofaFormValidator::REQUIRED);
		$validator->addConstraint('title', ofaFormValidator::REQUIRED);

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
