<?php
// p2 - お気にスレ関係の処理スクリプト

require_once("./filectl_class.inc");
require_once("./datactl.inc");

//==================================================================
// 変数
//==================================================================
if($_GET['setfav']=="0" or $_GET['setfav']=="1"){
	$favbool=$_GET['setfav'];
}

//==================================================================
// key.idx
//==================================================================

//idxfileのパスを求めて
$datdir_host=datdirOfHost($_GET['host']);
$idxfile="$datdir_host/{$_GET['bbs']}/{$_GET['key']}.idx";

//板ディレクトリが無ければ作る
//FileCtl::mkdir_for($idxfile);

//データがあるなら読み込んで
if( is_readable($idxfile) ){
	$lines=@file($idxfile);
	$data = explode("<>", $lines[0]);
}

//値設定
$s="$data[0]<>{$_GET['key']}<>$data[2]<>$data[3]<>$data[4]<>$data[5]<>{$favbool}<>$data[7]<>$data[8]<>$data[9]";
/*	
//書き込む
$fp=fopen($idxfile,"w") or die("Error: $idxfile を更新できませんでした");
fputs($fp,$s);
fclose($fp);
*/

//==================================================================
// favlist.idxに書き込む
//==================================================================

//================================================
// 読み込み
//================================================

//favlistファイルがなければ生成
FileCtl::make_datafile($favlistfile, $favlist_perm);

//favlist読み込み;
$favlines= @file($favlistfile);

//================================================
// 処理
//================================================

//最初に重複要素を削除
if($favlines){
	$i=-1;
	unset($neolines);
	foreach($favlines as $favl){
		$i++;
		$lar = explode("<>", $favl);
		if($lar[1]==$_GET['key']){ //重複回避
			$before_line_num=$i;
			continue;
		}elseif(! $lar[1]){ //keyのないものは不正データ
			continue;
		}else{
			$neolines[]=$favl;
		}
	}
}

//新規データ設定
if($_GET['setfav']){
	$newdata="$data[0]<>{$_GET['key']}<>$data[2]<>$data[3]<>$data[4]<>$data[5]<>{$favbool}<>$data[7]<>$data[8]<>$data[9]<>{$_GET['host']}<>{$_GET['bbs']}\n";
}
	
if($_GET['setfav']==1 or $_GET['setfav']=="top"){
	$after_line_num=0;
	
}elseif($_GET['setfav']=="up"){
	$after_line_num=$before_line_num-1;
	if($after_line_num<0){$after_line_num=0;}
	
}elseif($_GET['setfav']=="down"){
	$after_line_num=$before_line_num+1;
	if( $after_line_num >= sizeof($neolines) ){$after_line_num="bottom";}
	
}elseif($_GET['setfav']=="bottom"){
	$after_line_num="bottom";
}

//================================================
//書き込む
//================================================
$fp = @fopen($favlistfile,"wb") or die("Error: $favlistfile を更新できませんでした");

if($neolines){
	$i=0;
	foreach($neolines as $l){
		if($i===$after_line_num){fputs($fp, $newdata);}
		fputs($fp, $l);
		$i++;
	}
	if($after_line_num==="bottom"){fputs($fp, $newdata);}
	//「$after_line_num=="bottom"」だと誤動作する。
}else{
	fputs($fp, $newdata);
}
fclose($fp);

//================================================
// お気にスレ共有
//================================================

if($join_favrank){
	if($_GET['setfav']=="0"){
		$act="out";
	}elseif($_GET['setfav']=="1"){
		$act="add";
	}else{
		return;
	}
	$itaj = getItaName($_GET['host'], $_GET['bbs']);
	$post = array("host" => $_GET['host'], "bbs" => $_GET['bbs'], "key" => $_GET['key'], "ttitle" => $data[0], "ita" => $itaj, "act" => $act);
	postFavRank($post);
}

/**
 * お気にスレ共有でポストする関数
 */
function postFavRank($post)
{
	global $_conf, $favrank_url, $proxy;

	$method = "POST";
	$httpua = "Monazilla/1.00 (".$_conf['p2name']."/".$_conf['p2version'].")";
	
	$URL = parse_url($favrank_url); //URL分解
	if (isset($URL['query'])) { //クエリー
	    $URL['query'] = "?".$URL['query'];
	} else {
	    $URL['query'] = "";
	}

	// プロキシ
	if ($proxy['use']) {
		$send_host = $proxy['host'];
		$send_port = $proxy['port'];
		$send_path = $url;
	} else {
		$send_host = $URL['host'];
		$send_port = $URL['port'];
		$send_path = $URL['path'].$URL['query'];
	}
	
	if (!$send_port) {$send_port = 80;}	// デフォルトを80
	
	$request = $method." ".$send_path." HTTP/1.0\r\n";
	$request .= "Host: ".$URL['host']."\r\n";
	$request .= "User-Agent: ".$httpua."\r\n";
	$request .= "Connection: Close\r\n";

	/* POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付 */
	if (strtoupper($method) == "POST") {
	    while (list($name, $value) = each($post)) {
	        $POST[] = $name."=".urlencode($value);
	    }
	    $postdata = implode("&", $POST);
	    $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
	    $request .= "Content-Length: ".strlen($postdata)."\r\n";
	    $request .= "\r\n";
	    $request .= $postdata;
	} else {
	    $request .= "\r\n";
	}

	/* WEBサーバへ接続 */
	$fp = fsockopen($send_host, $send_port, $errno, $errstr, 3);
	if(!$fp){
		//echo "サーバ接続エラー: $errstr ($errno)<br>\n";
		//echo "p2 info: {$favrank_url} に接続できませんでした。<br>";
		return false;
	}else{
		fputs($fp, $request);
		/*
		while (!feof($fp)){
			if($start_here){
				echo $body = fread($fp,512000);
			}else{
				$l = fgets($fp,128000);
				if($l=="\r\n"){
					$start_here=true;
				}
			}
		}
		*/
		fclose ($fp);
		return true;
		//return $body;
	}
}

?>