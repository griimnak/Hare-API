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
