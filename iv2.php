<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

/* ImageCache2 - 画像キャッシュ一覧 */

// {{{ p2基本設定読み込み&認証

define('P2_FORCE_USE_SESSION', 1);
define('P2_SESSION_NO_CLOSE', 1);

require_once 'conf/conf.inc.php';

$_login->authorize();

if (!$_conf['expack.ic2.enabled']) {
    exit('<html><body><p>ImageCache2は無効です。<br>conf/conf_admin_ex.inc.php の設定を変えてください。</p></body></html>');
}
if ($_conf['view_forced_by_query']) {
    if (empty($_conf['ktai'])) {
        output_add_rewrite_var('b', 'pc');
    } else {
        output_add_rewrite_var('b', 'k');
    }
}

// }}}
// {{{ 初期化


// ライブラリ読み込み
require_once 'PEAR.php';
require_once 'DB.php';
require_once 'DB/DataObject.php';
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/Renderer/ObjectFlexy.php';
require_once 'HTML/Template/Flexy.php';
require_once 'HTML/Template/Flexy/Element.php';
require_once P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/database.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/db_images.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/thumbnail.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/quickrules.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/editform.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/managedb.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/getvalidvalue.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/buildimgcell.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/matrix.class.php';


// }}}
// {{{ config


// 設定ファイル読み込み
$ini = ic2_loadconfig();

// DB_DataObjectの設定
$_dbdo_options = &PEAR::getStaticProperty('DB_DataObject','options');
$_dbdo_options = array('database' => $ini['General']['dsn'], 'debug' => FALSE, 'quote_identifiers' => TRUE);

// Exif表示が有効か？
$show_exif = ($ini['Viewer']['exif'] && extension_loaded('exif'));

// フォームのデフォルト値
$_defaults = array(
    'page'  => 1,
    'cols'  => $ini['Viewer']['cols'],
    'rows'  => $ini['Viewer']['rows'],
    'inum'  => $ini['Viewer']['inum'],
    'order' => $ini['Viewer']['order'],
    'sort'  => $ini['Viewer']['sort'],
    'field' => $ini['Viewer']['field'],
    'key'   => '',
    'threshold' => $ini['Viewer']['threshold'],
    'compare' => '>=',
    'mode' => 0,
);

// フォームの固定値
$_constants = array(
    'start'   => '<<',
    'prev'    => '<',
    'next'    => '>',
    'end'     => '>>',
    'jump'    => 'Go',
    'search'  => '検索',
    'cngmode' => '変更',
    'hint'    => '◎◇　◇◎',
);

// 閾値比較方法
$_compare = array(
    '>=' => '&gt;=',
    '='  => '=',
    '<=' => '&lt;=',
);

// 閾値
$_threshold = array(
    '-1' => '-1',
    '0' => '0',
    '1' => '1',
    '2' => '2',
    '3' => '3',
    '4' => '4',
    '5' => '5',
);

// ソート基準
$_order = array(
    'time' => 'キャッシュした日時',
    'uri'  => 'URL',
    'date_uri' => '日付+URL',
    'name' => 'ファイル名',
    'size' => 'ファイルサイズ',
    'width' => '画像の横幅',
    'height' => '画像の高さ',
    'pixels' => '総ピクセル数',
);

// ソート方向
$_sort = array(
    'ASC'  => '昇順',
    'DESC' => '降順',
);

// 検索フィールド
$_field = array(
    'uri'  => 'URL',
    'name' => 'ファイル名',
    'memo' => 'メモ',
);

// モード
$_mode = array(
    '3' => 'ｻﾑﾈｲﾙだけ',
    '0' => '一覧',
    '1' => '一括変更',
    '2' => '個別管理',
);

// 携帯用に変換（フォームをパケット節約の対象外とするため）
if ($_conf['ktai']) {
    foreach ($_order as $_k => $_v) {
        $_order[$_k] = mb_convert_kana($_v, 'ask');
    }
    foreach ($_field as $_k => $_v) {
        $_field[$_k] = mb_convert_kana($_v, 'ask');
    }
}


// }}}
// {{{ prepare (DB & Cache)


// DB_DataObjectを継承したDAO
$icdb = &new IC2DB_Images;
$db = &$icdb->getDatabaseConnection();

// サムネイル作成クラス
$thumb = &new ThumbNailer(1);

