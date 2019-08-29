<?php
namespace resources;

class TestResource {
    // def on_get(self, req, resp):
    function on_post($req) {
        // quote = {}
        $quote = array(
            "data" => "This is a test resource!"
        );

        // resp.media = quote
        $this->response = $quote;
    }
}