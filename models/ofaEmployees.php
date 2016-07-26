<?php

/**
 * ofaEmployees
 * A model class to retrieve personnel data
 * @author Martin Ronquillo
 * @method load(int $id); load(array $where); multiLoad(); multiLoad(array $where); multiLoad(array $limitNumbers, array $orderBy);
 */
class ofaEmployees extends ofaDataObject {
	const table = 'ofaEmployees';
	
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
		return $this->clean($this->get(self::table, -1, '', array(), array(), array('column' => 'lastName', 'order' => 'ASC')));
	}
	
	/**
	 * Load multiple records using WHERE
	 * @param $where (column|value): multi-dimensional array with WHERE parameters
	 * @return $result: an object of retrieved records
	 */
	public function multiLoad_1($where) {
		return $this->clean($this->get(self::table, -1, '', $where, array(), array('column' => 'lastName', 'order' => 'ASC')));
	}
	
	/**
	 * Load multiple records - limit
	 * @param $limitNumbers (0,1): array with beginning and end record ids
	 * @param $orderBy (column|order): array with ORDER BY parameters
	 * @return $result: object with the retrieved records
	 */
	public function multiLoad_2($limitNumbers, $orderBy) {
		return $this->clean($this->get(self::table, -1, '', array(), $limitNumbers, $orderBy));
	}
	
	/**
	 * Load published/no published records
	 * @param $published (optional, default: true): load published records?
	 * @return $result: retrieved records
	 */
	public function multiLoadIsPublished($published = true) {
		if ($published) {
			return $this->multiLoad_1(array(
				array(
					'column' => 'published',
					'value' => 'Yes')));
		}
		else {
			return $this->multiLoad_1(array(
				array(
					'column' => 'published',
					'value' => 'No')));
		}
	}
	
	/**
	 * Load employees with the name & id
	 * @param none
	 * @return $results: the retrieved records
	 */
	public function multiLoadNames() {
		$table = self::table;
		
		$query = <<<SQL
SELECT `id`, `firstName`, `middleInitial`, `lastName` FROM `%s{$table}` ORDER BY `lastName` ASC
SQL;

		return $this->clean($this->getUsingQuery($query));
	}
  
	public function loadClassifications() {
    	$query = "SELECT DISTINCT `jobClassification` FROM `%sofaEmployees`";
      	return $this->getUsingQuery($query);
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
	 * Validate personnel data from a form
	 * @param $data: object with personnel data
	 * @return: validated/sanitized data or array with incorrect fields
	 */
	public function validate($data) {
		$validator = new ofaFormValidator();
		
		// Add rules for required fields
		$validator->addConstraint('published', ofaFormValidator::REQUIRED);
		$validator->addConstraint('firstName', ofaFormValidator::REQUIRED);
		$validator->addConstraint('lastName', ofaFormValidator::REQUIRED);
		$validator->addConstraint('jobTitle', ofaFormValidator::REQUIRED);
		$validator->addConstraint('jobClassification', ofaFormValidator::REQUIRED);
		$validator->addConstraint('email', ofaFormValidator::REQUIRED);
		$validator->addConstraint('phone', ofaFormValidator::REQUIRED);
		
		// Add rules for the special elements
		$validator->addRule('siteName', ofaFormValidator::INVALID, '/');
		$validator->addRule('email', ofaFormValidator::EMAIL);
		$validator->addRule('phone', ofaFormValidator::PHONE);
		$validator->addRule('bio', ofaFormValidator::HTML, '<b><i><u>');
		$validator->addRule('featuredPublications', ofaFormValidator::HTML, '<b><i><u><a>');

		// Validate the data
		// Returns an object with the data on true or an array with the invalid fields on false
		return $validator->validate($data);
	}
	
	/**
	 * Save a personnel record
	 * @param $data: object with personnel data
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
	
	/**
	 * Build a string with the name of the employee
	 * @param $employee: the employee object
	 * @param $lastNameFirst (optional, default: false): display the last name first?
	 * @param $displayDoctorate (optional, default: true): display the doctorate title?
	 * @return $name: the generated name
	 */
	public function getName($employee, $lastNameFirst = false, $displayDoctorate = true) {
		$middleInitial = ' ';
		$doctorate = '';
		
		if (!empty($employee->middleInitial))
			$middleInitial = ' ' . $employee->middleInitial . '. ';
		
		if (!empty($employee->doctorate) && $displayDoctorate)
			$doctorate = ', ' . $employee->doctorate;
		
		$name = '';
		
		if ($lastNameFirst) {
			$name = trim($employee->lastName . ', ' . $employee->firstName . $middleInitial);
			
			if ($displayDoctorate)
				$name .= $doctorate;
		}
		else
			$name = $employee->firstName . $middleInitial . $employee->lastName . $doctorate;
		
		return $name;
	}
}
