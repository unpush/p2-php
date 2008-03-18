<?php
/*
    p2 - 特殊機能実行スクリプト（携帯）
*/

include_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/spm_k.inc.php';
require_once P2_LIB_DIR . '/thread.class.php';
require_once P2_LIB_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

//=================================================
// 特殊リクエストを実行
//=================================================
if (isset($_GET['ktool_name']) && isset($_GET['ktool_value'])) {
    $ktv = (int)$_GET['ktool_value'];
    switch ($_GET['ktool_name']) {
        case 'goto':
            $_REQUEST['ls'] = $_GET['ls'] = $ktv . '-' . ($ktv + $_conf['k_rnum_range']);
            include 'read.php';
            exit;
        case 'res_quote':
            $_GET['resnum'] = $ktv;
            $_GET['inyou'] = 1;
            include 'post_form.php';
            exit;
        case 'copy_quote':
            $_GET['inyou'] = 1;
        case 'copy':
            $_GET['copy'] = $ktv;
            include 'read_copy_k.php';
            exit;
        case 'aas_rotate':
            $_GET['rotate'] = 1;
        case 'aas':
            $_GET['resnum'] = $ktv;
            include 'aas.php';
            exit;
        case 'aborn_res':
        case 'aborn_name':
        case 'aborn_mail':
        case 'aborn_id':
        case 'aborn_msg':
        case 'ng_name':
        case 'ng_mail':
        case 'ng_id':
        case 'ng_msg':
        case 'aborn_be':    // +Wiki
        case 'ng_be':       // +Wiki
            $_GET['resnum'] = $ktv;
            $_GET['popup'] = 1;
            $_GET['mode'] = $_GET['ktool_name'];
            include 'info_sp.php';
            exit;
        default:
            p2die('不正なコマンド');
    }
}

//=================================================
// スレの指定
//=================================================
kspDetectThread(); // global $host, $bbs, $key, $ls
$aThread =& new Thread();
// hostを分解してidxファイルのパスを求める
if (!isset($aThread->keyidx)) {
    $aThread->setThreadPathInfo($host, $bbs, $key);
}
$aThread->itaj = P2Util::getItaName($host, $bbs);
if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }
// idxファイルがあれば読み込む
if (is_readable($aThread->keyidx)) {
    $lines = @file($aThread->keyidx);
    $idx_data = explode('<>', rtrim($lines[0]));
} else {
    p2die('指定されたスレッドのidxがありません。');
}
$aThread->getThreadInfoFromIdx();

//=================================================
// 表示用変数を設定
//=================================================
$ptitle_ht = $aThread->ttitle_hd;
$thread_url = "{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}{$_conf['k_at_a']}";
$params = array();
if (!empty($_GET['from_read_new'])) {
    $params['from_read_new'] = '1';
}
$default = (!empty($_GET['spm_default'])) ? intval($_GET['spm_default']) : '';

//=================================================
// 表示
//=================================================
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOHEADER
<html>
<head>
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<title>{$ptitle_ht}</title>
</head>\n
EOHEADER;

echo "<body{$_conf['k_colors']}>";

echo $_info_msg_ht;
$_info_msg_ht = '';

echo "<p><a href=\"{$thread_url}\">{$ptitle_ht}</a></p>";
echo '<hr>';
echo kspform($aThread, $default, $params);
echo '<hr>';
echo '<p>';
if (!empty($_GET['from_read_new'])) {
    echo "<a href=\"{$_conf['read_new_k_php']}?cview=1{$_conf['k_at_a']}\">まとめ読みに戻る</a><br>";
}
echo "<a href=\"{$thread_url}\">ｽﾚに戻る<a/>";
echo '</p>';
echo '</body></html>';
exit;
