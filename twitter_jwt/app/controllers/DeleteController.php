<?php
use Phalcon\Http\Response;
use Phalcon\Crypt;

class DeleteController extends ControllerBase
{

  public function indexAction()
  {
  }

  public function clearAction()
  {
    if(!empty($this->request->getPost("id"))){
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
          $phql = 'DELETE FROM Store\Products\Users WHERE id = :id:';
          $id = $this->request->getPost("id");

          $status = $this->modelsManager->executeQuery(
            $phql,
            [
              'id' => $id,
            ]
          );

          // レスポンスの作成
          $response = new Response();

          if ($status->success() === true) {
            $response->setJsonContent(
              [
                'status' => 'OK'
              ]
            );
          } else {
            // HTTPステータスの変更
            $response->setStatusCode(409, 'Conflict');

            $errors = [];

            foreach ($status->getMessages() as $message) {
              $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
              [
                'status'   => 'ERROR',
                'messages' => $errors,
              ]
            );
          }

          print_r($response);
        }
      }else{
        echo "認証に失敗";
      }
    }
    echo "<p><a href='../delete'>前の画面へ</a></p>";
  }
}
