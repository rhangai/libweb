<?php
namespace LibWeb;

class Validator {
	
	public static function validate( $value, $rule ) {
		$state = new validator\State( $value );
		validator\Rule::validateState( $rule, $state );

		$errors = $state->errors();
		if ( $errors )
			throw new ValidatorException( $state, $errors );
		return $state->value;
	}

	/// This method will cal
	public static function __callStatic( $method, $args ) {
		$chain = new validator\RuleChain;
		$chain->__call( $method, $args );
		return $chain;
	}
	
};
