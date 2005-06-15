<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
	p2 - お気にスレ関係の処理スクリプト

	お気にスレの追加削除や、順序変更で呼ばれる

	2005/03/10 以前
	スレ個別idxでのお気に入りフラグは、現在は使用（機能）していない。
	お気にスレ情報は、favlist.idxでまとめて受け持つ。
	↓
	2005/03/10
	スレッド表示時の負荷軽減を目的として、スレッド.idxでもお気にスレ情報を持つこととする。
	subjectでお気にスレ一覧表示 → favlist.idx を参照
	スレッド表示時のお気にスレ表示 → スレッド.idx を参照
*/

require_once (P2_LIBRARY_DIR . '/p2util.class.php');	// p2用のユーティリティクラス
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

/**
 * お気にスレをセットする
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 */
function setFav($host, $bbs, $key, $setfav)
{
	global $_conf;

	//==================================================================
	// key.idx
	//==================================================================
	// idxfileのパスを求めて
	$datdir_host = P2Util::datdirOfHost($host);
	$idxfile = $datdir_host.'/'.$bbs.'/'.$key.'.idx';

	// 板ディレクトリが無ければ作る
	//FileCtl::mkdir_for($idxfile);

	// 既にidxデータがあるなら読み込む
	if (is_readable($idxfile) && ($lines = @file($idxfile))) {
		$l = rtrim($lines[0]);
		$data = explode('<>', $l);
		$c = count($data);
		if ($c < 10) {
			while ($c < 10) {
				$data[] = '';
				$c++;
			};
		} elseif ($c > 10) {
			$data = array_slice($data, 0, 10);
		}
		unset($c);
	} else {
		$data = array_fill(0, 10, '');
	}

	// スレッド.idx 記録
	if ($setfav == '0' or $setfav == '1') {
		// お気にスレから外した結果、idxの意味がなくなれば削除する
		if ($setfav == '0' and (!$data[3] && !$data[4] && $data[9] <= 1)) {
			@unlink($idxfile);
		} else {
			$newdata = $data;
			$newdata[1] = $key;
			$newdata[6] = $setfav;
			$s = implode('<>', $newdata);
			P2Util::recKeyIdx($idxfile, $s);
		}
	}

	//==================================================================
	// favlist.idx
	//==================================================================
	// favlistファイルがなければ生成
	FileCtl::make_datafile($_conf['favlist_file'], $_conf['favlist_perm']);

	//favlist読み込み;
	$favlines = @file($_conf['favlist_file']);

	//================================================
	// 処理
	//================================================

	// 最初に重複要素を削除しておく
	if (!empty($favlines)) {
		$i = -1;
		$neolines = array();
		foreach ($favlines as $line) {
			$i++;
			$line = rtrim($line);
			$lar = explode('<>', $line);
			// 重複回避
			if ($lar[1] == $key) {
				$before_line_num = $i;	// 移動前の行番号をセット
				continue;
			// keyのないものは不正データなのでスキップ
			} elseif (!$lar[1]) {
				continue;
			} else {
				$neolines[] = $line;
			}
		}
	}

	// 新規データ設定
	if ($setfav) {
		$newdata = $data;
		$newdata[1] = $key;
		$newdata[6] = 1;
		$newdata[10] = $host;
		$newdata[11] = $bbs;
		$newline = implode('<>', $newdata) . "\n";
	}

	if ($setfav == 1 or $setfav == 'top') {
		$after_line_num = 0;	// 移動後の行番号

	} elseif ($setfav == 'up') {
		$after_line_num = $before_line_num-1;
		if ($after_line_num < 0) {
			$after_line_num = 0;
		}

	} elseif ($setfav == 'down') {
		$after_line_num = $before_line_num+1;
		if ($after_line_num >= sizeof($neolines)) {
			$after_line_num = 'bottom';
		}

	} elseif ($setfav == 'bottom') {
		$after_line_num = 'bottom';

	} else {
		$after_line_num = null;
	}

	//================================================
	// 書き込む
	//================================================
	$fp = @fopen($_conf['favlist_file'], 'wb') or die("Error: {$_conf['favlist_file']} を更新できませんでした");
	@flock($fp, LOCK_EX);
	if (!empty($neolines)) {
		$i = 0;
		foreach ($neolines as $l) {
			if ($i === $after_line_num) {
				fputs($fp, $newline);
			}
			fputs($fp, $l."\n");
			$i++;
		}
		if ($after_line_num === 'bottom') {
			fputs($fp, $newline);
		}
		//「$after_line_num == 'bottom'」だと誤動作する。
	} else {
		fputs($fp, $newline);
	}
	@flock($fp, LOCK_UN);
	fclose($fp);

	//================================================
	// お気にスレ共有
	//================================================
	if ($_conf['join_favrank']) {
		if ($setfav == '0') {
			$act = 'out';
		} elseif ($setfav == '1') {
			$act = 'add';
		} else {
			return;
		}
		$itaj = P2Util::getItaName($host, $bbs);
		$post = array('host' => $host, 'bbs' => $bbs, 'key' => $key, 'ttitle' => $data[0], 'ita' => $itaj, 'act' => $act);
		postFavRank($post);
	}

	return true;
}

