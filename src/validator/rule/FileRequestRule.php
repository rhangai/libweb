<?php
namespace LibWeb\validator\rule;
use LibWeb\validator\Rule;
use LibWeb\api\Request;

class FileRequestRule extends Rule {

	private $multiple;

	public function __construct( $multiple = false ) {
		$this->multiple = $multiple;
	}

	public function _clone() {
		return new FileRequestRule( $this->multiple );
	}

	public function apply( $state ) {
		$files = $state->value;
		if ( $files ) {
			if ( is_array( $files ) )
				$files = $this->multiple ? $files : @$files[0];
			else
				$files = $this->multiple ? array($files) : $files;
		}
		$state->value = $files;
	}
	
};
