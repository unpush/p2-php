<?php
/*
	p2 - txt を 表示

	最新更新日: 2004/10/24
*/

include("./conf.php");   //基本設定ファイル読込
require_once("./filectl_class.inc");

authorize(); //ユーザ認証

//=========================================================
// 変数
//=========================================================
$_info_msg_ht = "";

$file = $_GET['file'];
$encode = "Shift_JIS";

//=========================================================
// 前処理
//=========================================================
// 読み込めるファイルを限定する
$readable_files = array("doc/README.txt", "doc/ChangeLog.txt");

if ($readable_files and (!in_array($file, $readable_files))) {
	$i = 0;
	foreach ($readable_files as $afile) {
		if ($i != 0) {
			$files_st .= "と";
		}
		$files_st .= "「".$afile."」";
		$i++;
	}
	die("Error: ".basename($_SERVER['PHP_SELF'])." 先生の読めるファイルは、".$files_st."だけ！");
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

	$filename = basename($file);
	$ptitle = $filename;
	
	//ファイル内容読み込み
	$cont = FileCtl::get_file_contents($file);
	
	if ($encode == "EUC-JP") {
		include_once("./strctl_class.inc");
		$cont = StrCtl::p2EUCtoSJIS($cont);
	}
	
	$cont_area = htmlspecialchars($cont);

	//プリント
	echo <<<EOHEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
	<title>{$ptitle}</title>
</head>
<body onLoad="top.document.title=self.document.title;">
EOHEADER;

echo $_info_msg_ht;
echo "<pre>";
echo $cont_area;
echo "</pre>";
echo <<<EOFOOTER
</body>
</html>
EOFOOTER;

}

?>