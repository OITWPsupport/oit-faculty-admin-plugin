<?php

/**
 * course
 * The Course module for the OFA plugin
 * Provides functionality for course & semester management
 * @author Martin Ronquillo
 */
class course extends ofaBaseModule implements iOfaModule {
	const title = 'Course';
	private $moduleData;

	/**
	 * Constructor
	 */
	public function course($moduleData) {
		// Call the parent constructor
		parent::__construct();
		
		// Save the instance of ofaModules
		$this->moduleData = $moduleData;
		
		// Define the WP menu items for the module in a multi-dimensional array
		// Each menu item must correspond to a page
		// All values are required
		$this->menuItems[] = array(
								'page_title' => 'Course Management',
								'menu_title' => 'Courses',
								'capability' => 'administrator',
								'menu_slug' => 'course',
								'function' => array($this, 'mainCoursePage'),
								'security' => array('rule' => ofaWpSecurity::NONFACULTY));
		
		$this->menuItems[] = array(
								'page_title' => 'Section Management',
								'menu_title' => 'Sections',
								'capability' => 'administrator',
								'menu_slug' => 'section',
								'function' => array($this, 'mainSectionPage'),
								'security' => array('rule' => ofaWpSecurity::NONFACULTY));
								
		$this->menuItems[] = array(
								'page_title' => 'Semester Management',
								'menu_title' => 'Semesters',
								'capability' => 'administrator',
								'menu_slug' => 'semester',
								'function' => array($this, 'mainSemesterPage'),
								'security' => array('rule' => ofaWpSecurity::NONFACULTY));
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
		$html = '<p>Course manages course/section information and semesters.</p>';
		$html .= '<p><b>Available Shortcode:</b><br /><code>[ofa-course][/ofa-course]</code></p>';
		$html .= '<p><b>Shortcode Attributes:</b><br /><code>departmentid="#"</code> (The Group ID of the department)</br /><code>search="true"</code></p>';
		return $html;
	}
	
	/**
	 * Define all the shortcodes used on the front-end for presenting content
	 * @param none
	 * @return none
	 */
	public function defineShortcodes() {
		/*
		 * Shortcode: 	ofa-course
		 * Attributes:
		 * 		departmentid	=	"#"
		 * 		search			=	"true"
		 */
		add_shortcode('ofa-course', array(&$this, 'courseShortcode'));
	}
	
	public function mainCoursePage() {
		$action = ofaGet::get('action');

		switch($action) {
			case 'new':
			case 'edit':
				$this->mainCourseEditPage($action);
				break;
			case 'delete':
			default:
				$this->mainCourseListPage();
				break;
		}
	}
	
