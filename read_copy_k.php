<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - 携帯版レスコピー

require_once 'conf/conf.inc.php';
require_once (P2_LIBRARY_DIR . '/thread.class.php');
require_once (P2_LIBRARY_DIR . '/threadread.class.php');

$_login->authorize(); // ユーザ認証

$name_txt = '';
$mail_txt = '';
$date_txt = '';
$id_txt = '';
$msg_txt = '';
$url_k_ht = '';
$id_ht = '';
$back_link = '';
$post_link = '';
$moto_link = '';
$form_id = P2_REQUEST_ID;

//=====================================================
// スレッド情報
//=====================================================
$host = $_GET['host'];
$bbs  = $_GET['bbs'];
$key  = $_GET['key'];
$resid = $_GET['copy'];
$quote = !empty($_GET['inyou']);

if (isset($_SERVER['HTTP_REFERER'])) {
    $back_link = '<a href="' . htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES) . '" title="戻る">' . 戻る . '</a>';
}

//=================================================
// レス読み込み
//=================================================
$aThread = &new ThreadRead;
$aThread->setThreadPathInfo($host, $bbs, $key);
if (file_exists($aThread->keydat)) {
    // スレッド情報
    $aThread->readDat($aThread->keydat);
    $one = $aThread->explodeDatLine($aThread->datlines[0]);
    $ttitle = trim($one[4]);
    $ttitle_en = rawurlencode(base64_encode($ttitle));
    $ttitle_ht = htmlspecialchars($ttitle, ENT_QUOTES);
    $url_txt = $aThread->getMotoThread(true);
    $url_k_txt = $aThread->getMotoThread();
    if ($quote) {
        $url_txt .= $resid;
        $url_k_txt .= $resid;
    }
    if ($url_txt != $url_k_txt) {
        $url_k_ht = "<input type=\"text\" name=\"url_k_txt\" value=\"{$url_k_txt}\"><br>";
    }
    // 投稿フォームへのリンク
    $post_url = "post_form.php?host={$host}&amp;bbs={$bbs}&amp;key={$key}";
    $post_url .= "&amp;rc={$aThread->rescount}&amp;ttitle_en={$ttitle_en}&amp;k=1";
    $post_link = "<a href=\"{$post_url}\">ﾚｽ</a>";
    // 元スレへのリンク
    $moto_link = '<a href="' . P2Util::throughIme($url_k_txt) . '">元ｽﾚ</a>';
    // 指定番号のレスをパース
    $p = $resid - 1;
    if (isset($aThread->datlines[$p])) {
        $resar = $aThread->explodeDatLine($aThread->datlines[$p]);
        $name_txt = trim(strip_tags($resar[0]));
        $mail_txt = trim(strip_tags($resar[1]));
        if (strstr($resar[2], 'ID:')) {
            $date_txt = preg_replace('/ ?ID: ?([0-9A-Za-z\/.+?]+)([.,]|†)?.*$/', '', $resar[2]);
            $id_txt = preg_replace('/^.*ID: ?([0-9A-Za-z\/.+?]+)([.,]|†)?.*$/', 'ID:$1', $resar[2]);
            $id_ht = "<input type=\"text\" name=\"id_txt\" value=\"{$id_txt}\"><br>";
        } else {
            $date_txt = $resar[2];
        }
        $msg_txt = trim(strip_tags($resar[3], '<br>'));
        if ($quote) {
            $msg_txt = "&gt;&gt;{$resid}\r\n&gt; " . preg_replace('/ *<br[^>]*> */i', "\n&gt; ", $msg_txt);
        } else {
            $msg_txt = preg_replace('/ *<br[^>]*> */i', "\n", $msg_txt);
        }
    } else {
        $_info_msg_ht .= '<p>p2 error: ﾚｽ番号の指定が変です｡</p>';
    }
} else {
    $_info_msg_ht .= '<p>p2 error: ｽﾚｯﾄﾞの指定が変です。</p>';
}

//=====================================================
// コピー用フォームを表示
//=====================================================
$action_ht = htmlspecialchars($_SERVER['PHP_SELF'].'?host='.$_GET['host'].'&bbs='.$_GET['bbs'].'&key='.$_GET['key'].'&copy='.$_GET['copy'], ENT_QUOTES);

P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOF
<html>
<head>
<title>{$ttitle_ht}/{$resid}</title>
</head>
<body{$k_color_settings}>
{$_info_msg_ht}
<form id="{$form_id}" action="{$action_ht}" method="post">
ｽﾚ:<br>
<input type="text" name="ttitle_txt" value="{$ttitle_ht}"><br>
<input type="text" name="url_txt" value="{$url_txt}"><br>
{$url_k_ht}
{$resid}:<br>
<input type="text" name="name_txt" value="{$name_txt}"><br>
<input type="text" name="mail_txt" value="{$mail_txt}"><br>
<input type="text" name="date_txt" value="{$date_txt}"><br>
{$id_ht}
<textarea name="msg_txt">{$msg_txt}</textarea><br>
ﾌﾘｰ:<br>
<textarea name="free" rows="2"></textarea>
</form>
{$back_link}
{$post_link}
{$moto_link}
</body>
</html>
EOF;

?>
