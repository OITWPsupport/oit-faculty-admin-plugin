<?php

/**
 * ofaDataObject
 * Base class for OFA data objects - contains several methods to facilitate interaction with the database
 * This class is for model classes to inherit from - use to retrieve and save data
 * @author Martin Ronquillo
 */
abstract class ofaDataObject {
	protected $db;
	protected $prefix;
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function __construct() {
		$this->db = new ofaWpDatabase();
		$this->prefix = $this->db->getPrefix();
	}
	
	/**
	 * Magic method to provide method "overloading"
	 * SEE the 'load' methods in the ofaEmployees model class to view sample usage
	 * @param $name: name of method called
	 * @param $args: arguments passed
	 * @return method to be executed
	 */
	public function __call($name, $args) {
		$method = $name . "_" . count($args);
		
		if (!method_exists($this, $method))
			throw new Exception('Call to undefined method ' . get_class($this) . "::{$method}");
		
		return call_user_func_array(array($this, $method), $args);
	}
	
	/**
	 * Query the database to retrieve a table schema
	 * @param $table: the table to retrieve the schema for
	 * @return $result: table schema in an object
	 */
	protected function describeTable($table) {
		$query = "DESCRIBE `{$this->prefix}{$table}`";
		return $this->db->getResults($query);
	}
	
	/**
	 * Return a blank array with all the database field names
	 * @param $table: the table name
	 * @return $columns: a blank object with keys for each database column
	 */
	protected function blankObject($table) {
		$columns = array();
		$columnData = $this->describeTable($table);
		
		foreach ($columnData as $item) {
			$columns[$item->Field] = '';
		}
		
		return (object)$columns;
	}
	
	/**
	 * Iterate through a record to remove slashes
	 * @param $data: the data record
	 * @return $clean: cleaned record
	 */
	public function clean($data) {
		$clean = array();
		
		foreach ($data as $key => $value) {
			if (is_object($value))
				$clean[$key] = $this->clean($value);
			else
				$clean[$key] = stripslashes($value);
		}
		
		return (object)$clean;
	}
	
	/**
	 * Iterate through a record and escape the data before saving to the database
	 * @param $data: the record
	 * @return $escaped: the escaped data
	 */
	public function prepare($data) {
		$escaped = array();
		
		foreach ($data as $key => $value)
			$escaped[$key] = $this->db->escape($value);
		
		return (object)$escaped;
	}
	
	/**
	 * Trim records to paginate a data list
	 * @param $data: a multi-dimensional object with all the records
	 * @param $currentPage: the current "page" number
	 * @param $itemsPerPage (default: 20): the number of items to display per page 
	 */
	public function paginate($data, $currentPage, $itemsPerPage = 20) {
		$records = (array)$data;
		$displayRecords = array();
		$recordCount = count($records);
		$startIndex = (($currentPage * $itemsPerPage) - $itemsPerPage);
		$endIndex = $startIndex + $itemsPerPage;
		$counter = $startIndex;

		while ($counter < $endIndex) {
			if (array_key_exists($counter, $records))
				$displayRecords[] = $records[$counter];
			
			$counter++; 
		}
		
		return (object)$displayRecords;
	}
	
	/**
	 * Retrieve data
	 * Default method to prepare a SQL statement and query the database to retrieve data entries
	 * @param $table: the table name (no prefix) to query
	 * @param $id (optional): retrieve a specific entry
	 * @param $idField (optional; default: `id`): the ID field of the table
	 * @param $limitNumbers (optional): limit the number of entries returned - good for pagination
	 * @param $where (column|value): multidimensional array to limit query by columns
	 * @param $orderBy (optional; keys: column, order): order data by a specific column
	 * @return $results: an object with all the retrieved datas
	 */
	protected function get($table, $id = -1, $idField = '', $where = array(), $limitNumbers = array(), $orderBy = array()) {
		$query = '';
		$idNumber = -1;
		$idColumn = 'id';
		
		if ($id != -1)
			$idNumber = (string)$id;
		
		if (!empty($idField))
			$idColumn = $idField;
		
		// Prepare the SQL statements
		// IF ID is provided	return a single entry
		// ELSE 				return all entries, or several based on LIMIT
		if ($idNumber != -1) {
			$query = "SELECT * FROM `{$this->prefix}{$table}` WHERE `{$idColumn}` = '{$idNumber}'";
		}
		else {
			$whereClause = '';
			$orderClause = '';
			$limitClause = '';
			
			// Is a WHERE clause necessary?
			if (!empty($where) && is_array($where)) {
				$whereBuild = ' WHERE';
				
				foreach ($where as $whereItem) {
					if (array_key_exists('column', $whereItem) && array_key_exists('value', $whereItem)) {
						$whereBuild .= " `{$whereItem['column']}` = '{$whereItem['value']}' AND";
					}
				}
				
				$whereClause = substr($whereBuild, 0 , -3);
			}
			
			// Build the ORDER BY clause
			$orderClause = $this->buildOrderClause($orderBy);
			
			// Do we need the LIMIT clause? If so, generate it
			if (!empty($limitNumbers) && is_array($limitNumbers)) {
				$limitClause = " LIMIT {$limitNumbers[0]}, {$limitNumbers[1]}";
			}
			
			$query = "SELECT * FROM `{$this->prefix}{$table}`{$whereClause}{$orderClause}{$limitClause}";
		}
		
		return $this->db->getResults($query);
	}

