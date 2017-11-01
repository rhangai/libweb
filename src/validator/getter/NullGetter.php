<?php
namespace LibWeb\validator\getter;

class NullGetter implements Getter {
	public function get( $key ) {
		return null;
	}
};