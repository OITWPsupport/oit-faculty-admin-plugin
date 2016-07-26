<?php

/*
 * ofaDefinitions.php
 * A file for system-wide code & definitions
 * 
 * CONTENTS:
 * - ofaCommon
 * 		Class with generic helper methods
 * - ofaTables
 * 		Contains all the SQL statements to create tables for the plugin
 * - ofaWpSecurity
 * 		Contains methods to ensure that proper permissions are implemented
 * - ofaOptions
 * 		Class to interact with the ofaOptions table
 * - ofaGet
 * 		Helper class to safely retrieve GET variables
 * - ofaPost
 * 		Class to interact with POST requests
 */
 
/**
 * Path to directory for plugin files (e.g. stylesheets, scripts)
 */
define('OFAPUBLICURL', OFAURL . '/public');

/**
 * URL for files uploaded using the plugin
 * These files will not be deleted when the plugin is removed
 */
define('OFACONTENTURL', get_option('siteurl') . '/wp-content/ofa');

/**
 * Path for files uploaded using the plugin
 */
define('OFACONTENTDIR', WP_CONTENT_DIR . '/ofa');

/**
 * The path to personnel images
 */
define('OFABIOPHOTODIR', '/images');

/**
 * Personnel image fall-back
 */
define('OFABIOBLANK', OFAPUBLICURL . '/images/blankPhoto.jpg');

/**
 * Path to CV directory
 */
define('OFACVDIR', '/files');

/**
 * Path to syllabi directory
 */
define('OFASYLLABIDIR', '/syllabi');

/**
 * ofaCommon
 * Class with helpful methods
 * @author Martin Ronquillo
 */
class ofaCommon {
	
	/**
	 * Splits HTML content using "\n" and creates paragraphs
	 * @param $content: content to split
	 * @return $html: the content in paragraph form
	 */
	public static function nl2p($content) {
		$html = '';
		$paragraphs = explode("\n", $content);
		
		foreach ($paragraphs as $p)
			$html .= sprintf('<p>%s</p>', $p);
		
		return $html;
	}

	/**
	 * Truncate text to desired length
	 * @param $text: content to truncate
	 * @param $length (optional, default: 255): the desired length of the string
	 * @param $ellipsis (optional, default="..."): string to append at the end of the truncated string
	 * @return $truncated|$text on no truncation
	 */
	public static function truncate($text, $length = 255, $ellipsis = '...') {		
		if (strlen($text) > $length) {
			return substr($text, 0, $length) . $ellipsis;
		}
		else
			return $text;
	}
}

/**
 * ofaTables
 * Define all the module database table SQL statements in the 'getTables' method of this class
 * SEE the SQL statements below to ensure good SQL standards.
 * USE '$this->db->getPrefix' to ensure that the WP table prefix is appended to all database tables
 * USE this naming scheme for tables: WP Prefix + 'ofa' + TableName
 * @author Martin Ronquillo
 */
class ofaTables {
	
	/**
	 * Function to retrieve all the SQL statements for the plugin tables
	 * @param $prefix: the WP table prefix
	 * @return $tables: an array containing all the SQL statements
	 */
	public static function getTables($prefix) {
		$tables = array();
		
		// The options table for general use
		$tables[] = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}ofaOptions` (
`id` INT NOT NULL AUTO_INCREMENT,
`optionType` VARCHAR(20) NOT NULL,
`optionValue` VARCHAR(255),
PRIMARY KEY (`id`)
) ENGINE = MYISAM
SQL;
		
		// The employees table for the personnel module
		$tables[] = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}ofaEmployees` (
`id` INT NOT NULL AUTO_INCREMENT,
`published` VARCHAR(3) NOT NULL,
`firstName` VARCHAR(35) NOT NULL,
`middleInitial` VARCHAR(2),
`lastName` VARCHAR(35) NOT NULL,
`doctorate` VARCHAR(3),
`siteName` VARCHAR(35),
`email` VARCHAR(75) NOT NULL,
`phone` VARCHAR(20) NOT NULL,
`mailStop` VARCHAR(8),
`officeNumber` VARCHAR(10),
`officeHours` VARCHAR(75),
`jobTitle` VARCHAR(75) NOT NULL,
`jobClassification` VARCHAR(8) NOT NULL,
`bio` TEXT,
`education` TEXT,
`featuredPublications` TEXT,
`awards` TEXT,
`teachingAreas` TEXT,
`cv` VARCHAR(255),
`photo` VARCHAR(255),
PRIMARY KEY (`id`),
UNIQUE (`email`),
KEY (`lastName`)
) ENGINE = MYISAM
SQL;

