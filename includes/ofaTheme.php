<?php

/**
 * ofaTheme
 * The skinning class for OFA
 * @author Martin Ronquillo
 */
class ofaTheme {
	const defaultTitle = 'OIT Faculty Admin';
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function ofaTheme() {
		// Nothing to do here
	}
	
	/**
	 * Return the top portion of the WP page
	 * @param $title: the title of the page to display
	 * @return $html: the html block
	 */
	public function getDefaultHeader($title = '', $new = '') {
		$pageTitle = (!empty($title)) ? $title : self::defaultTitle;
		$newButton = '';
		
		if (!empty($new))
			$newButton = '<a class="add-new-h2" href="' . $new . '">Add New</a>';
		
		$html = <<<HTML
<div class="wrap"><div id="ofaIcon" class="icon32"></div><h2>{$pageTitle} {$newButton}</h2>
HTML;
		
		return $html;
	}
	
	/**
	 * Display the WordPress message
	 * @param $message: The message to display
	 * @param $type (optional, default 'updated'): The type of message to display (updated|error)
	 * @return An HTML string with the message to display
	 */
	public function buildMessage($message, $type = 'updated') {
		if ($type == 'updated' || $type == 'error')
			return '<div class="' . $type . '"><p>' . $message . '</p></div>';
	}
	
	/**
	 * Create a display list like the native WP pages
	 * @param $items (display|class|href): a multidimensional array with the items to list
	 * @param $current (optional): the class of the current item; method will attempt to grab the 'view' GET variable if this parameter is not provided
	 * @return $html: the HTML for the list
	 */
	public function generateDisplayList($items, $currentPage = '') {
		$html = '<ul class="subsubsub">';
		$current = (!empty($currentPage)) ? $currentPage : (string)$_GET['view'];
		
		foreach ($items as $item) {
			$bold = '';
			
			if ($item['class'] == $current)
				$bold = 'current';
			
			$html .= sprintf('<li class="%s"><a class="%s" href="%s">%s</a> | </li>', $item['class'], $bold, $item['href'], $item['display']);
		}
		
		$html .= '</ul>';
		return $html;
	}
	
	/**
	 * Create pagination links
	 * @param $itemCount: the total number of items in existance
	 * @param $currentPage: the current "page"
	 * @param $itemsPerPage: number of items to display on each "page"
	 * @param $url: the static portion of the URL
	 */
	public function pagination($itemCount, $currentPage, $itemsPerPage, $url) {
		global $wp_query;
		$total = ceil($itemCount / $itemsPerPage);

		$paginate = array(
			'base'			=> $url . '%_%',
			'format'		=> '%#%',
			'total' 		=> $total,
			'current'		=> $currentPage,
			'end_size'		=> 2,
			'mid_size'		=> 3,
			'prev_text'    => __('&laquo;'),
			'next_text'    => __('&raquo;'),
			'type'			=> 'plain');
			
		return paginate_links($paginate);
	}
	
