<?php

/**
 * ofaBaseModule
 * Abstract class to act as the parent for the modules
 * @author Martin Ronquillo
 */
abstract class ofaBaseModule {
	protected $theme;
	protected $menuItems;

	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function __construct() {
		// Instantiate the theme class for the modules to use
		$this->theme = new ofaTheme();
	}
}