	private function mainCourseEditPage($action) {
		// Page Setup
		$continueURL = OFAADMIN . '?page=course';
		$ref = ofaGet::get('ref');
		
		if (!empty($ref))
			$continueURL = $ref;
		
		$course = new ofaCourses();
		$pageTitle = 'Edit Course';
		$newButton = '';
		$invalidFields = '';
		$submitted = false;
		$saved = 0;

		if ($action === 'new')
			$pageTitle = 'Add Course';
		
		if ($action === 'edit')
			$newButton = OFAADMIN . '?page=course&action=new';
		
		// Array to hold form data
		$data = $course->blank();
		
		// Load user data if a profile is being edited
		$id = ofaGet::get('id');
		
		if (!empty($siteId))
			$id = (int)$siteId;

		if (!empty($id)) {
			$data = $course->load($id);
		}
		
		// Attempt to process the form
		if (ofaPost::isPost() && check_admin_referer('ofaCourseEdit', 'ofaNonce')) {
			$data = ofaPost::get();
			
			// Validate/sanitize the data
			$validated = $course->validate($data);
			
			// IF		array is returned		data is invalid and messages are displayed
			// ELSE								save data
			if (is_array($validated)) {
				$data = $course->clean($data);
				echo $this->theme->buildMessage('One or more fields are invalid. Please fix the fields in red.', 'error');
				
				// Mark the fields in red by telling jquery which fields to color
				foreach ($validated as $invalid)
					$invalidFields .= '<span class="invalidFieldName">' . $invalid . '</span>';
			}
			else {
				$saved = $course->save($validated);
				$submitted = true;
			}
		}
		
		// Form logic starts here
		// ---------------
		
		// Departments
		$department = new ofaGroups();
		$departmentValues = array(
			array(
				'display'	=>	'Select',
				'value'		=>	''));
		$allDepartments = $department->multiLoadDepartments();
		
		foreach ($allDepartments as $d) {
			$value = array(
				'display'	=> $d->name,
				'value'		=> $d->id);
				
			$departmentValues[] = $value;
		}

		$departments = $this->theme->generateComplexField('select', 'departmentId', $departmentValues, $data->departmentId);
		
		// WP security
		$nonce = wp_nonce_field('ofaCourseEdit', 'ofaNonce', true, false);
		
		// Build the form
		$form = <<<HTML
<a href="{$continueURL}">&larr; Back</a>
{$invalidFields}
<form id="groupForm" method="POST" action="{$_SERVER['REQUEST_URI']}" enctype="multipart/form-data">
	{$nonce}
	<h3>Course Information</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="departmentId">Department</label></th>
				<td>
					{$departments}
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="course">Course</label></th>
				<td>
					<input id="course" type="text" class="all-options" maxlength="10" name="course" value="{$data->course}" />
					<span class="description">(e.g. ENTP)</span>
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="number">Course Number</label></th>
				<td>
					<input id="number" type="text" class="small-text" maxlength="10" name="number" value="{$data->number}" />
					<span class="description">(e.g. 396)</span>
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="title">Title</label></th>
				<td>
        			<input id="title" type="text" class="all-options" maxlength="100" name="title" value="{$data->title}" />
        			<span class="description">(e.g. Social Entrepreneurship)</span>
				</td>
			</tr>
			<tr>
				<th><label for="description">Description</label></th>
				<td>
        			<textarea id="description" name="description" cols="60" rows="4">{$data->description}</textarea>
        			<span class="description">No HTML allowed. <b>Not publicly displayed</b></span>
				</td>
			</tr>
			<tr>
				<th><label for="credits">Credits</label></th>
				<td>
        			<input id="credits" type="text" class="small-text" maxlength="2" name="credits" value="{$data->credits}" />
        			<span class="description"><b>Not publicly displayed</b></span>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input class="button-primary" type="submit" value="Update Course" />
		<input type="hidden" name="id" value="{$data->id}" />
	</p>
</form>
HTML;
		
		// Display the page
		echo $this->theme->getDefaultHeader('Course Management', $newButton);
		
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
	
	private function mainCourseListPage() {
		echo $this->theme->getDefaultHeader('Course Management', OFAADMIN . '?page=course&action=new' . '&ref=' . ofaGet::getCurrentPage());
		
		$paged = ofaGet::get('paged');
		$currentPage = (!empty($paged)) ? $paged : 1;
		$courses = new ofaCourses();
		
			// Delete a record
        $action = ofaGet::get('action');
		if ($action == 'delete') {
		
            $debug= "this delete function was called";
			
			
			$deleteId = ofaGet::get('id');
			
			$delete = $courses->delete('ofaCourses', $deleteId);
			
			if ($delete == 1)
				echo $this->theme->buildMessage('Record deleted successfully');
			else
				echo $this->theme->buildMessage('Record was not deleted. Please try again.', 'error');
		}
		
		
		// Handle search
		$currentView = '';
		$post = ofaPost::get();
		$search = $post->s;
		$getSearch = ofaGet::get('s');
		
		// Handle search & pagination. If not on first page of navigaton, see if search results need to be displayed
		if (empty($search) && !empty($getSearch))
			$search = $getSearch;
		
		if (!empty($search))
			$currentView = 'search';
		
		// Load a list of employees
		$course = new ofaCourses();
		
		switch($currentView) {
			case 'search':
				echo $this->theme->buildMessage('Displaying search results.');
				$courses = $course->search(
					array('title'),
					$search);
				break;
			default:
				$courses = $course->multiLoad();
				break;
		}
		
		// Create the pagination HTML
		$pagination = $this->theme->pagination(count((array)$courses), $currentPage, 10, OFAADMIN . '?page=course&s=' . $search . '&paged=');
		
		// Only show the records on the current page (pagination)
		$courses = $course->paginate($courses, $currentPage, 10);
					
		$rows = array();

		// Pack the data for the table using the employee entries
		foreach ($courses as $c) {
			$editLink = OFAADMIN . '?page=course&action=edit&id=' . $c->id . '&ref=' . ofaGet::getCurrentPage();
			$deleteLink = OFAADMIN . '?page=course&action=delete&id=' . $c->id . '&paged=' . $currentPage;
			$row = array();
			
			$row[] = sprintf('<strong><a class="row-title" title="Edit this Course" href="%s">%s</a></strong><div class="row-actions"><span><a title="Edit this Course" href="%s">Edit</a></span> | <span class="trash"><a title="Delete Course" href="%s">Delete</a></span></div>', $editLink, $c->course . ' ' . $c->number, $editLink, $deleteLink);
			$row[] = $c->title;
			$row[] = ofaCommon::truncate($c->description);
			
			$rows[] = $row;
		}

		// Generate the list table
		echo $this->theme->generateTable(
			array(
				'Course',
				'Title',
				'Description'),
			$rows,
			OFAADMIN . '?page=course',
			$pagination,
			array(),
			array(),
			true,
			'ofaCourseList');
		
		
		echo $this->theme->getDefaultFooter();
	}
	
	public function mainSectionPage() {
		$action = ofaGet::get('action');

		switch($action) {
			case 'bulk':
				$this->mainSectionBulkAddPage();
				break;
			case 'invalid':
				$this->mainSectionInvalidPage();
				break;
			case 'new':
			case 'edit':
				$this->mainSectionEditPage($action);
				break;
			case 'delete':
			default:
				$this->mainSectionListPage($action);
				break;
		}
	}
	
	private function mainSectionBulkAddPage() {
		echo $this->theme->getDefaultHeader('Section Management');
		
		// Page Setup
		$continueURL = OFAADMIN . '?page=section';
		$pageTitle = 'Bulk Upload Sections';
		$newButton = '';
		$invalidFields = '';
		$submitted = false;
		$saved = 0;
		
		// Attempt to process the form
		if (ofaPost::isPost() && check_admin_referer('ofaSectionUpload', 'ofaNonce')) {
			$section = new ofaSections();
			$semester = (int)$_POST['semesterId'];
			$fileName = $_FILES['courses']['name'];
			
			if ($semester != 0 && !empty($fileName)) {
				$data = $section->loadFromFile(
				$_FILES['courses']['tmp_name'], 
					array(
						'semester' 		=> 	$semester));
	
				$dataCount = count($data);
				$rows = array();
				
				foreach ($data as $s) {
					$row = array();
					$row[] = sprintf('<strong>%s</strong>', $s->courseId);
					$row[] = $s->departmentId;
					$row[] = $s->employeeId;
					$row[] = $s->section;
					$row[] = $s->hours;
					$row[] = $s->room;
					
					$rows[] = $row;
				}
				
				echo $this->theme->buildMessage('Courses with empty Course, Department ID, or Instructor ID fields will be categorized as "invalid" and must be manually fixed.');
				
				echo $this->theme->generateTable(
				array(
					'Course',
					'Department ID',
					'Instructor ID',
					'Section',
					'Times',
					'Room'),
				$rows,
				OFAADMIN . '?page=personnel',
				$pagination,
				array(),
				array(),
				false,
				'ofaBulkAddList');
				
				foreach ($data as $d) {
					$saved = $section->save($d);
				}
				
				$submitted = true;
			}
			else {
				if (empty($semester))
					$invalidFields .= '<span class="invalidFieldName">semesterId</span>';
				
				if (empty($fileName))
					$invalidFields .= '<span class="invalidFieldName">courses</span>';
				
				echo $this->theme->buildMessage('One more more fields are invalid.', 'error');
			}
		}
		
		// Form logic starts here
		// ---------------
		
		// Semesters
		$semester = new ofaSemesters();
		$semesterValues = array(
			array(
				'display'	=>	'Select',
				'value'		=>	''));
		$allSemesters = $semester->multiLoadWithNames();
		
		foreach ($allSemesters as $s) {
			$value = array(
				'display'	=> $s->name,
				'value'		=> $s->id);
				
			$semesterValues[] = $value;
		}
		
		$options = new ofaOptions();
		$semesterId = $options->getOption('CURRENT_SEMESTER');
		
		if (isset($_POST['semesterId']))
			$semesterId = (int)$_POST['semesterId'];

		$semesters = $this->theme->generateComplexField('select', 'semesterId', $semesterValues, $semesterId);
		
		// WP security
		$nonce = wp_nonce_field('ofaSectionUpload', 'ofaNonce', true, false);
		
		$invalidURL = OFAADMIN . '?page=section&view=invalid';
		$imageURL = OFAPUBLICURL . '/images/bulkAddInstructions.jpg';
		
		// Build the form
		$form = <<<HTML
<a href="{$continueURL}">&larr; Back</a>
{$invalidFields}
<br />
<div id="bulkAddWrapper" class="ofaCF">
	<a id="instructionTab" href="#">Instructions <span></span></a>
	<div id="bulkUploadInstructions">
		<h4>Upload Instructions</h4>
		<p>Course sections can be uploaded in bulk from BroncoWeb:</p>
		<ol>
			<li>Log in to <a target="_blank" href="http://broncoweb.boisestate.edu">http://broncoweb.boisestate.edu</a>. In Self Service/Student Center select the <b>SEARCH FOR CLASSES</b> button</li>
			<li>Click on <b>View Course Listing by Subject</b>. Select the correct semester and subject area (e.g. ACCT, ECON, etc.)</li>
			<li>Select the entire list, from the Class header at the top left to the last item of the last row at the bottom right (if column headers are not selected, the data will not paste properly in Excel)</li>
			<li>Copy the selected data and paste into Excel</li>
			<li>Save the Excel file as a CSV file (Windows CSV on Mac)</li>
			<li>Upload CSV file using the form below</li>
			<li>Sections not uploaded properly can be viewed on the <a href="{$invalidURL}">Invalid Sections</a> page</li>
		</ol>
		<img id="bulkAddImage" src="{$imageURL}" alt="Select all the column data from BroncoWeb and paste into Excel" />
	</div>
</div>
<form id="groupForm" method="POST" action="{$_SERVER['REQUEST_URI']}" enctype="multipart/form-data">
	{$nonce}
	<h3>Section Options</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="semesterId">Semester</label></th>
				<td>
					{$semesters}
				</td>
			</tr>
		</tbody>
	</table>
	
	<h3>Upload CSV File</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="courses">Courses File</label></th>
				<td>
					<input id="courses" type="file" name="courses" />
					<span class="description">File must be in CSV format</span>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input class="button-primary" type="submit" value="Upload" />
	</p>
</form>
HTML;
		
		// IF		form not submitted		display form
		// ELSE								display a message for success or on failure
		if (!$submitted)
			echo $form;
		else {
			if ($saved == 1) {
				echo $this->theme->buildMessage('Records have been successfully saved.');
				echo '<a href="' . $continueURL . '">Continue &rarr;</a>';
			}
			else
				echo $this->theme->buildMessage('Records were not saved due to an error. Please go back and try again.', 'error');
		}
		
		echo $this->theme->getDefaultFooter();
	}

	private function mainSectionEditPage($action) {
		// Page Setup
		$continueURL = OFAADMIN . '?page=section';
		$ref = ofaGet::get('ref');
		
		if (!empty($ref))
			$continueURL = $ref;
		
		$section = new ofaSections();
		$pageTitle = 'Edit Section';
		$newButton = '';
		$invalidFields = '';
		$submitted = false;
		$saved = 0;

		if ($action === 'new')
			$pageTitle = 'Add Section';
		
		if ($action === 'edit')
			$newButton = OFAADMIN . '?page=section&action=new';
		
		// Array to hold form data
		$data = $section->blank();
		
		// Load data if a section is being edited
		$id = ofaGet::get('id');
		
		if (!empty($siteId))
			$id = (int)$siteId;

		if (!empty($id)) {
			$data = $section->load($id);
		}
		
		// Attempt to process the form
		if (ofaPost::isPost() && check_admin_referer('ofaSectionEdit', 'ofaNonce')) {
			$data = ofaPost::get();

			// Handle CV upload
			if (!empty($_FILES['syllabus']['name'])) {
				$randomIndentifier = substr(time(), -3);
				$syllabusName = $randomIndentifier . '-' . $data->courseId . '-' . $data->section . '-' . 'Syllabus';
				$uploadSyllabus = ofaPost::upload($_FILES, OFACONTENTDIR . OFASYLLABIDIR, $syllabusName, 'syllabus', array('pdf'));
				
				if ($uploadSyllabus)
					$data->syllabus = $syllabusName . '.pdf';
			}
			else
				$data->syllabus = $data->syllabusOld;

			// Validate/sanitize the data
			$validated = $section->validate($data);
			
			// IF		array is returned		data is invalid and messages are displayed
			// ELSE								save data
			if (is_array($validated)) {
				$data = $section->clean($data);
				echo $this->theme->buildMessage('One or more fields are invalid. Please fix the fields in red.', 'error');
				
				// Mark the fields in red by telling jquery which fields to color
				foreach ($validated as $invalid)
					$invalidFields .= '<span class="invalidFieldName">' . $invalid . '</span>';
			}
			else {
				$saved = $section->save($validated);
				$submitted = true;
			}
		}
		
		// Form logic starts here
		// ---------------
		$defaultSelection = array(
			array(
				'display'	=>	'Select',
				'value'		=>	''));
		
		// Semesters
		$semester = new ofaSemesters();
		$semesterValues = $defaultSelection;
		$allSemesters = $semester->multiLoadWithNames();
		
		foreach ($allSemesters as $s) {
			$value = array(
				'display'	=> $s->name,
				'value'		=> $s->id);
				
			$semesterValues[] = $value;
		}

		$semesters = $this->theme->generateComplexField('select', 'semesterId', $semesterValues, $data->semesterId);
		
		// Courses
		$course = new ofaCourses();
		$courseValues = $defaultSelection;
		$allCourses = $course->multiLoadTitles();
		
		foreach ($allCourses as $c) {
			$value = array(
				'display'	=> $c->course . ' ' . $c->number,
				'value'		=> $c->id);
				
			$courseValues[] = $value;
		}

		$courses = $this->theme->generateComplexField('select', 'courseId', $courseValues, $data->courseId);
		
		// Instructors
		$employee = new ofaEmployees();
		$employeeValues = $defaultSelection;
		$allEmployees = $employee->multiLoadNames();
		
		foreach ($allEmployees as $e) {
			$value = array(
				'display'	=> $employee->getName($e),
				'value'		=> $e->id);
				
			$employeeValues[] = $value;
		}

		$employees = $this->theme->generateComplexField('select', 'employeeId', $employeeValues, $data->employeeId);
		
		// Syllabus
		$syllabus = $this->theme->generateUploadField('file', 'syllabus', 'Syllabus must be uploaded in PDF format', $data->syllabus, OFACONTENTURL . OFASYLLABIDIR);
		
		// WP security
		$nonce = wp_nonce_field('ofaSectionEdit', 'ofaNonce', true, false);
		
		// Build the form
		$form = <<<HTML
<a href="{$continueURL}">&larr; Back</a>
{$invalidFields}
<form id="groupForm" method="POST" action="{$_SERVER['REQUEST_URI']}" enctype="multipart/form-data">
	{$nonce}
	<h3>Section Attributes</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="semesterId">Semester</label></th>
				<td>
					{$semesters}
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="courseId">Course</label></th>
				<td>
					{$courses}
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="section">Section</label></th>
				<td>
        			<input id="section" type="text" class="small-text" maxlength="8" name="section" value="{$data->section}" />
				</td>
			</tr>
		</tbody>
	</table>
	
	<h3>Section Information</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="employeeId">Instructor</label></th>
				<td>
					{$employees}
				</td>
			</tr>
			<tr>
				<th><label for="room">Room</label></th>
				<td>
        			<input id="room" type="text" class="all-options" maxlength="10" name="room" value="{$data->room}" />
				</td>
			</tr>
			<tr>
				<th><label for="hours">Times</label></th>
				<td>
        			<input id="hours" type="text" class="regular-text" maxlength="75" name="hours" value="{$data->hours}" />
				</td>
			</tr>
			<tr>
				<th><label for="link">Link</label></th>
				<td>
        			<input id="link" type="text" class="regular-text" maxlength="100" name="link" value="{$data->link}" />
				</td>
			</tr>
			<tr>
				<th><label for="syllabus">Syllabus</label></th>
				<td>
					{$syllabus}
				</td>
			</tr>
			<tr>
				<th><label for="notes">Notes</label></th>
				<td>
        			<textarea id="notes" name="notes" maxlength="255" cols="60" rows="4">{$data->notes}</textarea>
        			<span class="description">Maximum 255 characters</span>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input class="button-primary" type="submit" value="Update Section" />
		<input type="hidden" name="id" value="{$data->id}" />
	</p>
</form>
HTML;
		
		// Display the page
		echo $this->theme->getDefaultHeader('Section Management', $newButton);
		
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

	private function mainSectionListPage($action) {
		echo $this->theme->getDefaultHeader('Section Management', OFAADMIN . '?page=section&action=new' . '&ref=' . ofaGet::getCurrentPage());
		echo '<a class="button-primary" href="?page=section&action=bulk">Bulk Add</a>';
		
		$section = new ofaSections();
		$ignoreDelete = ofaGet::get('ignore');
		 $action = ofaGet::get('action');
		
		
		// Delete a record
		if ($action == 'delete') {
			$deleteId = ofaGet::get('id');
			$delete = $section->delete('ofaSections', $deleteId);
			
			if ($delete == 1)
				echo $this->theme->buildMessage('Record deleted successfully');
			else
				echo $this->theme->buildMessage('Record was not deleted. Please try again.', 'error');
		}

		$paged = ofaGet::get('paged');
		$currentPage = (!empty($paged)) ? $paged : 1;
		
		// Handle views & search
		$currentView = '';
		
		// Handle view & pagination
		$view = ofaGet::get('view');
		$currentView = (!empty($view)) ? $view : 'valid';
		
		// Search
		$post = ofaPost::get();
		$search = $post->s;
		$getSearch = ofaGet::get('s');
		
		// Filter
		$semesterId = ofaGet::get('i');
		$deptId = ofaGet::get('did');
		
		if (!empty($semesterId))
			$post->semesterFilter = $semesterId;
		
		if (!empty($deptId))
			$post->deptFilter = $deptId;

		// Handle search & pagination. If not on first page of navigaton, see if search results need to be displayed
		if (empty($search) && !empty($getSearch))
			$search = $getSearch;
		
		if (!empty($search))
			$currentView = 'search';
		
		switch($currentView) {
			case 'invalid':
				$sections = $section->loadInvalid();
				break;
			case 'search':
				echo $this->theme->buildMessage('Search performed on course titles.');
				$sections = $section->searchAllJoined($search, $post->semesterFilter, false, $post->deptFilter);
				break;
			default:
				$sections = $section->loadAllJoined($post->semesterFilter, false, '', $post->deptFilter);
				break;
		}
		
		// Create the pagination HTML
		$pagination = $this->theme->pagination(count((array)$sections), $currentPage, 20, OFAADMIN . '?page=section&view='. $currentView . '&s='. $search . '&i=' . $post->semesterFilter . '&did=' . $post->deptFilter . '&paged=');
		
		// Only show the records on the current page (pagination)
		$sections = $section->paginate($sections, $currentPage, 20);
		
		// Display list of different view options
		echo '<br />';
		echo $this->theme->generateDisplayList(array(
			array(
				'display' => 'Valid',
				'class' => 'valid',
				'href' => '?page=section&view='),
			array(
				'display' => 'Invalid',
				'class' => 'invalid',
				'href' => '?page=section&view=invalid')),
			$currentView);
					
		$rows = array();
		
		//
		if ($currentView == 'invalid') {
			// Pack the data for the table using the employee entries
			foreach ($sections as $s) {
				$link = OFAADMIN . '?page=section&action=invalid&id=' . $s->id . '&ref=' . ofaGet::getCurrentPage() . '&ignore=yes';
				$deleteLink = OFAADMIN . '?page=section&action=delete&id=' . $s->id . '&paged=' . $currentPage;
				$row = array();
				
				$row[] = sprintf('<strong><a class="row-title" title="View Section" href="%s">%s</a></strong><div class="row-actions"><span><a title="View Section" href="%s">View Details</a></span> | <span class="trash"><a title="Delete Section" href="%s">Delete</a></span></div>', $link, $s->section, $link, $deleteLink);
				$row[] = $s->courseId;
				$row[] = $s->employeeId;
				$row[] = $s->semesterId;
				
				$rows[] = $row;
			}
	
			// Generate the list table
			echo $this->theme->generateTable(
				array(
					'Section',
					'Course ID',
					'Instructor ID',
					'Semester ID'),
				$rows,
				OFAADMIN . '?page=section&view=invalid',
				$pagination,
				array(),
				array(),
				true,
				'ofaInvalidSectionList');
		}
		else {
			// Pack the data for the table using the employee entries
			foreach ($sections as $s) {
				$filterVars = '';
				$getSearch = ofaGet::get('s');
		
		// Filter
		$semesterId = ofaGet::get('i');
		$deptId = ofaGet::get('did');
				
				if ($currentPage == 1)
					$filterVars = rawurlencode("&s={$search}&i={$post->semesterFilter}&did={$post->deptFilter}");
				
				$link = OFAADMIN . '?page=section&action=edit&id=' . $s->sectionId . '&ref=' . ofaGet::getCurrentPage() . $filterVars;
				$deleteLink = OFAADMIN . '?page=section&action=delete&id=' . $s->sectionId . '&paged=' . $currentPage;
				$row = array();
				
				$row[] = sprintf('<strong><a class="row-title" title="Edit this Section" href="%s">%s</a></strong><div class="row-actions"><span><a title="Edit this Section" href="%s">Edit</a></span> | <span class="trash"><a title="Delete Section" href="%s">Delete</a></span></div>', $link, $s->section, $link, $deleteLink);
				$row[] = $s->semester;
				$row[] = $s->course . ' ' . $s->courseNumber;
				$row[] = $s->courseTitle;
				$row[] = $s->firstName . ' ' . $s->lastName;
				$row[] = $s->room;
				
				$rows[] = $row;
			}
			
			// Semesters
			$semester = new ofaSemesters();
			$semesterValues = array(
				array(
					'display'	=>	'All Semesters',
					'value'		=>	0));
			$allSemesters = $semester->multiLoadWithNames();
			
			foreach ($allSemesters as $s) {
				$value = array(
					'display'	=> $s->name,
					'value'		=> $s->id);
					
				$semesterValues[] = $value;
			}

			$filters = $this->theme->generateComplexField('select', 'semesterFilter', $semesterValues, $post->semesterFilter);
			
			// Departments
			$department = new ofaGroups();
			$departmentValues = array(
				array(
					'display'	=>	'All Departments',
					'value'		=>	0));
			$allDepartments = $department->multiLoadDepartments();
			
			foreach ($allDepartments as $d) {
				$value = array(
					'display'	=> $d->name,
					'value'		=> $d->id);
					
				$departmentValues[] = $value;
			}
	
			$filters .= $this->theme->generateComplexField('select', 'deptFilter', $departmentValues, $post->deptFilter);
			$filters .= '<input type="submit" name="sectionFilter" id="sectionFilter" class="button-secondary" value="Filter" />';
	
			// Generate the list table
			echo $this->theme->generateTable(
				array(
					'Section',
					'Semester',
					'Course',
					'Course Title',
					'Instructor',
					'Room'),
				$rows,
				OFAADMIN . '?page=section',
				$pagination,
				array(),
				array(),
				true,
				'ofaSectionList',
				$filters);
		}
		
		echo $this->theme->getDefaultFooter();
	}

	private function mainSectionInvalidPage() {
		// Page Setup
		echo $this->theme->getDefaultHeader('Invalid Section Entry');
		$backURL = OFAADMIN . '?page=section&view=invalid';
		$ref = ofaGet::get('ref');
		
		if (!empty($ref))
			$backURL = $ref;
		
		$section = new ofaSections();
		
		// Load user data if a profile is being edited
		$id = ofaGet::get('id');

		if (!empty($id))
			$data = $section->load($id);
		else
			echo $this->theme->buildMessage('No Section ID provided.', 'error');
		
		// Display logic starts here
		// ---------------
		
		// Semester
		$semester = '';
		
		if (!empty($data->semesterId)) {
			$semesters = new ofaSemesters();
			$currentSemester = $semesters->load((int)$data->semesterId);
			$semester = $currentSemester->name;
		}
		else
			$invalidFields .= '<span class="invalidFieldName">semesterId</span>';
		
		// Course
		$course = '';
		
		if (!empty($data->courseId)) {
			$courses = new ofaCourses();
			$currentCourse = $courses->load((int)$data->courseId);
			$course = $currentCourse->course . ' ' . $currentCourse->number . ': ' . $currentCourse->title;
		}
		else
			$invalidFields .= '<span class="invalidFieldName">courseId</span>';
		
		// Employee
		$employee = '';
		
		if (!empty($data->employeeId)) {
			$employees = new ofaEmployees();
			$currentEmployee = $employees->load((int)$data->employeeId);
			$employee = $currentEmployee->firstName . ' ' . $currentEmployee->lastName;
		}
		else
			$invalidFields .= '<span class="invalidFieldName">employeeId</span>';
			
		$editURL = OFAADMIN . '?page=section&action=edit&id=' . $data->id;
		$deleteURL = OFAADMIN . '?page=section&action=delete&view=invalid&id=' . $data->id;

		// Build the display
		$form = <<<HTML
<a href="{$backURL}">&larr; Back</a>
{$invalidFields}
<form id="groupForm" method="POST" action="{$_SERVER['REQUEST_URI']}" enctype="multipart/form-data">
	<h3>Section Attributes</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="semesterId">Semester</label></th>
				<td>
					<input id="semesterId" type="text" class="regular-text" name="semesterId" value="{$semester}" readonly />
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="courseId">Course</label></th>
				<td>
					<input id="courseId" type="text" class="regular-text" name="courseId" value="{$course}" readonly />
				</td>
			</tr>
			<tr>
				<th><label class="bold" for="section">Section</label></th>
				<td>
        			<input id="section" type="text" class="small-text" maxlength="8" name="section" value="{$data->section}" readonly />
				</td>
			</tr>
		</tbody>
	</table>
	
	<h3>Section Information</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="employeeId">Instructor</label></th>
				<td>
					<input id="employeeId" type="text" class="regular-text" name="employeeId" value="{$employee}" readonly />
				</td>
			</tr>
			<tr>
				<th><label for="room">Room</label></th>
				<td>
        			<input id="room" type="text" class="all-options" maxlength="10" name="room" value="{$data->room}" readonly />
				</td>
			</tr>
			<tr>
				<th><label for="hours">Times</label></th>
				<td>
        			<input id="hours" type="text" class="regular-text" maxlength="75" name="hours" value="{$data->hours}" readonly />
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<a class="button-primary" href="{$editURL}">Edit</a>
		<a href="{$deleteURL}">Delete</a>
	</p>
</form>
HTML;
		
		echo $form;
		
		echo $this->theme->getDefaultFooter();
	}
	
	public function mainSemesterPage() {
		$action = ofaGet::get('action');

		switch($action) {
			case 'new':
			case 'edit':
				$this->mainSemesterEditPage($action);
				break;
			default:
				$this->mainSemesterListPage();
				break;
		}
	}
	
	/**
	 * 
	 */
	private function mainSemesterEditPage($action) {
		// Page Setup
		$continueURL = OFAADMIN . '?page=semester';
		$ref = ofaGet::get('ref');
		
		if (!empty($ref))
			$continueURL = $ref;
		
		$semester = new ofaSemesters();
		$pageTitle = 'Edit Semester';
		$newButton = '';
		$invalidFields = '';
		$submitted = false;
		$saved = 0;

		if ($action === 'new')
			$pageTitle = 'Add Semester';
		
		if ($action === 'edit')
			$newButton = OFAADMIN . '?page=semester&action=new';
		
		// Array to hold form data
		$data = $semester->blank();
		
		// Load user data if a profile is being edited
		$id = ofaGet::get('id');
		
		if (!empty($siteId))
			$id = (int)$siteId;

		if (!empty($id)) {
			$data = $semester->load($id);
		}
		
		// Attempt to process the form
		if (ofaPost::isPost() && check_admin_referer('ofaSemesterEdit', 'ofaNonce')) {
			$data = ofaPost::get();
			
			// Validate/sanitize the data
			$validated = $semester->validate($data);
			
			// IF		array is returned		data is invalid and messages are displayed
			// ELSE								save data
			if (is_array($validated)) {
				$data = $semester->clean($data);
				echo $this->theme->buildMessage('One or more fields are invalid. Please fix the fields in red.', 'error');
				
				// Mark the fields in red by telling jquery which fields to color
				foreach ($validated as $invalid)
					$invalidFields .= '<span class="invalidFieldName">' . $invalid . '</span>';
			}
			else {
				$saved = $semester->save($validated);
				$submitted = true;
			}
		}
		
		// Form logic starts here
		// ---------------
		
		// WP security
		$nonce = wp_nonce_field('ofaSemesterEdit', 'ofaNonce', true, false);
		
		// Build the form
		$form = <<<HTML
<a href="{$continueURL}">&larr; Back</a>
{$invalidFields}
<form id="groupForm" method="POST" action="{$_SERVER['REQUEST_URI']}" enctype="multipart/form-data">
	{$nonce}
	<h3>Semester Information</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label class="bold" for="name">Semester Name</label></th>
				<td>
					<input id="name" type="text" class="regular-text" maxlength="20" name="name" value="{$data->name}" />
				</td>
			</tr>
			<tr>
				<th><label for="startDate">Start Date</label></th>
				<td>
        			<input id="startDate" type="text" class="all-options" maxlength="10" name="startDate" value="{$data->startDate}" />
				</td>
			</tr>
			<tr>
				<th><label for="endDate">End Date</label></th>
				<td>
        			<input id="endDate" type="text" class="all-options" maxlength="10" name="endDate" value="{$data->endDate}" />
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input class="button-primary" type="submit" value="Update Semester" />
		<input type="hidden" name="id" value="{$data->id}" />
	</p>
</form>
HTML;
		
		// Display the page
		echo $this->theme->getDefaultHeader('Semester Management', $newButton);
		
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
	
	private function mainSemesterListPage() {
		echo $this->theme->getDefaultHeader('Semester Management', OFAADMIN . '?page=semester&action=new' . '&ref=' . ofaGet::getCurrentPage());
		
		$paged = ofaGet::get('paged');
		$currentPage = (!empty($paged)) ? $paged : 1;
		$semesters = new ofaSemesters();
		// Delete a record
        $action = ofaGet::get('action');
		if ($action == 'delete') {
          
			$deleteId = ofaGet::get('id');
          
			$delete = $semesters->delete('ofaSemesters', $deleteId);
       
			if ($delete == 1)
				echo $this->theme->buildMessage('Record deleted successfully');
			else
				echo $this->theme->buildMessage('Record was not deleted. Please try again.', 'error');
		}
		
		
		
		// Load a list of employees
		$semester = new ofaSemesters();
		$semesters = $semester->multiLoad();
		
		// Create the pagination HTML
		$pagination = $this->theme->pagination(count((array)$semesters), $currentPage, 5, OFAADMIN . '?page=semester&paged=');
		
		// Only show the records on the current page (pagination)
		$semesters = $semester->paginate($semesters, $currentPage, 5);
					
		$rows = array();

		$options = new ofaOptions();
		
		// Set the current semester
		if (ofaPost::isPost() && check_admin_referer('ofaCurrentSemester', 'ofaNonce')) {
			$data = ofaPost::get();
			$current = $data->semesterId;
			
			// User is attempting to set an actual value
			if ($current != 0) {
				$set = $options->setOption('CURRENT_SEMESTER', $current);

				if ($set == 1)
					echo $this->theme->buildMessage('Current Semester Updated');
				else
					echo $this->theme->buildMessage('Current Semester not updated. Please try again.', 'error');
			}
			else
				echo $this->theme->buildMessage('Select a semester to set as current', 'error');
		}
		
		$semesterValues = array(
			array(
				'display'	=>	'Semester',
				'value'		=>	0));
		$allSemesters = $semester->multiLoadWithNames();
		$currentSemester = $options->getOption('CURRENT_SEMESTER');
		
		foreach ($allSemesters as $s) {
			$value = array(
				'display'	=> $s->name,
				'value'		=> $s->id);
				
			$semesterValues[] = $value;
		}

		$semesterSelect = $this->theme->generateComplexField('select', 'semesterId', $semesterValues, $currentSemester);
		
		$nonce = wp_nonce_field('ofaCurrentSemester', 'ofaNonce', true, false);
		
		echo <<<HTML
<form id="groupForm" method="POST" action="{$_SERVER['REQUEST_URI']}" enctype="multipart/form-data">
	{$nonce}
	<div id="currentSemester">
		<h4>Current Semester</h4>
		{$semesterSelect}
		<input class="button-primary" type="submit" value="Update" />
	</div>
</form>
HTML;
		
		// Pack the data for the table using the employee entries
		foreach ($semesters as $s) {
			$link = OFAADMIN . '?page=semester&action=edit&id=' . $s->id . '&ref=' . ofaGet::getCurrentPage();
			$deleteLink = OFAADMIN . '?page=semester&action=delete&id=' . $s->id . '&paged=' . $currentPage;
			$row = array();
			
			$row[] = sprintf('<strong><a class="row-title" title="Edit this Semester" href="%s">%s</a></strong><div class="row-actions"><span><a title="Edit this Semester" href="%s">Edit</a></span> | <span class="trash"><a title="Delete Section" href="%s">Delete</a></span></div>', $link, $s->name, $link, $deleteLink);
			$row[] = $s->startDate;
			$row[] = $s->endDate;
			
			$rows[] = $row;
		}

		// Generate the list table
		echo $this->theme->generateTable(
			array(
				'Semester',
				'Start Date',
				'End Date'),
			$rows,
			OFAADMIN . '?page=semester',
			$pagination,
			array(),
			array(),
			false,
			'ofaSemesterList');

		echo $this->theme->getDefaultFooter();
	}

	public function courseShortcode($atts, $content = null) {
		extract(shortcode_atts(array(
            'search' 			=> false,
            'departmentid'	=> 0
        ), $atts));
        
		return $this->listAllShortcode((bool)$search, $departmentid);
	}

	private function listAllShortcode($doSearch, $department) {
		$html = '';
		$section = new ofaSections();
		
		$id = ofaGet::get('id');
		$paged = ofaGet::get('oPaged');
		$semesterId = ofaGet::get('filter');
		
		$post = ofaWpSecurity::getPost();
		$search = ofaGet::get('oQ');
		$sections = '';
		
		if (empty($paged))
			$paged = 1;

		// Retrieve the current semester
		$options = new ofaOptions();
		
		if (empty($semesterId))
			$semesterId = $options->getOption('CURRENT_SEMESTER');
		
		$semester = new ofaSemesters();
		$currentSemester = $semester->load((int)$semesterId);
		
		// Display current semester
		$html .= '<h3>' . $currentSemester->name . ' Courses';
		
		$start = $currentSemester->startDate;
		$end = $currentSemester->endDate;
		
		if (!empty($start) && !empty($end))
			$html .= " <span class=\"alignright\">{$start} - {$end}</span>";
		
		$html .= '</h3><br />';
		
		$action = ofaGet::getCurrentPageNoParams();

		// Set up search
		if ($doSearch == true) {		
			$html .= <<<HTML
<form id="ofaSearch" action="{$action}" method="get">
	<input type="text" name="oQ" value="{$search}" />
	<input type="hidden" name="filter" value="{$semesterId}" />
	<input type="submit" value="Search" />
	<a href="?view=">View All</a>
</form>
HTML;
		}

		// Load records
		if ($doSearch == true && !empty($search)) {
			$sections = $section->searchAllJoined($search, $semesterId, true, $department);
		}
		else
			$sections = $section->loadAllJoined($semesterId, true, '', $department);

		// Paginate
		$pagination = '<div class="ofaPagination" style="float: right;">' . $this->theme->pagination(count((array)$sections), $paged, 20, site_url($post->post_name) . '/?oQ=' . $search . '&filter=' . $semesterId . '&oPaged=') . '</div>';
		
		$sections = $section->paginate($sections, $paged, 20);
		
		// Dropdown for semesters
		$allSemesters = $semester->multiLoad();
		$listSemesters = array();
		
		foreach ($allSemesters as $s)
			$listSemesters[] = array('display' => $s->name, 'value' => $s->id);

		$html .= '<div style="float: left;"><form style="display: inline-block;" id="ofaFilter" action="' . $action . '" method="get">' . $this->theme->generateComplexField('select', 'filter', $listSemesters, $semesterId) . '<input type="hidden" name="oQ" value="' . $search . '" /><input style="margin-bottom: 0;" type="submit" value="Search" /></form></div>';
		
		$html .= $pagination;
		$html .= $this->buildListings($sections);
		
		$html .= $pagination;
		
		return $html;
	}
	
	private function buildListings($records) {
		$html = '';

		// IF		there are records		display listing
		// ELSE								display notice of no records found
		if (count((array)$records) > 0) {
			$headings = '<tr><th width="80%">Course</th><th>Instructor</th></tr>';
			
			$html .= "<table style=\"width: 100%;\"><thead>{$headings}</thead><tfoot style=\"background: #edeeef;\">{$headings}</tfoot><tbody>";
			
			// Iterate through each employee record to create the list
			foreach ($records as $entry) {
				$course = '<span style="font-size: 14px;"><b>' . $entry->course . ' ' . $entry->courseNumber . '</b>-' . $entry->section;
				
				if (!empty($entry->link))
					$course = '<a target="_blank" href="' . $entry->link . '">' . $course . '</a>';
				
				$course .= ': <i>' . $entry->courseTitle . '</i></span>';
				
				if (!empty($entry->syllabus))
					// $course .= '<br />[<a target="_blank" href="' . OFACONTENTURL . OFASYLLABIDIR . '/' . $entry->syllabus . '">Syllabus</a>]';
					$course .= '<br />[<a target="_blank" href="' . OFACONTENTURL . OFASYLLABIDIR . '/' . $entry->syllabus . '">' . $entry->course . ' ' . $entry->courseNumber . '-' . $entry->section . ' syllabus</a>]';

				if (!empty($entry->notes))
					$course .= '<br /><br /><b>Notes</b>:<br />' . $entry->notes;
				
				$html .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $course, $entry->lastName);
			}
			
			$html .= '</tbody></table>';
		}
		else
			$html .= '<div class="warning red"><h4>No Courses Found</h4>No course records were found for the current semester.</div>';
		
		return $html;
	}
}