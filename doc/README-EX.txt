p2機能拡張パック by (707改め)rsk <moonshine@s32.xrea.com>


●何？

 あきさん作、PHPでつくった2ch Viewer "p2" (http://akid.s17.xrea.com/) を元に
 独自の改良と機能追加をしたものです。
 詳細は http://moonshine.s32.xrea.com/


●免責

 本ソフトウェアの使用により直接および間接的に生じたいかなる損害も補償いたしません。
 使用は自己責任で。


●注意

 設置者本人が悪用するのはもちろん、認証を切るなどして
 第三者に悪用されても仕方ない環境で使うのはやめてください。


●ライセンス

 本家p2と同じく、X11ライセンスです。


●動作環境

 クライアント
  ・PCのWebブラウザはJavaScript 1.3, CSS2に対応したものならフル機能が使えます。
    ただしOperaはJavaScriptに独特のクセがあり、完全対応は難しいっつーかやる気なし。
  ・携帯は・・・よくわからんです。
    DoCoMo 503i以降, J-PHONE/Vodafone パケット対応機,
    au CDMA2000 1x / WIN あたりならたぶん大丈夫。

 サーバ
  ・ApacheなどPHPが動作するhttpdと、それが動作するOS。

 PHP
  ・バージョン:
     4.3.8以降を推奨、5.0.xは非推奨
  ・機能拡張:
     mbstring - 必須、アクティブモナーにはマルチバイト対応の正規表現が必要
         curl - ●ログインに必要/OpenSSL有効でビルドされていること
           gd - イメージキャッシュに必要
                ImageCache2なら外部プログラムのImageMagickも利用可
       socket - 必須
         xslt - RSSリーダでAtomを読むのに必要
         zlib - gzip圧縮されたdatを読むのに必要
     （※その他pcreなどconfigureでdisableにしない限り
         デフォルトでインストールされるものも必須）
  ・その他:
     セーフモードではサブディレクトリ作成ができないなどの制限をうけるため動作しない。
     PHPのBasic認証機能はApacheモジュール(mod_php4)でしか利用できないので注意。
     Windows向けのPHPインストール方法ではCGIとしてPHPを使う方法をよく見るが
     そのときは.htaccessを使うなどしてサーバ側でアクセス制限をかける必要がある。
     セキュリティのため、PHPがCGI(suEXEC)で動作するようになっているサーバでも同様。


●開発環境

 MacOS X 10.3 の Web共有 (Apache 1.3.33) + 独自ビルドの PHP 4.3.10 で開発
 気が向いたときだけDarwinPortsで入れた Apache2 + 独自ビルドの PHP5 でテスト

 自分用に使っている自宅サーバは FreeBSD 5.3 で Apache 1.3.33 と PHP 4.3.10
 （両方ともPortsからインストール）

 MacOS X 10.3 の Safari,Camino,Firefox
 Windows 2000 の IE6, Firefox
 au W21S
 で動作確認。


●余談：Safari

 Safari（というかWebKitを使うクライアント）からフォームのデータを送信すると
 Shift_JISで書かれたページ（またはaccept-charsetがShift_JIS）のフォーム
 半角のバックスラッシュとチルダが全角に、
 EUC-JPで書かれたページ（またはaccept-charsetがEUC-JP）のフォームでは
 半角のバックスラッシュが文字化けします。

 原因はSafariが内部エンコーディング(Unicode)からShift_JIS/EUC-JPに
 変換する際にUnicodeの仕様に厳密に従うことらしいです。
 ホスト側でできる対策はフォームのaccept-charset属性にUTF-8を含めてやることで、
 拡張パックでは日本語を入力する可能性のある箇所すべてでそうしています。
