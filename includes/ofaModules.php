<?php

/**
 * ofaModules
 * Class to keep track of module data
 * @author Martin Ronquillo
 */
class ofaModules {
	private $moduleDirectory;
	private $modelDirectory;
	private $modules;
	private $moduleData;
	private $commonDataObjects;
	private $dataSource;
	
	/**
	 * Constructor
	 * Instantiate some variables
	 * @param $directory: directory where module files are stored
	 * @return none
	 */
	public function ofaModules($directory = '') {
		$this->moduleDirectory = $directory;
		$this->modules = array();
		$this->moduleData = array();
	}
	
	/**
	 * Set the directory where the model files are located
	 * @param $directory: the directory
	 * @return none
	 */
	public function setModelDirectory($directory = '') {
		$this->modelDirectory = $directory;
	}
	
	/**
	 * Use this function to load PHP files using 'require_once'
	 * @param $path: the file path
	 * @return true|false or message on fail
	 */
	public function loadRequisite($path) {
		if (file_exists($path) && require_once($path))
				return true;
		else
			return 'File "' . $path . '" not found';	
	}
	
	/**
	 * Add a module to the list of modules
	 * @param $module: the new module name (must be the same as the module class name)
	 * @return true|false or message on error
	 */
	public function addModule($module) {
		if (!array_key_exists($module, $this->modules)) {
			$this->modules[] = $module;
			$this->moduleData[$module] = array('dataObject' => array());
			$fileName = $this->moduleDirectory . '/' . $module . '.php';
			
			return $this->loadRequisite($fileName);
		}
		else
			return false;
	}
	
	/**
	 * Declare a new data source specifically for a module
	 * @param $dataObject: the name of the new data source
	 * @param $module: the module to add the new source to
	 * @return true|false or message on error
	 */
	public function addDataObject($dataObject, $module) {
		if (in_array($module, $this->modules)) {
			$this->moduleData[$module]['dataObject'][] = $dataObject;
			$fileName = $this->modelDirectory . '/' . $dataObject . '.php';
			
			return $this->loadRequisite($fileName);
		}	
		else
			return false;
	}
	
	/**
	 * Declare a new common data source, used by more than one module
	 * @param $dataObject: the name of the data object
	 * @return true|false
	 */
	public function addCommonDataObject($dataObject) {
		if (!in_array($dataObject, $this->commonDataObjects)) {
			$this->commonDataObjects[] = $dataObject;
			$fileName = $this->modelDirectory . '/' . $dataObject . '.php';
			
			return $this->loadRequisite($fileName);
		}
		else
			return false;
	}
	
	/**
	 * Retrieve the list of modules
	 * @param none
	 * @return $modules: the list of modules
	 */
	public function getModules() {
		return $this->modules;
	}
	
	/**
	 * Retrieve module data
	 * @param $module (optional): the module to retrieve data for
	 * @return $moduleData: return the module data or all data if module not specified
	 */
	public function getModuleData($module = '') {
		if (!empty($module))
			return $this->moduleData[$module];
		else
			return $this->moduleData;
	}
	
	/**
	 * Check if a module is loaded
	 * @param $module: name of the module
	 * @return true|false
	 */
	public function isLoaded($module) {
		if (in_array($module, $this->modules))
			return true;
		else
			return false;
	}
}
