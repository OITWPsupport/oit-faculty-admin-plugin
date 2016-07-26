<?php

/**
 * ofaEmployees
 * A model class to retrieve personnel data
 * @author Martin Ronquillo
 * @method load(int id); load(array where); multiLoad(array $where); delete(int id);
 */
class ofaEmployeesGroupsX extends ofaDataObject {
	const table = 'ofaEmployeesGroupsX';
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function ofaEmployeesGroupsX() {
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
	 * @param $id: the ID of the record to load or array with WHERE parameters
	 * @return $result: an object with the record data
	 */
	public function load_1($var) {
		if (is_int($var)) {
			$result = $this->get(self::table, $var);
			return $this->clean($result[0]);
		}
		elseif (is_array($var)) {
			$result = $this->multiLoad_1(array($var));
			return $result[0];
		}
	}
	
	/**
	 * Retrieve several records
	 * @param $where (array('column', 'value')): multidimensional array with WHERE clause parameters
	 * @return $cleaned: the cleaned records
	 */
	public function multiLoad_1($where) {
		return $this->clean($this->get(self::table, -1, '', $where, array(), array('column' => 'groupId', 'order' => 'ASC')));
	}
	
	/**
	 * Return membership records joined with the employee's name & email
	 * @param $id: the group ID
	 * @return $records: the retrieved records
	 */
	public function loadJoinedEmployeeName($id) {
		$table = self::table;
		return $this->clean($this->getLeftJoined(
			array(
				self::table, 
				'ofaEmployees'), 
			'employeeId', 
			'id',
			array(),
			array(
				'firstName',
				'middleInitial',
				'lastName',
				'email'),
			array(
				array(
					'column' => 'groupId',
					'value' => $id))
					
          /*array(
				array(
					'column' => 'listFirst',
					'order' => 'DESC'),
				array(
					'column' => 'lastName',
                  'order' => 'ASC'))*/
                 ));
	}
  
  
	
	/**
	 * Return joined group membership data using the employee id to retrieve records
	 * @param $id: employee id
	 * @return $result: retrieved membership records
	 */
	public function loadJoinedByEmployeeId($id) {
		$table = self::table;
		return $this->clean($this->getLeftJoined(
			array(
				self::table, 
				'ofaGroups'), 
			'groupId', 
			'id',
			array(
				'listFirst',
				'position'),
			array(
				'name'),
			array(
				array(
					'column' => 'employeeId',
					'value' => $id))));
	}
	
	/**
	 * Return membership records with the group position & listFirst, along with all the employee data
	 * @param $groupId: the group id
	 * @return $result: the retrieved records
	 */
	public function loadJoinedEmployeeRecord($groupId) {
		$table = self::table;
		return $this->clean($this->getLeftJoined(
			array(
				self::table, 
				'ofaEmployees'), 
			'employeeId', 
			'id',
			array(
				'position',
				'listFirst'),
			array(),
			array(
				array(
					'column' => 'groupId',
					'value' => $groupId)),
			array(
				array(
					'column' => 'published',
					'value' => 'Yes')),
			array(
				array(
					'column' => 'listFirst',
					'order' => 'DESC'),
				array(
					'column' => 'lastName',
					'order' => 'ASC'))));
	}
  
  /**
	 * Return membership records with the group position & listFirst, along with all the employee data
	 * @param $groupId: the group id
	 * @return $result: the retrieved records
	 */
	public function loadSearchJoinedEmployeeRecord($groupId, $like, $search) {
		$table = self::table;
		return $this->clean($this->getSearchLeftJoined(
			array(
				self::table, 
				'ofaEmployees'), $like, $search,  
			'employeeId', 
			'id',
			array(
				'position',
				'listFirst'),
			array(),
			array(
				array(
					'column' => 'groupId',
					'value' => $groupId)),
			array(
				array(
					'column' => 'published',
					'value' => 'Yes')),
			array(
				array(
					'column' => 'listFirst',
					'order' => 'DESC'),
				array(
					'column' => 'lastName',
					'order' => 'ASC'))));
	}
	
	/**
	 * Validate/sanitize data
	 * @param $data: data record
	 * @return $validated: the validated data
	 */
	public function validate($data) {
		$validator = new ofaFormValidator();
		
		// Add rules for required fields
		$validator->addConstraint('employeeId', ofaFormValidator::REQUIRED);
		$validator->addConstraint('groupId', ofaFormValidator::REQUIRED);
		$validator->addConstraint('listFirst', ofaFormValidator::REQUIRED);
		
		// Validate the data
		// Returns an object with the data on true or an array with the invalid fields on false
		return $validator->validate($data);
	}
	
	/**
	 * Save the records
	 * @param $data: the data to save
	 * @return $saveCount: number of records saved
	 */
	public function save($data) {
		$validData = (array)$this->blank();
		$saveCount = 0;
		
		// Iterate through the multidimensional object to save each record
		foreach ($data as $d) {
			$valid = $validData;
			
			foreach ($d as $key => $item) {
				if (array_key_exists($key, $valid))
					$valid[$key] = $item;
			}
		
			$valid = (object)$valid;
			$id = $valid->id;
			
			if (empty($id))
				$saveCount += $this->set(self::table, $valid);
			else
				$saveCount += $this->set(self::table, $valid, $id);
		}

		return $saveCount;
	}
	
	/**
	 * Delete a record
	 * @param $id: the id of the record to delete
	 * @return $deleted: the results of the query
	 */
	public function delete_1($id) {
		return $this->delete(self::table, $id);
	}
}