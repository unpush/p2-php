<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 -  インデックスページ

require_once 'conf/conf.php';   //基本設定ファイル読込

authorize(); // ユーザ認証

// アクセスログを記録
if ($_conf['login_log_rec']) {
    if (isset($_conf['login_log_rec_num'])) {
        P2Util::recAccessLog($_conf['login_log_file'], $_conf['login_log_rec_num']);
    } else {
        P2Util::recAccessLog($_conf['login_log_file']);
    }
}

$s = $_SERVER['HTTPS'] ? 's' : '';
$me_url = "http{$s}://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
$me_dir_url = dirname($me_url);

if ($_conf['ktai']) {

    //=========================================================
    // 携帯用 インデックス
    //=========================================================
    // url指定があれば、そのままスレッド読みへ飛ばす
    if (!empty($_GET['url']) || !empty($_GET['nama_url'])) {
        header('Location: '.$me_dir_url.'/read.php?'.$_SERVER['QUERY_STRING']);
        exit;
    }
    include (P2_LIBRARY_DIR . '/index_print_k.inc.php');
    index_print_k();

} else {
    //=========================================
    // PC用 変数
    //=========================================
    $htm['menu_page']  = 'menu.php';
    $htm['title_page'] = 'title.php';

    if (!empty($_GET['url']) || !empty($_GET['nama_url'])) {
        list($host, $bbs, $key, $ls) = P2Util::detectThread();
        $htm['read_page']  = $_conf['read_php'] . '?' . $_SERVER['QUERY_STRING'];
        $htm['title_page'] = $_conf['subject_php'] . '?host=' . $host . '&bbs=' . $bbs;
    } else {
        if (!empty($_conf['first_page'])) {
            $htm['read_page'] = $_conf['first_page'];
        } else {
            $htm['read_page'] = 'first_cont.php';
        }
    }

    $sidebar = !empty($_GET['sidebar']);

    $ptitle = 'p2';

    $frame_split['menu'] = 'cols="156,*"';
    $frame_split['content'] = 'rows="40%,60%"';
    $frame_name['read']     = 'read';
    $frame_name['subject']  = 'subject';

    //======================================================
    // PC用 HTMLプリント
    //======================================================
    $frameset = <<<EOFRAMESET
<frameset {$frame_split['content']} frameborder="1" border="2">
    <frame src="{$htm['title_page']}" name="{$frame_name['subject']}" scrolling="auto">
    <frame src="{$htm['read_page']}" name="{$frame_name['read']}" scrolling="auto">
</frameset>
EOFRAMESET;
    if (!$sidebar) {
        $frameset = <<<EOFRAMESET
<frameset {$frame_split['menu']} frameborder="1" border="1">
<frame src="{$htm['menu_page']}" name="menu" scrolling="auto">
{$frameset}
</frameset>
EOFRAMESET;
    }

    P2Util::header_nocache();
    P2Util::header_content_type();
    echo <<<EOF
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
 "http://www.w3.org/TR/html4/frameset.dtd">
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>
    <link href="favicon.ico" type="image/x-icon" rel="shortcut icon">
</head>
{$frameset}
</html>
EOF;

}

?>
