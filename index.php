<?php
/**
 * rep2 - インデックスページ
 */

define('P2_SESSION_CLOSE_AFTER_AUTHENTICATION', 0);

if (array_key_exists('b', $_GET) && in_array($_GET['b'], array('h2', 'v2', 'v3'))) {
    $_GET['panes'] = $_GET['b'];
    $_GET['b'] = 'pc';
}

require_once './conf/conf.inc.php';

$_login->authorize(); //ユーザ認証

//=============================================================
// 前処理
//=============================================================
// アクセス拒否用の.htaccessをデータディレクトリに作成する
$secret_dirs = array_unique(array(
    $_conf['pref_dir'],
    $_conf['dat_dir'],
    $_conf['idx_dir'],
    $_conf['db_dir'],
    $_conf['admin_dir'],
    $_conf['cache_dir'],
    $_conf['cookie_dir'],
    $_conf['compile_dir'],
    $_conf['session_dir'],
    $_conf['tmp_dir'],
));
foreach ($secret_dirs as $dir) {
    makeDenyHtaccess($dir);
}

//=============================================================

$me_url = P2Util::getMyUrl();
$me_dir_url = dirname($me_url);
$me_url_b = htmlspecialchars(rtrim($me_dir_url, '/') . '/?b=', ENT_QUOTES);

if ($_conf['ktai']) {

    //=========================================================
    // 携帯用 インデックス
    //=========================================================
    // url指定があれば、そのままスレッド読みへ飛ばす
    if (!empty($_GET['url']) || !empty($_GET['nama_url'])) {
        header('Location: '.$me_dir_url . '/read.php?' . $_SERVER['QUERY_STRING']);
        exit;
    }
    if ($_conf['iphone']) {
        include P2_BASE_DIR . '/menu_i.php';
        exit;
    }
    require_once P2_LIB_DIR . '/index_print_k.inc.php';
    index_print_k();

} else {
    //=========================================
    // PC用 変数
    //=========================================
    $title_page = "title.php";

    if (!empty($_GET['url']) || !empty($_GET['nama_url'])) {
        $htm['read_page'] = 'read.php?' . $_SERVER['QUERY_STRING'];
    } else {
        if (!empty($_conf['first_page'])) {
            $htm['read_page'] = $_conf['first_page'];
        } else {
            $htm['read_page'] = 'first_cont.php';
        }
    }

    // デフォルトのペイン分割
    $panes = 'default';
    $direction = 'rows';
    $_SESSION['use_narrow_toolbars'] = false;

    // index.php?panes={v3,v2,h2} or index.php?sidebar=1 でペイン指定
    if (array_key_exists('panes', $_GET) && is_string($_GET['panes'])) {
        switch ($_GET['panes']) {
        case 'v3':
        case 'v2':
            $panes = $_GET['panes'];
            $direction = 'cols';
            $_SESSION['use_narrow_toolbars'] = true;
            break;
        case 'h2':
            $panes = 'h2';
            break;
        }
    } elseif (!empty($_GET['sidebar'])) {
        $panes = 'h2';
    }

    $ptitle = "rep2";
    //======================================================
    // PC用 HTMLプリント
    //======================================================
    //P2Util::header_nocache();
    if ($_conf['doctype']) {
        echo str_replace(array('Transitional', 'loose.dtd'),
                         array('Frameset', 'frameset.dtd'),
                         $_conf['doctype']);
    }
    echo <<<EOHEADER
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}
    <title>{$ptitle}</title>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
</head>\n
EOHEADER;

    if ($panes === 'default' || $panes === 'v3') {
        echo <<<EOMENUFRAME
<frameset id="menuframe" cols="{$_conf['frame_menu_width']},*" border="1">
    <frame src="menu.php" id="menu" name="menu" scrolling="auto" frameborder="1">\n
EOMENUFRAME;
    }

    echo <<<EOMAINFRAME
    <frameset id="mainframe" {$direction}="{$_conf['frame_subject_width']},{$_conf['frame_read_width']}" border="2">
        <frame src="{$title_page}" id="subject" name="subject" scrolling="auto" frameborder="1">
        <frame src="{$htm['read_page']}" id="read" name="read" scrolling="auto" frameborder="1">
    </frameset>\n
EOMAINFRAME;

    if ($panes === 'default' || $panes === 'v3') {
        echo "</frameset>\n";
    }

    echo <<<EONOFRAMES
<noframes>
    <body>
        <h1>{$ptitle}</h1>
        <ul>
            <li>　携帯用URL: <a href="{$me_url_b}k">{$me_url_b}k</a></li>
            <li>iPhone用URL: <a href="{$me_url_b}i">{$me_url_b}i</a></li>
        </ul>
    </body>
</noframes>\n
EONOFRAMES;

    echo '</html>';
}

// {{{ makeDenyHtaccess()

/**
 * ディレクトリに（アクセス拒否のための） .htaccess がなければ、自動で生成する
 */
function makeDenyHtaccess($dir)
{
    $hta = $dir . '/.htaccess';
    if (!file_exists($hta)) {
        if (!is_dir($dir)) {
            FileCtl::mkdirFor($hta);
        }
        $data = 'Order allow,deny'."\n".'Deny from all'."\n";
        FileCtl::file_write_contents($hta, $data);
    }
}

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
