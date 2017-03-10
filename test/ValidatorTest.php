<?php
use LibWeb\Validator as v;

class ValidatorTest extends PHPUnit\Framework\TestCase
{
	public function testValidate() {
		$rules = array(
			"email" => v::email()
		);

		$data = array(
		);

		v::validateObj( $data, $rules );
	}
}