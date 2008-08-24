<?php
/*
    p2 - txt を 表示
*/

include_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// 引数エラー
if (!isset($_GET['file'])) {
    die('Error: file が指定されていません');
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
    die("Error: ".basename($_SERVER['SCRIPT_NAME'])." 先生の読めるファイルは、".$files_st."だけ！");
}

//=========================================================
// HTMLプリント
//=========================================================
// 読み込むファイルは拡張子.txtだけ
if (preg_match("/\.txt$/i", $file)) {
    viewTxtFile($file, $encode);
} else {
    die("error: cannot view \"$file\"");
}

/**
 * ファイル内容を読み込んで表示する関数
 */
function viewTxtFile($file, $encode)
{
    global $_info_msg_ht;

    if ($file == '') {
        die('Error: file が指定されていません');
    }

    $filename = basename($file);
    $ptitle = $filename;

    //ファイル内容読み込み
    $cont = @file_get_contents($file);

    if ($encode == "EUC-JP") {
        $cont = mb_convert_encoding($cont, 'CP932', 'CP51932');
    }

    $cont_area = htmlspecialchars($cont, ENT_QUOTES);

    // プリント
    echo <<<EOHEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>
</head>
<body onLoad="top.document.title=self.document.title;">\n
EOHEADER;

    echo $_info_msg_ht;
    echo "<pre>";
    echo $cont_area;
    echo "</pre>";
    echo '</body></html>';

    return TRUE;
}