	/**
	 * Retrieve fields based on a search pattern
	 * @param $table: the table to query
	 * @param $like: array with fields to search using LIKE 
	 * @param $search: the search pattern
	 * @param $where: limit query by columns using WHERE statement
	 * @return $query: an object with the retrieved records
	 */
	protected function getSearch($table, $like, $search, $where = array()) {
		// Escape the search pattern
		$searchPattern = $this->db->escape($search);
		$likeClause = '';
		$whereClause = '';
		
      // Limit query using WHERE
		if (!empty($where) && is_array($where)) {
			$whereBuild = ' AND';
				
			foreach ($where as $whereItem) {
				if (array_key_exists('column', $whereItem) && array_key_exists('value', $whereItem)) {
					$whereBuild .= " `{$whereItem['column']}` = '{$whereItem['value']}' AND";
				}
			}
			
			$whereClause = substr($whereBuild, 0 , -4);
		}
      
		// Build the different parameters for the LIKE clause
		if (is_array($like)) {
			$likeBuild = '';
			
			foreach ($like as $l)
              $likeBuild .= " `{$l}` LIKE '%{$searchPattern}%' {$whereClause} OR";
			
			$likeClause = substr($likeBuild, 0, -3);
		}
		
		
		
		// Build the query and return the search results
      $query = "SELECT * FROM `{$this->prefix}{$table}` WHERE{$likeClause}";
	  //echo $query;
		return $this->db->getResults($query);
		
	}
  
  /**
	 * Retrieve records using a left join
	 * @param $tables: an array with two table names (no WP prefix)
	 * @param $like: array with fields to search using LIKE 
	 * @param $search: the search pattern
	 * @param $where1: name of column of left table to match
	 * @param $where2: name of column of right table to match
	 * @param $columns1 (optional): specific columns of the first table to return
	 * @param $columns2 (optional): specific columns of second table to return
	 * @param $leftWhere (optional): specify columns to match using WHERE
	 * @param $orderBy (optional): order results using columns
	 * @return $result: the queried records
	 */
	protected function getSearchLeftJoined($tables, $like, $search, $where1, $where2, $columns1 = array(), $columns2 = array(), $leftWhere = array(), $rightWhere = array(), $orderBy = array()) {
		if (is_array($tables) && count($tables) == 2) {
			$from = '';
			$where = '';
			$from1Build = '';
			$from2Build = '';
			$whereBuild = '';
			$orderClause = '';
			// Escape the search pattern
			$searchPattern = $this->db->escape($search);
			$likeClause = '';
			$whereClause = '';
			
          
		 
          
			// Assemble columns for the first table
			if (is_array($columns1) && !empty($columns1)) {
				foreach ($columns1 as $column)
					$from1Build .= "`{$this->prefix}{$tables[0]}`.`{$column}`, ";
			}
			
			// Assemble columns for the second table
			if (is_array($columns2) && !empty($columns2)) {
				foreach ($columns2 as $column)
					$from2Build .= "`{$this->prefix}{$tables[1]}`.`{$column}`, ";
			}
			
			// IF		no columns specified for first table		AND		columns specified for second	Select all columns from first by using an *
			// ELSEIF	no columns specified for second table		AND		columns specified for first		Select all from second using an * 
			if (empty($from1Build) && !empty($from2Build))
				$from1Build = "`{$this->prefix}{$tables[0]}`.*, ";
			elseif (!empty($from1Build) && empty($from2Build))
				$from2Build = "`{$this->prefix}{$tables[1]}`.*, ";
			
			$from = substr($from1Build . $from2Build, 0, -2);
			
			// No specified columns for either table - select all from both tables
			if (empty($from))
				$from = '*';
				
          //if (!empty($leftWhere) || !empty($rightWhere))
          //	$where = 'WHERE ';
				
			// Assemble WHERE clause, if needed
			if (is_array($leftWhere) && !empty($leftWhere)) {
				$whereBuild = '';
				
				foreach ($leftWhere as $lWhere)
					$whereBuild .= "`{$this->prefix}{$tables[0]}`.`{$lWhere['column']}` = '{$lWhere['value']}' AND ";
				
				$where .= substr($whereBuild, 0, -4);
			}
			
			// Assemble WHERE clause, if needed
			if (is_array($rightWhere) && !empty($rightWhere)) {
				$whereBuild2 = '';
				
				if (!empty($leftWhere))
					$whereBuild2 .= ' AND ';

				foreach ($rightWhere as $rWhere)
					$whereBuild2 .= "`{$this->prefix}{$tables[1]}`.`{$rWhere['column']}` = '{$rWhere['value']}' AND ";
				
				$where .= substr($whereBuild2, 0, -4);
			}
			
			$orderClause = $this->buildOrderClause($orderBy);
			
          // Build the different parameters for the LIKE clause
		if (is_array($like)) {
			$likeBuild = '';
			
			foreach ($like as $l)
              $likeBuild .= " `{$l}` LIKE '%{$searchPattern}%' AND {$where} OR";
			
			$likeClause = substr($likeBuild, 0, -3);
		} 
          
			// Build and execute the query
			$query = "SELECT {$from} FROM `{$this->prefix}{$tables[0]}` LEFT JOIN `{$this->prefix}{$tables[1]}`";
			$query .= " ON `{$this->prefix}{$tables[0]}`.`{$where1}` = `{$this->prefix}{$tables[1]}`.`{$where2}` WHERE {$likeClause}{$orderClause}";
          //echo $query;
          return $this->db->getResults($query);
		}
		else
			return false;
	}
	
