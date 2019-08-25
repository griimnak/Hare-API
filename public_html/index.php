<?php
// import falcon
require_once ('../api/hare.php');

class IndexResource {
    // def on_get(self, req, resp):
    function on_get($req) {
        // quote = {}
        $quote = array(
            "api" => "Hare API",
            "author" => "griimnak"
        );

        // resp.media = quote
        $this->_resp = $quote;
    }
}

class TestResource {
    // def on_get(self, req, resp):
    function on_post($req) {
        // quote = {}
        $test = array(
            "data" => "This is a test resource!"
        );

        // resp.media = quote
        $this->_resp = $test;
    }
}

class NotFoundResource {
    // def on_get(self, req, resp):
    function on_get($req) {
        // quote = {}
        $quote = array(
            "error" => "The resource you're looking for doesn't exist."
        );

        // resp.media = quote
        $this->_resp = $quote;
        $this->_status = 404;
    }
}

// api = falcon.API()
$api = new Hare("inc.config.php");

// api.add_route('/quote', QuoteResource())
$api->add_resource('GET', '{*}', new NotFoundResource());

$api->add_resource('GET', '/', new IndexResource());

$api->add_resource('GET','/quote', 'QuoteResource');

$api->add_resource('POST', '/test/{*}', new TestResource());

//print_r($api->_resources);

$api->prepare_req($_GET['uri']);
$api->dispatch();