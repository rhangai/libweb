<?php
namespace LibWeb\util;

trait ArrayTraits {
	function map( $cb ) {
		return new ArrayIteratorMap( $this, $cb );
	}
	function filter( $cb ) {
		return new ArrayIteratorFilter( $this, $cb );
	}
	function whitelist( $list ) {
		$cb = function( $item ) use ( $list ) {
			$ret = array();
			foreach ( $list as $key )
				$ret[$key] = $item->{$key};
			return (object) $ret;
		};
		return new ArrayIteratorMap( $this, $cb );
	}
	function blacklist( $list ) {
		$isBlacklisted = array();
		foreach ( $list as $key )
			$isBlacklisted[ $key ] = true;
		$cb = function( $item ) use ( $list ) {
			$ret = array();
			foreach ( $item as $key => $value ) {
				if ( !@$isBlacklisted[ $key ] )
					$ret[$key] = $value;
			}
			return (object) $ret;
		};
		return new ArrayIteratorMap( $this, $cb );
	}
};