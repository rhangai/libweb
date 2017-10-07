<?php
namespace LibWeb;

class Validator {
	
	public static function validate( $value, $rule, $options = array() ) {
		if ( ( $options === true ) || ( $options === false ) )
			$options = array( "serializable" => $options );
		$state = new validator\State( $value );
		validator\Rule::validateState( $rule, $state );

		$errors = $state->errors();
		if ( $errors )
			throw new ValidatorException( $state, $errors, @$options["serializable"] );
		return $state->value;
	}

	// Add a new inline rule
	public static function addInlineRule( $name, $rule ) {
		validator\RuleSet::addInlineRule( $name, $rule );
	}
	/// This method will cal
	public static function __callStatic( $method, $args ) {
		$chain = new validator\RuleChain;
		$chain->__call( $method, $args );
		return $chain;
	}
	
};
