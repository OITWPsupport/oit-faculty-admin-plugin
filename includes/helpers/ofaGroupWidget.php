<?php

/**
 * ofaGroupWidget
 * Class to handle the special form elements for integrating groups to personnel
 * @author Martin Ronquillo
 */
class ofaGroupWidget {
	private $theme;
	private $employeesGroupsX;
	private $data;
	private $head;
	private $foot;
	private $delete;
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function ofaGroupWidget($theme) {
		$this->theme = $theme;
		$this->employeesGroupsX = new ofaEmployeesGroupsX();
		$this->data = '';
		$this->delete = array();
	}
	
	/**
	 * Create the table headings for the widget
	 * @param $heading1 (optional, default: "Group"): the first table heading
	 * @param $heading2 (optional, default: "Options"): the second heading
	 * @param $heading3 (optional, default: "Remove"): the third heading
	 */
	public function addHeadings($heading1 = 'Group', $heading2 = 'Options', $heading3 = 'Remove') {
		$this->head = <<<HTML
<thead style="background: #f1f1f1;">
	<tr>
		<th>{$heading1}</th>
		<th colspan="2">{$heading2}</th>
		<th>{$heading3}</th>
	</tr>
</thead>
HTML;
	}
	
	/**
	 * Create the footer for the widget
	 * @param $label (optional, default: "New Membership Record"): the footer label
	 * @param $button (optional, default: "Add"): button text
	 */
	public function setupFooter($label = 'New Membership Record', $button = 'Add') {
		$this->foot = <<<HTML
<tfoot style="background: #f1f1f1;">
	<tr>
		<th><label>{$label}</label></th>
		<td>
			&nbsp;
		</td>
		<td>
			&nbsp;
		</td>
		<td>
			<a id="ofaGroupWidgetAdd" class="button-secondary" href="#">{$button}</a>
		</td>
	</tr>
</tfoot>
HTML;
	}
	
	/**
	 * Build the group widget
	 * @param $current: an object with all records to pre-populate the widget with
	 * @return $html: the HTML for the widget
	 */
	public function getElement($current = '') {
		$keys = array();
		$default = '';
		$groupList = '';
		$keyList = '';

		// Generate object for the default entry
		$data = new stdClass();
		$data->groupId = 0;
		$data->listFirst = 'No';
		$data->position = '';
		
		// Create the first entry
		$firstEntry = $this->buildEntry($data);
		$keys[] = $firstEntry['key'];
		
		// Save the group selection HTML
		$groupList = $firstEntry['groups'];
		
		if (!empty($current) && is_object($current)) {
			// Iterate through each current record to create entries
			foreach ($current as $c) {
				$entry = $this->buildEntry($c);
				$default .= $entry['html'];
				$keys[] = $entry['key'];
			}
		}

		// Create list of keys
		foreach ($keys as $key)
			$keyList .= '<input type="hidden" name="ofaGroupWidgetKeys[]" value="' . $key . '" />';
		
		// Build the group widget element
		return <<<HTML
<h3>Group Association</h3>
<table id="ofaGroupWidgetTable" class="form-table" style="border: 1px #dfdfdf solid;">
	{$this->head}
	{$this->foot}
	<tbody style="border-top: 1px #dfdfdf solid;">
		{$default}
	</tbody>
</table>
<input id="ofaGroupWidgetGroupList" type="hidden" name="ofaGroupWidgetGroupList" value="{$groupList}" />
<span id="ofaGroupWidgetKeyChain">
	{$keyList}
</span>
<span id="ofaGroupWidgetDelete"></span>
HTML;
	}

	/**
	 * Load the widget with the pre-existing entries of an employee
	 * @param $id: the employee ID
	 * @return $element: the HTML for the widget
	 */
	public function getElementByEmployee($id) {
		// Query the EmployeesGroupsX table to retrieve the relationship records for an employee
		$employeesGroupsX = new ofaEmployeesGroupsX();
		$groups = $employeesGroupsX->multiLoad(array(
			array(
				'column'	=> 'employeeId',
				'value' 	=> $id)));

		return $this->getElement($groups);
	}