	/**
	 * Load record using a custom SQL statement
	 * @param $query: the custom query - use %s to add prefixes to the table names
	 * @return $result: the retrieved records
	 */
	protected function getUsingQuery($query) {
		$readyQuery = str_replace('%s', $this->prefix, $query);
      //echo $readyQuery;
		return $this->db->getResults($readyQuery);
	}

	/**
	 * Save a record
	 * @param $table: the target table
	 * @param $data: the record (in object form) to save
	 * @param $id (optional): id of the record (use to update a record)
	 * @param $idField (optional, default: 'id'): the name of the ID column
	 * @return $result: number of affected rows or error
	 */
	protected function set($table, $data, $id = -1, $idField = '') {
		$query = '';
		$idNumber = -1;
		$idColumn = 'id';

		if ($id != -1)
			$idNumber = (string)$id;
		
		
		if (!empty($idField))
			$idColumn = $idField;
		
		// Prepare the statements to save data
		// IF ID is provided	update entry
		// ELSE					create new entry
		if ($idNumber != -1) {
			$data->$idColumn = '';
			$query = "UPDATE `{$this->prefix}{$table}` SET ";
			$innerQuery = '';
			
			// Iterate through each field
			foreach ($data as $key => $item) {
				if ($key != $idColumn) {
					$innerQuery .= "`{$key}` = '{$item}', ";
				}
				
				$counter++;
			}
			
			// Assemble the query
			$query .= substr($innerQuery, 0, -2);
			$query .= " WHERE `{$idColumn}` = '{$idNumber}'";
		}
		else {
			$columns = '';
			$values = '';
			
			// Iterate through each field in the record to create the query
			foreach ($data as $key => $item) {
				$columns .= "`{$key}`, ";
				$values .= "'{$item}', ";
			}
			
			$columns = substr($columns, 0 , -2);
			$values = substr($values, 0 , -2);
			
			// Assemble the query
			$query = "INSERT INTO `{$this->prefix}{$table}` ({$columns}) VALUES ({$values})";
		}

		return $this->db->query($query);
	}

