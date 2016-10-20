<?php
// 必要なライブラリ
require_once "HTTP/Request2.php";

// 短縮URLのID	
function get_id($url){
  return preg_replace('|http://(.*?)/|m','',$url);
}

// URLから画像を拾ってきて保存する
function get_img($url){
  $id     =get_id($url);
  $client =new HTTP_Request2($url,HTTP_Request2::METHOD_HEAD);
  $res    =$client->send();
  if ($res->getStatus() / 100 != 2) {
    echo $res->getReasonPhrase();
    die();
  }

  $img_url=$res->getBody();
  echo $img_url."\n";
  $thumb_url=getThumbnailHtml($img_url);  

  $img=file_get_contents($thumb_url);
  file_put_contents("pic/$id.jpg",$img);
}

// ツイートのURLをaタグで囲んでリンク貼る
function cnv_url($tweet) {
  if (preg_match("#(^|[\s\"\[<(（　])([\w]+?://[\w]+[^\s\"\]>)）　]*)#", $tweet)) {
    $tweet=preg_replace("#(^|[\s\"\[<(（　])([\w]+?://[\w]+[^\s\"\]>)）　]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $tweet);
  } else {
    if (preg_match("/([\w]+?:\/\/t\.co\/[\w]+)[$|\s]*/", $tweet)) {
      $tweet=preg_replace("/([\w]+?:\/\/t\.co\/[\w]+)[$|\s|\#]*/", "<a href=\"\\1\" target=\"_blank\">\\1</a>", $tweet);
    }
  }
  $tweet=preg_replace("#(^|[\s\"\[<(（　])((www|ftp)\.[^\s\"\]>)）　]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $tweet);  
  $tweet= preg_replace("/@(.*?)/", "<a href=\"http://www.twitter.com/\\1\" target=\"_blank\">@\\1</a>", $tweet);  
  $tweet=preg_replace("/#(.*?)/", "<a href=\"http://search.twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $tweet);  
  return $tweet;
}

// 文字列のトリミング
function e($str) {
  $str=mb_convert_encoding($str,'UTF-8','auto');
  $str=trim($str);
  $str=str_replace(array("\n","\t"),' ',$str);
  $str=htmlspecialchars($str);
  return $str;
}

// ツイートからURLを抜き出す
function get_url($str) {
  $str=str_replace(array("\n","\t"),'',$str);
  preg_match_all('|<a href="(.*?)"|m',$str,$res);

  // デバッグ
  //print_r($res[1]);
  //echo "\n";

  // 配列を返す
  return $res[1];
}

// 画像URLからサムネイルを抽出する関数
function getThumbnailHtml($status_text) {
  $status_text=str_replace(array("\n","\t"),'',$status_text);
  $patterns = array(
      // twitpic
      array('/http:\/\/twitpic[.]com\/(\w+)/m', 'http://twitpic.com/show/thumb/$1'),
      // Mobypicture
      array('/http:\/\/moby[.]to\/(\w+)/m', 'http://moby.to/$1:small'),
      // yFrog
      array('/http:\/\/yfrog[.]com\/(\w+)/m', 'http://yfrog.com/$1.th.jpg'),
      // 携帯百景
      array('/http:\/\/movapic[.]com\/pic\/(\w+)/m', 'http://image.movapic.com/pic/s_$1.jpeg'),
      // はてなフォトライフ
      array('/http:\/\/f[.]hatena[.]ne[.]jp\/(([\w\-])[\w\-]+)\/((\d{8})\d+)/m', 'http://img.f.hatena.ne.jp/images/fotolife/$2/$1/$4/$3_120.jp'),
      // PhotoShare
      array('/http:\/\/(?:www[.])?bcphotoshare[.]com\/photos\/\d+\/(\d+)/m', 'http://images.bcphotoshare.com/storages/$1/thumb180.jpg'),
      // PhotoShare の短縮 URL
      array('/http:\/\/bctiny[.]com\/p(\w+)/me', '\'http://images.bcphotoshare.com/storages/\' . base_convert("$1", 36, 10) . \'/thumb180.jpg'),
      // img.ly
      array('/http:\/\/img[.]ly\/(\w+)/m', 'http://img.ly/show/thumb/$1'),
      // brightkite
      array('/http:\/\/brightkite[.]com\/objects\/((\w{2})(\w{2})\w+)/m', 'http://cdn.brightkite.com/$2/$3/$1-feed.jpg'),
      // Twitgoo
      array('/http:\/\/twitgoo[.]com\/(\w+)/m', 'http://twitgoo.com/$1/mini'),
      // pic.im
      array('/http:\/\/pic[.]im\/(\w+)/m', 'http://pic.im/website/thumbnail/$1'),
      // youtube
      array('/http:\/\/(?:www[.]youtube[.]com\/watch(?:\?|#!)v=|youtu[.]be\/)([\w\-]+)(?:[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]*)/m', 'http://i.ytimg.com/vi/$1/hqdefault.jpg'),
      // imgur
      array('/http:\/\/imgur[.]com\/(\w+)[.]jpg/m', 'http://i.imgur.com/$1l.jpg'),
      // TweetPhoto, Plixi, Lockerz
      array('/http:\/\/tweetphoto[.]com\/\d+|http:\/\/plixi[.]com\/p\/\d+|http:\/\/lockerz[.]com\/s\/\d+/m', 'http://api.plixi.com/api/TPAPI.svc/imagefromurl?size=mobile&url=$0'),
      // Ow.ly
      array('/http:\/\/ow[.]ly\/i\/(\w+)/m', 'http://static.ow.ly/photos/thumb/$1.jpg'),
      // Instagram
      array('/http:\/\/instagr[.]am\/p\/([\w\-]+)\//m', 'http://instagr.am/p/$1/media/?size=t'),
  );

  foreach ($patterns as $pattern) {
    if (preg_match($pattern[0], $status_text, $matches)) {
       $url=$status_text;
       $html=preg_replace($pattern[0], $pattern[1], $url);
       break;
    }
  }

  return $html;
}

// 画像URLの展開？
function get_imgURL($url){
  $client=new HTTP_Request2($url,HTTP_Request2::METHOD_GET);
  $res   =$client->send();
  if ($res->getStatus() / 100 >= 4) {
    echo $res->getStatus() . "\n";
    echo $res->getReasonPhrase() . "\n";
    echo $url . "\n";
    //print_r($res->getHeader());
    //echo "\n";
    return "";
    //die();
  }

  $response=$res->getHeader();
  echo $url . "\n";
  $img_url=$response['location'];
  echo $img_url . "\n";

  // 画像のURLを返す
  return $img_url;
}

// 短縮URL？から画像のURLを取得する
// $status_textはURL
function myGetThumbHtml($status_text) {
  $html       ='';
  $status_text=str_replace(array("\n","\t"),'',$status_text);

  $status_text=get_imgURL($status_text);
  if (preg_match("/http:\/\/bit\.ly/",$status_text)) {
    $status_text=get_imgURL($status_text);
  }
  $status_text=str_replace(array("\n","\t"),'',$status_text);

  // ツイート内の画像のURLのパターン（多次元配列）（照合URL,imgタグ）
  $patterns = array(
      // twipple
      array('/http:\/\/p[.]twipple[.]jp\/(\w+)/', '<img src="http://p.twpl.jp/show/orig/$1" width="150" height="150" />'),
      // twitpic
      array('/http:\/\/twitpic[.]com\/(\w+)/', '<img src="http://twitpic.com/show/thumb/$1" width="150" height="150" />'),
      // Mobypicture
      array('/http:\/\/moby[.]to\/(\w+)/', '<img src="http://mobypicture.com/?$1:small" />'),
      // yFrog
      array('/http:\/\/yfrog[.]com\/(\w+)/', '<img src="http://yfrog.com/$1.th.jpg" />'),
      // 携帯百景
      array('/http:\/\/movapic[.]com\/pic\/(\w+)/', '<img src="http://image.movapic.com/pic/s_$1.jpeg" />'),
      // はてなフォトライフ
      array('/http:\/\/f[.]hatena[.]ne[.]jp\/(([\w\-])[\w\-]+)\/((\d{8})\d+)/', '<img src="http://img.f.hatena.ne.jp/images/fotolife/$2/$1/$4/$3_120.jpg" />'),
      // PhotoShare
      array('/http:\/\/(?:www[.])?bcphotoshare[.]com\/photos\/\d+\/(\d+)/', '<img src="http://images.bcphotoshare.com/storages/$1/thumb180.jpg" width="180" height="180" />'),
      // PhotoShare の短縮 URL
      array('/http:\/\/bctiny[.]com\/p(\w+)/e', '\'<img src="http://images.bcphotoshare.com/storages/\' . base_convert("$1", 36, 10) . \'/thumb180.jpg" width="180" height="180" />\''),
      // img.ly
      array('/http:\/\/img[.]ly\/(\w+)/', '<img src="http://img.ly/show/thumb/$1" width="150" height="150" />'),
      // brightkite
      array('/http:\/\/brightkite[.]com\/objects\/((\w{2})(\w{2})\w+)/', '<img src="http://cdn.brightkite.com/$2/$3/$1-feed.jpg" />'),
      // Twitgoo
      array('/http:\/\/twitgoo[.]com\/(\w+)/', '<img src="http://twitgoo.com/$1/mini" />'),
      // pic.im
      array('/http:\/\/pic[.]im\/(\w+)/', '<img src="http://pic.im/website/thumbnail/$1" />'),
      // youtube
      array('/http:\/\/(?:www[.]youtube[.]com\/watch(?:\?|#!)v=|youtu[.]be\/)([\w\-]+)(?:[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]*)/', '<img src="http://i.ytimg.com/vi/$1/hqdefault.jpg" width="240" height="180" />'),
      // imgur
      array('/http:\/\/imgur[.]com\/(\w+)[.]jpg/', '<img src="http://i.imgur.com/$1l.jpg" />'),
      // TweetPhoto
      array('/http:\/\/tweetphoto[.]com\/\d+/', '<img src="http://TweetPhotoAPI.com/api/TPAPI.svc/imagefromurl?size=medium&url=$0" />'),
      // Ow.ly
      array('/http:\/\/ow[.]ly\/i\/(\w+)/', '<img src="http://static.ow.ly/photos/thumb/$1.jpg" width="100" height="100" />'),
  );

  // 結果の初期化
  $html="";

  // ツイート内の画像のURLのパターンとの照合
  foreach ($patterns as $pattern) {
    if (preg_match($pattern[0],$status_text,$matches)) {
      $url=$matches[0];
      $html=preg_replace($pattern[0],$pattern[1],$url);
      $html = '<a href="' . $url . '" target="_blank">' . $html . '</a>';
      //echo "html=" . $html . "\n";
      break;
    }
  }

  // 結果を返す
  return $html;
}

// DBに登録
function intoDB($id,$user,$text1,$text2,$url,$geo_k,$geo_i,$time,$tstamp,$source) {
  // DB情報読み込み
  require('適当なディレクトリ/env.inc');

  $db=mysql_connect($hostname,$username,$password);
  if (!$db)
    exit('Mysqlに接続できません.');

  mysql_query("SET NAMES utf8",$db);
  if (!mysql_select_db($dbname))
    exit('データベースに接続できません.');

  $query="insert into test_twi_t (tid,user,btweet,tweet,pic,posx,posy,time,tstamp,source) values ('$id','$user','$text1','$text2','$url','$geo_k','$geo_i','$time','$tstamp','$source')";

  $result=mysql_query($query);
  if (!$result)
    exit('クエリの実行が失敗しました:'.$query);
}

// DBにすでに存在するかどうかの検索
function ifTweetExist($tid){
  // DB情報読み込み
  require('適当なディレクトリ/env.inc');

  $db=mysql_connect($hostname,$username,$password);
  if (!$db)
    exit('Mysqlに接続できません.');

  mysql_query("SET NAMES utf8",$db);
  if (!mysql_select_db($dbname))
    exit('データベースに接続できません.');

  $query="select * from test_twi_t where tid=$tid";

  $result=mysql_query($query);
  if (!$result)
    exit('クエリの実行が失敗しました:'.$query);

  return (mysql_num_rows($result));
}

// Tweet IDの最大値を返す
function maxTweetID() {
  // DB情報読み込み
  require('適当なディレクトリ/env.inc');

  $db=mysql_connect($hostname,$username,$password);
  if (!$db)
    exit('Mysqlに接続できません.');

  mysql_query("SET NAMES utf8",$db);
  if (!mysql_select_db($dbname))
    exit('データベースに接続できません.');

  $query="select tid from test_twi_t order by tid desc limit 1";

  $result=mysql_query($query);
  if (!$result)
    exit('クエリの実行が失敗しました:'.$query);

  if (mysql_num_rows($result)==1) {
    $row=mysql_fetch_array($result);
    $max_tid=$row['tid'];
  } else {
    $max_tid=0;
  }

  return ($max_tid);
}

?>