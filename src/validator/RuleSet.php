<?php
namespace LibWeb\validator;

class RuleSet {

	// Static rules
	private static $rules   = array(
		'call'    => '\LibWeb\validator\rule\InlineRule',
		'arrayOf' => '\LibWeb\validator\rule\ArrayOfRule',
		'date'    => '\LibWeb\validator\rule\DateTimeRule',
		'file'    => '\LibWeb\validator\rule\FileRequestRule',
	);

	/// Inline validators, shortcuts to default php functions
	private static $inlines = array();

	/// Get a rule based on the name and the args
	public static function get( $name, $args ) {
		if ( isset( self::$rules[$name] ) ) {
			$klass = self::$rules[ $name ];
			$setupArgs = null;
			if ( is_array( $klass ) ) {
				$setupArgs = $klass;
				$klass     = array_shift( $setupArgs );
			}
			$obj = new $klass( ... $args );
			if ( $setupArgs )
				$obj->setup( ...$setupArgs );
			return $obj;
		} else if ( isset( self::$inlines[$name] ) ) {
			return new rule\InlineRule( self::$inlines[$name], $args );
		} else if ( $name !== 'get' && is_callable( array( __CLASS__, $name ) ) )
			return new rule\InlineRule( array( __CLASS__, $name ), $args );
		else
			throw new \InvalidArgumentException( "Invalid rule ".$name );
	}

	// String value
	public static function strval( $value ) {
		return self::s( $value );
	}
	public static function s( $value ) {
		return trim( $value );
	}
	// Int value
	public static function intval( $value ) {
		return self::i( $value );
	}
	public static function i( $value ) {
		$error = false;
		if ( is_int( $value ) ) {
		    return true;
		} else if ( is_string( $value ) ) {
			if ( !ctype_digit( $value ) )
				return false;
			return intval( trim( $value ), 10 );
		} else
			return false;
	}

	// Float value
	public static function floatval( $value, $decimal = null, $thousands = null ) {
		return self::f( $value, $decimal, $thousands );
	}
	public static function f( $value, $decimal = null, $thousands = null ) {
		$error = false;
		if ( $decimal === null )
			$decimal   = '.';

		if ( is_int( $value ) ) {
		    return true;
		} else if ( is_float( $value ) ) {
		    return true;
		} else if ( is_string( $value ) ) {
			$value = trim( $value );
			if ( $thousands )
				$value = str_replace( $thousands, "", $value );
			if ( ctype_digit( $value ) )
				return intval( $value, 10 );
			$split = explode( $decimal, $value );
			if ( ( count($split) != 2 ) || ( !ctype_digit( $split[0] ) ) || ( !ctype_digit( $split[1] ) ) )
				return false;
			return $decimal === '.' ? floatval( $value ) : floatval( $split[0].'.'.$split[1] );
		} else
			return false;
	}

	// Boolean value
	public static function boolean( $value ) {
		return self::b( $value );
	}
	public static function b( $value ) {
		if ( !$value || ($value === 'false') )
		    return new rule\InlineRuleValue( false );
		else if ( ( $value === true ) || ( $value === 'true' ) || ( $value === '1' ) )
		    return new rule\InlineRuleValue( true );
		else
			return false;
	}
	
	// Validate against a regex
	public static function regex( $value, $pattern ) {
	    $match = preg_match( $pattern, $value );
		if ( !$match )
			return false;
		return true;
	}

};