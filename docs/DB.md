DB
==============

Introduction
-------------------


Methods
-------------------

- `fetchOne( $query, $data )`
  Selects a single row from the database.
  ```php
  $row = DB::fetchOne( "SELECT * FROM person WHERE id=?", array( 10 ) );
  $row = DB::fetchOne( "SELECT * FROM person WHERE id=:id AND name=:name", array( "id" => 10, "name" => "John" ) );
  ```
  Returns `NULL` or an `object` with the properties
  
- `fetchAll( $query, $data )`
  Selects all row from the database that matches the query.
  ```php
  $rows = DB::fetchAll( "SELECT * FROM person" );
  $rows = DB::fetchAll( "SELECT * FROM person WHERE name=:name", array( "name" => "John" ) );
  ```
  Returns an `ArrayIterator` with the result set
  
- `insertInto( $table, $data )`
  Insert the data on the given table
  ```php
  $id = DB::insertInto( "person", array(
    "name" => "John",
    "age"  => 15,
  ));
  ```
  Returns the ID of the inserted element, if applicable
  
- `execute( $query, $data = null )`
  Execute a query, normally used for UPDATEs or complex INSERTs
  ```php
  $result = DB::execute("UPDATE person SET name=:name WHERE id=:id", array( "id" => $id, "name" => $name ) );
  ```
  Returns an object containing `id` (the id of the insert) and `count` number of affected rows.

- `transaction( callback $callback )`
  Start transaction
  
  ```php
  DB::transaction ( function () use ($id, $name){
    DB::execute("UPDATE person SET name=:name WHERE id=:id", array( "id" => $id, "name" => $name ) );
  });
  ```
  
