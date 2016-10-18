<?php
// ツイートのチェック yap100crowler.php
// 2016.10.18

// twitter認証
$consumerKey      ='';  // '取得したコンシューマーキー';
$consumerSecret   ='';  // '取得した秘密鍵';
$accessToken      ='';  // 検索結果では不要
$accessTokenSecret='';  // 検索結果では不要

require_once('twitteroauth/twitteroauth.php');  // 公開されているソースがあるので取ってきます
$twObj=new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);

// 関数群の読み込み
require_once('yap100func.php');

// ハッシュタグの設定
$hashtag=urlencode('#横浜珍百景');

// ハッシュタグ出力（デバッグ）
echo $hashtag . "\n";

// データベース中の最大のTweetのID＝収集を開始するTweetのID
$id=maxTweetID();

// 収集の繰り返し
for ($loop=0; $loop<1; $loop++) {
  // API実行データ取得
  $req=$twObj->OAuthRequest('https://api.twitter.com/1.1/search/tweets.json','GET',array('q'=>$hashtag,'count' =>'100','since_id'=>$id,'include_entities' =>'true'));

  // デコード
  $xml='';
  $xml=json_decode($req,true);

  // 取得したツイートIDの最大値
  $max_id_str=$xml['search_metadata']['max_id_str'];

  // 最新の読み込みのIDを保存するためのフラグ
  $flag=TRUE;

  // foreachで呟きの分だけループする
  foreach ($xml['statuses'] as $entry) {
    $id    =$entry['id_str'];
    $time  =e($entry['created_at']);
    $tstamp=date('Y-m-d H:i:s', strtotime($time));  // datetimeに変換
    $user  =e($entry['user']['name']);
    //$username=e($entry['user']['name']);
    $text  =e($entry['text']);
    echo $text;
    $geo_k =e($entry['geo']['coordinates']['0']);
    $geo_i =e($entry['geo']['coordinates']['1']);
    $source=e($entry['source']);
    $source=html_entity_decode($source);  // &amp;を&にする&amp;lt;を&lt;に変換する必要あり
    $source=strip_tags(html_entity_decode($source));
    $text  =cnv_url($text);

    // 初期化
    $url='';

    // ツイッターの画像機能
    if (is_array($entry) && isset($entry['entities']['media']) && is_array($entry['entities']['media'])) {
      foreach ($entry['entities']['media'] as $media) {
        if (is_array($media) && isset($media['media_url'])) {
          $m_url=e($media['media_url']);
          $url .='<a href="' . $m_url . '" target="_blank">' . '<img src="' . $m_url . '" width="100" height="100" /></a>' . "\t";
          //echo "entry=" . $m_url . "\n";
        }
      }
    } else if (is_array($entry) && isset($entry['entities']['urls']) && is_array($entry['entities']['urls'])) {
      foreach ($entry['entities']['urls'] as $media) {
        if (is_array($media) && isset($media['expanded_url'])) {
          $expanded_url=e($media['expanded_url']);
          //echo "entry=" . $m_url . "\n";
          $m_url="";
          if (substr($expanded_url,0,strlen("http://自サーバのアドレス"))==="http://自サーバのアドレス") {
              // Match
          } else if (preg_match("/^http:\/\/search\.twitter/", $expanded_url)==0) {
            $m_url=myGetThumbHtml($expanded_url);
            if ($m_url!="") {
              $url .= $m_url . "\t";
            } else {
              echo "id=" . $id . " user=" . $user . " url=" . $expanded_url . "\n";
            }
          }
        }
      }
    }

    // ツイッターの画像機能では扱っていない画像
    if ($url=="") {
      echo "<br>not image function<br>";
      // ツイート中のaタグのリンク先を取得
      $url_t=get_url($text);  // $url_tは配列
      foreach($url_t as $val) {
        //get_img($val);
        //echo $id . " user=" . $user . " url=" . $val . "\n";
        $m_url="";
        if (substr($val,0,strlen("http://自サーバのアドレス"))==="http://自サーバのアドレス") {
            break;
            // Match
        } else if (substr($val,0,strlen("https://t.co/"))==="https://t.co/") {
            break;
        } else if (preg_match("/^http:\/\/search\.twitter/", $val)==0) {
          $m_url=myGetThumbHtml($val);
          if ($m_url!="") {
            $url .= $m_url . "\t";
          } else {
            echo $id . " user=" . $user . " url=" . $val . "\n";
          }
        }
      }
    }

    if ($url=="") {
      $url="nothing";
    }

    //DBに登録
    if (ifTweetExist($id)==0) {
      intoDB($id,$user,e($entry['text']),$text,$url,$geo_k,$geo_i,$time,$tstamp,$source);
    }
    //ブラウザから表示した時用
    /*
    echo '<pre>';
    echo '------------------------------------------------------------------------<br>';
    print_r($entry);
    echo '------------------------------------------------------------------------';
    echo '</pre>';
    echo '<br><br>';
    */

  }
}

//echo "\n" . "http://t.co/qeFE0GcE" . "\n\n";
//$m_url=myGetThumbHtml("http://t.co/qeFE0GcE");
echo "\n\nmax_id_str: " . $max_id_str . "\n\n";

?>