if ($ini['Viewer']['cache']) {
    require_once 'Cache.php';
    require_once 'Cache/Function.php';
    // データキャッシュにはCache_Container_db(Cache 1.5.4)をハックしてMySQL以外にも対応させ、
    // コンストラクタがDB_xxx(DB_mysqlなど)のインスタンスを受け取れるようにしたものを使う。
    // （ファイル名・クラス名は同じで、include_pathを調整して
    //   オリジナルのCache/Container/db.phpの代わりにする）
    $cache_options = array(
        'dsn'           => $ini['General']['dsn'],
        'cache_table'   => $ini['Cache']['table'],
        'highwater'     => (int)$ini['Cache']['highwater'],
        'lowwater'      => (int)$ini['Cache']['lowwater'],
        'db' => &$db
    );
    $cache = &new Cache_Function('db', $cache_options, (int)$ini['Cache']['expires']);
    // 有効期限切れキャッシュのガーベッジコレクションなど
    if (isset($_GET['cache_clean'])) {
        $cache_clean = $_GET['cache_clean'];
    } elseif (isset($_POST['cache_clean'])) {
        $cache_clean = $_POST['cache_clean'];
    } else {
        $cache_clean = FALSE;
    }
    switch ($cache_clean) {
        // キャッシュを全削除
        case 'all':
            $sql = sprintf('DELETE FROM %s', $db->quoteIdentifier($ini['Cache']['table']));
            $result = &$db->query($sql);
            if (DB::isError($result)) {
                die($result->getMessage());
            }
            $vacuumdb = TRUE;
            break;
        // 強制的にガーベッジコレクション
        case 'gc':
            $cache->garbageCollection(TRUE);
            $vacuumdb = TRUE;
            break;
        // gc_probability(デフォルトは1)/100の確率でガーベッジコレクション
        default:
            // $cache->gc_probability = 1;
            $cache->garbageCollection();
            $vacuumdb = FALSE;
    }
    // SQLiteならVACUUMを実行（PostgreSQLは普通cronでvacuumdbするのでここではしない）
    if ($vacuumdb && is_a($db, 'DB_sqlite')) {
        $result = &$db->query('VACUUM');
        if (DB::isError($result)) {
            die($result->getMessage());
        }
    }
    $enable_cache = TRUE;
} else {
    $enable_cache = FALSE;
}


// SQLite UDF
if (is_a($db, 'db_sqlite')) {
    $isSQLite = TRUE;
    function iv2_sqlite_unix2date($ts)
    {
        return intval(date('Ymd', $ts));
    }
    $sqlite = &$db->connection;
    sqlite_create_function($sqlite, 'unix2date', 'iv2_sqlite_unix2date', 1);
} else {
    $isSQLite = FALSE;
}


// }}}
// {{{ prepare (Form & Template)


// conf.inc.phpで一括stripslashes()しているけど、HTML_QuickFormでも独自にstripslashes()するので。
// バグの温床となる可能性も否定できない・・・
if (get_magic_quotes_gpc()) {
    $_GET = array_map('addslashes_r', $_GET);
    $_POST = array_map('addslashes_r', $_POST);
    $_REQUEST = array_map('addslashes_r', $_REQUEST);
}

// ページ遷移用フォームを設定
// ページ遷移はGETで行うが、画像情報の更新はPOSTで行うのでどちらでも受け入れるようにする
// （レンダリング前に $qf->updateAttributes(array('method' => 'get')); とする）
$_attribures = array('accept-charset' => 'UTF-8,Shift_JIS');
$_method = ($_SERVER['REQUEST_METHOD'] == 'GET') ? 'get' : 'post';
$qf = &new HTML_QuickForm('go', $_method, $_SERVER['SCRIPT_NAME'], '_self', $_attribures);
$qf->registerRule('numRange', null, 'RuleNumericRange');
$qf->registerRule('inArray', null, 'RuleInArray');
$qf->registerRule('inArrayKeys', null, 'RuleInArrayKeys');
$qf->setDefaults($_defaults);
$qf->setConstants($_constants);
$qfe = array();

// フォーム要素の定義

// ページ移動のためのsubmit要素
$qfe['start'] = &$qf->addElement('button', 'start');
$qfe['prev']  = &$qf->addElement('button', 'prev');
$qfe['next']  = &$qf->addElement('button', 'next');
$qfe['end']   = &$qf->addElement('button', 'end');
$qfe['jump']  = &$qf->addElement('button', 'jump');

// 表示方法などを指定するinput要素
$qfe['page']      = &$qf->addElement('text', 'page', 'ページ番号を指定', array('size' => 3));
$qfe['cols']      = &$qf->addElement('text', 'cols', '横', array('size' => 3, 'maxsize' => 2));
$qfe['rows']      = &$qf->addElement('text', 'rows', '縦', array('size' => 3, 'maxsize' => 2));
$qfe['order']     = &$qf->addElement('select', 'order', '並び順', $_order);
$qfe['sort']      = &$qf->addElement('select', 'sort', '方向', $_sort);
$qfe['field']     = &$qf->addElement('select', 'field', 'フィールド', $_field);
$qfe['key']       = &$qf->addElement('text', 'key', 'キーワード', array('size' => 20));
$qfe['compare']   = &$qf->addElement('select', 'compare', '比較方法', $_compare);
$qfe['threshold'] = &$qf->addElement('select', 'threshold', 'しきい値', $_threshold);

// 文字コード判定のヒントにする隠しinput要素
$qfe['hint'] = &$qf->addElement('hidden', 'hint');

// 検索を実行するsubmit要素
$qfe['search'] = &$qf->addElement('submit', 'search');

// モード変更をするselect要素
$qfe['mode'] = &$qf->addElement('select', 'mode', 'モード', $_mode);

// モード変更を確定するsubmit要素
$qfe['cngmode'] = &$qf->addElement('submit', 'cngmode');

