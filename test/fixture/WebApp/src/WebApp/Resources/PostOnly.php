<?php

namespace WebApp\Resources;

use Artax\Http\StdRequest,
    Artax\Framework\Http\ObservableResponse;


class PostOnly {
    
    private $request;
    private $response;
    
    public function __construct(StdRequest $request, ObservableResponse $response) {
        $this->request = $request;
        $this->response = $response;
    }
    
    public function post() {
        $body = 'PostOnly::post - ';
        $body .= $this->request->getBody();
        
        $this->response->setBody($body);
        $this->response->setStatusCode(200);
        $this->response->setStatusDescription('OK');
        $this->response->send();
        
        return $this->response;
    }
}
