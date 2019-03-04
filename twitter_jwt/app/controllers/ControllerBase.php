<?php

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller{
    protected function output($code, $content=array()) {
        //Header
        $code =100;
        $this->response->setContentType('application/json')
        ->setStatusCode($code, null)
        ->sendHeaders();
        //Body
        $this->response->setJsonContent($content)
        ->send();
    }
    public function notFoundAction() {
        $this->output(404);
    }
}
