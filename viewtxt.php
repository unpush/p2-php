<?php
/*
	p2 - txt を 表示
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
$file = isset($_GET['file']) ? $_GET['file'] : NULL;
$encode = "Shift_JIS";

//=========================================================
// 前処理
//=========================================================
// 読み込めるファイルを限定する
$readable_files = array("doc/README.txt", "doc/ChangeLog.txt");

if ($readable_files && $file and (!in_array($file, $readable_files))) {
	$i = 0;
	foreach ($readable_files as $afile) {
		if ($i != 0) {
			$files_st .= "と";
		}
		$files_st .= "「" . $afile . "」";
		$i++;
	}
    
    p2die(
        'ファイルの指定が正しくありません',
        hs(sprintf(
            '%s 先生の読めるファイルは、%sだけ！',
            basename($_SERVER['SCRIPT_NAME']), $files_st
        ))
    );
}

//=========================================================
// HTMLプリント
//=========================================================
// 読み込むファイルは拡張子.txtだけ
if (preg_match("/\.txt$/i", $file)) {
	viewTxtFile($file, $encode);
} else {
    p2die('cannot view - "' . hs($file) . '"');
}

//===================================================================
// 関数（このファイル内でのみ利用）
//===================================================================
/**
 * ファイル内容を読み込んで表示する関数
 *
 * @return  void
 */
function viewTxtFile($file, $encode)
{
	global $_info_msg_ht;
	
	if ($file == '') {
		die('Error: file が指定されていません');
	}
	
	$filename = basename($file);
	$ptitle = $filename;
	
	$cont = file_get_contents($file);
	
	if ($encode == "EUC-JP") {
		$cont = mb_convert_encoding($cont, 'SJIS-win', 'eucJP-win');
	}
	
	$cont_area = htmlspecialchars($cont, ENT_QUOTES);

	// HTMLプリント
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
	<title><?php eh($ptitle) ?></title>
</head>
<body onLoad="top.document.title=self.document.title;">
<?php
	P2Util::printInfoHtml();
?>
<pre>
<?php
	echo $cont_area;
?>
</pre>
</body></html>
<?php
}