// フォームのルール
$qf->addRule('cols', '1 to 20',  'numRange', array('min' => 1, 'max' => 20),  'client', TRUE);
$qf->addRule('rows', '1 to 100', 'numRange', array('min' => 1, 'max' => 100), 'client', TRUE);
$qf->addRule('order', 'invalid order.', 'inArrayKeys', $_order);
$qf->addRule('sort',  'invalid sort.',  'inArrayKeys', $_sort);
$qf->addRule('field', 'invalid field.', 'inArrayKeys', $_field);
$qf->addRule('threshold', '-1 to 5', 'numRange', array('min' => -1, 'max' => 5));
$qf->addRule('compare', 'invalid compare.', 'inArrayKeys', $_compare);
$qf->addRule('mode', 'invalid mode.', 'inArrayKeys', $_mode);

// Flexy
$_flexy_options = array(
    'locale' => 'ja',
    'charset' => 'cp932',
    'compileDir' => $ini['General']['cachedir'] . '/' . $ini['General']['compiledir'],
    'templateDir' => P2EX_LIBRARY_DIR . '/ic2/templates',
    'numberFormat' => '', // ",0,'.',','" と等価
    'plugins' => array('P2Util' => P2_LIBRARY_DIR . '/p2util.class.php')
);

$flexy = &new HTML_Template_Flexy($_flexy_options);

$flexy->setData('php_self', $_SERVER['SCRIPT_NAME']);
$flexy->setData('rep2expack', $_conf['p2expack']);
if ($_conf['ktai']) {
    $k_color = array();
    $k_color['c_bgcolor'] = isset($_conf['mobile.background_color']) ? $_conf['mobile.background_color'] : '';
    $k_color['c_text']  = isset($_conf['mobile.text_color'])  ? $_conf['mobile.text_color']  : '';
    $k_color['c_link']  = isset($_conf['mobile.link_color'])  ? $_conf['mobile.link_color']  : '';
    $k_color['c_vlink'] = isset($_conf['mobile.vlink_color']) ? $_conf['mobile.vlink_color'] : '';
    $flexy->setData('k_color', $k_color);
    $flexy->setData('top_url', dirname($_SERVER['SCRIPT_NAME']) . '/index.php');
    $flexy->setData('accesskey', $_conf['accesskey']);
} else {
    $flexy->setData('skin', str_replace('&amp;', '&', $skin_en));
}


// }}}
// {{{ validate


// 検証
$qf->validate();
$sv = $qf->getSubmitValues();
$page      = getValidValue('page',   $_defaults['page'], 'intval');
$cols      = getValidValue('cols',   $_defaults['cols'], 'intval');
$rows      = getValidValue('rows',   $_defaults['rows'], 'intval');
$order     = getValidValue('order',  $_defaults['order']);
$sort      = getValidValue('sort',   $_defaults['sort'] );
$field     = getValidValue('field',  $_defaults['field']);
$key       = getValidValue('key',    $_defaults['key']);
$threshold = getValidValue('threshold', $_defaults['threshold'], 'intval');
$compare   = getValidValue('compare',   $_defaults['compare']);
$mode      = getValidValue('mode',      $_defaults['mode'], 'intval');

// 携帯用に調整
if ($_conf['ktai']) {
    $lightbox = false;
    $mode = 1;
    $inum = (int) $ini['Viewer']['inum'];
    $overwritable_params = array('order', 'sort', 'field', 'key', 'threshold', 'compare');

    // 絵文字を読み込む
    require_once 'conf/conf_emoji.php';
    $emj = getEmoji();
    $flexy->setData('e', $emj);

    // フィルタリング用フォームを表示
    if (!empty($_GET['show_iv2_kfilter'])) {
        !defined('P2_NO_SAVE_PACKET') && define('P2_NO_SAVE_PACKET', TRUE);
        $r = &new HTML_QuickForm_Renderer_ObjectFlexy($flexy);
        $qfe['key']->removeAttribute('size');
        $qf->updateAttributes(array('method' => 'get'));
        $qf->accept($r);
        $qfObj = &$r->toObject();
        $flexy->setData('page', $page);
        $flexy->setData('move', $qfObj);
        P2Util::header_nocache();
        P2Util::header_content_type();
        $flexy->compile('iv2if.tpl.html');
        $flexy->output();
        exit;
    }
    // セッション変数を操作
    elseif (!empty($_GET['session_no_close'])) {
        // フィルタをリセット
        if (!empty($_GET['reset_filter'])) {
            unset($_SESSION['iv2i_filter']);
        // フィルタを設定
        } else {
            foreach ($overwritable_params as $ow_key) {
                if (isset($$ow_key)) {
                    $_SESSION['iv2i_filter'][$ow_key] = $$ow_key;
                }
            }
        }
        session_write_close();
    }
    // フィルタリング用変数を更新
    elseif (!empty($_SESSION['iv2i_filter'])) {
        foreach ($overwritable_params as $ow_key) {
            if (isset($_SESSION['iv2i_filter'][$ow_key])) {
                $$ow_key = $_SESSION['iv2i_filter'][$ow_key];
            }
        }
    }
} else {
    //$lightbox = ($mode == 0 || $mode == 3) ? $ini['Viewer']['lightbox'] : false;
    $lightbox = $ini['Viewer']['lightbox'];
}

