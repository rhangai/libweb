<?php
use LibWeb\Validator as v;

class ValidatorTest extends PHPUnit\Framework\TestCase
{

	/**
	 *  @dataProvider is_numeric_provider
	 */
	public function test_is_numeric( $expected, $v, $separator = "." ) {
		$this->assertEquals( $expected, v::validate( $v, v::is_numeric( $separator ) ) );
	}
	public function is_numeric_provider() {
		return [
			[ "0", 0 ],
			[ "100", 100 ],
			[ "100", "100" ],
			[ "100.01", 100.01 ],
			[ "100.01", "100.01" ],
			[ "100.01", "100,01", ",." ],
		];
	}
}