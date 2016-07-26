<?php

/**
 * ofaSemesters
 * Model class to manage the ofaSemesters table
 * @author Martin Ronquillo
 * @method load(int $id); load(array $where); multiLoad(); multiLoad(array $where);
 */
class ofaSemesters extends ofaDataObject {
	const table = 'ofaSemesters';
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function ofaSemesters() {
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
	 * Load all semester entries
	 * @param none
	 * @return none
	 */
	public function multiLoad_0() {
		return $this->clean($this->get(self::table, -1, '', array(), array(), array('column' => 'id', 'order' => 'DESC')));
	}
	
	/**
	 * Load multiple records using WHERE
	 * @param $where (column|value): multi-dimensional array with WHERE parameters
	 * @return $result: an object of retrieved records
	 */
	public function multiLoad_1($where) {
		return $this->clean($this->get(self::table, -1, '', $where, array(), array('column' => 'id', 'order' => 'DESC')));
	}
	
	/**
	 * Load all semesters with only the `id` & `name` columns
	 * @param none
	 * @return $results: the retrieved records
	 */
	public function multiLoadWithNames() {
		$table = self::table;
		
		$query = <<<SQL
SELECT `id`, `name` FROM `%s{$table}` ORDER BY `id` DESC
SQL;

		return $this->clean($this->getUsingQuery($query));
	}
	
	/**
	 * Validate form submission
	 * @param $data: object with the submitted semester data
	 * @return $validated: validated/sanitized data or array of errors
	 */
	public function validate($data) {
		$validator = new ofaFormValidator();
		
		// Add rules for required fields
		$validator->addConstraint('name', ofaFormValidator::REQUIRED);

		// Validate the data
		// Returns an object with the data on true or an array with the invalid fields on false
		return $validator->validate($data);
	}
	
	/**
	 * Save the semester data
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
