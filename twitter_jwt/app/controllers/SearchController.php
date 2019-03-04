<?php
use Phalcon\Http\Response;
use Phalcon\Crypt;

class SearchController extends ControllerBase
{

  public function indexAction()
  {
  }
  public function listAction()
  {
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
          $phql = 'SELECT * FROM Store\Products\Users WHERE name LIKE :name: ORDER BY name';
          $name = $this->request->getPost("name");

          $users = $this->modelsManager->executeQuery(
            $phql,
            [
              'name' => '%' . $name . '%'
            ]
          );

          $data = [];

          foreach ($users as $user) {
            $image_file = __DIR__;
            $image_file = str_replace("controllers", "images", $image_file);
            $image = file_get_contents($image_file."/".($user->id).".dat");

            if($image == false){
              $image = "No image.";
            }

            $data[] = [
              'id'   => $user->id,
              'name' => $user->name,
              'exp'  => $user->exp,
              'price'=> $user->price,
              'image'=> $image,
            ];
          }
          echo json_encode($data);
        }
      }else{
        echo "認証に失敗";
      }
  }
  echo "<p><a href='index/top'>前の画面へ</a></p>";
}
