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
		$parent = $state->getParent();
		if ( !$parent ) {
			$state->setError( "Invalid parent. Must be a Request instance" );
			return;
		}
		$parent = $parent->value;
		if ( ( !$parent ) || !($parent instanceof Request ) ) {
			$state->setError(  "Invalid parent. Must be a Request instance" );
			return;
		}
		$state->value = $parent->file( $state->getKey(), $this->multiple );
	}
	
};
