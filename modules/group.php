<?php

/**
 * group
 * The group module for the OFA plugin
 * See the personnel module for detailed comments.
 * @author Martin Ronquillo
 */
class group extends ofaBaseModule implements iOfaModule {
	const title = 'Group';
	private $moduleData;
	private $groupTypes;
	
	/*
	 * Constructor
	 */
	public function group($moduleData) {
		// Call the parent constructor
		parent::__construct();
		
		// Save the instance of ofaModules
		$this->moduleData = $moduleData;
		
		// Define the group types
		// Group types can be added/removed by editing this multi-dimensional array
		// The first array is a placeholder. Do not remove for validation to function properly
		$this->groupTypes = array(
			array(
				'display'	=>	'Select',
				'value'		=>	''),
			array(
				'display' => 'Administration',
				'value' => 'Administration'),
			array(
				'display' => 'Committee',
				'value' => 'Committee'),
			array(
				'display' => 'Department',
				'value' => 'Department'),
			array(
				'display' => 'Group',
				'value' => 'Group'),
			array(
				'display' => 'Organization',
				'value' => 'Organization'),
			array(
				'display' => 'Team',
				'value' => 'Team'));
		
		//Define the menu items for the module
		$this->menuItems[] = array(
								'page_title' => 'Group Management',
								'menu_title' => 'Groups',
								'capability' => 'administrator',
								'menu_slug' => 'group',
								'function' => array($this, 'mainPage'),
								'security' => array('rule' => ofaWpSecurity::ALL));
	}
	
	public function getName() {
		return self::title;
	}
	
	public function getMenuItems() {				
		return $this->menuItems;
	}
	
	public function getSplashBlock() {
		$html = '<p>Group is a module which represents an organizational structure. Groups are used to organize faculty & staff into categories based on organizations (e.g. Departments, Committees, etc.).</p>';
		$html .= '<p><b>Available Shortcode:</b><br /><code>[ofa-group][/ofa-group]</code></p>';
		$html .= '<p><b>Shortcode Attributes:</b><br /><code>view="list-all"</code> (default, lists all Departments)</br /><code>view="profile"</code><br /><code>search="true"</code><br /><code>search="false"</code> (default)<br /><code>category="?"</code> (List groups based on a category - see below)</p>';
		$html .= '<p><b>Group Categories:</b><br />The following are the categories which can be used with the <code>category</code> attribute:<br /><code>Administration</code><br /><code>Committee</code><br /><code>Department</code><br /><code>Group</code><br /><code>Organization</code><br /><code>Team</code></p>';
		return $html;
	}
	
	public function defineShortcodes() {
		/*
		 * Shortcode: 	ofa-group
		 * Attributes:
		 * 		view		=	"list-all" (default)
		 * 		view		=	"profile"
		 * 		search		=	"true"
		 * 		search		=	"false" (default)
		 * 		category	=	(category, default "Department")
		 */
		add_shortcode('ofa-group', array(&$this, 'groupShortcode'));
	}
	
	/**
	 * The group "page"
	 * WordPress calls this method when any group "page" is requested
	 * In this method, the request is routed to the proper internal method
	 */
	public function mainPage() {
		$action = ofaGet::get('action');
		
		// IF		current site is personnel site		show users their memberships
		// ELSE											allow access to the other "pages"
		if (PERSONNELSITE) {
			$this->personnelMembershipPage(PERSONNELID);
		}
		else {
			// Display the right "page"
			switch($action) {
				case 'new':
				case 'edit':
					if (!GROUPSITE)
						$this->mainEditPage($action);
					else
						$this->mainEditPage('edit', GROUPID);
					break;
				case 'membership':
					$this->mainMembershipPage();
					break;
				case 'delete':
				default:
					if (!GROUPSITE)
						$this->mainListPage();
					else
						$this->mainMembershipPage(GROUPID);
					break;
			}
		}
	}
	