// }}}
// {{{ query

$removed_files = array();

// 閾値でフィルタリング
if (!($threshold == -1 && $compate == '>=')) {
    $icdb->whereAddQuoted('rank', $compare, $threshold);
}

// キーワード検索をするとき
if ($key !== '') {
    $keys = explode(' ', $icdb->uniform($key, 'SJIS-win'));
    foreach ($keys as $k) {
        $operator = 'LIKE';
        $wildcard = '%';
        if (preg_match('/[%_]/', $k)) {
            // SQLite2はLIKE演算子の右辺でバックスラッシュによるエスケープや
            // ESCAPEでエスケープ文字を指定することができないのでGLOB演算子を使う
            if (strtolower(get_class($db)) == 'db_sqlite') {
                if (preg_match('/[*?]/', $k)) {
                    die('ImageCache2 - Warning:「%または_」と「*または?」が混在するキーワードは使えません。');
                } else {
                    $operator = 'GLOB';
                    $wildcard = '*';
                }
            } else {
                $k = preg_replace('/[%_]/', '\\\\$0', $k);
            }
        }
        $expr = $wildcard . $k . $wildcard;
        $icdb->whereAddQuoted($field, $operator, $expr);
    }
    $qfe['key']->setValue($key);
}

// 重複画像をスキップするとき
// 総数を正しくカウントするためにサブクエリを使う
// サブクエリに対応していないバージョン4.1未満のMySQLでは重複画像のスキップは無効
$dc = 0; // 試験的パラメータ、登録レコード数がこれ以上の画像のみを抽出
$mysql = preg_match('/^mysql:/', $ini['General']['dsn']); // MySQL 4.1.2以降のphptypeは"mysqli"
if ($mysql == 0 && ($ini['Viewer']['unique'] || $dc > 2)) {
    $subq = 'SELECT ' . (($sort == 'ASC') ? 'MIN' : 'MAX') . '(id) FROM ';
    $subq .= $icdb->_db->quoteIdentifier($ini['General']['table']);
    if (isset($keys)) {
        // サブクエリ内でフィルタリングするので親クエリのWHERE句をパクってきてリセット
        $subq .= $icdb->_query['condition'];
        $icdb->whereAdd();
    }
    // md5だけでグループ化しても十分とは思うけど、一応。
    $subq .= ' GROUP BY size, md5, mime';
    if ($dc > 1) {
        $subq .= ' HAVING COUNT(*) >= ' . $dc;
    }
    // echo '<!--', mb_convert_encoding($subq, 'SJIS-win', 'UTF-8'), '-->';
    $icdb->whereAdd("id IN ($subq)");
}

// データベースを更新するとき
if (isset($_POST['edit_submit']) && !empty($_POST['change'])) {

    $target = array_unique(array_map('intval', $_POST['change']));

    switch ($mode) {

    // 一括でパラメータ変更
    case 1:
        // ランクを変更
        $newrank = intoRange($_POST['setrank'], -1, 5);
        manageDB_setRank($target, $newrank);
        // メモを追加
        if (!empty($_POST['addmemo'])) {
            $newmemo = get_magic_quotes_gpc() ? stripslashes($_POST['addmemo']) : $_POST['addmemo'];
            $newmemo = $icdb->uniform($newmemo, 'SJIS-win');
            if ($newmemo !== '') {
                 manageDB_addMemo($target, $newmemo);
            }
        }
        break;

    // 個別にパラメータ変更
    case 2:
        // 更新用のデータをまとめる
        $updated = array();
        $removed = array();
        $to_blacklist = FALSE;
        $no_blacklist = FALSE;

        foreach ($target as $id) {
            if (!empty($_POST['img'][$id]['remove'])) {
                if (!empty($_POST['img'][$id]['black'])) {
                    $to_blacklist = TRUE;
                    $removed[$id] = TRUE;
                } else {
                    $no_blacklist = TRUE;
                    $removed[$id] = FALSE;
                }
            } else {
                $newmemo = get_magic_quotes_gpc() ? stripslashes($_POST['img'][$id]['memo']) : $_POST['img'][$id]['memo'];
                $data = array(
                    'rank' => intval($_POST['img'][$id]['rank']),
                    'memo' => $icdb->uniform($newmemo, 'SJIS-win')
                );
                if (0 < $id && -1 <= $data['rank'] && $data['rank'] <= 5) {
                    $updated[$id] = $data;
                }
            }
        }

        // 情報を更新
        if (count($updated) > 0) {
            manageDB_update($updated);
        }

        // 削除（＆ブラックリスト送り）
        if (count($removed) > 0) {
            foreach ($removed as $id => $to_blacklist) {
                $removed_files = array_merge($removed_files, manageDB_remove(array($id), $to_blacklist));
            }
            if ($to_blacklist) {
                if ($no_blacklist) {
                    $flexy->setData('toBlackListAll', FALSE);
                    $flexy->setData('toBlackListPartial', TRUE);
                } else {
                    $flexy->setData('toBlackListAll', TRUE);
                    $flexy->setData('toBlackListPartial', FALSE);
                }
            } else {
                $flexy->setData('toBlackListAll', FALSE);
                $flexy->setData('toBlackListPartial', FALSE);
            }
        }
        break;

    } // endswitch

// 一括で画像を削除するとき
} elseif ($mode == 1 && isset($_POST['edit_remove']) && !empty($_POST['change'])) {
    $target = array_unique(array_map('intval', $_POST['change']));
    $to_blacklist = !empty($_POST['edit_toblack']);
    $removed_files = array_merge($removed_files, manageDB_remove($target, $to_blacklist));
    $flexy->setData('toBlackList', $to_blacklist);
}


