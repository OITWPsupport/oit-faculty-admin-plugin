<?php

/**
 * ofaWpAdmin
 * This class governs the WP Admin interface for the plugin
 * Also handles the front-end setup
 * Do NOT invoke any method in this class yourself
 * @author Martin Ronquillo
 */
class ofaWpAdmin {
	private $db;
	private $modules;
	private $moduleData;
	private $security;
	private $theme;
	private $splashBlocks;
	
	/**
	 * Constructor
	 * This is the gateway to the plugin - this method starts the initialization process
	 * @param $ofaModules: an instantiated ofaModules class object
	 * @return none
	 */
	public function ofaWpAdmin($ofaModules) {
		$this->moduleData = $ofaModules;
		$this->db = new ofaWpDatabase();
		$this->security = new ofaWpSecurity($this->db);
		
		// Load the modules
		$this->loadModules();
		// All ready; now initialize the plugin
		add_action('init', array(&$this, 'update'));
		add_action('admin_menu', array(&$this, 'initialize'));
		add_action('wp_head', array(&$this, 'initializeFrontEnd'));
	}	
	
	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Run tasks when the plugin is activated
	 * This method is invoked by WP - do not invoke this method yourself
	 * @param none
	 * @return none
	 */
	public function install() {
		$db = new ofaWpDatabase();
		
		// Retrieve the SQL statements to create the plugin tables
		$tables = ofaTables::getTables($db->getPrefix());
		
		// Execute queries to set up plugin tables if they do not exist
		$db->queries($tables);
		
		// Create a directory under /wp-content/ to store uploaded files/images
		$dirCreated = ofaWpSecurity::createContentDirs();
		
		// Display error message if the file directory or any subdirectory could not be created
		if (!$dirCreated)
			echo $this->theme->buildMessage('File directory could not be created. File uploading will not work properly. Check file permissions and ensure that /wp-content/ is writeable.', 'error');
	}
	
	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Handle auto-updating - this method uses a third-party code library to check for plugin updates
	 * This method is invoked by WP - do not invoke this method yourself
	 * @param none
	 * @return none
	 */
	public function update() {
	    $remotePath = 'http://wpsupport.boisestate.edu/ofa/update.php';  
	    new WpAutoUpdate(OFAVERSION, $remotePath, OFASLUG);
	}
	
	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Load the plugin modules
	 * @param none
	 * @return none
	 */
	private function loadModules() {
		$moduleList = $this->moduleData->getModules();
		
		// Iterate throught the list of modules
		foreach ($moduleList as $module) {
			// Instantiate each module & pass the instance of ofaModules
			$this->modules[$module] = new $module($this->moduleData);
		}
		
		$this->loadShortcodes();
	}
	
	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Define the shortcodes for each module
	 * @param none
	 * @return none
	 */
	private function loadShortcodes() {
		foreach ($this->modules as $module) {
			// Run the shortcode definitions for each module
			$module->defineShortcodes();
		}
	}
	
	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Step through the setup process when a plugin page is called
	 * WP calls this method - do not invoke yourself
	 * Do not invoke any method which this method invokes
	 * @param none
	 * @return none
	 */
	public function initialize() {
		$this->setupModules();
		$this->registerScripts();
		$this->enqueue();
	}
	
	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Invoke several methods in each module
	 * @param none
	 * @return none
	 */
	private function setupModules() {
		// Check to see if current site is a Personnel or Group site
		$this->isPersonnelSite();
		$this->isGroupSite();
		
		// Set up the main item
		add_menu_page('OIT Faculty Admin', 'Faculty Admin', 'administrator', 'ofa', array(&$this, 'splashPage'), OFAURL . '/public/images/icon-16.png');
		
		// Iterate through each module to set up the necessary items, primarily set up pages
		foreach ($this->modules as $module) {
			// Get the splash block for the plugin splash page
			$this->splashBlocks[$module->getName()] = $module->getSplashBlock();
			
			// Retrieve the list of page menu items
			$pages = $module->getMenuItems();
			
			// Iterate through the menu items to add pages
			foreach ($pages as $page) {
				// Should this page be added?
				$allow = $this->security->doAddPage($page['security']);

				if ($allow)
					add_submenu_page('ofa', $page['page_title'], $page['menu_title'], $page['capability'], $page['menu_slug'], $page['function']);
			}
		}
	}
	
	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Check to see if the current site is associated with the current user
	 * @param none
	 * @return none
	 */
	private function isPersonnelSite() {
		// Retrieve the current WP user
		$user = ofaWpSecurity::getUser();
		$userEmail = $user->data->user_email;
		
		// Personnel module loaded, proceed...
		if ($this->moduleData->isLoaded('personnel')) {
			// Attempt to retrieve the personnel record for the current user
			$employees = new ofaEmployees();
			$employee = $employees->multiLoad(array(
				array(
					'column'	=>	'email',
					'value'		=>	$userEmail)));
			
			$employee = (array)$employee;
			
			// IF		user record retrieved		attempt to match personnel site name to current site path
			// ELSE									not a personnel site
			if (!empty($employee)) {
				$sitePath = '/' . $employee[0]->siteName . '/';
				$site = ofaWpSecurity::getCurrentSite();
				
				$currentSitePath = $site->path;
				
				if ($sitePath == $currentSitePath) {
					define('PERSONNELSITE', true);
					define('PERSONNELID', $employee[0]->id);
				}
				else
					define('PERSONNELSITE', false);
			}
			else
				define('PERSONNELSITE', false);
		}
		else
			define('PERSONNELSITE', false);
	}

	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Check to see if the current site is associated with a group
	 * @param none
	 * @return none
	 */
	private function isGroupSite() {
		$site = ofaWpSecurity::getCurrentSite();
		$siteName = substr(substr($site->path, 1), 0, -1);
		
		if (!empty($siteName)) {
			// Attempt to match the site name to a group site name
			$groups = new ofaGroups();
			$group = $groups->load(
				array(
					'column'	=>	'siteName',
					'value'		=>	$siteName));
					
			$group = (array)$group;
			
			// IF		record was retrieved		set group constants
			// ELSE		no record was retrieved		set GROUPSITE to false
			if (!empty($group)) {
				define('GROUPSITE', true);
				define('GROUPID', $group['id']);
			}
			else
				define('GROUPSITE', false);
		}
		else
			define('GROUPSITE', false);
	}
	
	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Load scripts for the plugin
	 * @param none
	 * @return none
	 */
	private function registerScripts() {
		wp_register_script('ofaForm', OFAURL . '/public/js/ofaForm.js');
	}
	
	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Enqueue scripts/styles for the plugin
	 * @param none
	 * @return none
	 */
	private function enqueue() {
		wp_enqueue_style('ofaStyle', OFAURL . '/public/css/ofaStyle.css');
		wp_enqueue_script('ofaForm');
	}