	/**
	 * When users (faculty) are in their site admin, they can view their group memberships
	 */
	private function personnelMembershipPage($id) {
		echo $this->theme->getDefaultHeader('My Group Memberships');
		
		// Handle pagination
		$paged = ofaGet::get('paged');
		$currentPage = (!empty($paged)) ? $paged : 1;

		// Handle search
		$post = ofaPost::get();
		
		if (!empty($search))
			$currentView = 'search';
		
		// Load a list of groups
		$employeesGroupsX = new ofaEmployeesGroupsX();
		$memberships = '';
		
		// Load data
		$memberships = $employeesGroupsX->loadJoinedByEmployeeId($id);

		// Create the pagination HTML
		$pagination = $this->theme->pagination(count((array)$memberships), $currentPage, 20, OFAADMIN . '?page=group&paged=');
		
		// Only show the records on the current page (pagination)
		$memberships = $employeesGroupsX->paginate($memberships, $currentPage, 20);
					
		$rows = array();
		
		// Pack the data for the table using the employee entries
		foreach ($memberships as $m) {
			$row = array();
			$row[] = '<strong>' . $m->name . '</strong>';
			$row[] = $m->position;
			$row[] = $m->listFirst;
			
			$rows[] = $row;
		}

		// Generate the list table
		// $headings, $data, $action = '', $pagination, $bulkActions = array(), $id = array(), $search = false
		echo $this->theme->generateTable(
			array('Group Name', 'Position', 'Listed First'),
			$rows,
			OFAADMIN . '?page=group',
			$pagination,
			array(),
			array(),
			false,
			'ofaPersonalMembershipList');
		
		echo $this->theme->getDefaultFooter();
	}
	
	/**
	 * Add/edit a group
	 */
	private function mainEditPage($action, $siteId = 0) {
		// Page Setup
		$continueURL = OFAADMIN . '?page=group';
		$ref = ofaGet::get('ref');
		
		if (!empty($ref))
			$continueURL = $ref;
		
		$group = new ofaGroups();
		$pageTitle = 'Edit Group';
		$newButton = '';
		$invalidFields = '';
		$submitted = false;
		$saved = 0;

		if ($action === 'new')
			$pageTitle = 'Add Group';
		
		if ($action === 'edit')
			$newButton = OFAADMIN . '?page=group&action=new';
		
		// Array to hold form data
		$data = $group->blank();
		
		// Load user data if a profile is being edited
		$id = ofaGet::get('id');
		
		if (!empty($siteId))
			$id = (int)$siteId;

		if (!empty($id)) {
			$data = $group->load($id);
		}
		
		// Attempt to process the form
		if (ofaPost::isPost() && check_admin_referer('ofaGroupEdit', 'ofaNonce')) {
			$data = ofaPost::get();
			
			// Validate/sanitize the data
			$validated = $group->validate($data);
			
			// IF		array is returned		data is invalid and messages are displayed
			// ELSE								save data
			if (is_array($validated)) {
				$data = $group->clean($data);
				echo $this->theme->buildMessage('One or more fields are invalid. Please fix the fields in red.', 'error');
				
				// Mark the fields in red by telling jquery which fields to color
				foreach ($validated as $invalid)
					$invalidFields .= '<span class="invalidFieldName">' . $invalid . '</span>';
			}
			else {
				$saved = $group->save($validated);
				$submitted = true;
			}
		}
		
		// Form logic starts here
		// ---------------
		
		// WP security
		$nonce = wp_nonce_field('ofaGroupEdit', 'ofaNonce', true, false);
		
		// Group types
		$groupType = $this->theme->generateComplexField('select', 'groupType', $this->groupTypes, $data->groupType);
		
		// Build the form
		$deleteURL = OFAADMIN . '?page=group&action=delete&id=' . $data->id;
		$form = <<<HTML
<a href="{$continueURL}">&larr; Back</a>
{$invalidFields}
<form id="groupForm" method="POST" action="{$_SERVER['REQUEST_URI']}" enctype="multipart/form-data">
	{$nonce}
	<h3>Group Information</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="name">Group Name</label></th>
				<td>
					<input id="name" type="text" class="regular-text" maxlength="75" name="name" value="{$data->name}" />
					<span class="description">Name must be unique</span>
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="groupType">Group Type</label></th>
				<td>
					{$groupType}
				</td>
			</tr>
			<tr>
				<th><label for="siteName">Site Name</label></th>
				<td>
        			<input id="siteName" type="text" class="regular-text" maxlength="35" name="siteName" value="{$data->siteName}" />
        			<span class="description">If group has a WP subsite, enter the subsite name (excluding the slashes - e.g. marketing)</span>
				</td>
			</tr>
			<tr>
				<th><label for="email">Email</label></th>
				<td>
					<input id="email" type="email" class="regular-text" maxlength="75" name="email" value="{$data->email}" />
				</td>
			</tr>
			<tr>
				<th><label for="phone">Phone</label></th>
				<td>
					<input id="phone" type="text" class="all-options" maxlength="20" name="phone" value="{$data->phone}" />
        			<span class="description">(xxx) xxx-xxxx</span>
				</td>
			</tr>
			<tr>
				<th><label for="mailStop">Mail Stop</label></th>
				<td>
					<input id="mailStop" type="text" class="all-options" maxlength="8" name="mailStop" value="{$data->mailStop}" />
				</td>
			</tr>
			<tr>
				<th><label for="officeNumber">Office Number</label></th>
				<td>
					<input id="officeNumber" type="text" class="all-options" maxlength="20" name="officeNumber" value="{$data->officeNumber}" />
				</td>
			</tr>
			<tr>
				<th><label for="officeHours">Office Hours</label></th>
				<td>
					<input id="officeHours" type="text" class="regular-text" maxlength="75" name="officeHours" value="{$data->officeHours}" />
				</td>
			</tr>
			<tr>
				<th><label for="about">About</label></th>
				<td>
					<textarea id="about" name="about" cols="60" rows="10">{$data->about}</textarea>
					<span class="description"><code>&lt;b&gt;&lt;i&gt;&lt;u&gt;</code> tags can be used in the field<span>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input class="button-primary" type="submit" value="Update Group" />
		<input type="hidden" name="id" value="{$data->id}" />
		</br><a href="{$deleteURL}">Delete</a>
	</p>
</form>
HTML;
		
		// Display the page
		echo $this->theme->getDefaultHeader('Group Management', $newButton);
		
		// IF		form not submitted		display form
		// ELSE								display a message for success or on failure
		if (!$submitted)
			echo $form;
		else {
			if ($saved == 1) {
				echo $this->theme->buildMessage('Record has been successfully saved.');
				echo '<a href="' . $continueURL . '">Continue &rarr;</a>';
			}
			else
				echo $this->theme->buildMessage('Record could not be saved. Please go back and try again.', 'error');
		}
		
		echo $this->theme->getDefaultFooter();
	}

