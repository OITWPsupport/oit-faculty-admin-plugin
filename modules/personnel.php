<?php

/**
 * Use this class as an example of how to create an OFA module
 * When creating a new module, inherit the class from 'ofaBaseModule' and implement the 'iOfaModule' interface
 * 
 * Required methods:
 * 	getName()
 * 	getMenuItems()
 * 	getSplashBlock()
 * 	defineShortcodes()
 */

/**
 * personnel
 * The Personnel module for the OFA plugin
 * @author Martin Ronquillo
 */
class personnel extends ofaBaseModule implements iOfaModule {
	const title = 'Personnel';
	private $moduleData;
	private $classificationValues;

	/**
	 * Constructor
	 */
	public function personnel($moduleData) {
		// Call the parent constructor
		parent::__construct();
		
		// Save the instance of ofaModules
		$this->moduleData = $moduleData;
		
		// Job classification values
      // Types can be added/removed here. //values are limited to eight characters.
		// First array is a placeholder. Do not remove to ensure proper validation functionality
		$this->classificationValues = array(
			array(
				'display'	=>	'Select',
				'value'		=>	''),
			array(
				'display'	=> 'Faculty',
				'value'		=> 'Faculty'),
			array(
				'display'	=> 'Staff',
				'value'		=> 'Staff'),
          	array(
            	'display'	=> 'Volunteer',
				'value'		=> 'Voluntee'),
	        array(
            	'display'	=> 'Executive Instructor',
				'value'		=> 'Executiv'),
            array(
            	'display'	=> 'Mentor',
				'value'		=> 'Mentor'),
            array(
            	'display'	=> 'Board Member',
				'value'		=> 'Board Me'),
        	array(
            	'display'	=> 'Other',
				'value'		=> 'Other'));
		
		// Define the WP menu items for the module in a multi-dimensional array
		// Each menu item must correspond to a page
		// All values are required 
		$this->menuItems[] = array(
								'page_title' => 'Personnel Management',
								'menu_title' => 'Personnel',
								'capability' => 'administrator',
								'menu_slug' => 'personnel',
								'function' => array($this, 'mainPage'),
								'security' => array('rule' => ofaWpSecurity::ALL));
	}
	
	/**
	 * Retrieve the name of the module
	 * @param none
	 * @return $title: the name of the module
	 */
	public function getName() {
		return self::title;
	}
	
	/**
	 * Retrieve the menu items to be used by the module
	 * @param none
	 * @return $menuItems: An array with the menu item data
	 */
	public function getMenuItems() {					
		return $this->menuItems;
	}
	
	/**
	 * Return a block of HTML to present on the plugin splash page
	 * The splash page is an intuitive tool for engaging users
	 * @param none
	 * @return $html: the HTML code for the block
	 */
	public function getSplashBlock() {
		$html = '<p>Personnel handles the storing & presentation of faculty & staff records.</p>';
		$html .= '<p><b>Available Shortcode:</b><br /><code>[ofa-personnel][/ofa-personnel]</code></p>';
		$html .= '<p><b>Shortcode Attributes:</b><br /><code>view="list-all"</code> (default)</br /><code>view="profile"</code><br /><code>search="true"</code><br /><code>search="false"</code> (default)<br /><code>groupid="#"</code> (The ID of the group to list)</p>';
		return $html;
	}
	
	/**
	 * Define all the shortcodes used on the front-end for presenting content
	 * @param none
	 * @return none
	 */
	public function defineShortcodes() {
		/*
		 * Shortcode: 	ofa-personnel
		 * Attributes:
		 * 		view	=	"list-all" (default)
		 * 		view	=	"profile"
		 * 		search	=	"true"
		 * 		search	=	"false" (default)
		 * 		groupid	=	(integer)
		 */
		add_shortcode('ofa-personnel', array(&$this, 'personnelShortcode'));
	}
	
	/**
	 * Main Personnel "page"
	 * This method is called by WP when an admin requests the page
	 * This method acts as a router to direct users to the right "page"
	 * WordPress only calls this method for all personnel pages, but here, we direct the request to the right internal method
	 * @param none
	 * @return none
	 */
	public function mainPage() {
		$action = ofaGet::get('action');

		// IF		current site is a personnel site		only allow user to edit their profile
		// ELSE												allow access to all pages
		if (PERSONNELSITE) {
			$this->mainEditPage($action, true);
		}
		else {
			// Display the right "page"
			switch($action) {
				case 'new':
				case 'edit':
					$this->mainEditPage($action);
					break;
				case 'delete':
				default:
					$this->mainListPage();
					break;
			}
		}
	}
	
