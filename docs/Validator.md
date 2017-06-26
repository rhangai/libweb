Validator
================================

Introduction
--------------------------------

Class used to validate data that usually comes from the user

```php
use LibWeb\Validator as v;

$data = array(
   "name" => "User name",
   "mother" => array(
       "name" => "My Mothers Name",
       "birthdate" => "10/02/1960",
   ),
   "children" => array(
       array( "name" => "First child", "age" => 15 ),
       array( "name" => "Second child", "age" => 4 ),
   )
);

v::validate( $data, array( 
    "name"   => v::s(),
	"mother" => array(
	    "name"      => v::s(),
            "birthdate" => v::date( "d/m/Y" ),
	),
	"children" => v::arrayOf(array(
		"name" => v::s(),
		"age"  => v::i(),	
	)),
) );
```