	/**
	 * View group memberships
	 */
	private function mainMembershipPage($siteId = 0) {
		$continueURL = (isset($_GET['ret'])) ? $_GET['ret'] : OFAADMIN . '?page=group';
		$id = ofaGet::get('id');
		$paged = ofaGet::get('paged');
		$currentPage = (!empty($paged)) ? $paged : 1;
		
		$employeesGroupsX = new ofaEmployeesGroupsX();
		
		if (!empty($siteId))
			$id = (int)$siteId;
		
		// Delete an entry, if the delete parameter is passed
		$delete = ofaGet::get('delete');
		$deleted = 0;
		
		if (!empty($delete))
			$deleted = $employeesGroupsX->delete_1($delete);
		
		if ($deleted == 1)
			echo $this->theme->buildMessage('Membership record successfully deleted.');
		
		$group = new ofaGroups();
		$currentGroup = $group->load($id);
		
		echo $this->theme->getDefaultHeader('Group Membership: ' . $currentGroup->name);
		
		if ($siteId == 0)
			echo '<a href="' . $continueURL . '">&larr; Back</a>';
		else
			echo '<br /><a class="button-primary" href="?page=group&action=edit&id=' . $currentGroup->id . '">Edit This Group</a>';
		
		$memberships = $employeesGroupsX->loadJoinedEmployeeName($id);
		
		// Create the pagination HTML
		$pagination = $this->theme->pagination(count((array)$memberships), $currentPage, 20, OFAADMIN . '?page=group&action=membership&id=' . $id . '&paged=');
		
		// Only show the records on the current page (pagination)
		$memberships = $employeesGroupsX->paginate($memberships, $currentPage, 20);
					
		$rows = array();
		
		$employees = new ofaEmployees();
		
		// Pack the data for the table using the employee entries
		foreach ($memberships as $g) {
			$name = $employees->getName($g);
			$link = OFAADMIN . '?page=group&action=membership&id=' . $id . '&delete=' . $g->id;
			$row = array();
			$row[] = sprintf('<strong>%s</strong><div class="row-actions"><span><a title="Delete Membership" href="%s">Delete Membership</a></span></div>', $name, $link);
			$row[] = $g->position;
			$row[] = '<a href="mailto:' . $g->email . '">' . $g->email . '</a>';
			
			$rows[] = $row;
		}

		// Generate the list table
		// $headings, $data, $action = '', $pagination, $bulkActions = array(), $id = array(), $search = false
		echo $this->theme->generateTable(
			array(
				'Member Name',
				'Position',
				'Email'),
			$rows,
			OFAADMIN . '?page=group&action=membership',
			$pagination,
			array(),
			array(),
			false,
			'ofaMembershipList');
		
		echo $this->theme->getDefaultFooter();
	}
	
