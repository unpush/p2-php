<?php
// p2 - データコントロール系の関数群

require_once './filectl.class.php';
require_once './p2util.class.php';	// p2用のユーティリティクラス

//===============================================
// ダウンロードURLからキャッシュファイルパスを返す
//===============================================
function cacheForDL($url){
	global $prefdir;
	$URL=parse_url($url); //URL分解
	$cachefile = $prefdir."/p2_cache/{$URL['host']}{$URL['path']}";
	FileCtl::mkdir_for($cachefile);
	return $cachefile;
}

//===============================================
// ホストからクッキーファイルパスを返す関数
//===============================================
function cacheForCookie($host){
	global $prefdir;
	$cachefile = $prefdir."/p2_cookie/{$host}/p2_cookie.txt";
	FileCtl::mkdir_for($cachefile);
	return $cachefile;
}

//===============================================
// hostとbbsから板名を返す関数
//===============================================
function getItaName($host, $bbs){
	global $prefdir, $ita_names, $p2_perm;
	
	if(! isset($ita_names["$host/$bbs"]) ){
		$datdir_host = datdirOfHost($host);
		$p2_setting_txt = $datdir_host."/".$bbs."/p2_setting.txt";
		$p2_setting_cont = FileCtl::get_file_contents($p2_setting_txt);
		if($p2_setting_cont){$p2_setting = unserialize($p2_setting_cont);}
		$ita_names["$host/$bbs"] = $p2_setting['itaj'];
	}

	/* 板名Longの取得
	if(! $p2_setting['itaj']){ //itaj未セットで2ch pink ならSETTING.TXTを読んでセット
		if( P2Util::isHost2chs($host) ){
			$tempfile=$prefdir."/SETTING.TXT.temp";
			P2Util::fileDownload("http://{$host}/{$bbs}/SETTING.TXT", $tempfile);
			//$setting=getHttpContents("http://{$host}/{$bbs}/SETTING.TXT", "", "GET", "", array(""), $httpua="p2");
			$setting=file($tempfile);
			if(file_exists($tempfile)){ unlink($tempfile); }
			if($setting){
				foreach($setting as $sl){
					$sl=trim($sl);
					if(preg_match("/^BBS_TITLE=(.+)/", $sl, $matches)){
						$p2_setting['itaj']=$matches[1];
					}
				}
				if($p2_setting['itaj']){
					FileCtl::make_datafile($p2_setting_txt, $p2_perm);
					if($p2_setting){$p2_setting_cont = serialize($p2_setting);}
					if($p2_setting_cont){
						$fp = fopen($p2_setting_txt, "w") or die("Error: $p2_setting_txt を更新できませんでした");
						fputs($fp, $p2_setting_cont);
						fclose($fp);
					}
				}
			}
		}
	}
	*/
	
	return $ita_names["$host/$bbs"];
}

//===============================================
// hostからdatの保存ディレクトリを返す関数
//===============================================
function datdirOfHost($host){
	global $datdir;
	if( P2Util::isHost2chs($host) ){ // 2channel or bbspink
		$datdir_host = $datdir."/2channel";
	}elseif( P2Util::isHostMachiBbs($host) ){ //machibbs.com
		$datdir_host = $datdir."/machibbs.com";
	}else{
		$datdir_host = $datdir."/".$host;
	}
	return $datdir_host;
}

/**
 * key.idx に data を記録する
 */
function setKeyIdx($keyidx, $data)
{
	global $key_perm;
	
	$data = rtrim($data);
	
	FileCtl::make_datafile($keyidx, $key_perm);
	$fp = fopen($keyidx, "wb") or die("Error: {$keyidx} を更新できませんでした");
	fputs($fp, $data."\n");
	fclose($fp);
}


//===============================================
// リストのナビ範囲を返す関数
//===============================================
function getListNaviRange($disp_from, $disp_range, $disp_all_num){

	$disp_end=0;
	$disp_navi=array();

	if(!$disp_all_num){
		$disp_navi['from']=0;
		$disp_navi['end']=0;
		$disp_navi['all_once']=true;
		$disp_navi['mae_from']=1;
		$disp_navi['tugi_from']=1;
		return $disp_navi;
	}	


	$disp_navi['from']=$disp_from;
	
	$disp_range = $disp_range-1;
	
	if($disp_navi['from'] > $disp_all_num){ //fromが越えた
		$disp_navi['from'] = $disp_all_num - $disp_range;
		if($disp_navi['from'] < 1){
			$disp_navi['from'] = 1;
		}
		$disp_navi['end'] = $disp_all_num;
					
	}else{//from 越えない
		if($disp_navi['from'] + $disp_range > $disp_all_num){ //end 越えた
			$disp_navi['end'] = $disp_all_num;
			if($disp_navi['from'] == 1){
				$disp_navi['all_once'] = true;
			}
		}else{// end 越えない
			$disp_navi['end'] = $disp_from + $disp_range;
		}
	}
		
	$disp_navi['mae_from'] = $disp_from -1 -$disp_range;
	if($disp_navi['mae_from'] < 1){
		$disp_navi['mae_from'] = 1;
	}	
	$disp_navi['tugi_from'] = $disp_navi['end'] +1;


	if($disp_navi['from']==$disp_navi['end']){
		$range_on_st = $disp_navi['from'];
	}else{
		$range_on_st = "{$disp_navi['from']}-{$disp_navi['end']}";
	}
	$disp_navi['range_st']="{$range_on_st}/{$disp_all_num} ";


	return $disp_navi;
	
}

//パーミッション注意喚起================
function datadir_writable_check($aDir)
{
	global $_info_msg_ht, $data_dir_perm;
	if( ! is_dir($aDir) ){
		$_info_msg_ht .= <<<EOP
<p class="infomsg">注意: データ保存用ディレクトリがありません。<br>
{$aDir}<br>
EOP;
		if( is_dir( dirname( realpath($aDir) ) ) && is_writable( dirname( realpath($aDir) ) )){
			$_info_msg_ht .="ディレクトリの自動作成を試みます...<br>";
			if (mkdir($aDir, $data_dir_perm)) {
				$_info_msg_ht .="ディレクトリの自動作成が成功しました。</p>";
				chmod($aDir, $data_dir_perm);
			} else {
				$_info_msg_ht .= "ディレクトリを自動作成できませんでした。<br>手動でディレクトリを作成し、パーミッションを設定して下さい。</p>";
			}
		}else{
				$_info_msg_ht .= "ディレクトリを作成し、パーミッションを設定して下さい。</p>";
		}
	}elseif( ! is_writable($aDir) ){
		$_info_msg_ht .= <<<EOP
<p class="infomsg">注意: データ保存用ディレクトリに書き込み権限がありません。<br>
{$aDir}<br>
ディレクトリのパーミッションを見直して下さい。</p>
EOP;
	}
}

?>