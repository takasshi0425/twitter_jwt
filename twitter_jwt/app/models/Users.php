<?php

namespace Store\Products;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Message;
use Phalcon\Mvc\Model\Validator\Uniqueness;
use Phalcon\Mvc\Model\Validator\InclusionIn;

class Users extends Model
{
  public function validation()
      {

          if ($this->price < 0) {
              $this->appendMessage(
                  new Message("Sorry. You can't resist.")
                  );
          }

          if ($this->validationHasFailed() === true) {
              return false;
          }
      }
}