// }}}
// {{{ build


// 総レコード数を数える
//$db->setFetchMode(DB_FETCHMODE_ORDERED);
//$all = (int)$icdb->count('*', TRUE);
//$db->setFetchMode(DB_FETCHMODE_ASSOC);
$sql = sprintf('SELECT COUNT(*) FROM %s %s', $db->quoteIdentifier($ini['General']['table']), $icdb->_query['condition']);
$all = $db->getOne($sql);
if (DB::isError($all)) {
    die($all->getMessage());
}

// マッチするレコードがなかったらエラーを表示、レコードがあれば表示用オブジェクトに値を代入
if ($all == 0) {

    // レコードなし
    $flexy->setData('nomatch', TRUE);
    $flexy->setData('reset', $_SERVER['SCRIPT_NAME']);
    if ($_conf['ktai']) {
        $flexy->setData('kfilter', !empty($_SESSION['iv2i_filter']));
    }
    $qfe['start']->updateAttributes('disabled');
    $qfe['prev']->updateAttributes('disabled');
    $qfe['next']->updateAttributes('disabled');
    $qfe['end']->updateAttributes('disabled');
    $qfe['page']->updateAttributes('disabled');
    $qfe['jump']->updateAttributes('disabled');

} else {

    // レコードあり
    $flexy->setData('nomatch', FALSE);

    // 表示範囲を設定
    $ipp = $_conf['ktai'] ? $inum : $cols * $rows; // images per page
    $last_page = ceil($all / $ipp);

    // ページ遷移用パラメータを準備
    if (isset($sv['search']) || isset($sv['cngmode'])) {
        $page = 1;
    } elseif (isset($sv['page'])) {
        $page = max(1, min((int)$sv['page'], $last_page));
    } else {
        $page = 1;
    }
    $prev_page = max(1, $page - 1);
    $next_page = min($page + 1, $last_page);

    // ページ遷移用リンク（携帯）を生成
    if ($_conf['ktai']) {
        $pg_base = htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES);
        $pg_pos = $page . '/' . $last_page;
        $pg_delim = ' | ';
        $pg_fmt_akey = '<a href="%s?page=%d" %s="%d">%s%s</a>';
        $pg_fmt_link = '<a href="%s?page=%d">%s</a>';
        $pg_fmt_none = '%s %s';
        $_ak = $_conf['accesskey'];
        $pager1 = '';
        $pager2 = '';
        if ($page == 1) {
            //$pager1 .= sprintf($pg_fmt_none, $emj['lt2'], $emj['lt1']) . ' ';
            //$pager2 .= sprintf($pg_fmt_none, $emj['lt2'], $emj['lt1']) . ' ';
        } else {
            $pager1 .= sprintf($pg_fmt_akey, $pg_base,          1, $_ak, 1, $emj[1], $emj['lt2']) . ' ';
            $pager1 .= sprintf($pg_fmt_akey, $pg_base, $prev_page, $_ak, 4, $emj[4], $emj['lt1']) . ' ';
            $pager2 .= sprintf($pg_fmt_link, $pg_base,          1, $emj['lt2']) . ' ';
            $pager2 .= sprintf($pg_fmt_link, $pg_base, $prev_page, $emj['lt1']) . ' ';
        }
        $pager1 .= $pg_pos;
        $pager2 .= $pg_pos;
        if ($page == $last_page) {
            //$pager1 .= ' ' . sprintf($pg_fmt_none, $emj['rt1'], $emj['rt2']);
            //$pager2 .= ' ' . sprintf($pg_fmt_none, $emj['rt1'], $emj['rt2']);
        } else {
            $pager1 .= ' ' . sprintf($pg_fmt_link, $pg_base, $next_page, $emj['rt1']);
            $pager1 .= ' ' . sprintf($pg_fmt_link, $pg_base, $last_page, $emj['rt2']);
            $pager2 .= ' ' . sprintf($pg_fmt_akey, $pg_base, $next_page, $_ak, 6, $emj[6], $emj['rt1']);
            $pager2 .= ' ' . sprintf($pg_fmt_akey, $pg_base, $last_page, $_ak, 9, $emj[9], $emj['rt2']);
        }
        /*$pager1 .= $pg_delim;
        $pager2 .= $pg_delim;
        if (empty($_SESSION['iv2i_filter'])) {
            $pager_search = "<a href=\"{$pg_url}?page={$page}&amp;show_iv2_kfilter=1\">索</a>";
            $pager1 .= $pager_search;
            $pager2 .= $pager_search;
        } else {
            $pager_reset = "<a href=\"{$pg_url}?page=1&amp;session_no_close=1&amp;reset_filter=1\">解</a>";
            $pager1 .= $pager_reset;
            $pager2 .= $pager_reset;
        }*/
        $flexy->setData('pager1', $pager1);
        $flexy->setData('pager2', $pager2);

    // ページ遷移用フォーム（PC）を生成
    } else {
        $mf_hiddens = array(
            'hint' => '◎◇　◇◎', 'mode' => $mode,
            'page' => $page, 'cols' => $cols, 'rows' => $rows,
            'order' => $order, 'sort' => $sort,
            'field' => $field, 'key' => $key,
            'compare' => $compare, 'threshold' => $threshold
        );
        $pager_q = $mf_hiddens;
        mb_convert_variables('UTF-8', 'SJIS-win', $pager_q);

        // ページ番号を更新
        $qfe['page']->setValue($page);
        $qf->addRule('page', "1 to {$last_page}", 'numRange', array('min' => 1, 'max' => $last_page), 'client', TRUE);

        // 一時的にパラメータ区切り文字を & にして現在のページのURLを生成
        $pager_separator = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $flexy->setData('current_page', $_SERVER['SCRIPT_NAME'] . '?' . http_build_query($pager_q));
        ini_set('arg_separator.output', $pager_separator);
        unset($pager_q, $pager_separator);

        // ページ後方移動ボタンの属性を更新
        if ($page == 1) {
            $qfe['start']->updateAttributes('disabled');
            $qfe['prev']->updateAttributes('disabled');
        } else {
            $qfe['start']->updateAttributes(array('onclick' => "pageJump(1)"));
            $qfe['prev']->updateAttributes(array('onclick' => "pageJump({$prev_page})"));
        }

        // ページ前方移動ボタンの属性を更新
        if ($page == $last_page) {
            $qfe['next']->updateAttributes('disabled');
            $qfe['end']->updateAttributes('disabled');
        } else {
            $qfe['next']->updateAttributes(array('onclick' => "pageJump({$next_page})"));
            $qfe['end']->updateAttributes(array('onclick' => "pageJump({$last_page})"));
        }

        // ページ指定移動用ボタンの属性を更新
        if ($last_page == 1) {
            $qfe['jump']->updateAttributes('disabled');
        } else {
            $qfe['jump']->updateAttributes(array('onclick' => "if(validate_go(this.form))pageJump(this.form.page.value)"));
        }
    }

    // 編集モード用フォームを生成
    if ($mode == 1 || $mode == 2) {
        $flexy->setData('editFormHeader', EditForm::header($mf_hiddens, $mode));
        if ($mode == 1) {
            $flexy->setData('editFormCheckAllOn', EditForm::checkAllOn());
            $flexy->setData('editFormCheckAllOff', EditForm::checkAllOff());
            $flexy->setData('editFormCheckAllReverse', EditForm::checkAllReverse());
            $flexy->setData('editFormSelect', EditForm::selectRank($_threshold));
            $flexy->setData('editFormText', EditForm::textMemo());
            $flexy->setData('editFormSubmit', EditForm::submit());
            $flexy->setData('editFormReset', EditForm::reset());
            $flexy->setData('editFormRemove', EditForm::remove());
            $flexy->setData('editFormBlackList', EditForm::toblack());
        } elseif ($mode == 2) {
            $editForm = &new EditForm;
            $flexy->setData('editForm', $editForm);
        }
    }

    // DBから取得する範囲を設定して検索
    $from = ($page - 1) * $ipp;
    if ($order == 'pixels') {
        $orderBy = '(width * height) ' . $sort;
    } elseif ($order == 'date_uri') {
        if ($isSQLite) {
            $time2date = 'unix2date("time")';
        } else {
            // 32400 = 9*60*60 (時差補正)
            $time2date = sprintf('floor((%s + 32400) / 86400)', $db->quoteIdentifier('time'));
        }
        $orderBy .= sprintf('%s %s, %s %s', $time2date, $sort, $db->quoteIdentifier('uri'), $sort);
    } else {
        $orderBy = $db->quoteIdentifier($order) . ' ' . $sort;
    }
    $orderBy .= ' , id ' . $sort;
    $icdb->orderBy($orderBy);
    $icdb->limit($from, $ipp);
    $found = $icdb->find();

    // テーブルのブロックに表示する値を取得&オブジェクトに代入
    $flexy->setData('all',  $all);
    $flexy->setData('cols', $cols);
    $flexy->setData('last', $last_page);
    $flexy->setData('from', $from + 1);
    $flexy->setData('to',   $from + $found);
    $flexy->setData('submit', array());
    $flexy->setData('reset', array());

    if ($_conf['ktai']) {
        $show_exif = FALSE;
        $popup = FALSE;
        $r_type = ($ini['General']['redirect'] == 1) ? 1 : 2;
    } else {
        switch ($mode) {
            case 3:
                $show_exif = FALSE;
            case 2:
                $popup = FALSE;
                break;
            default:
                $popup = TRUE;
        }
        $r_type = 1;
    }
    $items = array();
    if (!empty($_SERVER['REQUEST_URI'])) {
        $k_backto = '&from=' . rawurlencode($_SERVER['REQUEST_URI']);
    } else {
        $k_backto = '';
    }
    while ($icdb->fetch()) {
        // 検索結果を配列にし、レンダリング用の要素を付加
        // 配列どうしなら+演算子で要素を追加できる
        // （キーの重複する値を上書きしたいときはarray_merge()を使う）
        $img = $icdb->toArray();
        mb_convert_variables('SJIS-win', 'UTF-8', $img);
        // ランク・メモは変更されることが多く、一覧用のデータキャッシュに影響を与えないように別に処理する
        $status = array();
        $status['rank'] = $img['rank'];
        $status['rank_f'] = ($img['rank'] == -1) ? 'あぼーん' : $img['rank'];
        $status['memo'] = $img['memo'];
        unset($img['rank'], $img['memo']);

        // 表示用変数を設定
        if ($enable_cache) {
            $add = $cache->call('buildImgCell', $img);
            if ($mode == 1) {
                $chk = EditForm::imgChecker($img); // 比較的軽いのでキャッシュしない
                $add += $chk;
            } elseif ($mode == 2) {
                $mng = $cache->call('EditForm::imgManager', $img, $status);
                $add += $mng;
            }
        } else {
            $add = buildImgCell($img);
            if ($mode == 1) {
                $chk = EditForm::imgChecker($img);
                $add += $chk;
            } elseif ($mode == 2) {
                $mng = EditForm::imgManager($img, $status);
                $add += $mng;
            }
        }
        // オリジナル画像が存在しないレコードを自動で削除
        if ($ini['Viewer']['delete_src_not_exists'] && !file_exists($add['src'])) {
            $add['thumb_k'] = $add['thumb'] = 'img/ic_removed.png';
            $add['t_width'] = $add['t_height'] = 32;
            $to_blacklist = false;
            $removed_files = array_merge($removed_files, manageDB_remove(array($img['id'], $to_blacklist)));
            $flexy->setData('toBlackList', $to_blacklist);
        } else {
            if (!file_exists($add['thumb'])) {
                // レンダリング時に自動でhtmlspecialchars()されるので&amp;にしない
                $add['thumb'] = 'ic2.php?r=' . $r_type . '&t=1';
                if (file_exists($add['src'])) {
                    $add['thumb'] .= '&id=' . $img['id'];
                } else {
                    $add['thumb'] .= '&uri=' . rawurlencode($img['uri']);
                }
            }
            if ($_conf['ktai']) {
                $add['thumb_k'] = 'ic2.php?r=0&t=2';
                if (file_exists($add['src'])) {
                    $add['thumb_k'] .= '&id=' . $img['id'];
                } else {
                    $add['thumb_k'] .= '&uri=' . rawurlencode($img['uri']);
                }
                $add['thumb_k'] .= $k_backto;
            }
        }
        $item = array_merge($img, $add, $status);

        // Exif情報を取得
        if ($show_exif && file_exists($add['src']) && $img['mime'] == 'image/jpeg') {
            $item['exif'] = $enable_cache ? $cache->call('ic2_read_exif', $add['src']) : ic2_read_exif($add['src']);
        } else {
            $item['exif'] = NULL;
        }

        // Lightbox JS用パラメータを設定
        if ($lightbox) {
            $item['lightbox_attr'] = ' rel="lightbox" title="' . htmlspecialchars($item['memo'], ENT_QUOTES) . '"';
        } else {
            $item['lightbox_attr'] = '';
        }

        $items[] = $item;
    }

    $i = count($items); // == $found
    // テーブルの余白を埋めるためにNULLを挿入
    if (!$_conf['ktai'] && $i > $cols && ($j = $i % $cols) > 0) {
        for ($k = 0; $k < $cols - $j; $k++) {
            $items[] = NULL;
            $i++;
        }
    }
    // この時点で $i == $cols * 自然数

    $flexy->setData('items', $items);
    $flexy->setData('popup', $popup);
    $flexy->setData('matrix', new MatrixManager($cols, $rows, $i));
}

