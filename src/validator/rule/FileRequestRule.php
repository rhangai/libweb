<?php
namespace LibWeb\validator\rule;
use LibWeb\validator\Rule;
use LibWeb\api\Request;

class FileRequestRule extends Rule {

	private $multiple;

	public function __construct( $multiple = false ) {
		parent::__construct( Rule::FLAG_ALWAYS );
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
		if ( !$this->multiple ) {
			$state->value = $parent->file( $state->getKey(), false );
			if ( !$state->value && !$this->testFlag( Rule::FLAG_OPTIONAL ) )
				$state->setError( "Invalid file" );
		} else {
			$state->value = $parent->file( $state->getKey(), true );
		}
	}
	
};
