

    rep2

    2ちゃんねる、まちBBS、JBBS@したらばBBS の閲覧スクリプト
    詳細URL http://akid.s17.xrea.com/


■動作環境：サーバサイド

 PHP4.3.8以降。PHP5でも動きます。
 OSは、UNIX、Linux、Windows、Mac OS Xでの動作報告あり。
 
 ※PHPのPEARを利用しています。
 ※PHPは、mbstring が有効である必要があります。
 ※2ちゃんねるの「●ログイン」にはSSL通信を利用するので、PHPのcurl拡張が有効か、システムのcurlにパスが通っていないとdat落ちした過去ログが読めません。 cURLはOpenSSLが有効でコンパイルされている必要がある点に注意してください。
 
■動作環境：クライアントサイド

 各種ブラウザで閲覧。使用OS、ブラウザは特に問わない設計。携帯可。
 CSS、JavaScriptはONにすることを強く推奨。

■動かそう

  1. サーバを立ち上げて、PHPが動くようにする。PEARも忘れずに（下記参照）
  2. rep2ディレクトリをサーバからアクセスできる所（「~/Sites」とか）へ置く。
  3. rep2ディレクトリの中にデータ保存用のディレクトリを作成する。（デフォルトでは "data" ディレクトリ）
  4. データ保存用ディレクトリのパーミッションを「707」（または777）にする。
  5. 必要に応じて、 conf/conf_admin.inc.php などのconfファイルをテキストエディタで開いて設定編集。
  6. ブラウザから、
    http://127.0.0.1/~(ユーザ名)/rep2/index.php
   てな具合にrep2ディレクトリへアクセス。

 ※PHPが確かに動いているかどうかを確かめたい時は？
 http://127.0.0.1/~(ユーザ名)/rep2/phpinfo.php
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

 rep2は PEAR の Net_UserAgent_Mobile, PHP_Compat を利用しています。
 PEAR が、サーバにインストールされていない場合は、
 pearコマンドを使って、自分でサーバにインストールするか、
 rep2のディレクトリに includes ディレクトリを作成し、
 その中にネットからダウンロードしてきたファイルを入れてやってください。

 pear install でサーバにインストールする場合、Net_UserAgent_Mobile は現在betaなので、
 pear install Net_UserAgent_Mobile
 でインストールできない時は、
 pear install Net_UserAgent_Mobile-beta
 とコマンドを打つとよいかも。

 includesディレクトリで利用する場合は、拡張パックさんの p2pear がそのまま使えます。
 http://moonshine.s32.xrea.com/

■設定について

 データ保存ディレクトリとセキュリティ機能の設定は、conf/conf_admin.inc.php をテキストエディタで編集。
 （デフォルトでは、指定されたホスト以外はアクセスできなくなっています）
 ホストチェックの詳細設定は、conf/conf_hostcheck.php をテキストエディタで編集。
 デザイン設定は、conf/conf_style.inc.php をテキストエディタで編集。
 その他のユーザの設定は、ログイン後の「設定管理」＞「ユーザ設定編集」で。

■ログインユーザについて

 最初のログイン時のみ、新規ユーザ登録となります。

 パスワードを忘れたりして、認証ユーザ情報を初期化したい場合は、
 データ保存ディレクトリの p2_auth_user.php を手動で削除してください。

■ライセンス

 X11ライセンスです。

■免責

 rep2のご使用は自己責任でよろしくお願いします。


(c)aki <akid@s17.xrea.com>