		// The groups table for the group module
		$tables[] = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}ofaGroups` (
`id` INT NOT NULL AUTO_INCREMENT,
`name` VARCHAR(75) NOT NULL,
`siteName` VARCHAR(35),
`groupType` VARCHAR(20) NOT NULL,
`email` VARCHAR(75),
`phone` VARCHAR(20),
`mailStop` VARCHAR(8),
`officeNumber` VARCHAR(10),
`officeHours` VARCHAR(75),
`about` VARCHAR(255),
PRIMARY KEY (`id`),
UNIQUE (`name`),
KEY (`siteName`)
) ENGINE = MYISAM
SQL;

		// A table to resolve the relationship between personnel and group
		$tables[] = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}ofaEmployeesGroupsX` (
`id` INT NOT NULL AUTO_INCREMENT,
`employeeId` INT NOT NULL,
`groupId` INT NOT NULL,
`listFirst` VARCHAR(3) NOT NULL,
`position` VARCHAR(75),
PRIMARY KEY (`id`),
KEY (`employeeId`),
KEY (`groupId`)
) ENGINE = MYISAM
SQL;

		// Sections table for Course module
		$tables[] = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}ofaSections` (
`id` INT NOT NULL AUTO_INCREMENT,
`semesterId` INT NOT NULL,
`employeeId` INT NOT NULL,
`courseId` INT NOT NULL,
`section` VARCHAR(8) NOT NULL,
`hours` VARCHAR(75),
`room` VARCHAR(10),
`syllabus` VARCHAR(255),
`link` VARCHAR(100),
`notes` VARCHAR(255),
PRIMARY KEY (`id`),
KEY (`semesterId`),
KEY (`employeeId`)
) ENGINE = MYISAM
SQL;

		// Courses table
		$tables[] = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}ofaCourses` (
`id` INT NOT NULL AUTO_INCREMENT,
`departmentId` INT NOT NULL,
`course` VARCHAR(10) NOT NULL,
`number` VARCHAR(10) NOT NULL,
`title` VARCHAR(100) NOT NULL,
`description` TEXT,
`credits` TINYINT(2),
PRIMARY KEY (`id`),
KEY (`departmentId`),
KEY (`course`),
KEY (`number`),
KEY (`title`)
) ENGINE = MYISAM
SQL;

		// Semesters table for Course module
		$tables[] = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}ofaSemesters` (
`id` INT NOT NULL AUTO_INCREMENT,
`name` VARCHAR(20) NOT NULL,
`startDate` VARCHAR(10),
`endDate` VARCHAR(10),
PRIMARY KEY (`id`)
) ENGINE = MYISAM
SQL;
		
		return $tables;
	}
}

/**
 * ofaWpSecurity
 * Class with the security rules for the modules and module pages
 * @author Martin Ronquillo
 */
class ofaWpSecurity {
	const ALL			= 'WP_ALL';
	const FACULTY		= 'WP_FACULTY_SITE';
	const GROUP			= 'WP_GROUP_SITE';
	const MAIN			= 'WP_MAIN_SITE';
	const NONFACULTY	= 'WP_NON_FACULTY_SITE';
	const SUB			= 'WP_SUB_SITE';
	private $rules;
	private $db;
	
	/**
	 * Constructor
	 * @param $database: The current instance of ofaWpDatabase
	 * @return none
	 */
	public function ofaWpSecurity($database) {
		$this->db = $database;
		
		// Make a copy of the constants, to use internally
		$this->rules = array(
			'ALL'			=> 'WP_ALL',
			'FACULTY'		=> 'WP_FACULTY_SITE',
			'GROUP'			=> 'WP_GROUP_SITE',
			'MAIN'			=> 'WP_MAIN_SITE',
			'NONFACULTY'	=> 'WP_NON_FACULTY_SITE',
			'SUB' 			=> 'WP_SUB_SITE');
	}
	
	/**
	 * Retrieve the current post object
	 * @param none
	 * @return none
	 */
	public static function getPost() {
		global $post;
		return $post;
	}
	
	/**
	 * Retrieve the information of the current user
	 * @param none
	 * @return $currentUser: an array with the information of the current user
	 */
	public static function getUser() {
      	return wp_get_current_user();
	}
	
	/**
	 * Get information about the current site
	 * @param none
	 * @return $information: an object with the blog information
	 */
	public static function getCurrentSite() {
		global $blog_id;
		return array();
		// return get_blog_details(
		//	array(
		//		'blog_id' => $blog_id));
	}
	
	/**
	 * Get information about the upload directory
	 * @param none
	 * @return $information: an object with the upload directory information
	 */
	public static function getUploadDir() {
		return wp_upload_dir();
	}
	
	/**
	 * Attempt to create a directory under /wp-content/
	 * @param none
	 * @return true|false
	 */
	public static function createContentDirs() {
		if (is_writable(WP_CONTENT_DIR)) {
			$ofaDir = WP_CONTENT_DIR . '/ofa';
			
			if (!file_exists($ofaDir)) {
				$success = false;
				
				$root = mkdir($ofaDir);
				$files = mkdir($ofaDir . '/files/');
				$images = mkdir($ofaDir . '/images/');
				$syllabi = mkdir($ofaDir . '/syllabi/');
				
				if ($root == true && $files == true && $images == true && $syllabi == true)
					$success = true;
				
				return $success;
			}
			elseif (file_exists($ofaDir) && is_dir($ofaDir))
				return true;
		}
		
		return false;
	}
	
	/**
	 * Decide if a page should be made available to the user
	 * @param $data (rule|data): an array with the page rules
	 * @return true|false
	 */
	public function doAddPage($data) {
		global $blog_id;
		extract(shortcode_atts(array(
            'rule' => self::MAIN,
            'data' => array()
        ), $data));
		
		// Compare the selected rule to the type of blog and decide if page should be made available
		if (in_array($rule, $this->rules)) {
			switch($rule) {
				case 'WP_ALL':
					return true;
					break;
				case 'WP_FACULTY_SITE':
					if (defined('PERSONNELSITE')) {
						if (PERSONNELSITE == true)
							return true;
					}
					break;
				case 'WP_GROUP_SITE':
					if (defined('GROUPSITE')) {
						if (GROUPSITE == true)
							return true;
					}
					break;
				case 'WP_MAIN_SITE':
					return is_main_site();
					break;
				case 'WP_NON_FACULTY_SITE':
					if (defined('PERSONNELSITE')) {
						if (PERSONNELSITE == false)
							return true;
					}
					break;
				case 'WP_SUB_SITE':
					$isMain = is_main_site();
					
					if (!isMain)
						return true;
					break;
				default:
					return false;
					break;
			}
		}
		
		return false;
	}
}

/**
 * ofaOptions
 * Model class to manage the ofaSemesters table
 * @author Martin Ronquillo
 */
class ofaOptions extends ofaDataObject {
	const table = 'ofaOptions';
	
	private $options;
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function ofaOptions() {
		parent::__construct();
		
		$this->options = array();
		$this->loadOptions();
	}
	
	/**
	 * Build an array of existing options
	 * @param none
	 * @return none
	 */
	private function loadOptions() {
		$options = $this->clean($this->get(self::table, -1, '', array(), array(), array('column' => 'id', 'order' => 'ASC')));

		if (!empty($options)) {
			foreach ($options as $option)
				$this->options[$option->optionType] = array(
					'id'		=> $option->id,
					'option'	=> $option->optionType);
					
			return true;
		}

		return false;
	}
	
	/**
	 * Retrieve the value of an option
	 * @param $option: the option name
	 * @return $value|false
	 */
	public function getOption($option) {
		if (array_key_exists($option, $this->options)) {
			$record = $this->clean($this->get(self::table, -1, '', array(array('column' => 'optionType', 'value' => $option)), array(), array('column' => 'id', 'order' => 'ASC')));
			$record = (array)$record;
			return $record[0]->optionValue;
		}
		else
			return false;
	}
	
	/**
	 * Save the option data
	 * @param $option: the option name
	 * @param $value: the new option value
	 * @return $result: query results
	 */
	public function setOption($option, $value) {
		if (array_key_exists($option, $this->options)) {
			$validData = $this->blankObject(self::table);
			$validData->optionType = $this->options[$option]['option'];
			$validData->optionValue = $value;
			
			$id = $this->options[$option]['id'];
			return $this->set(self::table, $validData, $id);
		}
		else {
			$validData = $this->blankObject(self::table);
			$validData->optionType = $option;
			$validData->optionValue = $value;
			
			return $this->set(self::table, $validData);
		}
	}
}

/**
 * ofaGet
 * A class to retrieve GET variables
 * To define new variables, add the variable name to the 'variables' array
 * @author Martin Ronquillo
 */
class ofaGet {
	
	/**
	 * Retrieve a GET variable - only defined variables will be retrieved
	 * @param $variable: the variable name to retrieve
	 * @return $value: the variable value
	 */
	public static function get($variable) {
		$variables = array(
			'action',
			'delete',
			'did',
			'filter',
			'i',
			'id',
			'ignore',
			'oPaged',
			'oQ',
			'page',
			'paged',
			'ref',
			's',
			'view'
			);
		
		// If the variable requested is defined, return the value
		if (in_array($variable, $variables)) {
			if (array_key_exists($variable, $_GET)) {
				switch ($variable) {
					case 'action':
					case 'filter':
					case 'ignore':
					case 'oQ':
					case 'page':
					case 's':
					case 'view':
						return (string)$_GET[$variable];
						break;
					case 'delete':
					case 'did':
					case 'i':
					case 'id':
					case 'oPaged':
					case 'paged':
						return (int)$_GET[$variable];
						break;
					case 'ref':
						return rawurldecode(htmlspecialchars($_GET[$variable]));
						break;
					
				}
			}
			else
				return '';
		}
		
		return false;
	}
	
	/**
	 * Get the URL & parameters of the current page
	 * @param $encoded (default: true): whether to encode the URL using rawurlencode
	 * @return $url: the URL of the current page
	 */
	public static function getCurrentPage($encoded = true) {
		$url = 'http';
		
		if ($_SERVER["HTTPS"] == "on")
			$url .= 's';
		
		$url .= '://';
		$url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		
		if ($encoded == true)
			return rawurlencode($url);
		else
			return $url;
	}
	
	/**
	 * Get the current page URL without parameters
	 * @param none
	 * @return $url: the URL of current page with no parameters
	 */
	public static function getCurrentPageNoParams() {
		$url = self::getCurrentPage(false);
		$urlParts = explode('?', $url);
		return $urlParts[0];
	}
}

/**
 * ofaPost
 * Class to interact with POST requests
 * @author Martin Ronquillo
 */
class ofaPost {
	
	/**
	 * Check if a page was submitted using a POST request
	 * @param none
	 * @return true|false
	 */
	public static function isPost() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
			return true;
		else
			return false;
	}
	
	/**
	 * Retrieve the contents of $_POST
	 * @param none
	 * @return $data: the POST contents
	 */
	public static function get() {
		return (object)$_POST;
	}
	
	/**
	 * Upload files using a single function to perform the grunt work
	 * @param $files: The $_FILES array with the upload data
	 * @param $uploadDir: The target directory (e.g. /some/random/directory). No trailing slash '/' necessary
	 * @param $fileName (optional, default: uploaded file name): Specify an explicit name for the uploaded file
	 * @param $uploadElement (optional, default: first element): If form contains more than one 'file' element, select the one to use
	 * @param $fileTypes (optional, default: doc|docx|gif|jpg|pdf|png|rtf|txt|xls|xlsx): Specify what file types to allow
	 * @return A message on error, or true on success
	 */
	public static function upload($files, $uploadDir, $fileName = '', $uploadElement = '', $fileTypes = array()) {
		$fileElement = '';
		$types = array(
			'doc',
			'docx',
			'gif',
			'jpg',
			'pdf',
			'png',
			'rtf',
			'txt',
			'xls',
			'xlsx');
		$name = '';
		
		// If no file element is provided, use the first array in the $_FILES array
		if (empty($uploadElement)) {
			$elements = array_keys($files);
			$fileElement = $elements[0];
		}
		elseif (array_key_exists($uploadElement, $files))
			$fileElement = $uploadElement;
		else
			return 'Error: file data unusable.';
			
		// No file uploaded, return null
		if (empty($files[$fileElement]['name']))
			return 0;
			
		// If provided with an array of file types to accept, override the default
		if (!empty($fileTypes) && is_array($fileTypes))
			$types = $fileTypes;
		
		// Find the file type
		$fileType = self::getFileExtension($files[$fileElement]['name']);
			
		// Set the file name
		$name = (!empty($fileName)) ? $name = $fileName . '.' . $fileType : $files[$fileElement]['name'];
		
		// If file type is allowable, attempt to process the file
		if (in_array($fileType, $types)) {		
			if ($files[$fileElement]['error'])
				return 'Upload not successful due to file error.';
			elseif ($files[$fileElement]['name'] != '') {
				if (move_uploaded_file($files[$fileElement]['tmp_name'], $uploadDir . '/' . $name))
					return true;
				else
					return 'Error: file could not be saved.';
			}
		}
		else
			return 'Error: file type cannot be uploaded for security reasons.';
	}

	/**
	 * Get the file extension from a file name
	 * @param $fileName: name of the file
	 * @return $extension: the extension of the file
	 */
	public static function getFileExtension($fileName) {
		return substr($fileName, strrpos($fileName, '.') + 1);
	}
}
