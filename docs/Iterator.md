Iterator
==============

For all examples, consider the following dataset:
```js
[
  { id: 1, fullname: "Foo bar", age: 25, valid: true }, 
  { id: 2, fullname: "Bar Baz", age: 30 },
]
```

- `map( Callable $callback )`

  Calls the callback on every item, returning the new item
  ```php
  // Output: [1, 2]
  ...->map(function( $item ) {
    return $item->id;
  });
  // Output: [{ id: 1, name: "Foo bar"}, { id: 2, name: "Bar baz"}]
  ...->map(function( $item ) {
    return (object) array( 
	  "id" => $item->id,
	  "name" => $item->fullname
    );
  });
  ```
  
- `map( array $fields )`

  Maps using the array as fields
  ```php
  // Output: [{ id: 1, name: "Foo bar"}, { id: 2, name: "Bar baz"}]
  ...->map(array(
     "id" => "id",
	 "name" => "fullname",
  ));
  ```
  
- `map( string $field )`

  Maps using the single field as data
  ```php
  // Output: [1, 2]
  ...->map( "id" );
  ```
  
- `whitelist( array $fields )`

  Only allows the specifics fields to pass
  ```php
  // Output: [{ id: 1, valid: true }, { id: 2 }]
  ...->whitelist( array( "id", "valid" ) );
  ```
  
- `blacklist( array $fields )`

  Blocks the specifics fields
  ```php
  // Output: [{ fullname: "Foo bar", age: 25, valid: true }, { fullname: "Bar Baz", age: 30 }]
  ...->blacklist( array( "id" ) );
  ```
  
