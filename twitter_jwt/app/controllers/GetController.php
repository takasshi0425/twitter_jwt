<?php
use Phalcon\Http\Response;
use Phalcon\Crypt;

class GetController extends ControllerBase
{

  public function indexAction()
  {
    $jwt=$this->session->get("jwt");

    //JWT認証
    $crypt = new Crypt();

    $key = "krQkZVTL7J6f";
    $base64_key  = "N7z5KNH9CXCQ";
    $JWT = explode(".",$jwt);
    $claims = $JWT[0];
    $header = $JWT[1];
    $secret = $JWT[2];
    $token = "$claims.$header";
    $key = "krQkZVTL7J6f";
    $TOKEN = $crypt->decrypt($secret,$key);
    if($token==$TOKEN){
      $claims = $crypt->decryptBase64($claims, $base64_key);
      $CLAIMS = explode(",", $claims);
      $current_time = time();
      $expiry = $CLAIMS[1];
      if(intval($current_time) >= intval($expiry)){
        echo "認証に失敗";
      }else{
        $phql = 'SELECT * FROM Store\Products\Users ORDER BY id';

        $users = $this->modelsManager->executeQuery($phql);

        $data = [];

        foreach ($users as $user) {
          $data[] = [
            'id' => $user->id,
            'name' => $user->name,

          ];
        }

        echo json_encode($data);
      }
    }else{
      echo "認証に失敗";
    }
    echo "<p><a href='index/top'>前の画面へ</a></p>";
  }
}
