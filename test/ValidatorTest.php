<?php
use LibWeb\Validator as v;
use LibWeb\api\Request;
use RtLopez\Decimal;

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
			[ -100.01, "-100.01" ],
			[ -123123.12, "-123123,12", "," ],
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
	 *  @dataProvider decimal_provider
	 */
	public function test_decimal( $digits, $precision, $expected, $v, $decimal = null ) {
		$expectedDecimal = Decimal::create( $expected, $precision );
		$valueDecimal    = v::validate( $v, v::decimal( $digits, $precision, $decimal ) );
		$this->assertTrue( $expectedDecimal->eq( $valueDecimal ), "Expected $expectedDecimal. Got $valueDecimal" );
		$this->assertSame( $expected, $valueDecimal->__toString() );
	}
	public function decimal_provider() {
		return [
			[ 20, 2, "0.00", "0" ],
			[ 20, 10, "0.0000000000", "0" ],
			[ 20, 2, "100.00", 100 ],
			[ 20, 10, "100.0000000000", 100 ],
			[ 20, 2, "100.00", "100" ],
			[ 20, 2, "100.01", "100.01" ],
			[ 20, 2, "-100.01", "-100.01" ],
			[ 20, 2, "123123.12", "123123,12", "," ],
			[ 20, 10, "12391823.1230000000", "12391823,123", "," ],
			[ 20, 10, "-12391823.1230000000", "-12391823,123", "," ],
		];
	}
	/**
	 *  @expectedException \LibWeb\ValidatorException
	 *  @dataProvider decimal_fail_provider
	 */
	public function test_decimal_fail( $v, $decimal = null ) {
		v::validate( $v, v::decimal( 10, 2, $decimal ) );
	}
	public function decimal_fail_provider() {
		return [
			[ "xupiqs" ],
			[ "MKAMSD" ],
			[ "1000.2", "," ],
			[ "100000000.00" ],
			[ "-100000000.00" ],
			[ "-1238912839.00" ],
			[ "12391209312.00" ],
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


    
	public function test_request() {
		$get = array(
			"name" => "test",
			"data" => "16/03",
		);
		$post = array();
		$files = array(
			"simple" => array(
				"name"     => array( "simple.txt", "data" ),
				"tmp_name" => array( "/tmp/simple.txt", "maksd" ),
				"type"     => array( "text/plain", "maksd" ),
				"error"    => array( 0, 0 ),
				"size"     => array( 0, 0 ),
			),
		);
		$server = array();
		$req = new Request( null, "", "GET", $get, $post, $files, $server, array() );
		
		$data = $req->params(array(
			"name"    => v::s(),
			"data"    => v::date( "d/m", "Y/m/d" ),
			"simple"  => v::file(),
		));
	}

}