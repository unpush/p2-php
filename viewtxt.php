<?php
/**
 * rep2 - txt を 表示
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// 引数エラー
if (!isset($_GET['file'])) {
    p2die('file が指定されていません');
}

//=========================================================
// 変数
//=========================================================
$file = (isset($_GET['file'])) ? $_GET['file'] : NULL;
$encode = "Shift_JIS";

//=========================================================
// 前処理
//=========================================================
// 読み込めるファイルを限定する
$readable_files = array("doc/README.txt", "doc/README-EX.txt", "doc/ChangeLog.txt");

if ($readable_files && $file and (!in_array($file, $readable_files))) {
    $i = 0;
    foreach ($readable_files as $afile) {
        if ($i != 0) {
            $files_st .= "と";
        }
        $files_st .= "「".$afile."」";
        $i++;
    }
    p2die(basename($_SERVER['SCRIPT_NAME'])." 先生の読めるファイルは、{$files_st}だけ！");
}

//=========================================================
// HTMLプリント
//=========================================================
// 読み込むファイルは拡張子.txtだけ
if (preg_match('/\\.txt$/i', $file)) {
    viewTxtFile($file, $encode);
} else {
    p2die("error: cannot view \"{$file}\"");
}

// {{{ viewTxtFile()

/**
 * ファイル内容を読み込んで表示する関数
 */
function viewTxtFile($file, $encode)
{
    if ($file == '') {
        p2die('file が指定されていません');
    }

    $filename = basename($file);
    $ptitle = $filename;

    //ファイル内容読み込み
    $cont = FileCtl::file_read_contents($file);
    if ($cont === false) {
        $cont_area = '';
    } else {
        if ($encode == 'EUC-JP') {
            $cont = mb_convert_encoding($cont, 'CP932', 'CP51932');
        } elseif ($encode == 'UTF-8') {
            $cont = mb_convert_encoding($cont, 'CP932', 'UTF-8');
        }
        $cont_area = htmlspecialchars($cont, ENT_QUOTES);
    }

    // プリント
    echo <<<EOHEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}
    <title>{$ptitle}</title>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
</head>
<body onload="top.document.title=self.document.title;">\n
EOHEADER;

    P2Util::printInfoHtml();
    echo "<pre>";
    echo $cont_area;
    echo "</pre>";
    echo '</body></html>';

    return TRUE;
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