/**
 * お気にスレ共有でポストする
 */
function postFavRank($post)
{
	global $_conf;

	$method = 'POST';
	$httpua = 'Monazilla/1.00 ('.$_conf['p2name_ua'].'/'.$_conf['p2version_ua'].')';

	$URL = parse_url($_conf['favrank_url']); // URL分解
	if (isset($URL['query'])) { // クエリー
		$URL['query'] = '?'.$URL['query'];
	} else {
		$URL['query'] = '';
	}

	// プロキシ
	if ($_conf['proxy_use']) {
		$send_host = $_conf['proxy_host'];
		$send_port = $_conf['proxy_port'];
		$send_path = $url;
	} else {
		$send_host = $URL['host'];
		$send_port = $URL['port'];
		$send_path = $URL['path'].$URL['query'];
	}

	if (!$send_port) { $send_port = 80; }//デフォルトを80

	$request = $method.' '.$send_path.' HTTP/1.0'."\r\n";
	$request .= 'Host: '.$URL['host']."\r\n";
	$request .= 'User-Agent: '.$httpua."\r\n";
	$request .= 'Connection: Close'."\r\n";

	/* POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付 */
	if (strtoupper($method) == 'POST') {
		while (list($name, $value) = each($post)) {
			$POST[] = $name.'='.urlencode($value);
		}
		$postdata = implode('&', $POST);
		$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
		$request .= 'Content-Length: '.strlen($postdata)."\r\n";
		$request .= "\r\n";
		$request .= $postdata;
	} else {
		$request .= "\r\n";
	}

	/* WEBサーバへ接続 */
	require_once (P2_LIBRARY_DIR . '/p2socket.class.php');
	$sock = &P2Socket::open($send_host, $send_port, 3);
	if ($sock->isError()) {
		$fp = false;
		list($errno, $errstr) = $sock->getError();
		//$_info_msg_ht .= $sock->getWarning();
	} else {
		$fp = &$sock->getResource();
	}
	if (!$fp) {
		//echo "サーバ接続エラー: $errstr ($errno)<br>\n";
		//echo "p2 info: {$_conf['favrank_url']} に接続できませんでした。<br>";
		return false;
	} else {
		fputs($fp, $request);
		/*
		while (!feof($fp)){
			if ($start_here) {
				echo $body = fread($fp,512000);
			} else {
				$l = fgets($fp,128000);
				if ($l == "\r\n") {
					$start_here = true;
				}
			}
		}
		*/
		fclose($fp);
		return true;
		//return $body;
	}
}

?>
