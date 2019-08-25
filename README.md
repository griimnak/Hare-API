# Hare API
Single file, json api micro-framework, inspired by Falcon.py & Bottle.py


Fast API building, with the performance of php
-------------------------------------------------------
#### Falcon.py:

```python
import falcon

class TestResource:
  def on_get(self, req, resp):
    data = {
      'data': 'This is falcon, one of my favorite Python frameworks.'
    }
    
    resp.media = data
    
api = falcon.API()
api.add_route('/test', TestResource())
```

#### Hare-API:

```php
require_once ('../app/hare.php');

class TestResource {
  function on_get($resp) {
    $data = array(
      'data' => 'This is Hare. Its like falcon but with the speed of php :o'
    );
    
    $this->_resp = $data;
  }
}


$api = new Hare('inc.config.php');
$api->add_resource('GET', '/test', new TestResource());
```

# Getting started

Hare was developed and tested on apache + php 7.3, but it should work on php 5.3+ and nginx etc.

### Steps
- Create entry file in <b>public_html</b> or equivalent. (eg. `index.php` or `api.php`.)

- Back out from <b>public_html</b> or equivalent (`cd ..`) and make a dir for your api/app (`mkdir yourapp`).

- Go into your app/api dir (`cd yourapp`), extract <b>hare.php</b> to your app/api dir root.

- Create a config file (eg `config.php`) in your app/api dir with the following template:

```php
<?php
return [
    'resources_path' => 'resources/',
    'db_host' => '',
    'db_user' => '',
    'db_pass' => '',
    'db_name' => ''
];
```

###### Set `'resources_path'` to a dir relative to <b>hare.php</b>

Now for the easy part


### Entry file setup

(eg. `index.php` or `api.php`)

```php
<?php
require_once ('../path/to/hare.php');

// create api instance
$api = Hare("path/to/config.php");
// (path relative to hare.php)

// add resources!
//      Method GET or POST    Url      Class
$api->add_resource('GET', '/url, 'ResourceClass');
// ('ResourceClass'.php loads from 'resources_path' in config.php)

// Or do it with a raw class and import it your way
$api->add_resource('GET', '/url', new ResourceClass());

// When done, prepare request
$api->prepare_req($_GET['uri']);

// .. Could put some middlewares here evenentually

// And dipsatch
$api->dispatch();
```

###### Clean routing

```php
// will accept any strings like http://localhost/hello/bob
$api->add_resource('GET', '/hello/{str}', 'ResourceClass');

// will accept ints only
$api->add_resource('GET', '/user/id/{int}', 'ResourceClass');

// accepts anything
$api->add_resource('GET', '/test/{*}', 'ResourceClass');
```

### Resources

The Resource Object must be a Class that has atleast a `on_get()` or `on_post` function.

It should also have `namespace resources;` as the first line always so the autoloader can load it.

```php
<?php
namespace resources;

class MyResource {
  // GET request example
  function on_get($req) {
      // make your json objects with arrays that will later on get encoded.
      $quote = array(
          "api" => "Hare API",
          "author" => "griimnak"
      );
      
      // set response content
      $this->_resp = $quote;
      
      // you can also set the status code of the response (default 200)
      $this->_status = 500;
      
      // check out $request args for this resource
      print_r($req);
  }
}
