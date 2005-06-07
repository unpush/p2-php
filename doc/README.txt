

    p2

    2ちゃんねる、まちBBS、JBBS@したらばBBS の閲覧スクリプト
    詳細URL http://akid.s17.xrea.com/


■動作環境：サーバサイド

 PHP4.3.8以降。PHP5でも動きます。
 OSは、UNIX、Linux、Windows、Mac OS Xでの動作報告あり。
 
 ※PHPのPEAR（Net_UserAgent_Mobile）を利用しています。
 ※PHPは、mbstring が有効である必要があります。
 ※2ちゃんねるの「●ログイン」にはSSL通信を利用するので、PHPのcurl拡張が有効か、システムのcurlにパスが通っていないとdat落ちした過去ログが読めません。 cURLはOpenSSLが有効でコンパイルされている必要がある点に注意してください。
 
■動作環境：クライアントサイド

 各種ブラウザで閲覧。使用OS、ブラウザは特に問わない設計。携帯可。
 （ただし、いまのところ対応不足からJavaScriptの動かないブラウザもありそう）
 CSS、JavaScriptはONにすることを強く推奨。

■動かそう

  1. サーバを立ち上げて、PHPが動くようにする。
  2. p2ディレクトリをサーバからアクセスできる所（「~/Sites」とか）へ置く。
  3. p2ディレクトリの中にデータ保存用のディレクトリを作成する。（デフォルトでは "data" ディレクトリ）
  4. データ保存用ディレクトリのパーミッションを「707」（または777）にする。
  5. ユーザ設定は conf_user.inc.php、デザイン設定は conf_user_style.inc.php をテキストエディタで開いて編集。
  6. ブラウザから、
    http://127.0.0.1/~(ユーザ名)/p2/index.php
   てな具合にp2ディレクトリへアクセス。

 ※PHPが確かに動いているかどうかを確かめたい時は？
 http://127.0.0.1/~(ユーザ名)/p2/phpifno.php
 てなとこにアクセスしてみて下さい。
 ずらずらーっとPHPの環境情報が表示されたならば、PHPは正常に動作しています。
 （確認ができましたら、phpinfo.php はもう必要ないので削除しても構いません）

 ※Mac OS XでPHPが動かない人（標準そのままでは動かない）は、
 http://homepage1.nifty.com/glass/tom_neko/web/web_cgi_osx.html#php
 を参考にhttpd.confを編集して下さい。
 その後は、「システム環境設定」＞「共有」＞「パーソナルWeb共有」＞「開始」で稼働します。

 ※Mac OS Xでの「data」ディレクトリのパーミッションの簡単な変更方法：
 Finderで「data」フォルダを選択後、「情報を見る」＞「所有権とアクセス権」を選ぶ。
 オーナー、その他のアクセスを「読み／書き」可能に設定。

■PEARのインストール

 p2は PEAR の Net_UserAgent_Mobile を利用しています。
 Net_UserAgent_Mobile が、サーバにインストールされていない場合は、
 pearコマンドを使って、自分でサーバにインストールするか、
 p2のディレクトリに includes ディレクトリを作成し、
 その中にネットからダウンロードしてきたファイルを入れてやってください。

 pear install でサーバにインストールする場合、Net_UserAgent_Mobile は現在betaなので、
 pear install Net_UserAgent_Mobile
 でインストールできない時は、
 pear install Net_UserAgent_Mobile-beta
 とコマンドを打つとよいかも。

 includesディレクトリで利用する場合は、拡張パックさんの p2pear がそのまま使えます。
 http://moonshine.s32.xrea.com/

■ライセンス

 X11ライセンスです。

■免責

 p2のご使用は自己責任でよろしくお願いします。


(c)aki <akid@s17.xrea.com>
