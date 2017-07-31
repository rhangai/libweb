API
================================

Introduction
--------------------------------

The API class is used to process HTTP requests using a simple folder structure
```txt
dir
|- test
|   |- Data.php
|   |- Something.php
|- user
|   |- Info.php
|   |- Shop.php
|- Customer.php
```

Using the current PHP class in `dir/test/Data.php`
```php
<?php
namespace MyProject\api\test;

class DataAPI {
	public function GET_one( $req, $res ) { /* ... */ }
	public function POST_two( $req, $res ) { /* ... */ }
	public function GET_myTest( $req, $res ) { /* ... */ }
};
```

Will generate the following APIs
```txt
GET  /test/data/one
POST /test/data/two
GET  /test/data/my-text
```

Getting started
--------------------------------

Setup a single entry point on your foler

```php
<?php
use LibWeb\API;

$api = new API( "MyProject\\Base\\Namespace", "/path/to/root/dir" );
$api->dispatch();
```

Will load files recursevily on `/path/to/root/dir` using `MyProject\\Base\\Namespace`.

e.g. If a request is made on /data/user/list the script will try to load the file `/path/to/root/dir/data/User.php` and then
```php
$obj = new MyProject\Base\Namespace\data\User;
$obj->GET_list();
```

Request
-----------------------------

- `param( $name, $default = null )`
  
  Get the parameter of the request. (`$_GET` or `$_POST`)
  
- `file( $name = null, $multiple = false )`
  
  Get the file identified by `$name` as a file object. <br />
  If `$name` is null, return the first file found<br />
  If `$multiple` is true, will return an array of files<br />
  Obs: `file( true )` is the same as `file( null, true )`
  
