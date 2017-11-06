<?php
namespace LibWeb\validator;

class RuleSet {

	// Static rules
	private static $rules   = array(
		'call'    => '\LibWeb\validator\rule\CallRule',
		'arrayOf' => '\LibWeb\validator\rule\ArrayOfRule',
		'date'    => '\LibWeb\validator\rule\DateTimeRule',
		'file'    => '\LibWeb\validator\rule\FileRequestRule',
		'obj'     => '\LibWeb\validator\rule\ObjectRule',
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
		} else if ( $name !== 'get' && is_callable( array( __CLASS__, $name ) ) ) {
			return new rule\InlineRule( array( __CLASS__, $name ), $args );
		} else if ( $name !== 'get' && is_callable( array( RuleSetRaw::class, $name ) ) ) {
			return new RuleSetRaw( $name, $args );
		} else
			throw new \InvalidArgumentException( "Invalid rule ".$name );
	}
	// Add a new inline rule
	public static function addInlineRule( $name, $rule ) {
		self::$inlines[ $name ] = $rule;
	}

	// Any value
	public static function any( $value ) {
		return true;
	}
	
	// Check if value matches the expected
	public static function value( $value, $expected ) {
		return ( $value === $expected );
	}
		
	// String value
	public static function strval( $value, $trim = true ) {
		return self::s( $value, $trim );
	}
	public static function s( $value, $trim = true ) {
		if ( is_object( $value ) ) {
			if ( !method_exists( $value, '__toString' ) )
				throw new \Exception( "Object cannot be converted to string" );
			$value = (string) $value;
		}
		if ( $trim !== false )
			return trim( $value );
		else
			return strval( $value );
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
			$isNegative = ( @$value[0] === '-' );
			if ( $isNegative )
				$value = substr( $value, 1 );
			if ( $thousands )
				$value = str_replace( $thousands, "", $value );
			if ( ctype_digit( $value ) ) {
				$value = intval( $value, 10 );
				return $isNegative ? -$value : $value;
			}
			$split = explode( $decimal, $value );
			if ( ( count($split) != 2 ) || ( !ctype_digit( $split[0] ) ) || ( !ctype_digit( $split[1] ) ) )
				return false;
			$value = ( $decimal === '.' ) ? floatval( $value ) : floatval( $split[0].'.'.$split[1] );
			return $isNegative ? -$value : $value;
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
		else if ( ( $value === true ) || ( $value === 'true' ) || ( $value == '1' ) )
		    return new rule\InlineRuleValue( true );
		else
			return false;
	}
	
	// Decimal value
	public static function decimal( $value, $digits, $decimal, $decimalSeparator = null, $thousandsSeparator = null ) {
		if ( !is_int( $decimal ) || ( $decimal < 0 ) )
			throw new \InvalidArgumentException( "Decimal precision must be a positive integral or 0. $decimal given" );
		
		if ( $decimalSeparator === null )
			$decimalSeparator = '.';

		if ( is_string( $value ) ) {
			if ( $thousandsSeparator )
				$value = str_replace( $thousandsSeparator, "", $value );
			if ( $decimalSeparator !== '.' ) {
				$value = str_replace( '.', "#.", $value );
				$value = str_replace( $decimalSeparator, '.', $value );
			}
			$value = new impl\Decimal( $value, $decimal );
		} else {
			$value = new impl\Decimal( $value, $decimal );
		}

		$integralDigits = $digits - $decimal;
		$max = \RtLopez\Decimal::create( '10', $decimal )->pow( $integralDigits );
		$min = $max->mul( -1 );
		if ( $value->ge( $max ) || $value->le( $min ) )
			return false;
		
		
		return $value;
	}

	// Validate against a regex
	public static function regex( $value, $pattern ) {
	    $match = preg_match( $pattern, $value );
		if ( !$match )
			return false;
		return true;
	}
	
	// Check against a set
	public static function set( $value, $ary ) {
		return in_array( $value, $ary );
	}
	
	// Map the value to another one
	public static function map( $value, $map ) {
		$value = @$map[$value];
		if ( $value === null )
			return false;
		return $value;
	}



	// String rules
	public static function len( $value, $min, $max = null ) {
		$len = is_array( $value ) ? count( $value ) : strlen( $value );
		if ( $max === null )
			$max = $min;
		
		if ( ( $len > $max ) && ( $max > 0 ) )
			throw new RuleException( "Maximun lenght must be ".$max );
		else if ( $len < $min )
			throw new RuleException( "Minimun lenght must be ".$min );
		return true;
	}
	public static function minlen( $value, $min ) {
		$len = is_array( $value ) ? count( $value ) : strlen( $value );
	    if ( $len < $min )
			throw new RuleException( "Minimun lenght must be ".$min );
		return true;
	}
	public static function str_replace( $value, $search, $replace ) {
		return str_replace( $search, $replace, $value );
	}
	public static function preg_replace( $value, $search, $replace ) {
		if ( is_string( $replace ) )
			return preg_replace( $search, $replace, $value );
		else if ( is_callable( $replace ) )
			return preg_replace_callback( $search, $replace, $value );
		else
			return false;
	}
	public static function blacklist( $value, $chars ) {
		$out = array();
		for ( $i = 0, $len = strlen( $value ); $i < $len; ++$i ) {
			$c = $value[ $i ];
			if ( strpos( $chars, $c ) === false )
				$out[] = $c;
		}
		return implode( "", $out );
	}
	public static function whitelist( $value, $chars ) {
		$out = array();
		for ( $i = 0, $len = strlen( $value ); $i < $len; ++$i ) {
			$c = $value[ $i ];
			if ( strpos( $chars, $c ) !== false )
				$out[] = $c;
		}
		return implode( "", $out );
	}


	// Object rules

	// Allow only one field (or N) on a single object
	public static function allowOnlyOneOf( $value, $keys, $count = 1 ) {
		$value = (array) $value;

		$existing = [];
		foreach ( $keys as $key ) {
			if ( @$value[$key] != null )
				$existing[] = $key;
		}

		$existingLen = count($existing);
		if ( $existingLen != $count ) {
			$passed = ($existingLen === 0) ? "None passed." : ("Passed: ".implode(", ", $existing).".");
			if ( $existingLen < $count ) {
				throw new \Exception( "You must pass at least ".$count." of ".implode( ", ", $keys ).". ".$passed );
			} else if ( $existingLen > $count ) {
				throw new \Exception( "Only ".$count." of ".implode( ", ", $keys )." are allowed. ".$passed );
			}
		}
		return true;
	}

	// Email
	public static function email( $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL );
	}

	/// Brazilian CPF validator
	public static function cpf( $cpf ) {
		$cpf = preg_replace('/[^0-9]/', '', (string) $cpf);

		// Valida tamanho
		if (strlen($cpf) != 11)
			return false;
		$all_equals = true;
		for ( $i = 1; $i<11; ++$i ) {
			if ( $cpf[$i] !== $cpf[$i-1] ) {
				$all_equals = false;
				break;
			}
		}
		if ( $all_equals )
			return false;
		// Calcula e confere primeiro dígito verificador
		for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--)
			$soma += $cpf{$i} * $j;
		$resto = $soma % 11;
		if ($cpf{9} != ($resto < 2 ? 0 : 11 - $resto))
			return false;
		// Calcula e confere segundo dígito verificador
		for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--)
			$soma += $cpf{$i} * $j;
		$resto = $soma % 11;
		if ( $cpf{10} != ($resto < 2 ? 0 : 11 - $resto) )
			return false;
		return $cpf;
	}
	// Brazilian CNPJ validator
	public static function cnpj( $cnpj ) {
		$cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
		// Valida tamanho
		if (strlen($cnpj) != 14)
			return false;
		$all_equals = true;
		for ( $i = 1; $i<14; ++$i ) {
			if ( $cnpj[$i] !== $cnpj[$i-1] ) {
				$all_equals = false;
				break;
			}
		}
		if ( $all_equals )
			return false;
		// Valida primeiro dígito verificador
		for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
		{
			$soma += $cnpj{$i} * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}
		$resto = $soma % 11;
		if ($cnpj{12} != ($resto < 2 ? 0 : 11 - $resto))
			return false;
		// Valida segundo dígito verificador
		for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
		{
			$soma += $cnpj{$i} * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}
		$resto = $soma % 11;
		if ( $cnpj{13} != ($resto < 2 ? 0 : 11 - $resto) )
			return false;
		return $cnpj;
	}


};