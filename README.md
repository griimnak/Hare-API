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

Usage *WIP*
----------------

- In `public_html`, `htdocs` or `wwroot`, etc.

`index.php`:

Import the single framework file from your api dir, <b>it should not be in your public folder. (for security)</b

```php
<?php
require_once ('../myapi/hare.php');
```

Then create and instance of the framework, with a location of your config file relative to `hare.php`

```php
$api = new Hare('inc.config.php');
```

That's basically it for setup, you're ready to add resource routes now.

```php
// This way below imports the class from your resources_path set in your `config.php`
$api->add_resource('METHOD', '/url', 'ResourceClass');

// or just import the raw object your own way
$api->add_resource('METHOD', '/url', new ResourceClass());
```