$flexy->setData('removedFiles', $removed_files);

// }}}
// {{{ output


// モード別の最終処理
if ($_conf['ktai']) {
    $title = str_replace('ImageCache2', 'IC2', $ini['Viewer']['title']);
    $list_template = 'iv2i.tpl.html';
} else {
    switch ($mode) {
        case 2:
            $title = $ini['Manager']['title'];
            $list_template = 'iv2m.tpl.html';
            break;
        case 1:
            $title = $ini['Viewer']['title'];
            $list_template = 'iv2a.tpl.html';
            break;
        default:
            $title = $ini['Viewer']['title'];
            $list_template = 'iv2.tpl.html';
    }
}

// フォームを最終調整し、テンプレート用オブジェクトに変換
$r = &new HTML_QuickForm_Renderer_ObjectFlexy($flexy);
//$r->setLabelTemplate('_label.tpl.html');
//$r->setHtmlTemplate('_html.tpl.html');
$qf->updateAttributes(array('method' => 'get')); // リクエストをPOSTでも受け入れるため、ここで変更
/*if ($_conf['input_type_search']) {
    $input_type_search_attributes = array(
        'type' => 'search',
        'autosave' => 'rep2.expack.search.imgcache',
        'results' => '10',
        'placeholder' => '',
    );
    $qfe['key']->updateAttributes($input_type_search_attributes);
}*/
$qf->accept($r);
$qfObj = &$r->toObject();

