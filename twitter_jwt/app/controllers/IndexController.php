<?php
define("Consumer_Key", "7ZkCPLbcqxujtYRrSM07Dgd35"); //Consumer Key (API Key)
define("Consumer_Secret", "5qGEnBqfR64JPg0WAAotBuX955amMUx0vTlA3gm762Zo2DDjnc");//Consumer Secret (API Secret)
define("Callback", "http://localhost/twitter_jwt/index/callback");
require "twitteroauth-master/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;
use Phalcon\Http\Response;
use Phalcon\Crypt;

class IndexController extends ControllerBase
{

  public function indexAction()
  {

  }

  public function loginAction()
  {
    //TwitterOAuthのインスタンスを生成し、Twitterからリクエストトークンを取得する
    $connection = new TwitterOAuth(Consumer_Key, Consumer_Secret);
    $request_token = $connection->oauth("oauth/request_token", array("oauth_callback" => Callback));

    //リクエストトークンはcallback.phpでも利用するのでセッションに保存する
    $this->session->set("oauth_token",$request_token['oauth_token']);
    $this->session->set("oauth_token_secret",$request_token['oauth_token_secret']);

    // Twitterの認証画面へリダイレクト
    $url = $connection->url("oauth/authorize", array("oauth_token" => $request_token['oauth_token']));
    header('Location: ' . $url);
  }

  public function  callbackAction()
  {

    //Twitterからアクセストークンを取得する
    $connection = new TwitterOAuth(Consumer_Key, Consumer_Secret, $this->session->get("oauth_token"), $this->session->get("oauth_token_secret"));
    $access_token = $connection->oauth('oauth/access_token', array('oauth_verifier' => $_GET['oauth_verifier'], 'oauth_token'=> $_GET['oauth_token']));

    //取得したアクセストークンでユーザ情報を取得
    $user_connection = new TwitterOAuth(Consumer_Key, Consumer_Secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);
    $user_info = $user_connection->get('account/verify_credentials');

    //各値をセッションに入れる
    $this->session->set("access_oauth",$access_token['oauth_token']);
    $this->session->set("access_secret",$access_token['oauth_token_secret']);
    if(isset($user_info)){
      header('Location: top');
    }else{
      header('Location: error');
    }

    $this->view->disable();

  }

  public function topAction()
  {

    $user_connection = new TwitterOAuth(Consumer_Key, Consumer_Secret, $this->session->get("access_oauth"), $this->session->get("access_secret"));
    $user_info = $user_connection->get('account/verify_credentials');

    //JWT生成
    $current_time = time();
    $expiry = $current_time + (30 * 24 * 60 * 60); //有効期限として30日後を指定

    $claims = array(
      'iat' => $current_time,
      'exp' => $expiry,
      'user_id' => $user_info->id,
      'foo' => 'bar'
    );
    $header = array(
      'alg' => "RS256",
      'typ' => "JWT"
    );
    $claims = implode(",", $claims);  //配列を文字列化
    $header = implode(",", $header);

    $crypt = new Crypt();

    $base64_key  = "N7z5KNH9CXCQ";
    $claims = $crypt->encryptBase64($claims, $base64_key);
    $header = $crypt->encryptBase64($header, $base64_key);

    $key = "krQkZVTL7J6f";
    $text = $claims.$header;

    $secret = $crypt->encrypt($text,$key);
    $jwt = $claims.$header.$secret;
    echo $jwt;


    //適当にユーザ情報を取得
    $id = $user_info->id;
    $name = $user_info->name;
    $screen_name = $user_info->screen_name;
    $profile_image_url_https = $user_info->profile_image_url_https;
    $text = $user_info->status->text;

//DBへの保存　(検索->判別->挿入or更新)
    $phql = 'SELECT * FROM Twitter\Users WHERE twitter_id LIKE :id: ORDER BY Twitter\Users.twitter_id';

    $users = $this->modelsManager->executeQuery(
      $phql,
      [
        'id' => $id
      ]
    );

    if($users->twitter_id==NULL){ //ここでエラー　Notice: Undefined property: Phalcon\Mvc\Model\Resultset\Simple::$twitter_id
      $phql = 'INSERT INTO Twitter\Users (twitter_id,name, screen_name, text) VALUES (:id:,:name:,:screen_name:,:text:)';

      $status = $this->modelsManager->executeQuery(
        $phql,
        [
          'id'  => $id,
          'name'   => $name,
          'screen_name' => $screen_name,
          'text'  => $text,
        ]
      );

      $image_file = __DIR__;
      $image_file = str_replace("controllers", "images", $image_file);
      $image_file = $image_file."/".($status->getModel()->twitter_id).".dat";
      $image_file = file_put_contents($image_file, $profile_image_url_https);
    }else{
      $phql = 'UPDATE Twitter\Users SET name = :name:, screen_name = :screen_name:, text = :text: WHERE twitter_id = :id:';

        $status = $this->modelsManager->executeQuery(
            $phql,
            [
                'id'   => $id,
                'name' => $name,
                'screen_name'  => $screen_name,
                'text'=> $text,
            ]
            );

        $image_file = __DIR__;
        $image_file = str_replace("controllers", "images", $image_file);
        $image_file = $image_file."/".($id).".dat";
        $image_file = file_put_contents($image_file, $profile_image_url_https);

    }

//DBへの保存ここまで

    //各値をセッションに入れる

    $this->session->set("id","$id");
    $this->session->set("name","$name");
    $this->session->set("screen_name","$screen_name");
    $this->session->set("text","$text");
    $this->session->set("profile_image_url_https","$profile_image_url_https");

    echo "<p>ID：". $this->session->get("id") . "</p>";
    echo "<p>名前：". $this->session->get("name") . "</p>";
    echo "<p>スクリーン名：". $this->session->get("screen_name") . "</p>";
    echo "<p>最新ツイート：" .$this->session->get("text"). "</p>";
    echo "<p><img src=".$this->session->get("profile_image_url_https")."></p>";

    echo "<p><a href='../image'>プロフィール画像のアップデート</a></p>";
    echo "<p><a href='logout'>ログアウト</a></p>";

    $this->view->disable();
  }

  public function logoutAction()
  {

    //セッションクッキーの削除
    if (isset($_COOKIE["PHPSESSID"])) {
      setcookie("PHPSESSID", '', time() - 1800, '/');
    }

    //セッションを破棄する
    $this->session->destroy();

    echo "<p>ログアウトしました。</p>";

    echo "<a href='./'>はじめのページへ</a>";

    $this->view->disable();
  }

  public function errorAction()
  {
    if (isset($_COOKIE["PHPSESSID"])) {
      setcookie("PHPSESSID", '', time() - 1800, '/');
    }

    //セッションを破棄する
    $this->session->destroy();

    echo "<p>ログインできませんでした。</p>";
    echo "<a href='./'>はじめのページへ</a>";

    $this->view->disable();
  }

}
