<?php
namespace resources;

class QuoteResource {
    
    // def on_get(self, req, resp):
    function on_get($req) {
        // quote = {}
        $quote = array(
            "quote" => "I've always been more interested in the future than in the past",
            "author" => "Grace Hopper"
        );

        // resp.media = quote
        $this->_resp = $quote;
    }
}