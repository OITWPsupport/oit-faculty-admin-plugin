<?php

/**
 * iOfaModule
 * Interface to be implemented by the plugin modules to ensure that they have the necessary methods
 * @author Martin Ronquillo
 */
interface iOfaModule {
	
	/**
	 * This method must return the name of the module
	 */
	public function getName();
	
	/**
	 * This method must return an array with the information necessary to create WP admin subpages
	 */
	public function getMenuItems();
	
	/**
	 * This method must return a block of code to be presented on the plugin splash page
	 */
	public function getSplashBlock();
	
	/**
	 * This method needs to define the shortcodes used to present content on the front-end
	 */
	public function defineShortcodes();
}
