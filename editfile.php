<?php
/*
    ファイルをブラウザで編集する
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

// 引数エラー
if (!isset($_POST['path'])) {
    die('Error: path が指定されていません');
}

// 変数 ==================================
isset($_POST['path'])       and $path = $_POST['path'];
isset($_POST['modori_url']) and $modori_url = $_POST['modori_url'];
isset($_POST['encode'])     and $encode = $_POST['encode'];

$rows = isset($_POST['rows']) ? $_POST['rows'] : 36;
$cols = isset($_POST['cols']) ? $_POST['cols'] : 128;

isset($_POST['filecont']) and $filecont = $_POST['filecont'];

//=========================================================
// 前処理
//=========================================================
// 書き込めるファイルを限定する
$writable_files = array(
    "p2_aborn_name.txt", "p2_aborn_mail.txt", "p2_aborn_msg.txt", "p2_aborn_id.txt",
    "p2_ng_name.txt", "p2_ng_mail.txt", "p2_ng_msg.txt", "p2_ng_id.txt", "p2_aborn_res.txt",
);

if ($writable_files and (!in_array(basename($path), $writable_files))) {
    $i = 0;
    foreach ($writable_files as $afile) {
        if ($i != 0) {
            $files_st .= "と";
        }
        $files_st .= "「" . $afile . "」";
        $i++;
    }
    die("Error: " . basename($_SERVER['SCRIPT_NAME']) . " 先生の書き込めるファイルは、" . $files_st . "だけ！");
}

//=========================================================
// メイン 
//=========================================================
if (isset($filecont)) {
    if (setFile($path, $filecont, $encode)) {
        P2Util::pushInfoHtml("saved, OK.");
    }
}

printEditFileHtml($path, $encode);


//=========================================================
// 関数（このファイル内でのみ利用）
//=========================================================
/**
 * ファイルに内容をセットする関数
 *
 * @return  boolean
 */
function setFile($path, $cont, $encode)
{
    if ($path == '') {
        die('Error: path が指定されていません');
    }

    if ($encode == "EUC-JP") {
        $cont = mb_convert_encoding($cont, 'SJIS-win', 'eucJP-win');
    }
    // 書き込む
    if (false === file_put_contents($path, $cont, LOCK_EX)) {
        die("Error: cannot write. ( $path )");
        return false;
    }
    return true;
}

/**
 * ファイル内容を読み込んで編集のためのHTMLを表示する
 *
 * @return  void
 */
function printEditFileHtml($path, $encode)
{
    global $_conf, $modori_url, $rows, $cols;
    
    $info_msg_ht = P2Util::getInfoHtml();
    
    if ($path == '') {
        die('Error: path が指定されていません');
    }
    
    $filename = basename($path);
    $ptitle = "Edit: " . $filename;
    
    if (false === FileCtl::make_datafile($path)) {
         die("Error: cannot make file. ( $path )");
    }
    $cont = file_get_contents($path);
    
    if ($encode == "EUC-JP") {
        $cont = mb_convert_encoding($cont, 'SJIS-win', 'eucJP-win');
    }
    
    $cont_area = htmlspecialchars($cont, ENT_QUOTES);
    
    if ($modori_url) {
        $modori_url_ht = "<p><a href=\"{$modori_url}\">Back</a></p>\n";
    } else {
        $modori_url_ht = '';
    }
    
    // HTML出力
    echo <<<EOHEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>
</head>
<body onLoad="top.document.title=self.document.title;">
EOHEADER;

    echo $modori_url_ht;

    echo "Edit: ".$path;
    echo <<<EOFORM
<form action="{$_SERVER['SCRIPT_NAME']}" method="post" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" name="path" value="{$path}">
    <input type="hidden" name="modori_url" value="{$modori_url}">
    <input type="hidden" name="encode" value="{$encode}">
    <input type="hidden" name="rows" value="{$rows}">
    <input type="hidden" name="cols" value="{$cols}">
    <input type="submit" name="submit" value="Save"> $info_msg_ht<br>
    <textarea style="font-size:9pt;" id="filecont" name="filecont" rows="{$rows}" cols="{$cols}" wrap="off">{$cont_area}</textarea>    
</form>
EOFORM;

    echo '</body></html>';
}

