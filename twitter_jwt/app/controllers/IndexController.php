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
    $text = "$claims.$header";

    $secret = $crypt->encrypt($text,$key);
    $jwt = "$claims.$header.$secret";

    //各値をセッションに入れる
    $this->session->set("access_oauth",$access_token['oauth_token']);
    $this->session->set("access_secret",$access_token['oauth_token_secret']);
    $this->session->set("jwt",$jwt);

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
    if(!isset($user_info)){
      header('Location: error');
    }

    $jwt=$this->session->get("jwt");
    echo $jwt;

    echo "<p><a href='../get'>全商品の表示</a></p>";
    echo "<p><a href='../resist'>商品の登録</a></p>";
    echo "<p><a href='../search'>商品の検索</a></p>";
    echo "<p><a href='../update'>商品の更新</a></p>";
    echo "<p><a href='../delete'>商品の削除</a></p>";
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
