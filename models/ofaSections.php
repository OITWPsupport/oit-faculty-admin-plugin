<?php

/**
 * ofaSections
 * Model class to manage the ofaSections table
 * @author Martin Ronquillo
 * @method load(int $id); load(array $where); delete(int $id);
 */
class ofaSections extends ofaDataObject {
	const table = 'ofaSections';
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function ofaSections() {
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
		if (is_int($var)) 
			$result = $this->get(self::table, $var);
			return $this->clean($result[0]);
	}
	
	/**
	 * Returns all the necessary data to display sections
	 * @param $semesterId (optional): load records from a specific semester
	 * @param $more (optional): load all section columns
	 * @param $search (optional): search pattern
	 * @param $departmentId (optional): limit results to a specific department
	 * @return $result: the retrieved records
	 */
	public function loadAllJoined($semesterId = 0, $more = false, $search = '', $departmentId = 0) {
		$semester = '';
		$moreColumns = '';
		$searchColumns = '';
		$searchPattern = trim($this->db->escape($search));
		$department = '';
		
		// Limit results by semester
		if ($semesterId != 0) {
			$semesterId = $this->db->escape($semesterId);
			$semester = " WHERE `%sofaSections`.`semesterId` = {$semesterId}";
		}
		
		// Load all section fields
		if ($more == true)
			$moreColumns = "`%sofaSections`.`syllabus`, `%sofaSections`.`link`, `%sofaSections`.`notes`, ";
		
		// Perform search
		// Breaks up strings if there are any spaces
		if (!empty($search)) {
		 	$searchColumns = ' AND (';
			$columnBuilder = '';
		 	$terms = explode(' ', $searchPattern);
			
			// Break up terms such as ENTREP496 to ENTREP 496 to build a double match query
			if (count($terms) == 1) {
				$length = strlen($terms[0]);
				$pieces = str_split($terms[0], $length - 3);
				$course = '';
				$number = 0;
				
				if (count($pieces) == 2) {
					$course = $pieces[0];
					$number = (int)$pieces[1];
				}
				
				if ($number != 0)
					$terms = array($course, $number);
			}
			
			// Build the sub query to match search terms
			foreach ($terms as $term) {
				$columnBuilder .= "(`%sofaCourses`.`course` LIKE '%{$term}%' OR `%sofaCourses`.`number` LIKE '%{$term}%' OR `%sofaCourses`.`title` LIKE '%{$term}%') OR ";
			}
			
		 	$searchColumns .= substr($columnBuilder, 0, -4) . ') ';
		}

		// Limit results to a specific department
		if ($departmentId != 0) {
			$departmentId = $this->db->escape($departmentId);
			$department = " AND `%sofaCourses`.`departmentId` = {$departmentId} ";
		}

		// Build the query
		$query = <<<SQL
SELECT 
`%sofaSections`.`id` AS `sectionId`, 
`%sofaSections`.`section`, 
`%sofaSections`.`hours`, 
`%sofaSections`.`room`, {$moreColumns}
`%sofaCourses`.`course` AS `course`,
`%sofaCourses`.`number` AS `courseNumber`,
`%sofaCourses`.`title` AS `courseTitle`, 
`%sofaGroups`.`name` AS `departmentName`, 
`%sofaEmployees`.`firstName`, 
`%sofaEmployees`.`lastName`,
`%sofaSemesters`.`id` AS `semesterId`,
`%sofaSemesters`.`name` AS `semester`
FROM 
`%sofaSections`
JOIN `%sofaCourses`
ON `%sofaSections`.`courseId` = `%sofaCourses`.`id`{$searchColumns}{$department}
JOIN `%sofaGroups`
ON `%sofaCourses`.`departmentId` = `%sofaGroups`.`id`
JOIN `%sofaEmployees`
ON `%sofaSections`.`employeeId` = `%sofaEmployees`.`id`
JOIN `%sofaSemesters`
ON `%sofaSections`.`semesterId` = `%sofaSemesters`.`id`{$semester}
ORDER BY `semesterId` DESC, `course` ASC, `courseNumber` ASC, `sectionId` ASC
SQL;

		return $this->clean($this->getUsingQuery($query));
	}
	
	/**
	 * Load sections that are incomplete
	 * @param none
	 * @return $results: the invalid records
	 */
	public function loadInvalid() {
		$query = <<<SQL
SELECT
*
FROM `%sofaSections` 
WHERE
`semesterId` = 0 OR
`employeeId` = 0 OR
`courseId` = 0
ORDER BY `id` ASC
SQL;

		return $this->clean($this->getUsingQuery($query));
	}
	
	/**
	 * Peforms a search on the course
	 * @param $search: the search string
	 * @param $semesterId (optional): load records from a specific semester
	 * @param $more (optional): load all section columns
	 * @param $departmentId (optional): limit results to a specific department
	 * @return $result: object of records retrieved
	 */
	public function searchAllJoined($search, $semesterId = 0, $more = false, $departmentId = 0) {
		return $this->loadAllJoined($semesterId, $more, $search, $departmentId);
	}
	
