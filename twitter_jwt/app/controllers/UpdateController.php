<?php
use Phalcon\Http\Response;
use Phalcon\Crypt;

class UpdateController extends ControllerBase
{

  public function indexAction()
  {
  }
  public function resistAction()
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
          $phql = 'UPDATE Store\Products\Users SET name = :name:, exp = :exp:, price = :price: WHERE id = :id:';
          $user = array(
            'id'  => $this->request->getPost("id"),
            'name'  => $this->request->getPost("name"),
            'exp'   => $this->request->getPost("exp"),
            'price' => $this->request->getPost("price"),
            'image' => $this->request->getUploadedFiles("image"),
          );
          $status = $this->modelsManager->executeQuery(
            $phql,
            [
              'id'    => $user['id'],
              'name'  => $user['name'],
              'exp'   => $user['exp'],
              'price' => $user['price'],
            ]
          );

          //画像更新(画像はbase64形式)
          $image_file = __DIR__;
          $image_file = str_replace("controllers", "images", $image_file);
          $image_file = $image_file."/".($user['id']).".dat";
          $image_file = file_put_contents($image_file, $user['image']);

          // レスポンスの作成
          $response = new Response();

          // この挿入が成功したか確認する
          if ($status->success() === true) {
            $response->setJsonContent(
              [
                'status' => 'OK'
              ]
            );
          } else {
            // HTTP ステータスの変更
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
    echo "<p><a href='../update'>前の画面へ</a></p>";
  }
}