	/**
	 * List all groups
	 */
	private function mainListPage() {
		echo $this->theme->getDefaultHeader('Group Management', OFAADMIN . '?page=group&action=new' . '&ref=' . ofaGet::getCurrentPage());
				$group = new ofaGroups();
				$ignoreDelete = ofaGet::get('ignore');

		$action = ofaGet::get('action');
		if ($action == 'delete') {
          //echo 'weener';
			$deleteId = ofaGet::get('id');
          //echo $deleteId;
          //echo $employee->area(5,4);
			$delete = $group->delete('ofaGroups', $deleteId);
          //echo $delete;
			if ($delete == 1)
				echo $this->theme->buildMessage('Record deleted successfully');
			else
				echo $this->theme->buildMessage('Record was not deleted. Please try again.', 'error');
		}
		
		// Handle view & pagination
		$view = ofaGet::get('view');
		$currentView = (!empty($view)) ? $view : 'all';
		
		$paged = ofaGet::get('paged');
		$currentPage = (!empty($paged)) ? $paged : 1;
      
		// Handle search
		$post = ofaPost::get();
		$search = $post->s;
		$getSearch = ofaGet::get('s');
		
		// Handle search & pagination. If not on first page of navigaton, see if search results need to be displayed
		if (empty($search) && !empty($getSearch))
			$search = $getSearch;
		
		if (!empty($search))
			$currentView = 'search';
		
		// Load a list of groups
		$group = new ofaGroups();
		$groups = '';
		
		// Decide what data should be loaded
		switch($currentView) {
			case 'search':
				echo $this->theme->buildMessage('Search was executed on Group Name.');
				$groups = $group->search('name', $search);
				break;
			default:
				$groups = $group->multiLoad();
				break;
		}

		// Create the pagination HTML
		$pagination = $this->theme->pagination(count((array)$groups), $currentPage, 20, OFAADMIN . '?page=group&view=' . $view . '&s=' . $search . '&paged=');
		
		// Only show the records on the current page (pagination)
		$groups = $group->paginate($groups, $currentPage, 20);
					
		$rows = array();
		
		// Pack the data for the table using the employee entries
		foreach ($groups as $g) {
			$link = OFAADMIN . '?page=group&action=edit&id=' . $g->id . '&ref=' . ofaGet::getCurrentPage();
			$deleteLink = OFAADMIN . '?page=group&action=delete&id=' . $g->id . '&ref=' . ofaGet::getCurrentPage();
			$membershipLink = OFAADMIN . '?page=group&action=membership&id=' . $g->id;
			$row = array();
			$row[] = sprintf('<strong><a class="row-title" title="Edit this Group" href="%s">%s</a></strong><div class="row-actions"><span><a title="Edit this Group" href="%s">Edit</a></span> | <span><a title="View Group Membership" href="%s">Group Membership</a></span> | <span class="trash"><a title="Delete Group Membership" href="%s">Delete</a></span></div>', $link, $g->name, $link, $membershipLink, $deleteLink);
			$row[] = '<a href="mailto:' . $g->email . '">' . $g->email . '</a>';
			$row[] = $g->siteName;
			$row[] = $g->id;
			
			$rows[] = $row;
		}

		// Generate the list table
		// $headings, $data, $action = '', $pagination, $bulkActions = array(), $id = array(), $search = false
		echo $this->theme->generateTable(
			array(
				'Group Name',
				'Email',
				'WP Sub Site',
				'Group ID'),
			$rows,
			OFAADMIN . '?page=group',
			$pagination,
			array(),
			array(),
			true,
			'ofaGroupList');
		
		echo $this->theme->getDefaultFooter();
	}

	/**
	 * Handle shortcode requests
	 * Route request based on the 'view' attribute
	 */
	public function groupShortcode($atts, $content = null) {

		extract(shortcode_atts(array(
            'view'		=> 'list-all',
            'category'	=> '',
            'search'	=> 'false'
        ), $atts));
				
		switch($view) {
			case 'list-all':
				return $this->listAllShortcode($category, $search);
				break;
			case 'profile':
				$group = new ofaGroups();
				return $this->buildGroupProfile($group, 0, ofaGet::get('ref'));
				break;
		}
	}