	/**
	 * RESTRICTED - DO NOT INVOKE
	 * Perform tasks to set up the front-end
	 * @param none
	 * @return none
	 */
	public function initializeFrontEnd() {
		echo '<link rel="stylesheet" type="text/css" href="' . OFAPUBLICURL . '/css/frontStyle.css" />';
	}
	
	/*
	 * The main plugin page
	 */
	public function splashPage() {
		$this->theme = new ofaTheme();
		$moduleCount = count($this->modules);
		
		// Main meta box
		$mainMetaBox = $this->theme->generateMetaBox('OIT Faculty Admin Information', '<p>The OIT Faculty Admin is enabled and running. See below to view information from the enabled plugin modules.</p><p><b>Modules Loaded:</b> <span style="color: green">' . $moduleCount . '</span></p>');
		
		// First sidebar meta box
		$adminURL = OFAADMIN;
		
		$sideBoxAContent = <<<HTML
<ol>
	<li><b><a href="{$adminURL}?page=group&action=new">Create</a> a group</b></li>
	<li>
		<b><a href="{$adminURL}?page=personnel&action=new">Create</a> a personnel record</b>
		<ol class="lettered">
			<li>Fill in the required fields (in bold)</li>
			<li>Save information</li>
			<li>Return to record using the "Edit" link</li>
			<li>At the bottom of the page, add group associations</li>
		</ol>
	</li>
	<li>
		<b>Display list of faculty or staff</b>
		<ol class="lettered">
			<li>Create new page</li>
			<li>Paste shortcode:<br/><code>[ofa-personnel][/ofa-personnel]</code></li>
		</ol>
	</li>
	<li>
		<b>Display profile on sub site (optional)</b>
		<ol class="lettered">
			<li>Create a WordPress sub site for the personnel (superadmin rights required)</li>
			<li>Enter the sub site slug (without slashes) into the Site Name field in the personnel record</li>
			<li>Create new page on the sub site</li>
			<li>Paste shortcode:<br/><code>[ofa-personnel view="profile"][/ofa-personnel]</code></li>
		</ol>
	</li>
	<li>
		<b>Set current semester</b>
		<ol class="lettered">
			<li><a href="{$adminURL}?page=semester&action=new">Create</a> a semester</li>
			<li><a href="{$adminURL}?page=semester">Set</a> as current semester</li>
		</ol>
	</li>
	<li>
		<b><a href="{$adminURL}?page=course&action=new">Create</a> a Course</b>
	</li>
	<li>
		<b><a href="{$adminURL}?page=section&action=new">Create</a> a section of the course</b>
	</li>
</ol>
HTML;
		
		$sideMetaBoxA = $this->theme->generateMetaBox('Quick Start', $sideBoxAContent);
		
		// Second sidebar meta box
		$sideMetaBoxB = $this->theme->generateMetaBox('About the Plugin', '<p>The <i>OIT Faculty Admin</i> is a custom-developed plugin for WordPress. Brought to you by the <b><a target="_blank" href="http://wpsupport.boisestate.edu"> OIT WordPress Support Team</a></b>.</p><p><b>Version:</b> <code>' . OFAVERSION . '</code><br /><b>Changelog:</b> <a target="_blank" href="' . OFAURL . '/changelog.txt">View</a><br /><b>Developer:</b> <a href="mailto:martinronquillo@boisestate.edu">Martin Ronquillo</a><br /><b>Quick Start Author:</b> <a href="mailto:davidsarver@boisestate.edu">David Sarver</a></p>');
		
		// Generate meta boxes for each module
		$moduleBoxes = '';
		
		foreach ($this->splashBlocks as $name => $content)
			$moduleBoxes .= $this->theme->generateMetaBox($name, $content);
		
		// Build the page content
		$html = <<<HTML
<div id="poststuff">
	<div id="post-body" class="metabox-holder columns-2">
		<div id="post-body-content">
			{$mainMetaBox}
			{$moduleBoxes}
		</div>
		<div id="postbox-container-1" class="postbox-container">
			{$sideMetaBoxA}
			{$sideMetaBoxB}
		</div>
	</div>
	<br class="clear">
</div>
HTML;

		// Display the page
		echo $this->theme->getDefaultHeader('OIT Faculty Admin');
		echo $html;
		echo $this->theme->getDefaultFooter();
	}
}
