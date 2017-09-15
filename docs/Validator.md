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

Rules
-------------------------------

  - `s` or `strval`: Convert to string
  - `i` or `intval`: Convert to int
  - `f` or `floatval` `($decimal = '.', $thousands = ',')`: Convert to float
  - `b` or `boolean`: Convert to bool
  - `set( $ary )`: Validate if the value is in the given set
     ```php
     v::set(["MALE", "FEMALE"]);
     ```
  
  - `map( $ary )`: Map keys to values
     ```php
     v::map([
       "M" => "Male", 
       "F" => "Female",
     ]);
     ```
  - `call( $fn )`: Calls the $fn on the value
     ```php
     // Validate the string 'Bona'
     v::call(function( $value ) { return $value === 'Bona'; });
     v::call('trim');          // calls trim( $value );
     v::call('substr', 0, 20); // calls substr( $value, 0, 20 );
     ```
  - `date($format, $out = null)`: Expects a date on the given format
     ```php
     v::date( "d/m/Y" ); // Converts 20/03/2017 to a DateTime-like object
     ```
  - `arrayOf( $rules )`: Expects an array following the rules
  
  - `len( $min, $max = null )`
  - `minlen( $min )`
  - `str_replace( string $search, string $replace )`
  - `preg_replace( string $search, string|function $replace )`
  - `whitelist( array $chars )`
  - `blacklist( array $chars )`
  
  
Flags
-------------------------------------------

  - `?` Optional flag:   ( The field will only be validated if non-empty )
  - `??` Skippable flag: ( The field will only be validated if non-empty and will NOT be on the output )
  
    ```php
	$data = array(
		"name"  => "John Foo",
		"age"   => 32,
		"job"   => "Developer",
		"email" => "johnfoo@bar.com",
	);
	$validated = v::validate( $data, array(
		"name"      => v::s(),
		"age"       => v::i(),
		"job?"      => v::s(),
		"company?"  => v::s(),
		"email??"   => v::s(),
		"address??" => v::s(),
	) );
	
    /*
		stdClass(
			name    => "John Foo"
			age     => 32
			job     => "Developer"
			company => null
			email   => "johnfoo@Bar.com"
		)
		// No address field as it is skipabble, company was injected because only optional
	*/
	```