	/**
	 * List all groups
	 */
	private function listAllShortcode($category, $doSearch) {
      
		$html = '';
		$group = new ofaGroups();
		
		$view = ofaGet::get('view');
		$id = ofaGet::get('id');
		$paged = ofaGet::get('oPaged');
		
		// IF no view is requested, list all personnel
		// ELSEIF	'view' = 'profile'		Show personnel profile
		// TODO: incorporate personnel site url if subsite is defined
		if (empty($view)) {
			$post = ofaWpSecurity::getPost();
			$search = ofaGet::get('oQ');
			$allGroups = '';
			
			if (empty($paged))
				$paged = 1;
			
			// Search is to be enabled, add search box HTML
			if ($doSearch == 'true') {
				$action = ofaGet::getCurrentPageNoParams();
				
				$html .= <<<HTML
<form id="ofaSearch" action="{$action}" method="get">
	<input type="text" name="oQ" value="" />
	<input type="submit" value="Search" />
	<a href="?view=">View All</a>
</form>
HTML;
			}

			// Handle the retrieval by search
			if ($doSearch == 'true' && !empty($search)) {
				if (empty($category))
					$allGroups = $group->search('name', $search);
				else
					$allGroups = $group->search('name', $search,
						array(
							array(
								'column' =>'groupType',
								'value' => $category)));
			}
			else {
				if (empty($category))
					$allGroups = $group->multiLoad();
				else
					$allGroups = $group->multiLoad(
						array(
							array(
								'column' => 'groupType',
								'value' => $category)));
			}

			$pagination = '<div class="ofaPagination" style="float: right;">' . $this->theme->pagination(count((array)$allGroups), $paged, 10, site_url($post->post_name) . '/?oQ=' . $search . '&oPaged=') . '</div>';
			
			$groups = $group->paginate($allGroups, $paged, 10);
			
			$html .= $pagination;
			$html .= '<hr />';
			$html .= $this->buildListings($group, $groups);
			
			$html .= $pagination;
		}
		elseif ($view == 'profile') {
			$html .= $this->buildGroupProfile($group, $id);
		}
		
		return $html;
	}

	/**
	 * Build group listings
	 */
	private function buildListings($group, $groups) {
		$html = '';
		
		// IF		there are records		display listing
		// ELSE								display notice of no records found
		if (count((array)$groups) > 0) {
			// Iterate through each group record to create the list
			foreach ($groups as $entry) {
				$profile = '';
				
				// Build the URL
				if (empty($entry->siteName))
					$profile = '?view=profile&id=' . $entry->id;
				else
					$profile = network_home_url() . $entry->siteName . '/';
				
				// Compile the entry
				$html .= sprintf('<div class="ofaPersonnelEntry"><h2><a href="%s">%s</a></h2><h6>%s</h6></div><hr />', $profile, $entry->name, $entry->groupType);
			}
		}
		else
			$html .= '<div class="warning red"><h4>No Records Found</h4>The search returned an empty result set. Please try again.</div>';
		
		return $html;
	}
	
	/**
	 * Generate a group profile
	 */
	private function buildGroupProfile($group, $id) {
      
		$back = '';
		$profile = '';
		
		// Grab the group record
		if (!empty($id))
			$profile = $group->load($id);
		else {
			$site = ofaWpSecurity::getCurrentSite();
			$siteName = substr(substr($site->path, 1), 0 , -1);
			$profile = $group->load(
				array(
					'column'	=>	'siteName',
					'value'		=>	$siteName));
		}

		// Title
		$title = '';
		
		if (!empty($id))
			$title = '<h2>' . $profile->name . '</h2><h6>' . $profile->groupType . '</h6>';
		else
			$title .= '<br />';

		// Build up the optional profile pieces
		$email = '';
		$phone = '';
		$mailStop = '';
		$officeNumber = '';
		$officeHours = '';
		$about = '';

		if (!empty($profile->email))
			$email = '<a href="mailto:' . $profile->email . '">' . $profile->email . '</a> &rsaquo;';
		
		if (!empty($profile->phone))
			$phone = $profile->phone . ' &rsaquo;';
			
		if (!empty($profile->mailStop))
			$mailStop = '<b>Mail Stop:</b> ' . $profile->mailStop;
			
		if (!empty($profile->officeNumber))
			$officeNumber = '<b>Office Number:</b> ' . $profile->officeNumber . ' &rsaquo;';
		
		if (!empty($profile->officeHours))
			$officeHours = '<b>Office Hours:</b> ' . $profile->officeHours;
		
		if (!empty($profile->about))
			$about = '<p>' . $profile->about . '</p>';
		
		// Some logic
		if (!empty($profile->email) && empty($profile->phone) && empty($profile->mailStop))
			$email = substr($email, 0, -9);
		
		if (!empty($profile->phone) && empty($profile->mailStop))
			$phone = substr($phone, 0, -9);
		
		if (!empty($profile->officeNumber) && empty($profile->officeHours))
			$officeNumber = substr($officeNumber, 0, -9);
		
		return sprintf('%s<br />%s%s %s %s<br /> %s %s %s', $back, $title, $email, $phone, $mailStop, $officeNumber, $officeHours, $about);
	}
}
