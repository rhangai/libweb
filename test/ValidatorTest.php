<?php
use LibWeb\Validator as v;

class ValidatorTest extends PHPUnit\Framework\TestCase
{

	/**
	 *  @dataProvider floatval_provider
	 */
	public function test_floatval( $expected, $v, $decimal = null ) {
		$this->assertEquals( $expected, v::validate( $v, v::f( $decimal ) ) );
		$this->assertEquals( $expected, v::validate( $v, v::floatval( $decimal ) ) );
	}
	public function floatval_provider() {
		return [
			[ 0, "0" ],
			[ 100, 100 ],
			[ 100, "100" ],
			[ 100.01, "100.01" ],
			[ 100.01, "100.01" ],
			[ 123123.12, "123123,12", "," ],
		];
	}
	/**
	 *  @expectedException \LibWeb\ValidatorException
	 *  @dataProvider floatval_fail_provider
	 */
	public function test_floatval_fail( $v, $decimal = null ) {
		v::validate( $v, v::f( $decimal ) );
		v::validate( $v, v::floatval( $decimal ) );
	}
	public function floatval_fail_provider() {
		return [
			[ "xupiqs" ],
			[ "MKAMSD" ],
			[ "1000.2", "," ],
		];
	}
	
	
	/**
	 *  @dataProvider intval_provider
	 */
	public function test_intval( $expected, $v ) {
		$this->assertEquals( $expected, v::validate( $v, v::i() ) );
		$this->assertEquals( $expected, v::validate( $v, v::intval() ) );
	}
	public function intval_provider() {
		return [
			[ 0, "0" ],
			[ 100, 100 ],
			[ 100, "100" ],
			[ 12300, 12300 ],
			[ 12300, "12300" ],
		];
	}
	/**
	 *  @expectedException \LibWeb\ValidatorException
	 *  @dataProvider intval_fail_provider
	 */
	public function test_intval_fail( $v ) {
		v::validate( $v, v::i() );
	}
	public function intval_fail_provider() {
		return [
			[ "xupiqs" ],
			[ "MKAMSD" ],
			[ 100.2 ],
		];
	}
}