	/**
	 * Personnel edit page
	 * @param $action: page action (new/edit)
	 * @param $restricted (optional, default: false): set to true to limit editing to only a user's profile
	 * @return none
	 */
	private function mainEditPage($action, $restricted = false) {
		// Page Setup
		$continueURL = OFAADMIN . '?page=personnel';
		$ref = ofaGet::get('ref');
		
		if (!empty($ref))
			$continueURL = $ref;
		
		$employee = new ofaEmployees();
		$pageTitle = 'Edit Personnel Profile';
		$newButton = '';
		$invalidFields = '';
		$submitted = false;
		$saved = 0;
		$groupsSaved = 0;
		
		if ($action === 'new')
			$pageTitle = 'Add Person';
		
		if ($action === 'edit')
			$newButton = OFAADMIN . '?page=personnel&action=new';
		
		// Array to hold form data
		$data = $employee->blank();

		// Load user data if a profile is being edited
		if ($restricted)
			$id = (int)PERSONNELID;
		else
			$id = ofaGet::get('id');

		if (!empty($id))
			$data = $employee->load($id);
		
		// Group element
		if ($this->moduleData->isLoaded('group') && !empty($id) && !$restricted) {
			$groupWidget = new ofaGroupWidget($this->theme);
			$groupWidget->addHeadings();
			$groupWidget->setupFooter();
			$groups = $groupWidget->getElementByEmployee($data->id);
		}
		else
			$groups = '';
		
		// Attempt to process the form
		if (ofaPost::isPost() && check_admin_referer('ofaPersonnelEdit', 'ofaNonce')) {
			$data = ofaPost::get();

			// Take care of the photo upload
			if (!empty($_FILES['photo']['name'])) {
				$randomIndentifier = substr(time(), -3);
				$photoExtension = ofaPost::getFileExtension($_FILES['photo']['name']);
				$photoName = $randomIndentifier . $data->lastName . $data->firstName . 'Photo';
				$uploadPhoto = ofaPost::upload($_FILES, OFACONTENTDIR . OFABIOPHOTODIR, $photoName, 'photo', array('gif', 'jpg', 'jpeg', 'png'));
				
				if($data->photoOld != ''){
					unlink(OFACONTENTDIR . OFABIOPHOTODIR . '/' . $data->photoOld);
				}
				
				if ($uploadPhoto)
					$data->photo = $photoName . '.' . $photoExtension;	
			}
			else
				$data->photo = $data->photoOld;
			if ($data->removePhoto=="on" && $data->photoOld!='' && $data->photo==$data->photoOld){
				unlink(OFACONTENTDIR . OFABIOPHOTODIR . '/' . $data->photoOld);
				$data->photo = '';
			}
			
			
			// Handle CV upload
			if (!empty($_FILES['cv']['name'])) {
				$randomIndentifier = substr(time(), -3);
				$cvName = $randomIndentifier . $data->lastName . $data->firstName . 'CV';
				$uploadCV = ofaPost::upload($_FILES, OFACONTENTDIR . OFACVDIR, $cvName, 'cv', array('pdf'));
				
				if($data->cvOld != ''){
					unlink(OFACONTENTDIR . OFACVDIR . '/' . $data->cvOld);
				}
				
				if ($uploadCV)
					$data->cv = $cvName . '.pdf';
			}
			else
				$data->cv = $data->cvOld;
			if ($data->removeCv=="on" && $data->cvOld!='' && $data->cv==$data->cvOld){
				unlink(OFACONTENTDIR . OFACVDIR . '/' . $data->cvOld);
				$data->cv = '';
			}
			
			// Group variables
			$groupsToSave = array();
			$groupsValid = true;
			
			// Process the group widget and clean up the data record
			if ($this->moduleData->isLoaded('group') && !empty($id) && !$restricted) {
				$validatedGroups = $groupWidget->process($data);
				$data = $groupWidget->cleanDataRecord($data);
				
				// Iterate through all the retrieved group records to make sure they're valid
				foreach ($validatedGroups as $validateGroup) {
					if (is_array($validateGroup)) {
						$groupsValid = false;
						echo $this->theme->buildMessage('A group association was not valid. Please see below and trying adding the assocation again.', 'error');
					}
					else
						$groupsToSave[] = $validateGroup;
				}
				
				$groups = $groupWidget->getElement((object)$groupsToSave);
			}
			
			// Validate/sanitize the data
			$validated = $employee->validate($data);
			
			// IF		array is returned or groups invalid		data is invalid and messages are displayed
			// ELSE												save data
			if (is_array($validated)) {
				$data = $employee->clean($data);
				
				echo $this->theme->buildMessage('One or more fields are invalid. Please fix the fields in red.', 'error');
				
				// Mark the fields in red by telling jquery which fields to color
				foreach ($validated as $invalid)
					$invalidFields .= '<span class="invalidFieldName">' . $invalid . '</span>';
			}
			elseif ($groupsValid == false) {
				echo $this->theme->buildMessage('All invalid group association entries were removed.', 'error');
				echo $this->theme->buildMessage('Profile not saved. Press "Update" to save changes.', 'error');
			}
			elseif (is_object($validated) && $groupsValid == true) {
				$saved = $employee->save($validated);
				
				// Attempt to save group data
				if ($this->moduleData->isLoaded('group') && !empty($id) && !$restricted)
					$groupsSaved = $groupWidget->save($groupsToSave);
				
				$submitted = true;
			}
		}
		
		// Form logic below
		// ---------------
		
		// WP security
		$nonce = wp_nonce_field('ofaPersonnelEdit', 'ofaNonce', true, false);

		// Back button
		$back = '';
		
		if (!$restricted)
			$back = '<a href="' . $continueURL . '">&larr; Back</a>';

		// 'Publish' radios
		$publishedValues = array(
			array(
				'display' => '<b>Yes</b> - Allow this profile to display in search results, faculty & staff lists, and on profile pages',
				'value' => 'Yes'),
			array(
				'display' => '<b>No</b> - This profile will not show up on any public page',
				'value' => 'No'));
		$published = $this->theme->generateComplexField('radio', 'published', $publishedValues, $data->published);
		
		// Site name should be read-only if being edited by personnel
		$siteNameReadonly = '';
		
		if ($restricted)
			$siteNameReadonly = ' readonly';
		
		// Job Classification
		$jobClassification = $this->theme->generateComplexField('select', 'jobClassification', $this->classificationValues, $data->jobClassification);
      //echo $data->jobClassification;
		// Photo
		$photoField = $this->theme->generateUploadField('photo', 'photo', 'For optimal displaying, photo must be 200px X 225px & in GIF, JPG or PNG format', $data->photo, OFACONTENTURL . OFABIOPHOTODIR);
		
		// CV
		$cvField = $this->theme->generateUploadField('file', 'cv', 'CV must be uploaded in PDF format', $data->cv, OFACONTENTURL . OFACVDIR);
				
		// Assemble the form
		$deleteURL = OFAADMIN . '?page=personnel&action=delete&id=' . $data->id;
		$form = <<<HTML
{$back}
{$invalidFields}
<form id="personnelForm" method="POST" action="{$_SERVER['REQUEST_URI']}" enctype="multipart/form-data">
	{$nonce}
	<h3>Preferences</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="published">Display Publicly?</label></th>
				<td>
					{$published}
				</td>
			</tr>
			<tr>
				<th><label for="siteName">Site Name</label></th>
				<td>
        			<input id="siteName" type="text" class="regular-text" maxlength="35" name="siteName" value="{$data->siteName}"{$siteNameReadonly} />
        			<span class="description">If person has a WP subsite, enter the subsite path (excluding the slashes - e.g. jdoe)</span>
				</td>
			</tr>
		</tbody>
	</table>
	
	<h3>Basic Information</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="firstName">First Name</label></th>
				<td>
        			<input id="firstName" type="text" class="regular-text" maxlength="35" name="firstName" value="{$data->firstName}" />
				</td>
			</tr>
			<tr>
				<th><label for="middleInitial">Middle Initial</label></th>
				<td>
        			<input id="middleInitial" type="text" class="small-text" maxlength="2" name="middleInitial" value="{$data->middleInitial}" />
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="lastName">Last Name</label></th>
				<td>
        			<input id="lastName" type="text" class="regular-text" maxlength="35" name="lastName" value="{$data->lastName}" />
				</td>
			</tr>
			<tr>
				<th><label for="doctorate">Doctorate</label></th>
				<td>
        			<input id="doctorate" type="text" class="small-text" maxlength="3" name="doctorate" value="{$data->doctorate}" />
        			<span class="description">e.g. PhD/JD</span>
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="jobTitle">Job Title</label></th>
				<td>
        			<input id="jobTitle" type="text" class="regular-text" maxlength="75" name="jobTitle" value="{$data->jobTitle}" />
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="jobClassification">Job Classification</label></th>
				<td>
					{$jobClassification}
				</td>
			</tr>
			<tr>
				<th><label for="photo">Photo</label></th>
				<td>
					{$photoField}
					<br/><label for="removePhoto">Remove the photo? </label><input id="removePhoto" name="removePhoto" type="checkbox" />
				</td>
			</tr>
			<tr>
				<th><label for="cv">CV</label></th>
				<td>
					{$cvField}
					<br/><label for="removeCv">Remove the file? </label><input id="removeCv" name="removeCv" type="checkbox" />
				</td>
			</tr>
		</tbody>
	</table>
	
	<h3>Contact Information</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="email">Email</label></th>
				<td>
        			<input id="email" type="email" class="regular-text" maxlength="75" name="email" value="{$data->email}" />
        			<span class="description">Email address must be unique</span>
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="phone">Phone</label></th>
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
        			<input id="officeNumber" type="text" class="all-options" maxlength="10" name="officeNumber" value="{$data->officeNumber}" />
				</td>
			</tr>
			<tr>
				<th><label for="officeHours">Office Hours</label></th>
				<td>
        			<input id="officeHours" type="text" class="regular-text" maxlength="200" name="officeHours" value="{$data->officeHours}" />
				</td>
			</tr>
		</tbody>
	</table>
	
	<h3>Biography &amp; Experience</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label for="bio">Bio</label></th>
				<td>
        			<textarea id="bio" name="bio" cols="60" rows="10">{$data->bio}</textarea>
        			<span class="description"><code>&lt;b&gt;&lt;i&gt;&lt;u&gt;</code> tags can be used in the field. Place content on a new row to create paragraphs.<span>
				</td>
			</tr>
			<tr>
				<th><label for="education">Education</label></th>
				<td>
        			<textarea id="education" name="education" cols="60" rows="6">{$data->education}</textarea>
        			<span class="description">Enter one entry per row</span>
				</td>
			</tr>
			<tr>
				<th><label for="featuredPublications">Featured Publications</label></th>
				<td>
        			<textarea id="featuredPublications" name="featuredPublications" cols="60" rows="6">{$data->featuredPublications}</textarea>
        			<span class="description"><code>&lt;b&gt;&lt;i&gt;&lt;u&gt;</code> tags can be used in the field. Enter one entry per row</span>
				</td>
			</tr>
			<tr>
				<th><label for="awards">Awards</label></th>
				<td>
        			<textarea id="awards" name="awards" cols="60" rows="4">{$data->awards}</textarea>
        			<span class="description">Enter one entry per row</span>
				</td>
			</tr>
			<tr>
				<th><label for="teachingAreas">Teaching Areas</label></th>
				<td>
        			<textarea id="teachingAreas" name="teachingAreas" cols="60" rows="4">{$data->teachingAreas}</textarea>
        			<span class="description">Enter one entry per row</span>
				</td>
			</tr>
		</tbody>
	</table>
	{$groups}
	<p class="submit">
		<input class="button-primary" type="submit" value="Update Profile" />
		<input type="hidden" name="id" value="{$data->id}" />
		</p>
		<p>
		</br><a href="{$deleteURL}">Delete</a>
	</p>
</form>
HTML;

		// START DISPLAYING THE PAGE
		echo $this->theme->getDefaultHeader($pageTitle, $newButton);

		// IF		form not submitted		display form
		// ELSE								display a message for success or on failure
		if (!$submitted) {
			// New record is being created - let user know that group membership cannot be set yet
			if (empty($id) && !$restricted)
				echo $this->theme->buildMessage('Group memberships can be added after a user is created.');
		
			echo $form;
		}
		else {
			if (($saved == 0 || $saved == 1) && ($groupsSaved > -1)) {
				echo $this->theme->buildMessage('Record has been successfully saved.');
				echo '<a href="' . $continueURL . '">Continue &rarr;</a>';
			}
			else
				echo $this->theme->buildMessage('Record could not be saved. Please go back and try again.', 'error');
		}

		echo $this->theme->getDefaultFooter();
	}
	
