<?php
/**
 * ImageCache2::Installer
 */

// {{{ p2基本設定読み込み＆認証

require_once './conf/conf.inc.php';

$_login->authorize();

if ($_conf['expack.ic2.enabled'] == 0) {
    p2die('ImageCache2は無効です。', 'conf/conf_admin_ex.inc.php の設定を変えてください。');
}

// }}}
// {{{ ライブラリ読み込み＆初期化

$ok = true;

// ライブラリ読み込み
require_once 'PEAR.php';
require_once 'DB.php';
require_once 'DB/DataObject.php';
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/Renderer/ObjectFlexy.php';
require_once 'HTML/Template/Flexy.php';
require_once 'HTML/Template/Flexy/Element.php';
require_once 'Validate.php';
require_once P2EX_LIB_DIR . '/ic2/findexec.inc.php';
require_once P2EX_LIB_DIR . '/ic2/DataObject/Images.php';
require_once P2EX_LIB_DIR . '/ic2/Thumbnailer.php';
require_once P2EX_LIB_DIR . '/ic2/loadconfig.inc.php';

// 設定ファイル読み込み
$ini = ic2_loadconfig();

// DB_DataObjectの設定
$options = &PEAR::getStaticProperty('DB_DataObject','options');
$options = array('database' => $ini['General']['dsn'], 'quote_identifiers' => true);

// 設定関連のエラーはこれらのクラスのコンストラクタでチェックされる
$thumbnailer = new IC2_Thumbnailer;
$icdb = new IC2_DataObject_Images;
$db = $icdb->getDatabaseConnection();

// }}}
// {{{ SQL生成

// 連番で主キーとなる列の型など
preg_match('/^(\w+)(?:\((\w+)\))?:/', $ini['General']['dsn'], $m);
switch ($m[1]) { // phptype
case 'mysql':
case 'mysqli':
    $serial = 'INTEGER PRIMARY KEY AUTO_INCREMENT';
    $table_extra_defs = ' TYPE=MyISAM';
    $version = $db->getRow("SHOW VARIABLES LIKE 'version'", array(), DB_FETCHMODE_ORDERED);
    if (!DB::isError($version) && version_compare($version[1], '4.1.0') != -1) {
        $charset = $db->getRow("SHOW VARIABLES LIKE 'character_set_database'", array(), DB_FETCHMODE_ORDERED);
        if (!DB::isError($charset) && $charset[1] == 'latin1') {
            $errmsg = "<p><b>Warning:</b> データベースの文字セットが latin1 に設定されています。</p>";
            $errmsg .= "<p>mysqld の default-character-set が binary, ujis, utf8 等でないと日本語の文字が壊れるので ";
            $errmsg .= "<a href=\"http://www.mysql.gr.jp/frame/modules/bwiki/?FAQ#content_1_40\">日本MySQLユーザ会のFAQ</a>";
            $errmsg .= " を参考に my.cnf の設定を変えてください。</p>";
            die($errmsg);
        }
        $db->query('SET NAMES utf8');
        if (version_compare($version[1], '4.1.2') != -1) {
            $table_extra_defs = ' ENGINE=MyISAM DEFAULT CHARACTER SET utf8';
            //$table_extra_defs = ' ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
            //$table_extra_defs = ' ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_bin';
        }
    }
    break;
case 'pgsql':
    $serial = 'SERIAL PRIMARY KEY';
    $table_extra_defs = '';
    break;
case 'sqlite':
case 'sqlite3':
    $serial = 'INTEGER PRIMARY KEY';
    $table_extra_defs = '';
    break;
default:
    die('MySQL, PostgreSQL, SQLite2, SQLite3以外のデータベースには対応していません。');
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
)$table_extra_defs;
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
)$table_extra_defs;
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
)$table_extra_defs;
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
)$table_extra_defs;
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
            $ok = false;
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
            $ok = false;
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
    $ini['Source']['name'],
    $ini['Thumb1']['name'],
    $ini['Thumb2']['name'],
    $ini['Thumb3']['name'],
);

foreach ($dirs as $dir) {
    $path = $ini['General']['cachedir'] . DIRECTORY_SEPARATOR . $dir;
    if (is_dir($path)) {
        echo "<p>ディレクトリ <em>{$path}</em> は作成済";
        if (is_writable($path)) {
            echo "（書き込み権限あり）</p>\n";
        } else {
            echo "（<strong>書き込み権限なし</strong>）</p>\n";
            $ok = false;
        }
    } else {
        if (@mkdir($path)) {
            echo "<p>ディレクトリ <em>{$path}</em> を作成</p>\n";
        } else {
            echo "<p>ディレクトリ <em>{$path}</em> の<strong>作成失敗</strong></p>\n";
            $ok = false;
        }
    }
}
?>

<hr>

<h2>.htaccessを作成</h2>

<?php
$htaccess_path = $ini['General']['cachedir'] . '/.htaccess';
$htaccess_cont = <<<EOS
Order allow,deny
Deny from all
<FilesMatch "\\.(gif|jpg|png)\$">
    Allow from all
</FilesMatch>\n
EOS;
$cachedir_path_ht = htmlspecialchars(realpath($ini['General']['cachedir']), ENT_QUOTES);
$htaccess_path_ht = htmlspecialchars($htaccess_path, ENT_QUOTES);
$htaccess_cont_ht = htmlspecialchars($htaccess_cont, ENT_QUOTES);

if (FileCtl::file_write_contents($htaccess_path, $htaccess_cont) !== false) {
    echo <<<EOS
<p>ファイル <em>{$htaccess_path_ht}</em> を作成</p>
<div>Apacheの場合、パフォーマンスのため、また、.htaccess自体が無効かもしれないので、上記.htaccesを削除してhttpd.confに以下のような記述をすることをおすすめします。</div>
<pre>&lt;Directory &quot;{$cachedir_path_ht}&quot;&gt;
{$htaccess_cont_ht}&lt;/Directory&gt;</pre>
EOS;
} else {
    echo "<p>ファイル <em>{$htaccess_path_ht}</em> の<strong>作成失敗</strong></p>\n";
    $ok = false;
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

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
