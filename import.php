<?php
/**
 * rep2expack - datをインポートする
 *
 * @todo Zipアーカイブのインポート対応
 * @todo HTMLのインポート対応
 */

require_once 'conf/conf.inc.php';
require_once P2_LIBRARY_DIR . '/p2util.class.php';
require_once P2_LIBRARY_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

// 変数
$link_ht = '';
$max_size = 1000000;

$default_host = !empty($_REQUEST['host']) ? htmlspecialchars($_REQUEST['host'], ENT_QUOTES) : '_.2ch.net';
$default_bbs = !empty($_REQUEST['bbs']) ? htmlspecialchars($_REQUEST['bbs'], ENT_QUOTES) : '';
$default_key = !empty($_REQUEST['key']) ? htmlspecialchars($_REQUEST['key'], ENT_QUOTES) : 'auto';

//================================================================
// アップロードされたファイルの処理
//================================================================
if (!empty($_POST['host']) && !empty($_POST['bbs']) && !empty($_POST['key']) && isset($_FILES['dat_file'])) {
    $is_error = false;

    // アップロード成功のとき
    if ($_FILES['dat_file']['error'] == UPLOAD_ERR_OK) {
        // 値の検証
        if ($_POST['MAX_FILE_SIZE'] != $max_size) {
            $is_error = f;
            $_info_msg_ht .= '<p>Warning: フォームの MAX_FILE_SIZE の値が改ざんされています。</p>';
        }
        if (!preg_match('/^[1-9][0-9]+\.dat$/', $_FILES['dat_file']['name'])) {
            $is_error = true;
            $_info_msg_ht .= '<p>Error: アップロードされたdatのファイル名が変です。</p>';
        }
        $host = $_POST['host'];
        $bbs  = $_POST['bbs'];
        //if ($_POST['key'] == 'auto') {
            $key = preg_replace('/\.(dat|html?)$/', '', $_FILES['dat_file']['name']);
        /*} elseif (preg_match('/^[1-9][0-9]+$/', $_POST['key'])) {
            $key = $_POST['key'];
            if ($key != preg_replace('/\.(dat|html?)$/', '', $_FILES['dat_file']['name'])) {
                $is_error = true;
                $_info_msg_ht .= '<p>Error: アップロードされたdatのファイル名とスレッドキーがマッチしません。</p>';
            }
        } else {
            $is_error = true;
            $_info_msg_ht .= '<p>Error: スレッドキーの指定が変です。</p>';
        }*/
        $dat_name = $key . '.dat';
        $dat_path = P2Util::datDirOfHost($host) . '/' . $bbs . '/' . $dat_name;

    // アップロード失敗のとき
    } else {
        $is_error = true;
        // エラーメッセージは http://jp.php.net/manual/ja/features.file-upload.errors.php からコピペ
        switch ($_FILES['dat_file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $_info_msg_ht .= '<p>Error: アップロードされたファイルは、php.ini の upload_max_filesize ディレクティブの値を超えています。</p>';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $_info_msg_ht .= '<p>Error: アップロードされたファイルは、HTMLフォームで指定された MAX_FILE_SIZE を超えています。</p>';
                break;
            case UPLOAD_ERR_PARTIAL:
                $_info_msg_ht .= '<p>Error: アップロードされたファイルは一部のみしかアップロードされていません。</p>';
                break;
            case UPLOAD_ERR_NO_FILE:
                $_info_msg_ht .= '<p>Error: ファイルはアップロードされませんでした。</p>';
                break;
            default:
                $_info_msg_ht .= '<p>Error: 原因不明のエラー。</p>';
                break;
        }
    }

    // ファイルを保存し、リンクを作成
    if (!$is_error) {
        move_uploaded_file($_FILES['dat_file']['tmp_name'], $dat_path);
        $datlines = file($dat_path);
        if (strstr($datlines[0], '<>')) {
            $one = explode('<>', $datlines[0]);
        } else {
            $one = explode(',', $datlines[0]);
        }
        unset($datlines);
        $ttitle = array_pop($one);
        $read_url = sprintf('%s?host=%s&bbs=%s&key=%d&offline=true', $_conf['read_php'], rawurlencode($host), rawurlencode($bbs), $key);
        $link_ht = sprintf('<p><a href="%s" target="read"><b>%s</b> を今すぐ読む。</a></p>', $read_url, $ttitle);
    }

} elseif (!empty($_POST['host']) || !empty($_POST['bbs']) || !empty($_POST['key']) || isset($_FILES['dat_file'])) {
    $_info_msg_ht .= '<p>Error: 板URLが指定されていないか、datが選択されていません。</p>';
}

//================================================================
// HTML表示
//================================================================

// ヘッダ
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>p2 - datのインポート</title>
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

// ボディ
echo <<<EOP
<p>datのインポート</p>
<form method="post" enctype="multipart/form-data" action="{$_SERVER['SCRIPT_NAME']}">
    <input type="hidden" name="MAX_FILE_SIZE" value="{$max_size}">
    板URL: http://<input type="text" size="20" value="{$default_host}" name="host">/<input type="text" size="10"  value="{$default_bbs}" name="bbs">/
    <input type="hidden" value="{$default_key}" name="key">(スレッドキーはファイル名から自動判定)<br>
    datを選択: <input type="file" size="50" name="dat_file"><br>
    <input type="submit" value="送信">
</form>
EOP;
if ($link_ht) {
    echo '<hr><p>アップロード成功！</p>';
    echo $link_ht;
} else {
    echo <<<EOP
<hr>
<div>
使い方
<ul>
    <li>
        板URL欄の2番目の項目に板名（例:software）を入力し、datを選んでから<br>
        送信ボタンを押すと、datをアップロードしてp2で読むことができます。
    </li>
    <li>
        2ちゃんねるのdatをインポートするとき、1番目の項目（ホスト名）は _.2ch.net のままでOKです。<br>
        他の掲示板では正しいホスト名を入力してください。<br>
        したらばの板ではホスト名に続けて半角スラッシュとカテゴリ名が必要です。（例:jbbs.livedoor.jp/computer）
    </li>
</ul>
</div>
EOP;
}

// フッタ
echo '</body></html>';

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