	/**
	 * Generate a WP-like table to list items
	 * Modules must provide the ability to process the form submissions (bulk actions & search)
	 * Uses WP nonces, so be sure to perform a security check using check_admin_referer('ofaPersonnel')
	 * @param $headings: the table headings
	 * @param $data: the data to display in the table
	 * @param $bulkActions (optional): provide the bulk action capability
	 * @param $id: an array with the ids for the list items (to enable checkbox functionality)
	 * @param $search (optional): allow users to search data by providing a search form
	 * @param $tableId (optional): the css table ID
	 * @param $filterHTML (optional): HTML string with filter options
	 * @return $html: the generated HTML
	 */
	public function generateTable($headings, $data, $action = '', $pagination, $bulkActions = array(), $id = array(), $search = false, $tableId = 'ofaWpTable', $filterHTML = '') {
		$html = '<form id="posts-filter" method="post" action="' . $action . '">' . wp_nonce_field('ofaList', 'ofaNonce', true, false);

		// Generate search
		if ($search != false) {
			$html .= <<<HTML
<p class="search-box">
	<label class="screen-reader-text" for="post-search-input">Search:</label>
	<input id="post-search-input" type="search" value="" name="s" />
	<input id="search-submit" class="button" type="submit" value="Search" />
</p>
HTML;
		}
		
		$tablenav = '';
		$paginationHTML = '';
		
		// Generate HTML for bulk actions
		if (!empty($bulkActions)) {
			$options = '';
			
			// Iterate through the bulk actions to create option elements
			foreach ($bulkActions as $key => $item)
				$options .= sprintf('<option value="%s">%s</option>', $item, $key);
			
			$tablenav .= <<<HTML
	<div class="alignleft actions">
		<select name="action">
			<option selected="selected" value="-1">Bulk Actions</option>
			{$options}
		</select>
		<input id="doaction" class="button-secondary action" type="submit" value="Apply" name="">
	</div>
HTML;
		}

		if (!empty($filterHTML))
			$tablenav .= '<div class="alignleft actions">' . $filterHTML . '</div>';
		
		// Append pagination HTML, if provided
		if (!empty($pagination))
			$paginationHTML = '<div class="tablenav-pages">' . $pagination . '</div>';
		
		// Table headings
		$header = '<tr>';

		if (!empty($bulkActions))
			$header .= '<th id="cb" class="manage-column column-cb check-column" style="" scope="col"><input type="checkbox"></th>';
		
		$count = 1;
		
		foreach ($headings as $head) {
			$countClass= ' column-' . $count . '-heading';
			$header .= '<th class="manage-column column-title' . $countClass . '">' . $head . '</th>';
			$count++;
		}
		
		$header .= '</tr>';
		
		// Table content
		$content = '';

		// Keep track of rows
		$count = 0;
		
		// Create the rows
		foreach ($data as $row) {
			$columns = '';
			
			if (!empty($bulkActions))
				$columns .= '<th class="check-column" scope="row"><input type="checkbox" value="' . $id[$count] . '" name="s[]"></th>';
			
			// Populate the columns
			foreach ($row as $item) {
				$columns .= '<td>' . $item . '</td>';
			}
			
			$content .= '<tr>' . $columns . '</tr>';
			$count++;
		}
		
		// Assemble the table
		$html .= <<<HTML
<div class="tablenav top">
{$tablenav}{$paginationHTML}
</div>
<table id="{$tableId}" class="wp-list-table widefat fixed">
	<thead>
		{$header}
	</thead>
	<tfoot>
		{$header}
	</tfoot>
	<tbody id="the-list">
		{$content}
	</tbody>
</table>
<div class="tablenav bottom">
{$paginationHTML}
</div>
HTML;
		
		$html .= '</form>';
		return $html;
	}

	/**
	 * Generate a WP meta box
	 * @param $title: the title to display
	 * @param $content: the HTML content of the meta box
	 * @return $html: the HTML for the meta box
	 */
	public function generateMetaBox($title, $content) {
		$html = <<<HTML
<div class="meta-box-sortables">
	<div class="stuffbox">
		<h3><span>{$title}</span></h3>
		<div class="inside">
			{$content}
		</div>
	</div>
</div>
HTML;

		return $html;
	}
	
	/**
	 * Return the bottom portion of the WP page
	 * @param none
	 * @return $html: the html block
	 */
	public function getDefaultFooter() {
		$imageSrc = OFAURL . '/public/images/logo-stacked.png';
		$html = <<<HTML
	<div>
		<br />
		<br />
		<p class="alignright"><i>OIT Faculty Admin developed by</i><img style="margin-left: 10px; vertical-align:middle;" src="{$imageSrc}" alt="OIT Faculty Admin: Boise State" /></p>
	</div>
</div>
HTML;

		return $html;
	}
	
	/**
	 * Render a page using the default settings
	 * @param $content: the main content of the page
	 * @param $title (optional): the title of the page
	 * @return none
	 */
	public function defaultRender($content, $title = '') {
		echo $this->getDefaultHeader($title);
		echo $content;
		echo $this->getDefaultFooter();
	}
	
	/**
	 * Generate a complex HTML form field
	 * @param $type: the form type (radio|select)
	 * @param $name: name to give the element
	 * @param $values: array with the values to present
	 * @param $current (optional): current value (use for sticky forms)
	 * @return $html: form HTML
	 */
	public function generateComplexField($type, $name, $values, $current = '') {
		$html = '';
		
		switch($type) {
			case 'radio':
				$html = $this->generateRadioSet($name, $values, $current);
				break;
			case 'select':
				$html = $this->generateSelect($name, $values, $current);
				break;
		}
		
		return $html;
	}
	
