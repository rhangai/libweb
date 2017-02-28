<?php

class MockAPI extends LibWeb\API {
	private $output;
	protected function sendOutput( $output ) {
		$this->output = $output;
	}
	public function getOutput() { return $this->output; }


};


class APITest extends PHPUnit\Framework\TestCase
{
	private $api;
	
	public function setUp() {
		$this->api = new MockAPI;
		$this->api->GET( '/predefined/100', function() { return 100; } );
		$this->api->GET( '/predefined/str', function() { return 'str'; } );
		$this->api->GET( '/predefined/null', function() { return null; } );
	}

	public function dispatchTest( $path, $expected ) {
		$this->api->dispatch( $path );
		$output = $this->api->getOutput();
		$this->assertEquals( $expected, $output );
	}
	
    public function testPredefined() {
		$this->dispatchTest( '/predefined/100', array(
			"status" => "success",
			"data"   => 100
		));
		$this->dispatchTest( '/predefined/str', array(
			"status" => "success",
			"data"   => 'str'
		));
		$this->dispatchTest( '/predefined/null', array(
			"status" => "success",
			"data"   => null
		));
    }

	/**
	 * @dataProvider dispatchProvider
	 */
    public function testDispatch( $path, $output ) {
		$this->api->GET( $path, function() use ($output) { return $output; } );
		$this->api->dispatch( $path );

		$this->assertEquals( array(
			"status" => "success",
			"data"   => $output
		), $this->api->getOutput() );
    }
	
	/// Provides data for step
	public function dispatchProvider() {
		$data = array();
		for ( $i = 0; $i < 100; ++$i ) {
			$item   = array( rand(), uniqid() );
			$data[] = array( '/test/'.uniqid('x-'), $item );
		}
        return $data;
    }
	
}