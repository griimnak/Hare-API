<?php
// import falcon
require_once ('../api/hare.php');

// api = falcon.API()
$api = new Hare("inc.config.php");


// api.add_route('/quote', QuoteResource())
$api->add_resource('GET', '{*}', 'NotFoundResource');

$api->add_resource('GET', '/', 'IndexResource');

$api->add_resource('GET','/quote', 'QuoteResource');

$api->add_resource('POST', '/test/{*}', 'TestResource');

//print_r($api->_resources);

$api->prepare_req($_GET['uri']);
$api->dispatch();