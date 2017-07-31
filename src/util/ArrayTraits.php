<?php
namespace LibWeb\util;

trait ArrayTraits {
	function map( $cb ) {
		return new ArrayIteratorMap( $this, $cb );
	}
	function filter( $cb ) {
		return new ArrayIteratorFilter( $this, $cb );
	}
};