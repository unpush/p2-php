;<?php /*
; vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker:
; mi: charset=Shift_JIS

;{{{ -------- 全般 --------
[General]

;キャッシュ保存ディレクトリのパス
cachedir = "./cache"

;コンパイル済テンプレート保存ディレクトリ名
;（cachedirのサブディレクトリ）
compiledir = compile

;DSN (DBに接続するためのデータソース名)
;@link http://jp.pear.php.net/manual/ja/package.database.db.intro-dsn.php
;例1 (SQLite): dsn = "sqlite:///./cache/imgcache.sqlite"
;例2 (PosrgreSQL): dsn = "pgsql://username:password@localhost:5432/database"
;例3 (MySQL): dsn = "mysql://username:password@localhost:3306/database"
;注1: username,password,databaseは実際のものと読み替える。
;注2: MySQL,PosrgreSQLでは予めデータベースを作っておく。
dsn = ""

;DBで使うテーブル名
table = imgcache

;削除済み＆再ダウンロードしない画像リストのテーブル名
blacklist_table = ic2_blacklist

;エラーを記録するテーブル名
error_table = ic2_errors

;エラーを記録する最大の行数
error_log_num = 100

;画像のURLが貼られたスレッドのタイトルを自動で記録する (off:0;on:1)
automemo = 1

;画像を処理するプログラム (GD | ImageMagick | ImageMagick6)
;GDはPHPのイメージ関数を利用、ImageMagick(6)は外部プログラムを利用
;おすすめはImageMagick6
driver = GD

;ImageMagickのパス（convertがある“ディレクトリの”パス）
;httpdの環境変数でパスが通っているなら空のままでよい
;パスを明示的に指定する場合は、スペースがあるとサムネイルが作成できないので注意
magick = ""

;透過画像をサムネイル化する際の背景色 (GDのみ有効、16進6桁で指定)
bgcolor = "#FFFFFF"

;携帯でもサムネイルをインライン表示する (off:0;on:1)
;このときの大きさはPCと同じ
inline = 0


;}}}
;{{{ -------- データキャッシュ --------
[Cache]

;データをキャッシュするためのテーブル名
table = datacache

;キャッシュの有効期限（秒）
;1時間=3600
;1日=86400
;1週間=604800
expires = 3600

;キャッシュするデータの最大量（バイト）
highwater = 2048000

;キャッシュしたデータがhighwaterを超えたとき、この値まで減らす（バイト）
lowwater = 1536000


;}}}
;{{{ -------- 一覧 --------
[Viewer]

;ページタイトル
title = "ImageCache2::Viewer"

;表示用に調整した画像情報をキャッシュ (off:0;on:1)
;キャッシュの有効期限などは[Cache]の項で設定
cache = 0

;重複画像を最初にヒットする1枚だけ表示 (on:0;off:1)
;サブクエリを使うためバージョン4.1未満のMySQLでは無効
unique = 0

;Exif情報を表示 (off:0;on:1)
exif = 0

;--以下の設定ははデフォルト値で、ツールバーで変更できる--

;1ページ当たりの列数
cols = 8

;1ページ当たりの行数
rows = 5

;しきい値 (-1 ~ 5)
threshold = 0

;並び替え基準 (time | uri | name | size)
order = time

;並び替え方向 (ASC | DESC)
sort = DESC

;検索フィールド (uri | name | memo)
field = memo


;}}}
;{{{ -------- 管理 --------
[Manager]

;ページタイトル
title = "ImageCache2::Manager"

;メモ記入欄の1行当たりの半角文字数
cols = 40

;メモ記入欄の行数
rows = 5


;}}}
;{{{ -------- ダウンロード --------
[Getter]

;ページタイトル
title = "ImageCache2::Getter"

;エラーログにある画像はダウンロードを試みない (no:0;yes:1)
checkerror = 1

;デフォルトでURL+.htmlの偽リファラを送る (no:0;yes:1)
sendreferer = 0

;sendreferer = 0 のとき、例外的にリファラを送るホスト（カンマ区切り）
refhosts = ""

;sendreferer = 1 のとき、例外的にリファラを送らないホスト（カンマ区切り）
norefhosts = ""

;強制あぼーんのホスト（カンマ区切り）
reject = "rotten.com,shinrei.net";

;ウィルススキャンをする (no:0;clamscan:1;clamdscan:2)
;（Clam AntiVirusを利用）
;ImageCache2や手動スキャンにしかClamAVを使わないなら1でclamscanの方が無難と思われる
virusscan = 0

;ClamAVのパス（clam(d)scanがある“ディレクトリの”パス）
;httpdの環境変数でパスが通っているなら空のままでよい
;パスを明示的に指定する場合は、スペースがあるとウィルススキャンできないので注意
clamav = ""


;}}}
;{{{ -------- プロキシ --------
[Proxy]

;画像のダウンロードにプロキシを使う (no:0;yes:1)
enabled = 0

;ホスト
host = ""

;ポート
port = ""

;ユーザ名
user = ""

;パスワード
pass = ""


;}}}
;{{{ -------- ソース --------
[Source]

;保存用サブディレクトリ名
name = src

;キャッシュする最大データサイズ（これを越えると禁止リスト行き、0は無制限）
maxsize = 10000000

;キャッシュする最大の幅（上に同じく）
maxwidth = 4000

;キャッシュする最大の高さ（〃）
maxheight = 4000


;}}}
;{{{ -------- サムネイル --------
[Thumb1]

;設定名（＝保存用サブディレクトリ名）
name = 6464

;サムネイルの最大幅（正の整数）
width = 64

;サムネイルの最大高さ（正の整数）
height = 64

;サムネイルのJPEG品質（正の整数、1~100以外にするとPNG）
quality = 80


;}}}
;{{{ -------- 携帯フルスクリーン --------
[Thumb2]

;設定名
name = qvga_v

;サムネイルの最大幅
width = 240

;サムネイルの最大高さ
height = 320

;サムネイルのJPEG品質
quality = 80


;}}}
;{{{ -------- 中間イメージ --------
[Thumb3]

;設定名
name = vga

;サムネイルの最大幅
width = 640

;サムネイルの最大高さ
height = 480

;サムネイルのJPEG品質
quality = 80


;}}}
;*/ ?>
