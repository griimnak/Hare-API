<?php
namespace resources;

class IndexResource {
    // def on_get(self, req, resp):
    function on_get($req) {
        // quote = {}
        $quote = array(
            "api" => "Hare API",
            "author" => "griimnak",
            "info" => "Check out the response headers"
        );

        // resp.media = quote
        $this->headers = array(
            'X-Forwarded-For' => 'tada',
            'X-Custom-Header' => 'custom val'
        );

        $this->response = $quote;
    }
}