// 変数をAssign
$flexy->setData('title', $title);
$flexy->setData('mode', $mode);
$flexy->setData('js', $qf->getValidationScript());
$flexy->setData('page', $page);
$flexy->setData('move', $qfObj);
if ($lightbox === 'plus') {
    /**
     * Lightbox Plus () を使うときのためのヒント
     * @link    http://serennz.cool.ne.jp/sb/sp/lightbox/index_ja.html
     */
/*
--- lightbox_plus.orig
+++ lightbox_plus.js
@@ -152,7 +152,14 @@
 	_genOpener : function(num)
 	{
 		var self = this;
-		return function() { self._show(num); return false; }
+		return function(evt) {
+			evt = (evt) ? evt : ((window.event) ? window.event : null);
+			if (evt && evt.shiftKey) {
+				return true;
+			}
+			self._show(num);
+			return false;
+		}
 	},
 	_createWrapOn : function(obj,imagePath)
 	{
@@ -415,12 +422,12 @@
 // === main ===
 addEvent(window,"load",function() {
 	var lightbox = new LightBox({
-		loadingimg:'loading.gif',
-		expandimg:'expand.gif',
-		shrinkimg:'shrink.gif',
-		effectimg:'zzoop.gif',
+		loadingimg:'lightbox_plus/loading.gif',
+		expandimg:'lightbox_plus/expand.gif',
+		shrinkimg:'lightbox_plus/shrink.gif',
+		effectimg:'lightbox_plus/zzoop.gif',
 		effectpos:{x:-40,y:-20},
 		effectclass:'effectable',
-		closeimg:'close.gif'
+		closeimg:'lightbox_plus/close.gif'
 	});
 });
*/
    $additional_script_and_style = <<<EOP
<script type="text/javascript" src="lightbox_plus/lightbox_plus.js?{$_conf['p2expack']}"></script>
<link rel="stylesheet" type="text/css" href="lightbox_plus/lightbox.css?{$_conf['p2expack']}">
EOP;
} elseif ($lightbox) {
    /**
     * シフトキーを押しながサムネイルをクリックしたときは
     * 無効になるように Lightbox をオーバーロード
     */
    $additional_script_and_style = <<<EOP
<script type="text/javascript" src="lightbox/lightbox.js?{$_conf['p2expack']}"></script>
<script type="text/javascript">
//<![CDATA[
loadingImage='lightbox/loading.gif';
closeButton='lightbox/close.gif';

addLoadEvent(setWinTitle);

function overloadLightbox()
{
    if (!document.getElementsByTagName){ return; }
    var anchors = document.getElementsByTagName('a');
    // loop through all anchor tags
    for (var i = 0; i < anchors.length; i++){
        var anchor = anchors[i];
        if (anchor.getAttribute('href') && (anchor.getAttribute('rel') == 'lightbox')){
            anchor.onclick = function(evt) {
                evt = (evt) ? evt : ((window.event) ? window.event : null);
                if (evt && evt.shiftKey) {
                    return true;
                }
                showLightbox(this);
                return false;
            }
        }
    }
}

addLoadEvent(overloadLightbox);
//]]>
</script>
<link rel="stylesheet" type="text/css" href="lightbox/lightbox.css?{$_conf['p2expack']}">
EOP;
} elseif (empty($_conf['ktai'])) {
    $additional_script_and_style = <<<EOP
<script type="text/javascript">
    window.onload = setWinTitle;
</script>
EOP;
} else {
    $additional_script_and_style = '';
}
$flexy->setData('lightbox', $additional_script_and_style);

// ページを表示
P2Util::header_nocache();
P2Util::header_content_type();
$flexy->compile($list_template);
if ($list_template == 'iv2i.tpl.html') {
    $mobile = &Net_UserAgent_Mobile::singleton();
    $elements = $flexy->getElements();
    if ($mobile->isDoCoMo()) {
        $elements['page']->setAttributes('istyle="4"');
    } elseif ($mobile->isEZweb()) {
        $elements['page']->setAttributes('format="*N"');
    } elseif ($mobile->isVodafone()) {
        $elements['page']->setAttributes('mode="numeric"');
    }
    $view = FALSE;
    $flexy->outputObject($view, $elements);
} else {
    $flexy->output();
}

// }}}

?>