	/**
	 * Retrieve sections for an employee
	 * @param $id: the employee id
	 * @param $semester: current semester
	 * @return $result: object of records retrieved
	 */
	public function loadEmployeeSections($id, $semester) {
		$employeeId = $this->db->escape($id);
		$semesterId = $this->db->escape($semester);
		
		$query = <<<SQL
SELECT 
`%sofaSections`.`section`, 
`%sofaSections`.`hours`, 
`%sofaSections`.`room`, 
`%sofaSections`.`syllabus`, 
`%sofaSections`.`link`, 
`%sofaSections`.`notes`, 
`%sofaCourses`.`course` AS `course`,
`%sofaCourses`.`number` AS `courseNumber`,
`%sofaCourses`.`title` AS `courseTitle`
FROM 
`%sofaSections`
JOIN `%sofaCourses`
ON `%sofaSections`.`courseId` = `%sofaCourses`.`id`
WHERE
`%sofaSections`.`semesterId` = {$semesterId}
AND `%sofaSections`.`employeeId` = {$employeeId}
ORDER BY `course` ASC
SQL;

		return $this->clean($this->getUsingQuery($query));
	}
	
	/**
	 * Load section data from a CSV file
	 * @param $fileName: the path to the file
	 * @param $args (optional): array of arguments
	 * @return $data: multidimensional object with all the loaded data
	 */
	public function loadFromFile($fileName, $args = array()) {
		$courses = array();
		extract($args);
		
		// Open the CSV file and retrieve data
		if (($handle = fopen($fileName, 'r')) !== FALSE) {
			// Iterate through the rows
		    while (($data = fgetcsv($handle, 1000, "\r")) !== FALSE) {
		        $entry = $data[0];
		        $info = explode(',', $entry);
		        $newCourse = $this->blank();
				
				// Load all the available data
				$newCourse->semesterId = $semester;
		        $newCourse->section = $info[2];
		        $newCourse->hours = $info[6] . ' ' . $info[7] . ' - ' . $info[8];
				$newCourse->room = $info[10];
				
				// Find the instructor id
				$lastName = str_replace('"', '', $info[4]);
				$firstMiddleName = $info[5];
				$break = strrpos($firstMiddleName, ' ');
				$firstName = '';
				
				if (!empty($break)) {
					$firstName = str_replace('"', '', substr($firstMiddleName, 0, $break));
				}
				else
					$firstName = str_replace('"', '', $firstMiddleName);
				
				$employee = new ofaEmployees();
				$instructor = $employee->multiLoad(
					array(
						array(
							'column'	=> 'lastName',
							'value'		=> $lastName),
						array(
							'column'	=> 'firstName',
							'value'		=> $firstName)));
							
				$instructor = (array)$instructor;
				$instructorId = null;

				if (!empty($instructor))
					$instructorId = $instructor[0]->id;

				$newCourse->employeeId = $instructorId;

				// Find the course id
				$course = new ofaCourses();
				$courseData = $course->multiLoad(
					array(
						array(
							'column'	=>	'course',
							'value'		=>	$info[0]),
						array(
							'column'	=>	'number',
							'value'		=>	$info[1])));
							
				$courseData = (array)$courseData;
				$courseId = null;
				$departmentId = null;
				
				if (!empty($courseData)) {
					$courseId = $courseData[0]->id;
					$departmentId = $courseData[0]->departmentId;
				}
				
				$newCourse->courseId = $courseId;
				$newCourse->departmentId = $departmentId;
				
		        $courses[] = $newCourse;
		    }
			
		    fclose($handle);
		}
		else
			return false;
		
		return (object)$courses;
	}
	
	/**
	 * Validate section data from a form
	 * @param $data: object with section data
	 * @return: validated/sanitized data or array with incorrect fields
	 */
	public function validate($data) {
		$validator = new ofaFormValidator();
		
		// Add rules for required fields
		$validator->addConstraint('semesterId', ofaFormValidator::REQUIRED);
		$validator->addConstraint('courseId', ofaFormValidator::REQUIRED);
		$validator->addConstraint('section', ofaFormValidator::REQUIRED);
		$validator->addConstraint('employeeId', ofaFormValidator::REQUIRED);
		
		// Add rules for the special elements
		$validator->addRule('link', ofaFormValidator::URL);
		
		// Validate the data
		// Returns an object with the data on true or an array with the invalid fields on false
		return $validator->validate($data);
	}
	
	/**
	 * Save a section record
	 * @param $data: object with section data
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
	 * Delete a record
	 * @param $id: the id of the record to delete
	 * @return $result: result of query
	 */
	public function delete_1($id) {
		return $this->delete(self::table, $id);
		
	}
}
