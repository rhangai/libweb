<?php
namespace LibWeb\validator\getter;

class MergeGetter implements Getter {

	private $getters_;
	public function __construct( $getters ) {
		$this->getters_ = $getters;
	}
	public function get( $key ) {
		foreach ( $this->getters_ as $getter ) {
			$value = $getter->get( $key );
			if ( $value !== null )
				return $value;
		}	
		return null;
	}
};