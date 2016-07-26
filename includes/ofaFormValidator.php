<?php

/**
 * ofaFormValidator
 * A class to handle form validation
 * Use of this class is optional, however it is the plugin's default method of validating/sanitizing form data
 * @author Martin Ronquillo
 */
class ofaFormValidator {
	private $userRules;
	private $userConstraints;
	private $rules;
	private $ignore;
	const EMPTYFIELD = 'ofaFormElement[Empty]';
	
	/**
	 * The following are the rules which users can define
	 */
	const EMAIL		= 'email';
	const HTML 		= 'html';
	const INVALID 	= 'invalid';
	const PHONE 	= 'phone';
	const REQUIRED	= 'required';
	const URL		= 'url';
	
	/**
	 * Constructor
	 * @param none
	 * @return none
	 */
	public function ofaFormValidator(){
		$this->userRules = array();
		$this->userConstraints = array();
		$this->ignore = array();
		
		// Redefine all rules, for internal usage
		$this->rules = array(
			'EMAIL'		=> 'email',
			'HTML'		=> 'html',
			'INVALID'	=> 'invalid',
			'PHONE'		=> 'phone',
			'REQUIRED'	=> 'required',
			'URL'		=> 'url');
	}
	
	/**
	 * Add a rule for a particular form element
	 * @param $element: element name
	 * @param $rule: the rule to apply
	 * @param $data (optional): additional info pertaining to the rule
	 * @param $constraint (optional, default: false): if set to true, rule will be added as constraint
	 * @return true|false
	 */
	public function addRule($element, $rule, $data = '', $constraint = false) {
		// Add the rule, if it is one which
		if (in_array($rule, $this->rules)) {
			if ($constraint) {
				$this->userConstraints[$element] = array(
					array(
						'rule' => $rule,
						'data' => $data));
			}
			else {
				$this->userRules[$element] = array(
					array(
						'rule' => $rule,
						'data' => $data));	
			}
				
			return true;
		}
		
		return false;
	}
	
	/**
	 * Add a constraint to an element
	 * @param $element: name of the element
	 * @param $rule: the rule to specify for the element
	 * @param $data (optional): pass information related to the rule
	 * @return true|false
	 */
	public function addConstraint($element, $rule, $data = '') {
		if ($this->addRule($element, $rule, $data, true))
			return true;
		else
			return false;
	}
	
	/**
	 * Ignore an element during validation
	 * @param $name: name of element
	 * @return true
	 */
	public function ignoreElement($name) {
		$this->ignore[] = $name;
		return true;
	}
	
	/**
	 * Validate & sanitize a record
	 * @param $data: the data record
	 * @return validated data on true | array of invalid elements on false
	 */
	public function validate($data) {
		$processed = array();
		
		// Iterate through each piece of data
		foreach ($data as $key => $item) {
			// If the element is to be processed, continue
			if (!in_array($key, $this->ignore)) {
				// Check if the element has a rule
				if (array_key_exists($key, $this->userRules)) {
					$rule = $this->userRules[$key][0]['rule'];
					$ruleData = $this->userRules[$key][0]['data'];
	
					switch($rule) {
						case 'email':
							$processed[$key] = $this->processEmail($item);
							break;
						case 'html':
							$processed[$key] = $this->processHTML($item, $ruleData);
							break;
						case 'invalid':
							$processed[$key] = $this->processInvalid($item, $ruleData);
							break;
						case 'phone':
							$processed[$key] = $this->processPhone($item);
							break;
						case 'url':
							$processed[$key] = $this->processUrl($item);
							break;
					}
				}
				else {
					if ($item != '')
						$processed[$key] = addslashes(htmlspecialchars($item));
					else
						$processed[$key] = self::EMPTYFIELD;
				}
				
				// Check if the element has a constraint
				if (array_key_exists($key, $this->userConstraints)) {
					$constraint = $this->userConstraints[$key][0]['rule'];
					$constraintData = $this->userConstraints[$key][0]['data'];
					
					switch ($constraint) {
						case 'required':
								if (empty($item))
									$processed[$key] = false;
							break;
					}
				}
			}
			}
		
		return $this->processData($processed);
	}
	
	/**
	 * Complete the validation process
	 * @return validated data on true | array of invalid elements on false
	 */
	private function processData($data) {
		// Iterate throught each validation item to ensure that it is allowable
		foreach ($validation as $key => $item) {
			// Check if the validation item is an element
			if (array_key_exists($key, $data)) {
				$add = false;
				
				// IF		element has a constraint		check constraints
				// ELSE		no constraint					check to see if false
				if (array_key_exists($key, $this->$userConstraints)) {
					$constraint = $this->userConstraints[$key][0]['rule'];
					
					if ($constraint == 'required' && $item != false)
						$add = true;
				}
				else {
					if ($item != false)
						$add = true;
				}
				
				$data[$key] = ($add == true) ? $item : false;
			}
		}
		
		$false = array();
		
		foreach ($data as $element => $value) {
			if ($value == false && strlen($value) < 1)
				$false[] = $element;
			elseif ($value == self::EMPTYFIELD)
				$data[$element] = '';
		}
		
		if (empty($false))
			return (object)$data;
		else
			return $false;
	}

	/**
	 * Check an email element
	 * @param $email: an email address to check
	 * @return $email|false|empty
	 */
	private function processEmail($email) {
		if (!empty($email)) {
			if (filter_var($email, FILTER_VALIDATE_EMAIL))
				return $email;
			else
				return false;
		}
		else
			return self::EMPTYFIELD;
	}
	
	/**
	 * Check an element which allows HTML tags
	 * @param $html: the content to check
	 * @param $allowedTags (optional): tags to allow
	 * @return $html|false|empty
	 */
	private function processHTML($html, $allowedTags = '') {
		if (!empty($html)) {
			return strip_tags($html, $allowedTags . '<br>');
		}
		else
			return self::EMPTYFIELD;
	}
	
	/**
	 * Check an element to find invalid characters
	 * @param $text: the content to check
	 * @param $invalid: string of characters to check for - separated by a pipe character ("|")
	 * @return $text|empty|false
	 */
	private function processInvalid($text, $invalid) {
		if (!empty($text)) {
			$valid = true;
			$characters = explode("|", $invalid);
			
			foreach ($characters as $c) {
				$charCheck = strpos($text, $c);

				if (is_int($charCheck))
					return false;
			}
			
			return $text;
		}
		else
			return self::EMPTYFIELD;
	}
	
	/**
	 * Check a phone number
	 * @param $phone: phone number to check
	 * @return $phone|false|empty
	 */
	private function processPhone($phone) {
		if (!empty($phone)) {
			if (preg_match('/^\(?\d{3}\)?[-\s.]?\d{3}[-\s.]\d{4}$/', $phone))
				return $phone;
			else
				return false;
		}
		else
			return self::EMPTYFIELD;
	}
	
	/**
	 * Check a URL element
	 * @param $url: a URL to check
	 * @return $email|false|empty
	 */
	private function processUrl($url) {
		if (!empty($url)) {
			if (filter_var($url, FILTER_VALIDATE_URL))
				return $url;
			else
				return false;
		}
		else
			return self::EMPTYFIELD;
	}
}
