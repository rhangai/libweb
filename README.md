LibWeb
===============================

  - [API](docs/API.md)
  - [DB](docs/DB.md)
  - [Validator](docs/Validator.md)
  
  
Example
-------------------------

A simple example using the API
```php
use LibWeb\Validator as v;
use LibWeb\DB;

class TestAPI {

	public function GET_multiply( $req ) {
		$data = $req->params(array(
			"a" => v::f(),
			"b" => v::f(),
		));
		return $data->a * $data->b;
	}
	
	public function POST_create( $req ) {
		$data = $req->params(array(
			"name" => v::s(),
			"age"  => v::i(),
		));
		$id = DB::insertInto( "person", $data ); // "INSERT INTO person (name, age) VALUES (:name, :age)"
		return $id;
	}
	
	public function POST_update( $req ) {
		$data = $req->params(array(
			"id"   => v::i(),
			"age"  => v::i(),
		));
		$query = "UPDATE person SET age=:age WHERE id=:id"
		$result = DB::execute( $query, $data );
	}
	
};
```
