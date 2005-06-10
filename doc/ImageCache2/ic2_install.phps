<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

/* ImageCache2 - インストーラっていうか環境チェッカ */

// {{{ p2基本設定読み込み＆認証

require_once 'conf/conf.php';

authorize();

if ($_exconf['imgCache']['*'] == 0) {
    exit('<html><body><p>ImageCache2は無効です。<br>conf/conf_user_ex.phpの設定を変えてください。</p></body></html>');
}

// }}}
// {{{ ライブラリ読み込み＆初期化

$ok = TRUE;

// ライブラリ読み込み
function libNotFound() { die('<p>必要なライブラリがありません。</p>'); }
(require_once 'PEAR.php') or libNotFound();
(require_once 'DB.php') or libNotFound();
(require_once 'DB/DataObject.php') or libNotFound();
(require_once 'HTML/QuickForm.php') or libNotFound();
(require_once 'HTML/QuickForm/Renderer/ObjectFlexy.php') or libNotFound();
(require_once 'HTML/Template/Flexy.php') or libNotFound();
(require_once 'HTML/Template/Flexy/Element.php') or libNotFound();
(require_once 'Validate.php') or libNotFound();
(require_once P2EX_LIBRARY_DIR . '/ic2/findexec.inc.php') or libNotFound();
(require_once P2EX_LIBRARY_DIR . '/ic2/db_images.class.php') or libNotFound();
(require_once P2EX_LIBRARY_DIR . '/ic2/thumbnail.class.php') or libNotFound();
(require_once P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php') or libNotFound();

// 設定ファイル読み込み
$ini = ic2_loadconfig();

// DB_DataObjectの設定
$options = &PEAR::getStaticProperty('DB_DataObject','options');
$options = array('database' => $ini['General']['dsn'], 'quote_identifiers' => TRUE);

// 設定関連のエラーはこれらのクラスのコンストラクタでチェックされる
$thumbnailer = &new ThumbNailer;
$icdb = &new IC2DB_images;
$db =& $icdb->getDatabaseConnection();

// }}}
// {{{ SQL生成

// 連番で主キーとなる列の型
preg_match('/^(\w+)(?:\((\w+)\))?:/', $ini['General']['dsn'], $m);
switch ($m[1]) { // phptype
    case 'mysql': case 'mysqli': $serial = 'INTEGER PRIMARY KEY AUTO_INCREMENT'; break;
    case 'pgsql': $serial = 'SERIAL PRIMARY KEY'; break;
    case 'sqlite': $serial = 'INTEGER PRIMARY KEY'; break;
    default:
        die('MySQL, PostgreSQL, SQLite以外のデータベースには対応していません。');
}

// テーブル名は設定によってはDBの予約語が使われるかもしれないのでDB_xxx::quoteIdentifier()で
// クォートするが、カラム名には予約語を使わないのでquoteIdentifier()は省略する。

$createTableSQL = array();
$createIndexSQL = array();
$format_createIndex = 'CREATE INDEX %s ON %s (%s);';

// メインテーブル
$imgcache_table_quoted = $db->quoteIdentifier($ini['General']['table']);
$createTableSQL['imgcache'] = <<<EOQ
CREATE TABLE $imgcache_table_quoted (
    id     $serial,
    uri    VARCHAR (255),
    host   VARCHAR (255),
    name   VARCHAR (255),
    size   INTEGER NOT NULL,
    md5    CHAR (32) NOT NULL,
    width  SMALLINT NOT NULL,
    height SMALLINT NOT NULL,
    mime   VARCHAR (50) NOT NULL,
    time   INTEGER NOT NULL,
    rank   SMALLINT NOT NULL DEFAULT 0,
    memo   TEXT
);
EOQ;

// メインテーブルのインデックス（URL）
$createIndexSQL['imgcache_uri'] = sprintf($format_createIndex,
    $db->quoteIdentifier('idx_'.$ini['General']['table'].'_uri'),
    $imgcache_table_quoted,
    'uri'
);

// メインテーブルのインデックス（キャッシュした時間のUNIXタイムスタンプ）
$createIndexSQL['imgcache_time'] = sprintf($format_createIndex,
    $db->quoteIdentifier('idx_'.$ini['General']['table'].'_time'),
    $imgcache_table_quoted,
    'time'
);

// メインテーブルのインデックス（ファイルサイズ・MD5チェックサム・MIMEタイプ）
$createIndexSQL['imgcache_unique'] = sprintf($format_createIndex,
    $db->quoteIdentifier('idx_'.$ini['General']['table'].'_unique'),
    $imgcache_table_quoted,
    'size, md5, mime'
);

// 主に画像キャッシュ一覧で使うデータキャッシュ用テーブル
$datacache_table_quoted = $db->quoteIdentifier($ini['Cache']['table']);
$createTableSQL['datacache'] = <<<EOQ
CREATE TABLE $datacache_table_quoted (
    id         CHAR(32) NOT NULL,
    cachegroup VARCHAR (127) NOT NULL,
    cachedata  TEXT,
    userdata   VARCHAR (255),
    expires    INTEGER NOT NULL,
    changed    INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (id, cachegroup)
);
EOQ;

// データキャッシュ用テーブルのインデックス（有効期限）
$createIndexSQL['datacache_expires'] = sprintf($format_createIndex,
    $db->quoteIdentifier('idx_'.$ini['Cache']['table'].'_expires'),
    $datacache_table_quoted,
    'expires'
);

// エラーログ用テーブル
$ic2error_table_quoted = $db->quoteIdentifier($ini['General']['error_table']);
$createTableSQL['ic2_error'] = <<<EOQ
CREATE TABLE $ic2error_table_quoted (
    uri     VARCHAR (255),
    errcode VARCHAR(64) NOT NULL,
    errmsg  TEXT,
    occured INTEGER NOT NULL
);
EOQ;

// エラーログのインデックス（URL）
$createIndexSQL['errorlog_uri'] = sprintf($format_createIndex,
    $db->quoteIdentifier('idx_'.$ini['General']['error_table'].'_uri'),
    $ic2error_table_quoted,
    'uri'
);

// ブラックリスト
$blacklist_table_quoted = $db->quoteIdentifier($ini['General']['blacklist_table']);
$createTableSQL['blacklist'] = <<<EOQ
CREATE TABLE $blacklist_table_quoted (
    id     $serial,
    uri    VARCHAR (255),
    size   INTEGER NOT NULL,
    md5    CHAR (32) NOT NULL,
    type   SMALLINT NOT NULL DEFAULT 0
);
EOQ;

// ブラックリストのインデックス（URL）
$createIndexSQL['blacklist_uri'] = sprintf($format_createIndex,
    $db->quoteIdentifier('idx_'.$ini['General']['blacklist_table'].'_uri'),
    $blacklist_table_quoted,
    'uri'
);

// ブラックリストのインデックス（ファイルサイズ・MD5チェックサム・MIMEタイプ）
$createIndexSQL['blacklist_unique'] = sprintf($format_createIndex,
    $db->quoteIdentifier('idx_'.$ini['General']['blacklist_table'].'_unique'),
    $blacklist_table_quoted,
    'size, md5'
);

// }}}
// {{{ 関数

function ic2_createTable($sql)
{
    global $db, $ok;

    echo "<pre>{$sql}</pre>\n";
    echo "<p><strong>";

    $result = $db->query($sql);

    if (DB::isError($result)) {
        $why = $result->getMessage();
        if (!stristr($why, 'already exists')) {
            $ok = FALSE;
        }
        echo $why;
    } else {
        echo "OK!";
    }

    echo "</strong></p>\n";
}

function ic2_createIndex($sql)
{
    global $db, $ok;

    echo "<pre>{$sql}</pre>\n";
    echo "<p><strong>";

    $result = $db->query($sql);

    if (DB::isError($result)) {
        $why = $result->getMessage();
        echo $why;
        if (!stristr($why, 'already exists') && !stristr($why, 'unknown error')) {
            $ok = FALSE;
        } else {
            echo " (既にインデックス作成済みならOK)";
        }
    } else {
        echo "OK!";
    }

    echo "</strong></p>\n";
}

// }}}
// {{{ 確認＆表示

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
 "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <title>ImageCache2::Install</title>
</head>
<body style="background:white;font-size:small">

<h1>ImageCache2::install</h1>

<hr>

<h2>イメージドライバ</h2>

<p><?php echo $ini['General']['driver']; ?> - OK!</p>

<hr>

<h2>テーブルを作成</h2>

<?php
foreach ($createTableSQL as $sql) {
    ic2_createTable($sql);
}
?>

<hr>

<h2>インデックスを作成</h2>

<?php
foreach ($createIndexSQL as $sql) {
    ic2_createIndex($sql);
}
?>

<hr>

<h2>ディレクトリを作成</h2>

<?php
$dirs = array(
    $ini['General']['compiledir'],
    $ini['Source']['name'],
    $ini['Thumb1']['name'],
    $ini['Thumb2']['name'],
    $ini['Thumb3']['name'],
);

foreach ($dirs as $dir) {
    $path = $ini['General']['cachedir'] . '/' . $dir;
    if (is_dir($path)) {
        echo "<p>ディレクトリ <em>{$path}</em> は作成済";
        if (is_writable($path)) {
            echo "（書き込み権限あり）</p>\n";
        } else {
            echo "（<strong>書き込み権限なし</strong>）</p>\n";
            $ok = FALSE;
        }
    } else {
        if (@mkdir($path)) {
            echo "<p>ディレクトリ <em>{$path}</em> を作成</p>\n";
        } else {
            echo "<p>ディレクトリ <em>{$path}</em> の<strong>作成失敗</strong></p>\n";
            $ok = FALSE;
        }
    }
}
?>

<hr>

<h2><?php echo ($ok ? "準備OK" : "だめぽ"); ?></h2>

<?php if (!$ok) echo '<!-- '; ?>
<p>インストールに成功したらこのファイル(ic2_install.php)は削除してください。</p>
<?php if (!$ok) echo ' -->'; ?>

</body>
</html>
<?php
// }}}
?>
