DB
=====================================

Configuration
------------------------------

This class uses the following variables
  * `PDO.url`: The url to be passed directly through PDO
  * `PDO.user`: Database username
  * `PDO.password`: Database password

Usage
------------------------------

The database class may be used by doing:
```php
\LibWeb\DB::instance()->method();
```

But, you can use the following so you can use it like `DB::method()`
```php
class DB {
  
	public static function __callStatic( $name, $args ) {
		$db = \LibWeb\DB::instance();
		return call_user_func_array( array( $db, $name ), $args );
	}
  
};
```


Select
----------
You can use `fetchAll` or `fetchOne` with the same arguments
```php
// Simple select all rows
$result = DB::fetchAll( "SELECT * FROM user" );

// Prepared statement
$result = DB::fetchAll( "SELECT * FROM user WHERE id=?", array( $id ) );

// Prepared statement (named)
$result = DB::fetchAll( "SELECT * FROM user WHERE id=:id AND age=:age", array( 'id' => $id, 'age' => $age ) );
```

Insert/Update
----------
When inserting/updating you must use `execute`
```php

```