	/**
	 * Retrieve records using a left join
	 * @param $tables: an array with two table names (no WP prefix)
	 * @param $where1: name of column of left table to match
	 * @param $where2: name of column of right table to match
	 * @param $columns1 (optional): specific columns of the first table to return
	 * @param $columns2 (optional): specific columns of second table to return
	 * @param $leftWhere (optional): specify columns to match using WHERE
	 * @param $orderBy (optional): order results using columns
	 * @return $result: the queried records
	 */
	protected function getLeftJoined($tables, $where1, $where2, $columns1 = array(), $columns2 = array(), $leftWhere = array(), $rightWhere = array(), $orderBy = array()) {
		if (is_array($tables) && count($tables) == 2) {
			$from = '';
			$where = '';
			$from1Build = '';
			$from2Build = '';
			$whereBuild = '';
			$orderClause = '';
			
			// Assemble columns for the first table
			if (is_array($columns1) && !empty($columns1)) {
				foreach ($columns1 as $column)
					$from1Build .= "`{$this->prefix}{$tables[0]}`.`{$column}`, ";
			}
			
			// Assemble columns for the second table
			if (is_array($columns2) && !empty($columns2)) {
				foreach ($columns2 as $column)
					$from2Build .= "`{$this->prefix}{$tables[1]}`.`{$column}`, ";
			}
			
			// IF		no columns specified for first table		AND		columns specified for second	Select all columns from first by using an *
			// ELSEIF	no columns specified for second table		AND		columns specified for first		Select all from second using an * 
			if (empty($from1Build) && !empty($from2Build))
				$from1Build = "`{$this->prefix}{$tables[0]}`.*, ";
			elseif (!empty($from1Build) && empty($from2Build))
				$from2Build = "`{$this->prefix}{$tables[1]}`.*, ";
			
			$from = substr($from1Build . $from2Build, 0, -2);
			
			// No specified columns for either table - select all from both tables
			if (empty($from))
				$from = '*';
				
			if (!empty($leftWhere) || !empty($rightWhere))
				$where = 'WHERE ';
				
			// Assemble WHERE clause, if needed
			if (is_array($leftWhere) && !empty($leftWhere)) {
				$whereBuild = '';
				
				foreach ($leftWhere as $lWhere)
					$whereBuild .= "`{$this->prefix}{$tables[0]}`.`{$lWhere['column']}` = '{$lWhere['value']}' AND ";
				
				$where .= substr($whereBuild, 0, -4);
			}
			
			// Assemble WHERE clause, if needed
			if (is_array($rightWhere) && !empty($rightWhere)) {
				$whereBuild2 = '';
				
				if (!empty($leftWhere))
					$whereBuild2 .= ' AND ';

				foreach ($rightWhere as $rWhere)
					$whereBuild2 .= "`{$this->prefix}{$tables[1]}`.`{$rWhere['column']}` = '{$rWhere['value']}' AND ";
				
				$where .= substr($whereBuild2, 0, -4);
			}
			
			$orderClause = $this->buildOrderClause($orderBy);
			
			// Build and execute the query
			$query = "SELECT {$from} FROM `{$this->prefix}{$tables[0]}` LEFT JOIN `{$this->prefix}{$tables[1]}`";
			$query .= " ON `{$this->prefix}{$tables[0]}`.`{$where1}` = `{$this->prefix}{$tables[1]}`.`{$where2}` {$where}{$orderClause}";
			return $this->db->getResults($query);
		}
		else
			return false;
	}

	/**
	 * Delete a record
	 * @param $table: table name (no WP prefix)
	 * @param $id: the id of the record to delete
	 * @param $idField: the column name of the ID column
	 * @param $where (optional): multidimensional array with parameters for the WHERE clause - notice: INCOMPLETE
	 * @return $result: the number of rows affected | false
	 * 
	 */
	public function delete($table, $id, $idField = 'id', $where = array()) {
      $query = '';
      $idNumber = $id;

      //if ($id != -1)
      //	$idNumber = (string)$id;
			
		// IF		$where contains parameters		Delete a record using specific columns
		// ELSEIF	$idNumber is set				Delete a record using the id
      if (!empty($where)) {
			// TODO finish this block
			// This code will be added in a future release
      }
      elseif ($idNumber != -1) {
      		$query = "DELETE FROM `{$this->prefix}{$table}` WHERE `{$idField}` = '{$idNumber}'";
      }
		
      return $this->db->query($query);
	}
	
	/**
	 * Build the ORDER clause from an array or multi-dimensional array
	 * @param $orderBy (array: "column", "value"): array or multi-dimensional array with the column data
	 * @param $handleAsMulti (optional, default: false): whether to build entire clause or only the column portion
	 * @return $orderClause: the entire ORDER clause
	 */
	private function buildOrderClause($orderBy, $handleAsMulti = false) {
		$data = (array)$orderBy;
		$multi = false;
		$orderClause = '';
		$order = '';
		
		// Do not proceed if it's not necessary
		if (empty($orderBy))
			return '';
		
		// Build as full clause or just handle the column part
		if ($handleAsMulti == false)
			$orderClause = ' ORDER BY';
		else
			$orderClause = '';
		
		// If first entry is array, then assume that several ORDER parameters must be handled
		if (is_array($data[0]))
			$multi = true;
		
		if ($multi) {
			foreach ($data as $item)
				$order .= $this->buildOrderClause($item, true);
		}
		else {
			if (!empty($orderBy) && is_array($orderBy)) {	
				// Make sure all data was provided
				if (array_key_exists('column', $orderBy) && array_key_exists('order', $orderBy)) {
					$direction = strtoupper($orderBy['order']);
					
					// Only ASC|DESC are allowed - else, default to ASC
					$direction = ($direction == 'ASC' || $direction == 'DESC') ? $direction : 'ASC';
					
					$order .= " `{$orderBy['column']}` {$direction}, ";
				}
			}
			else
				return '';
		}
		
		// Finalize assembly
		if (!empty($order)) {
			if ($handleAsMulti == false)
				$orderClause .= substr($order, 0, -2);
			else
				$orderClause .= $order;
			
			return $orderClause;
		}
		else
			return '';
	}
}
