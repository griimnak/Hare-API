<?php
namespace resources;

use Hare;

class NotFoundResource {
    // def on_get(self, req, resp):
    function on_get($req) {
        // quote = {}
        $quote = array(
            "error" => "The resource you're looking for doesn't exist."
        );

        // resp.media = quote
        $this->response = $quote;
        $this->status = 404;
    }
}