<?php
use Phalcon\Http\Response;
use Phalcon\Crypt;

class ResistController extends ControllerBase
{

  public function indexAction()
  {

  }
  public function insertAction(){
    if(!empty($this->request->getPost("name"))){
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
          $user = array(
            'name'  => $this->request->getPost("name"),
            'exp'   => $this->request->getPost("exp"),
            'price' => $this->request->getPost("price"),
            'image' => $this->request->getUploadedFiles("image"),
          );

          $phql = 'INSERT INTO Store\Products\Users (name, exp, price) VALUES (:name:, :exp:, :price:)';

          $status = $this->modelsManager->executeQuery(
            $phql,
            [
              'name'  => $user['name'],
              'exp'   => $user['exp'],
              'price' => $user['price'],
            ]
          );

          // レスポンスの作成
          $response = new Response();

          //画像保存(画像はbase64形式)
          $image_file = __DIR__;
          $image_file = str_replace("controllers", "images", $image_file);
          $image_file = $image_file."/".($status->getModel()->id).".dat";
          $image_file = file_put_contents($image_file, $user['image']);

          // 挿入が成功したかを確認
          if ($status->success() === true) {
            // HTTPステータスの変更
            $response->setStatusCode(201, 'Created');

            $response->setJsonContent(
              [
                'status' => 'OK',
                'data'   => $user,
              ]
            );
          } else {
            // HTTPステータスの変更
            $response->setStatusCode(409, 'Conflict');

            // クライアントにエラーを送信
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
    echo "<p><a href='../resist'>前の画面へ</a></p>";
  }
}