	/**
	 * Generate a group of radio elements
	 * @param $name: name of radio element
	 * @param $values: array with the radio values
	 * @param $current: current value
	 * @return $html: element HTML
	 */
	private function generateRadioSet($name, $values, $current) {
		$html = '<fieldset>';
		$counter = 0;

		foreach ($values as $value) {
			$checked = '';
			
			if ($value['value'] === $current)
				$checked = ' checked';
			
			if (empty($current) && $counter == 0)
				$checked = ' checked';
			
			$html .= sprintf('<label><input type="radio" name="%s" value="%s"%s /> <span>%s</span></label><br />', $name, $value['value'], $checked, $value['display']);
			$counter++;
		}
		
		$html .= '</fieldset>';
		return $html;
	}
	
	/**
	 * Generate a select element using the provided values
	 * @param $name: element name
	 * @param $values: array with the element values
	 * @param $current (optional): current value
	 * @return $html: element HTML
	 */
	private function generateSelect($name, $values, $current = '') {
		$html = sprintf('<select id="%s" name="%s">', $name, $name);
		$counter = 0;

		foreach ($values as $value) {
			$selected = '';
			
			if ($value['value'] == $current)
				$selected = ' selected';
			
			if (empty($current) && $counter == 0)
				$selected = ' selected';
			
			$html .= sprintf('<option value="%s"%s>%s</option>', $value['value'], $selected, $value['display']);
			$counter++;
		}
		
		$html .= '</select>';
		return $html;
	}
	
	/**
	 * Generate a file upload element
	 * @param $type: type of upload field (file|photo)
	 * @param $name: name of upload element
	 * @param $description (optional): element description to display to user
	 * @param $current (optional): current value (use for sticky forms)
	 * @param $dir (optional): directory of existing file (to present to user)
	 * @return $html: element HMTL
	 */
	public function generateUploadField($type, $name, $description = '', $current = '', $dir = '') {
		$html = '';
		
		switch($type) {
			case 'file':
				$html = $this->generateFileField($name, $description, $current, $dir);
				break;
			case 'photo':
				$html = $this->generatePhotoField($name, $description, $current, $dir);
				break;
		}

		return $html;
	}
	
	/**
	 * Generate a file upload element
	 * @param $name: name of element
	 * @param $description (optional): description to display to user
	 * @param $current (optional): current value
	 * @param $dir (optional): directory of existing file
	 * @return $html: element HTML 
	 */
	private function generateFileField($name, $description = '', $current = '', $dir = '') {
		$display = '';
		$fileSrc = (!empty($current)) ? $current : 'NONE';
		$href = (!empty($dir)) ? $dir . '/' . $fileSrc : '';
		$desc = (!empty($description)) ? '<span class="description">' . $description . '</span>' : '';
		
		if (!empty($href) && $fileSrc != 'NONE')
			$display = sprintf('<a target="_blank" href="%s">%s</a>', $href, $fileSrc);
		else
			$display = $fileSrc;
		
		return sprintf('<span>Current file: <code>%s</code></span><br /><input id="%s" type="file" name="%s" /><input type="hidden" name="%s" value="%s" />%s', $display, $name, $name, $name . 'Old', $current, $desc);
	}
	
	/**
	 * Generate an image upload element
	 * @param $name: name of element
	 * @param $description (optional): description to display to user
	 * @param $current (optional): current value
	 * @param $dir (optional): directory of existing file
	 * @return $html: element HTML 
	 */
	private function generatePhotoField($name, $description = '', $current = '', $dir = '') {
		$imageSrc = (!empty($current)) ? $dir . '/' . $current : OFABIOBLANK;
		$desc = (!empty($description)) ? '<span class="description">' .  $description . '</span>' : '';
		
		return sprintf('<img class="photo" src="%s" /><br /><input id="%s" type="file" name="%s" /><input type="hidden" name="%s" value="%s" />%s', $imageSrc, $name, $name, $name . 'Old', $current, $desc);
	}
	
	/**
	 * Create list items from an array
	 * @param $items: an array with the list items
	 * @return $listItems: the HTML for the list items
	 */
	public function createListItems($items) {
		$listItems = '';
		
		foreach ($items as $item)
			$listItems .= '<li>' . $item . '</li>';
		
		return $listItems;
	}
}