	/**
	 * Assemble the entry rows
	 * @param $data (id|groupId|listFirst|position): an object with the entry data
	 * @return $entry (array('html', 'key', 'groups')): an array with a variety of entry information
	 */
	private function buildEntry($data) {
		$groups = $this->getGroups();
		$key = substr(uniqid(), 6);
		
		$groupNames = array();
		
		foreach ($groups as $g) {
			$groupNames[$g->groupType . '-' . $g->name] = array(
				'display' => $g->groupType . ": " . $g->name,
				'value' => $g->id);
		}
		
		ksort($groupNames);
		
		$groupSelect = $this->theme->generateComplexField('select', $key . 'ofaGroupWidgetGroupSelect', $groupNames, $data->groupId);
		
		$groupList = str_replace('<select id="' . $key . 'ofaGroupWidgetGroupSelect" name="' . $key . 'ofaGroupWidgetGroupSelect">', '', $groupSelect);
		$groupList = str_replace('</select>', '', $groupList);
		$groupList = htmlentities($groupList);

		// ID field
		$idField = (isset($data->id)) ? '<input type="hidden" name="' . $key . 'ofaGroupWidgetGroupId" value="' . $data->id . '" />' : '';

		// List first checkbox
		$listFirstChecked = ($data->listFirst == 'Yes') ? ' checked' : '';

		// Remove Button Link
		$groupRemoveLink = OFAADMIN . '?page=group&action=membership&id=' . $data->groupId . '&delete=' . $data->id . '&ret=' . urlencode(OFAADMIN . '?page=personnel&action=edit&id=' . $_GET['id']);
		
		// Create the HTML for the entry
		$html = <<<HTML
<tr>
	<th>
		{$groupSelect}
	</th>
	<td>
		<label for="">List First</label>
		{$idField}
	</td>
	<td>
		<fieldset>
			<label><input type="checkbox" name="{$key}ofaGroupWidgetisFirst[]" value="Yes"{$listFirstChecked}> <span>List first in the group listings</span></label><br>
		</fieldset>
	</td>
	<td>
		<span><a id="ofaGroupWidgetRemove" class="button-secondary" href="$groupRemoveLink">X</a></span>
	</td>
</tr>
<tr style="border-bottom: 1px #dfdfdf solid;">
	<th>
		&nbsp;
	</th>
	<td>
		<label for="">Position</label>
	</td>
	<td>
		<fieldset>
			<input type="text" class="regular-text" name="{$key}ofaGroupWidgetPosition" value="{$data->position}" />
		</fieldset>
	</td>
	<td>
		&nbsp;
	</td>
</tr>
HTML;
	
		return array(
			'html' => $html,
			'key' => $key,
			'groups' => $groupList);
	}
	
	/**
	 * Load all the groups
	 * @param none
	 * @return $groups: the groups retrieved
	 */
	private function getGroups() {
		$groups = new ofaGroups();
		return $groups->multiLoad();
	}
	
	/**
	 * Process & validate submitted widget data
	 * @param $data: the raw form POST
	 * @return $validatedGroups: the results of the processing and validation
	 */
	public function process($data) {
		$elements = (array)$data;
		$keys = $elements['ofaGroupWidgetKeys'];
		$groups = array();
		$employeeId = 0;
		$deleteIds = $elements['ofaGroupWidgetDeleteKeys'];
		
		// Grab the IDs of the entries to delete
		foreach ($deleteIds as $delete)
			$this->delete[] = $delete;

		// Retrieve the ID of the personnel record currently being edited
		if (empty($elements['id'])) {
			$employees = new ofaEmployees();
			$employee = $employees->multiLoad(array(
				array(
					'column' => 'email',
					'value' => $elements['email'])));
					
			$employeeId = $employee[0]->id;
		}
		else
			$employeeId = $elements['id'];
		
		// Iterate through each entry key to retrieve data
		foreach ($keys as $key) {
			$idKey = $key . 'ofaGroupWidgetGroupId';
			$groupKey = $key . 'ofaGroupWidgetGroupSelect';
			$isFirst = $key . 'ofaGroupWidgetisFirst';
			$positionKey = $key . 'ofaGroupWidgetPosition';
			
			// Check to see if key is valid
			if (array_key_exists($groupKey, $elements)) {
				$group = array();
				
				$group['id'] = (isset($elements[$idKey])) ? $elements[$idKey] : '';
				$group['employeeId'] = $employeeId;
				$group['groupId'] = $elements[$groupKey];
				$group['listFirst'] = (isset($elements[$isFirst]) && $elements[$isFirst][0] == 'Yes') ? 'Yes' : 'No';
				$group['position'] = $elements[$positionKey];
				
				$groups[] = $group;
			}
		}
		
		$validatedGroups = array();
		
		// Iterate through each group to validate
		foreach ($groups as $validate)
			$validatedGroups[] = $this->employeesGroupsX->validate($validate);

		$this->data = $validatedGroups;
		return $validatedGroups;
	}

	/**
	 * Save all the records and delete the specified records
	 * @param $data: the records to save
	 * @return $result: the query results
	 */
	public function save($data = '') {
		$this->delete();
		
		if (empty($data))
			$data = $this->data;
		
		return $this->employeesGroupsX->save($data);
	}
	
	/**
	 * Delete a group widget entry
	 * @param none
	 * @return $deleteCount|false: the number of deleted records or false
	 */
	public function delete() {
		$counter = 0;

		// If there are keys to delete, iterate through them
		if (!empty($this->delete)) {
			foreach ($this->delete as $item)
				$counter += $this->employeesGroupsX->delete($item);
			
			return $counter;
		}
		
		return false;
	}
	
	/**
	 * Remove all group widget fields from the data record to prevent the Personnel validation to fail
	 * @param $data: data record to be cleaned
	 * @return $elements: the cleaned data record
	 */
	public function cleanDataRecord($data) {
		$keys = $data->ofaGroupWidgetKeys;
		$elements = (array)$data;
		
		// Using the keys, iterate through the array to find indicies to clean up
		foreach ($keys as $key) {
			$groupKey = $key . 'ofaGroupWidgetGroupSelect';
			$isFirst = $key . 'ofaGroupWidgetisFirst';
			$positionKey = $key . 'ofaGroupWidgetPosition';
			
			if (array_key_exists($groupKey, $elements)) {
				unset($elements[$groupKey]);
				unset($elements[$positionKey]);
				
				if (array_key_exists($isFirst, $elements))
					unset($elements[$isFirst]);
			}
		}
		
		// A little more cleaning up
		unset($elements['ofaGroupWidgetGroupList']);
		unset($elements['ofaGroupWidgetKeys']);
		unset($elements['ofaGroupWidgetDeleteKeys']);

		return (object)$elements;
	}
}
