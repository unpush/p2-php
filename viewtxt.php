<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - txt を 表示
*/

require_once 'conf/conf.php';   //基本設定ファイル読込

authorize(); // ユーザ認証

// 引数エラー
if (!isset($_GET['file'])) {
    die('Error: file が指定されていません');
}

//=========================================================
// 変数
//=========================================================

$file = (isset($_GET['file'])) ? $_GET['file'] : NULL;
$encode = 'Shift_JIS';

//=========================================================
// 前処理
//=========================================================
// 読み込めるファイルを限定する
$readable_files = array('doc/README.txt', 'doc/ChangeLog.txt', 'doc/Bookmarklet.txt', 'doc/README-EX.txt');

if ($readable_files && $file && (!in_array($file, $readable_files))) {
    $i = 0;
    foreach ($readable_files as $afile) {
        if ($i != 0) {
            $files_st .= 'と';
        }
        $files_st .= '「'.$afile.'」';
        $i++;
    }
    die('Error: '.basename($_SERVER['PHP_SELF']).' 先生の読めるファイルは、'.$files_st.'だけ！');
}

//=========================================================
// HTMLプリント
//=========================================================
// 読み込むファイルは拡張子.txtだけ
if (preg_match('/\.txt$/i', $file)) {
    viewTxtFile($file, $encode);
} else {
    die("error: cannot view \"{$file}\"");
}

/**
 * ファイル内容を読み込んで表示する
 */
function viewTxtFile($file, $encode)
{
    global $_info_msg_ht;

    if ($file == '') {
        die('Error: file が指定されていません');
    }

    $filename = basename($file);
    $ptitle = $filename;

    // ファイル内容読み込み
    $cont = @file_get_contents($file);

    if ($encode == 'EUC-JP') {
        $cont = mb_convert_encoding($cont, 'SJIS-win', 'eucJP-win');
    }

    $cont_area = htmlspecialchars($cont);

    // プリント
    echo <<<EOF
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>
</head>
<body onload="top.document.title=self.document.title;">
{$_info_msg_ht}
<pre>{$cont_area}</pre>
</body>
</html>
EOF;

    return TRUE;
}

?>
