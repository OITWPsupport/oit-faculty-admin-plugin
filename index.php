<?php
/**
Plugin Name: OIT Faculty Admin
Description: The official Boise State plugin for faculty and staff management.
Version: 1.1.17
Author: Martin Ronquillo, Boise State University OIT/EAS WP Support Team

This plugin was developed to provide an easy tool for listing personnel on WordPress websites.
Functionality can be added to the plugin via the usage of "modules" which are essentially
classes which contain the bulk of the functionality for a specific purpose (e.g. personnel
management, course management). Without modules, this plugin will still operate, but will not
provide any functionality.

The plugin is pre-loaded with these modules:
1) Personnel
	Manages personnel records to be listed on websites
2) Group
	Allows for "groups" to be defined and then works with the Personnel module to handle listings
3) Course
	Manages courses and semesters. Also interacts with the personnel module to associate course sections to personnel
*/


if( ! class_exists( 'Boise_State_OFA_Plugin_Updater' ) ){
	include_once( plugin_dir_path( __FILE__ ) . 'updater.php' );
}

$updater = new Boise_State_OFA_Plugin_Updater( __FILE__ );
$updater->set_username( 'OITWPsupport' );
$updater->set_repository( 'oit-faculty-admin-plugin' );
$updater->initialize();


/**
 * The plugin directory root
 */
define('OFADIR', dirname(__FILE__));

/**
 * Plugin URL
 */
define('OFAURL', plugins_url('/' . basename(OFADIR)));

/**
 * WordPress Admin URL (base)
 * Example usage: echo OFAADMIN . '?page=ofa';
 */
define('OFAADMIN', get_admin_url() . 'admin.php');

/**
 * Plugin version
 * Note: change version here and in the comment at the top of this file
 */
define('OFAVERSION', '1.1.17');

/**
 * Plugin slug
 * DO NOT CHANGE
 */
define('OFASLUG', plugin_basename(__FILE__));

/**
 * Bring in the necessary includes
 * These are necessary for the basic operation of the plugin
 */

require_once(OFADIR . '/includes/ofaDataObject.php');
require_once(OFADIR . '/includes/ofaDefinitions.php');
require_once(OFADIR . '/includes/iOfaModule.php');
require_once(OFADIR . '/includes/ofaBaseModule.php');
require_once(OFADIR . '/includes/ofaModules.php');
require_once(OFADIR . '/includes/ofaTheme.php');
require_once(OFADIR . '/includes/ofaFormValidator.php');
require_once(OFADIR . '/includes/ofaWpAdmin.php');
require_once(OFADIR . '/includes/ofaWpDatabase.php');

/**
 * Bring in helpers & third-party files
 * Code libraries specific to modules need to be placed in /includes/helpers/ directory
 */
 
require_once(OFADIR . '/includes/helpers/ofaGroupWidget.php');
require_once(OFADIR . '/includes/third-party/WpAutoUpdate.php');

/**
 * Load modules and prepare data for WordPress configuration
 * SEE the 'personnel' module for a sample of how to define new modules
 * Modules must be located under /modules/ and the file name needs to be the same as the class name
 * Data objects (models) must be located under /models/; the file and class name must match the name of the database table (without the WP prefix)
 * SEE /models/ofaEmployees.php for a sample of how to create a data object
 */

$ofaModules = new ofaModules(OFADIR . '/modules');
$ofaModules->addModule('personnel');
$ofaModules->addModule('group');
$ofaModules->addModule('course');

$ofaModules->setModelDirectory(OFADIR . '/models');
$ofaModules->addDataObject('ofaEmployees', 'personnel');
$ofaModules->addDataObject('ofaGroups', 'group');
$ofaModules->addDataObject('ofaEmployeesGroupsX', 'group');
$ofaModules->addDataObject('ofaCourses', 'course');
$ofaModules->addDataObject('ofaSections', 'course');
$ofaModules->addDataObject('ofaSemesters', 'course');

/**
 * Fire up the plugin - no more editing necessary, the plugin is now ready to be started
 */

$wpInterface = new ofaWpAdmin($ofaModules);
register_activation_hook( __FILE__, array('ofaWpAdmin', 'install'));
