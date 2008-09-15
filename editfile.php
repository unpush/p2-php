<?php
/*
    ファイルをブラウザで編集する
*/

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// 引数エラー
if (!isset($_REQUEST['path'])) {
    p2die('path が指定されていません');
}

// 変数 ==================================
$path       = isset($_REQUEST['path'])       ? $_REQUEST['path']       : null;
$modori_url = isset($_REQUEST['modori_url']) ? $_REQUEST['modori_url'] : null;
$encode     = isset($_REQUEST['encode'])     ? $_REQUEST['encode']     : null;

$rows = isset($_REQUEST['rows']) ? intval($_REQUEST['rows']) : ($_conf['ktai'] ? 5 : 36);
$cols = isset($_REQUEST['cols']) ? intval($_REQUEST['cols']) : ($_conf['ktai'] ? 0 : 128);

isset($_POST['filecont']) and $filecont = $_POST['filecont'];

//=========================================================
// 前処理
//=========================================================
// 書き込めるファイルを限定する
$writable_files = array(
                        //"conf.inc.php", "conf_user_style.inc.php",
                        //"p2_aborn_name.txt", "p2_aborn_mail.txt", "p2_aborn_msg.txt", "p2_aborn_id.txt",
                        //"p2_ng_name.txt", "p2_ng_mail.txt", "p2_ng_msg.txt", "p2_ng_id.txt",
                        "p2_aborn_res.txt",
                        //"conf_user_ex.php", "conf_constant.inc",
                        //"conf_user_ex.inc.php", "conf_user_constant.inc.php"
                    );

if ($writable_files and (!in_array(basename($path), $writable_files))) {
    $i = 0;
    foreach ($writable_files as $afile) {
        if ($i != 0) {
            $files_st .= "と";
        }
        $files_st .= "「".$afile."」";
        $i++;
    }
    p2die(basename($_SERVER['SCRIPT_NAME']) . " 先生の書き込めるファイルは、{$files_st}だけ！");
}

//=========================================================
// メイン
//=========================================================
if (isset($filecont)) {
    if (setFile($path, $filecont, $encode)) {
        $_info_msg_ht .= "saved, OK.";
    }
}

editFile($path, $encode);

exit;

//=========================================================
// 関数
//=========================================================
// {{{ setFile()

/**
 * ファイルに内容をセットする関数
 */
function setFile($path, $cont, $encode)
{
    if ($path == '') {
        p2die('path が指定されていません');
    }

    if ($encode == "EUC-JP") {
        $cont = mb_convert_encoding($cont, 'CP932', 'CP51932');
    }
    // 書き込む
    $fp = @fopen($path, 'wb') or p2die("cannot write. ({$path})");
    @flock($fp, LOCK_EX);
    fputs($fp, $cont);
    @flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

// }}}
// {{{ editFile()

/**
 * ファイル内容を読み込んで編集する関数
 */
function editFile($path, $encode)
{
    global $_conf, $modori_url, $_info_msg_ht, $rows, $cols;

    if ($path == '') {
        p2die('path が指定されていません');
    }

    $filename = basename($path);
    $ptitle = "Edit: ".$filename;

    //ファイル内容読み込み
    FileCtl::make_datafile($path) or p2die("cannot make file. ({$path})");
    $cont = file_get_contents($path);

    if ($encode == "EUC-JP") {
        $cont = mb_convert_encoding($cont, 'CP932', 'CP51932');
    }

    $cont_area = htmlspecialchars($cont, ENT_QUOTES);

    if ($modori_url) {
        $modori_url_ht = "<p><a href=\"{$modori_url}\">Back</a></p>\n";
    }

    $rows_at = ($rows > 0) ? sprintf(' rows="%d"', $rows) : '';
    $cols_at = ($cols > 0) ? sprintf(' cols="%d"', $cols) : '';

    // プリント
    echo $_conf['doctype'];
    echo <<<EOHEADER
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}
    <title>{$ptitle}</title>
</head>
<body onload="top.document.title=self.document.title;">
EOHEADER;

    echo $modori_url_ht;

    echo "Edit: ".$path;
    echo <<<EOFORM
<form action="{$_SERVER['SCRIPT_NAME']}" method="post" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="path" value="{$path}">
    <input type="hidden" name="modori_url" value="{$modori_url}">
    <input type="hidden" name="encode" value="{$encode}">
    <input type="hidden" name="rows" value="{$rows}">
    <input type="hidden" name="cols" value="{$cols}">
    <input type="submit" name="submit" value="Save">
    {$_info_msg_ht}<br>
    <textarea style="font-size:9pt;" id="filecont" name="filecont" wrap="off"{$rows_at}{$cols_at}>{$cont_area}</textarea>
    {$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
</form>
EOFORM;

    echo '</body></html>';

    return true;
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