	/**
	 * Personnel list page
	 * @param none
	 * @return none
	 */
	private function mainListPage() {
		echo $this->theme->getDefaultHeader('Personnel Management', OFAADMIN . '?page=personnel&action=new' . '&ref=' . ofaGet::getCurrentPage());
				$employee = new ofaEmployees();
      			$ignoreDelete = ofaGet::get('ignore');
				
		// Delete a record
		$action = ofaGet::get('action');
		if ($action == 'delete') {
          //echo 'weener';
			$deleteId = ofaGet::get('id');
          //echo $deleteId;
          //echo $employee->area(5,4);
			$delete = $employee->delete('ofaEmployees', $deleteId);
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
		
		// Load a list of employees
		$employee = new ofaEmployees();
		$employees = '';
		
		// Decide what data should be loaded
		switch($currentView) {
			case 'published':
				$employees = $employee->multiLoadIsPublished();
				break;
			case 'search':
				echo $this->theme->buildMessage('Search was performed on First Name & Last Name.');
				$employees = $employee->search(
					array(
						'lastName',
						'firstName'),
					$search);
				break;
			case 'not published':
			case 'unpublished':
				$employees = $employee->multiLoadIsPublished(false);
				break;
          	case 'all':
          		$employees = $employee->multiLoad();
          		break;
          	case 'faculty':
			case 'staff':
			default:
				$jobClassification = ucfirst($currentView);
				
				$employees = $employee->multiLoad(array(
					array(
						'column' => 'jobClassification',
						'value' => $jobClassification)));
				break;
		}
		
		// Create the pagination HTML
		$pagination = $this->theme->pagination(count((array)$employees), $currentPage, 20, OFAADMIN . '?page=personnel&view=' . $view . '&s=' . $search . '&paged=');
		
		// Only show the records on the current page (pagination)
		$employees = $employee->paginate($employees, $currentPage, 20);
					
		$rows = array();
		
		// Pack the data for the table using the employee entries
		foreach ($employees as $e) {
			$name = $employee->getName($e, true);
			$link = OFAADMIN . '?page=personnel&action=edit&id=' . $e->id . '&ref=' . ofaGet::getCurrentPage();
			$deleteLink = OFAADMIN . '?page=personnel&action=delete&id=' . $e->id . '&paged=' . $currentPage;
			$row = array();
			$row[] = sprintf('<strong><a class="row-title" title="Edit this Person" href="%s">%s</a></strong><div class="row-actions"><span><a title="Edit this Person" href="%s">Edit</a></span> | <span class="trash"><a title="Delete Person" href="%s">Delete</a></span></div>', $link, $name, $link, $deleteLink);
			$row[] = '<a href="mailto:' . $e->email . '">' . $e->email . '</a>';
			$row[] = $e->jobTitle;
			$row[] = $e->siteName;
			$row[] = $e->published;
			
			$rows[] = $row;
		}

      //poll the database to see what job classifications are being used and
      //display list of different view options
      //All, Published, Not Published will always be displayed.
        $listItems[0] = array(
				'display' => 'All',
				'class' => 'all',
				'href' => '?page=personnel&view=all');
		$classificationValuesData = $employee->loadClassifications();
        for ($x=1; $x<count($this->classificationValues)+1; $x++) {
          //echo $this->classificationValues[$x]['display'];
    		foreach ( $classificationValuesData as $classificationValuesDatax ){
              //echo $classificationValuesDatax->jobClassification;
                if (strtolower($this->classificationValues[$x]['value'])==strtolower($classificationValuesDatax->jobClassification)){
                  array_push($listItems,array('display' => $this->classificationValues[$x]['display'],
                                             'class' => $this->classificationValues[$x]['value'],
                                             'href' => '?page=personnel&view='.strtolower($this->classificationValues[$x]['value'])));
                }
			}
		}
      array_push($listItems,array(
				'display' => 'Published',
				'class' => 'published',
				'href' => '?page=personnel&view=published'),
			array(
				'display' => 'Not Published',
				'class' => 'unpublished',
				'href' => '?page=personnel&view=unpublished'));

      
		// Display list of different view options
      echo $this->theme->generateDisplayList($listItems/*array(
			array(
				'display' => 'All',
				'class' => 'all',
				'href' => '?page=personnel&view=all'),
			array(
				'display' => 'Faculty',
				'class' => 'faculty',
				'href' => '?page=personnel&view=faculty'),
			array(
				'display' => 'Staff',
				'class' => 'staff',
				'href' => '?page=personnel&view=staff'),
			array(
				'display' => 'Published',
				'class' => 'published',
				'href' => '?page=personnel&view=published'),
			array(
				'display' => 'Not Published',
				'class' => 'unpublished',
'href' => '?page=personnel&view=unpublished'))*/,
			$currentView);

		// Generate the list table
		echo $this->theme->generateTable(
			array(
				'Name',
				'Email',
				'Job Title',
				'WP Sub Site',
				'Published'),
			$rows,
			OFAADMIN . '?page=personnel',
			$pagination,
			array(),
			array(),
			true,
			'ofaPersonnelList');
		
		echo $this->theme->getDefaultFooter();
	}
	
  
	/**
	 * Personnel shortcode router
	 * Called by WP when a user users a personnel shortcode
	 * @param $atts: attributes as specified by user
	 * @param $content (optional, default: null): content between the shortcode tags
	 * @return $html: shortcode markup
	 */
	public function personnelShortcode($atts, $content = null) {
		extract(shortcode_atts(array(
			'search'	=> 'false',
            'view' 		=> 'list-all',
            'groupid'	=> '0'
        ), $atts));
		
		switch($view) {
			case 'list-all':
				return $this->listAllShortcode($atts);
				break;
			case 'profile':
				$employee = new ofaEmployees();
				return $this->buildProfilePage($employee, 0, ofaGet::get('ref'));
				break;
		}
	}
	
	/**
	 * Handles displaying of all personnel
	 * @param $atts: array of attributes
	 * @return $html: generated HTML to display
	 */
	private function listAllShortcode($atts) {
		$html = '';
		$employee = new ofaEmployees();
		
		$view = ofaGet::get('view');
		$id = ofaGet::get('id');
		$paged = ofaGet::get('oPaged');
		
		// IF no view is requested, list all personnel
		// ELSEIF	'view' = 'profile'		Show personnel profile
		// TODO: incorporate personnel site url if subsite is defined
		if (empty($view)) {
			$post = ofaWpSecurity::getPost();
			$doSearch = ($atts['search'] == 'true' || $atts['search'] == 'false') ? $atts['search'] : 'false';
			$groupId = $atts['groupid'];
			$search = ofaGet::get('oQ');
			$allEmployees = '';

			// If group is being listed, disable search
          //if ($groupId != 0)
          	//$doSearch = false;
			
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
			if ($doSearch == 'true' && !empty($search) && $groupId == 0) {
				$allEmployees = $employee->search(
					array(
						'lastName',
						'firstName'),
						$search,
						array(
							array(
								'column' => 'published',
								'value' => 'Yes')));
			}
          elseif ($doSearch == 'true' && !empty($search) && $groupId > 0) {
				$employeesGroupsX = new ofaEmployeesGroupsX();
				$allEmployees = $employeesGroupsX->loadSearchJoinedEmployeeRecord(
					$groupId,
                  array(
						'lastName',
						'firstName'),
						$search,
						array(
							array(
								'column' => 'published',
								'value' => 'Yes')));
			}
			elseif ($groupId > 0) {
				$employeesGroupsX = new ofaEmployeesGroupsX();
				$allEmployees = $employeesGroupsX->loadJoinedEmployeeRecord($groupId);
			}
			else {
				$allEmployees = $employee->multiLoadIsPublished();
			}

			$pagination = '<div class="ofaPagination" style="float: right;">' . $this->theme->pagination(count((array)$allEmployees), $paged, 10, site_url($post->post_name) . '/?oQ=' . $search . '&oPaged=') . '</div>';
			
			$employees = $employee->paginate($allEmployees, $paged, 10);
			
			$html .= $pagination;
			$html .= '<hr />';
			$html .= $this->buildListings($employee, $employees);
			
			$html .= $pagination;
		}
		elseif ($view == 'profile') {
			$html .= $this->buildProfilePage($employee, $id);
		}
		
		return $html;
	}

	/**
	 * Builds individual listings for the personnel lists
	 * @param $employee: an ofaEmployees instance
	 * @param $employees: object with the employee records to convert to list entries
	 * @param $currentPage: the current page URL
	 * @return $html: generated HTML for the listing
	 */
	private function buildListings($employee, $employees) {
		$html = '';
		
		// IF		there are records		display listing
		// ELSE								display notice of no records found
		if (count((array)$employees) > 0) {
			// Iterate through each employee record to create the list
			foreach ($employees as $entry) {
				$profile = '';
				
				if (empty($entry->siteName))
					$profile = '?view=profile&id=' . $entry->id;
				else
					$profile = network_home_url() . $entry->siteName . '/';
				
				// Handle the profile image on the left side
				$source = (!empty($entry->photo)) ? OFACONTENTURL . OFABIOPHOTODIR . '/' . $entry->photo : OFABIOBLANK;
				// $left = sprintf('<a href="%s"><img id="ofaBioPhotoHalf" src="%s" title="%s" /></a>', $profile, $source, $name);
				$left = sprintf('<a href="%s"><img class="ofaBioPhotoHalf" id="ofaBioPhotoHalf%s" src="%s" alt="%s" /></a>', $profile, $entry->id, $source, $employee->getName($entry));
				
				// Use position if group listing, otherwise use job title
				$title = (isset($entry->position) && !empty($entry->position)) ? $entry->position : $entry->jobTitle;
				
				// Display office hours / room
				$office = '';
				
				if (!empty($entry->officeNumber))
					$office .= '<b>Office</b>: ' . $entry->officeNumber;
				
				if (!empty($entry->officeNumber) && !empty($entry->officeHours))
					$office .= ' &bull; ';
				
				if (!empty($entry->officeHours))
					$office .= '<b>Hours</b>: ' . $entry->officeHours;
				
				// Assemble the right side of the row
				$right = sprintf('<h2><a href="%s">%s</a></h2><h6>%s</h6><a href="mailto:%s">%s</a> &bull; %s<br />%s', $profile, $employee->getName($entry), $title, $entry->email, $entry->email, $entry->phone, $office);
				
				// Compile the entry
				$html .= sprintf('<div class="ofaPersonnelEntry"><div class="one_third">%s</div><div class="two_thirds last">%s</div></div><div class="clear"></div><hr />', $left, $right);
			}
		}
		else
			$html .= '<div class="warning red"><h4>No Records Found</h4>An empty result set was requested. Please try again.</div>';
		
		return $html;
	}

	/**
	 * Build personnel profile page
	 * @param $employee: an instance of ofaEmployees
	 * @param $id: the employee id to generate a profile for
	 * @param $backUrl (optional): URL for the "Back" link
	 * @return $html: the HTML markup for the profile page
	 */
	private function buildProfilePage($employee, $id) {
		$back = '';
		$profile = '';
		
		if (!empty($id))
			$profile = $employee->load($id);
		else {
			$site = ofaWpSecurity::getCurrentSite();
			$siteName = substr(substr($site->path, 1), 0 , -1);
			$profile = $employee->load(
				array(
					'column'	=>	'siteName',
					'value'		=>	$siteName));
		}

		// ------- FIRST ROW -------
		// Biography
		$bio = ofaCommon::nl2p($profile->bio);
		
		// Handle the profile image & contact info
		$source = (!empty($profile->photo)) ? OFACONTENTURL . OFABIOPHOTODIR . '/' . $profile->photo : OFABIOBLANK;
		$cv = '';
		
		if (!empty($profile->cv)) {
			$cvFile = OFACONTENTURL . OFACVDIR . '/' . $profile->cv;
			$cv = '<p><a target="_blank" href="' . $cvFile . '">&rsaquo; Download CV</a></p>';
		}
		
      $left = sprintf('<div id="ofaBioPhoto" style="background: url(%s) no-repeat top left;float:left;margin-right:15px;"></div>%s', $source, $cv);
		
		// Handle the first row
		if (!empty($profile->photo) || !empty($profile->cv))
			$rowOne = sprintf('<div>%s %s</div>', $left, $bio);
		else
			$rowOne = '<p>' . $bio . '</p>';
		
		// ------- SECOND ROW -------
		$education = '';
		$teaching = '';
		
		// Education
		if (!empty($profile->education)) {
			$education = '<ul>';
			$educationItems = explode("\r\n", $profile->education);
			$education .= $this->theme->createListItems($educationItems);
			$education .= '</ul>';
		}
		
		// Teaching Areas
		if (!empty($profile->teachingAreas)) {
			$teaching = '<ul>';
			$teachingItems = explode("\r\n", $profile->teachingAreas);
			$teaching .= $this->theme->createListItems($teachingItems);
			$teaching .= '</ul>';
		}
		
		// Second row items
		if (!empty($profile->education) && !empty($profile->teachingAreas))
			$rowTwo = sprintf('<div class="one_half"><h3>Education</h3>%s</div><div class="one_half last"><h3>Teaching Areas</h3>%s</div><div class="clear"></div>', $education, $teaching);
		elseif (!empty($profile->education))
			$rowTwo = '<h3>Education</h3>' . $education;
		elseif (!empty($profile->teachingAreas))
			$rowTwo = '<h3>Teaching Areas</h3>' . $teaching;
		else
			$rowTwo = '';
		
		// ------- THIRD ROW -------
		$featuredPubs = '';
		$awards = '';
		
		// Featured Publications
		if (!empty($profile->featuredPublications)) {
			$featuredPubs = '<ul>';
			$featuredPubsItems = explode("\r\n", $profile->featuredPublications);
			$featuredPubs .= $this->theme->createListItems($featuredPubsItems);
			$featuredPubs .= '</ul>';
		}
		
		// Awards
		if (!empty($profile->awards)) {
			$awards = '<ul>';
			$awardItems = explode("\r\n", $profile->awards);
			$awards .= $this->theme->createListItems($awardItems);
			$awards .= '</ul>';
		}
		
		// Third row items
		if (!empty($profile->featuredPublications) && !empty($profile->awards))
			$rowThree = sprintf('<div class="one_half"><h3>Featured Publications</h3>%s</div><div class="one_half last"><h3>Awards</h3>%s</div><div class="clear"></div>', $featuredPubs, $awards);
		elseif (!empty($profile->featuredPublications))
			$rowThree = '<h3>Featured Publications</h3>' . $featuredPubs;
		elseif (!empty($profile->awards))
			$rowThree = '<h3>Awards</h3>' . $awards;
		else
			$rowThree = '';
		
		// ------- Build the page -------
		$officeNumber = '';
		$officeHours = '';
		$mailStop = '';
		
		if (!empty($profile->officeNumber))
			$officeNumber = '<b>Office Number:</b> ' . $profile->officeNumber;
		
		if (!empty($profile->officeHours))
			$officeHours = '&bull; <b>Office Hours:</b> ' . $profile->officeHours;
      		//Bullets and breaks should be added within the if statements in case people don't have that information. 
		if (!empty($profile->mailStop))
			$mailStop = '&bull; <b>Mail Stop:</b> ' . $profile->mailStop;
      
      	if (!empty($profile->officeNumber)||!empty($profile->officeHours)||!empty($profile->mailStop))
          $officeNumber = "<br/>" . $officeNumber;
		
      $top = sprintf('%s<h2>%s</h2><div style="margin: 0 0 20px; padding: 0 20px 0 19px; border-left: 10px solid #f6f6f5;"><h6>%s</h6><a href="mailto:%s">%s</a> &bull; %s %s %s %s</div><hr />', $back, $employee->getName($profile), $profile->jobTitle, $profile->email, $profile->email, $profile->phone, $mailStop, $officeNumber, $officeHours);
		
		// Display employee's courses
		$courseList = '';
		
		// Display courses if module is enabled
		if ($this->moduleData->isLoaded('course')) {
			$options = new ofaOptions();
			$semesterId = $options->getOption('CURRENT_SEMESTER');
			$semester = new ofaSemesters();
			$currentSemester = $semester->load((int)$semesterId);
			
			$section = new ofaSections();
			$sections = $section->loadEmployeeSections($profile->id, $semesterId);

			if (count((array)$sections) > 0) {
				$courseList = '<h3>Current Courses: ' . $currentSemester->name . '</h3><table style="width: 100%;"><thead><tr><th width="75%">Course</th><th>Room & Times</th></tr></thead><tbody>';
			
				foreach ($sections as $s) {
					// Build the course info cell
					$course = '<span style="font-size: 14px;"><b>' . $s->course . ' ' . $s->courseNumber . '</b>-' . $s->section;
					
					if (!empty($s->link))
						$course = '<a target="_blank" href="' . $s->link . '">' . $course . '</a>';
					
					$course .= ': <i>' . $s->courseTitle . '</i></span>';
					
					if (!empty($s->syllabus))
					$course .= '<br />[<a target="_blank" href="' . OFACONTENTURL . OFASYLLABIDIR . '/' . $s->syllabus . '">' . $s->course . ' ' . $s->courseNumber . '-' . $s->section . ' syllabus</a>]';
					
					if (!empty($s->notes))
					$course .= '<br /><br /><b>Notes</b>:<br />' . $s->notes;
					
					// Course room/times
					$info = "<b>{$s->room}</b><br />{$s->hours}";
									
					$courseList .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $course, $info);
				}
				
				$courseList .= '</tbody></table>';
			}
		}
		
		return sprintf('%s%s<div class="clear"></div>%s%s%s', $top, $rowOne, $rowTwo, $rowThree, $courseList);
	}
}
