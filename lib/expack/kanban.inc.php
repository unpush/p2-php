<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - 看板・スレッド情報ライブラリ

//========================================================================
// getSignboard -- 看板と背景を取得する関数
// $page_url - 板のURL
// $cache_img - 画像キャッシュ（しない:0, する:1, 毎回更新:2）
// $return_popup - 返り値の切り替え（看板情報:0, ポップアップURL:1）
//========================================================================
function getSignboard($ptitle_url, $cache_img = 1, $return_popup = 0)
{
	global $_exconf, $kanban_info;
	global $datdir, $datdir_host, $bbs;

	$host = str_replace("{$datdir}/", '', $datdir_host);
	$datdir_bbs = $datdir_host . '/' . $bbs;
	$setting_src = $ptitle_url . 'SETTING.TXT';
	$setting_file = $datdir_bbs . '/SETTING.TXT';
	$setting_cache = $datdir_bbs . '/p2_kb_setting.inc';
	$rule_src = $ptitle_url . 'head.txt';
	$rule_file = $datdir_bbs . '/head.txt';
	$rule_cache = $datdir_bbs . '/p2_kb_head.html';
	$today = date('md');

	/*----SETTING.TXT（設定ファイル）のチェック----*/
	if (file_exists($setting_cache)) {
		$setting_exists = true;
		$setting_lastmod = filemtime($setting_cache);
	} else {
		$setting_res = &P2Util::fileDownload($setting_src, $setting_file, 0);
		if (in_array($setting_res->code, array('200', '206', '304'))) {
			$setting_exists = true;
		} else {
			$setting_exists = false;
		}
		$setting_lastmod = 0;
	}

	/*----head.txt（ローカルルール）のチェック----*/
	if (file_exists($rule_cache)) {
		$rule_exists = true;
		$rule_lastmod = filemtime($rule_cache);
	} else {
		$rule_res = &P2Util::fileDownload($rule_src, $rule_file, 0);
		if (in_array($rule_res->code, array('200', '206', '304'))) {
			$rule_exists = true;
		} else {
			$rule_exists = false;
		}
		$rule_lastmod = 0;
	}

	/*----SETTING.TXTがあるとき----*/
	if ($setting_exists) {

		//日付がファイルの最終変更日と異なるとき、SETTING.TXTを更新
		if (($setting_lastmod != 0 && date('md', $setting_lastmod) != $today) || $cache_img == 2) {
			//ファイルの最終変更日時を変更、同日の更新チェックを避ける
			touch($setting_cache);
			//SETTING.TXTの更新をチェック
			$setting_res = &P2Util::fileDownload($setting_src, $setting_file);
			if ($cache_img == 1) {
				if ($setting_res->code == '304') {
					$cache_img = 0; //更新されていなければ、再取得＆画像の更新チェックをしない
				} else {
					$cache_img = 2; //更新されていれば、ファイルをダウンロード
				}
			}
		}

		//板設定情報を取得
		$setting = parse_setting_txt($setting_file, $setting_cache, $cache_img);

		//看板のURLを取得、可能ならば保存する。
		if (isset($setting['BBS_TITLE_PICTURE'])) {
			$kb_src = getAbsoluteURL($setting['BBS_TITLE_PICTURE'], $ptitle_url);
			list($kb_url, $kb_path, $wap_res_kb) = getImageFile($kb_src, $cache_img);
		} elseif (isset($setting['BBS_FIGUREHEAD'])) {
			$kb_src = getAbsoluteURL($setting['BBS_FIGUREHEAD'], $ptitle_url);
			list($kb_url, $kb_path, $wap_res_kb) = getImageFile($kb_src, $cache_img);
		}
		//背景画像のURLを取得、可能ならば保存する。
		if (isset($setting['BBS_BG_PICTURE'])) {
			$bg_src = getAbsoluteURL($setting['BBS_BG_PICTURE'], $ptitle_url);
			list($bg_url, $bg_path, $wap_res_bg) = getImageFile($bg_src, $cache_img);
		} elseif (isset($setting['BBS_BACKGROUND'])) {
			$bg_src = getAbsoluteURL($setting['BBS_BACKGROUND'], $ptitle_url);
			list($bg_url, $bg_path, $wap_res_bg) = getImageFile($bg_src, $cache_img);
		}

		if (P2Util::isHostMachiBbs($host) || P2Util::isHostJbbsShitaraba($host) || $host == 'be.2ch.net') {
			$setting['BBS_TITLE'] = mb_convert_encoding($setting['BBS_TITLE'], 'SJIS-win', 'eucJP-win');
		}

		$kanban = array('title' => $setting['BBS_TITLE'], 'image' => $kb_url,
			'background' => $bg_url, 'bgcolor' => $setting['BBS_BG_COLOR'], 'info' => false);

	}

	/*----SETTING.TXTがないとき----*/
	elseif ($_exconf['kanban']['nosetting']) {
		$fp = @fopen($ptitle_url, 'rb');
		if (!$fp) { return false; }
		$img_pat = '/<img(?: .+)? src=[\'"]?([^\s\'"<>]+)[\'"]?[^<>]*>/i';
		$bgi_pat = '/<body.+?background=[\'"]?([^\s\'"<>]+)[\'"]?.*?>/i';
		$bgc_pat = '/<body.+?bgcolor=[\'"]?([#\d\w]+)[\'"]?.*?>/i';
		$ttl_pat = '/<title>(.+)<\/title>/i';
		do {
			$line = fgets($fp, 1024);
			//看板のURLを取得、可能ならば保存する。看板URLを取得した時点で読み込み中止。
			if (preg_match($img_pat, $line, $match)) {
				if (strstr($match[1], 'access.pl')) { continue; }
				$kb_src = getAbsoluteURL($match[1], $ptitle_url);
				list($kb_url, $kb_path, $wap_res_kb) = getImageFile($kb_src, $cache_img);
				break;
			}
			//背景画像のURLを取得、可能ならば保存する。
			if (preg_match($bgi_pat, $line, $match)) {
				$bg_src = getAbsoluteURL($match[1], $ptitle_url);
				list($bg_url, $bg_path, $wap_res_bg) = getImageFile($bg_src, $cache_img);
			}
			//背景色を取得。
			if (preg_match($bgc_pat, $line, $match)) {
				$bgcolor = $match[1];
			}
			//板名を取得。
			if (preg_match($ttl_pat, $line, $match)) {
				$title = $match[1];
			}
		} while (!feof($fp));
		fclose($fp);

		if (P2Util::isHostMachiBbs($host) || P2Util::isHostJbbsShitaraba($host) || $host == 'be.2ch.net') {
			$title = mb_convert_encoding($title, 'SJIS-win', 'eucJP-win');
		}

		$kanban = array('title' => $title, 'image' => $kb_url,
			'background' => $bg_url, 'bgcolor' => $bgcolor, 'info' => false);
	}

	/*----SETTING.TXTがなく、ポップアップを返さないとき----*/
	else {
		$kanban = null;
		$popup = null;
		$return_popup = false;
	}

	/*----head.txtがあるとき----*/
	if ($rule_exists) {
		//日付がファイルの最終変更日と異なるとき、head.txtを更新
		if ($rule_lastmod != 0 && date('md', $rule_lastmod) != $today) {
			//ファイルの最終変更日時を変更、同日の更新チェックを避ける
			touch($rule_cache);
			//head.txtの更新をチェック
			$rule_res = &P2Util::fileDownload($rule_src, $rule_file);
			if ($rule_res->code != '304') {
				$cache_img = 0; //更新されていれれば、再取得
			}
		}
		//ローカルルールを取得
		$local_rule = parse_head_txt($rule_file, $rule_cache, $cache_img);
	}

	//板情報を整理
	if (((isset($_GET['mode']) && $_GET['mode'] == 'info') || $_exconf['kanban']['disp_rule'] || $_exconf['kanban']['disp_img_result'] || $_exconf['kanban']['disp_file_result']) && $return_popup !== false) {
		require (P2EX_LIBRARY_DIR . '/kanban_info.inc.php');
	}
	if ($return_popup && !isset($popup)) {
		$popup = makePopUpURL($kanban, $datdir_host, $bbs, $ptitle_url);
	}

	/**/
	//$trace_http_redirect = false;
	/**/

	if ($return_popup) {
		return $popup;
	} else {
		return $kanban;
	}
}


//========================================================================
// getAbsoluteURL -- ページのURLとファイルのURLからファイルの絶対URLを設定する関数
//========================================================================
function getAbsoluteURL($link_url, $page_url)
{
	if (substr($link_url, 0, 7) == 'http://') {
		//$link_urlが絶対URLのとき
		return $link_url;
	} elseif (substr($page_url, 0, 7) == 'http://') {
		//$link_urlが相対URLで、$page_urlが絶対URLのとき
		$root_url = substr($page_url, 0, strpos($page_url, '/', 7));
		$dir_url = substr($page_url, 0, strrpos($page_url, '/'));
		$pdir_url = substr($dir_url, 0, strrpos($dir_url, '/'));
		$gpdir_url = substr($pdir_url, 0, strrpos($pdir_url, '/'));
		if (substr($link_url, 0, 1) == '/') {
			$abs_url = $root_url . $link_url;
		} elseif (substr($link_url, 0, 6) == '../../') {
			$abs_url = $gpdir_url . substr($link_url, 5);
		} elseif (substr($link_url, 0, 3) == '../') {
			$abs_url = $pdir_url . substr($link_url, 2);
		} elseif (substr($link_url, 0, 2) == './') {
			$abs_url = $dir_url . substr($link_url, 1);
		} else {
			$abs_url = $dir_url . '/' . $link_url;
		}
		return $abs_url;
	} else {
		//画像の絶対URLの設定に失敗したとき
		return false;
	}
}

//========================================================================
// getImageFile -- 画像の取得および更新をする関数
//========================================================================
function getImageFile($img_src, $img_cache = 1)
{
	global $datdir, $_exconf;

	$parsed_url = parse_url($img_src);
	$dp = strrpos($parsed_url['path'], '.');
	if (!$dp) { $dp = strlen($parsed_url['path']); }
	if (substr($_exconf['kanban']['savedir'], -1) == '/') { $_exconf['kanban']['savedir'] = substr($_exconf['kanban']['savedir'], 0, -1); }
	if (!is_dir($_exconf['kanban']['savedir'])) {
		$ddp = '/^' . str_replace(array('/', '.'), array('\/', '\.'), $datdir) . '/';
		if (!FileCtl::mkdir_for($_exconf['kanban']['savedir'].'/dummy')) {
			return false;
		}
	}

	$img_path = $_exconf['kanban']['savedir'] . '/' . $parsed_url['host'] . $parsed_url['path'];

	if (preg_match('/\.(gif|jpe?g|png)$/i', $img_src) && ((!file_exists($img_path) && $img_cache == 1) || $img_cache == 2)) {
		FileCtl::mkdir_for($img_path);
		$wap_res = &P2Util::fileDownload($img_src, $img_path, 0); //画像を保存
		$wap_msg = "{$wap_res->code} {$wap_res->message}";
	} else {
		$wap_msg = 'No Renewal';
	}

	if (is_file($img_path)) {
		$img_url = $img_path;
	} else {
		$img_url = $img_src;
	}

	return array($img_url, $img_path, $wap_msg);
}

//========================================================================
// makePopUpURL -- 画像の取得および更新をする関数
//========================================================================
function makePopUpURL($kanban, $datdir_host, $bbs, $ptitle_url)
{
	$popup = rawurlencode(base64_encode(serialize($kanban)));
	$popup = 'kanban.php?popup=' . $popup;
	$popup .= '&amp;datdir_host=' . rawurlencode($datdir_host);
	$popup .= '&amp;bbs=' . rawurlencode($bbs);
	$popup .= '&amp;ptitle_url=' . rawurlencode($ptitle_url);

	return $popup;
}

//========================================================================
// getNoName -- デフォルトの名前を取得する関数
//========================================================================
function getNoName($host, $bbs)
{
	static $nonames = array();

	$id = $host . '/' . $bbs;
	if (isset($nonames[$id])) {
		return $nonames[$id];
	}

	$datdir_host = P2Util::datdirOfHost($host);
	$setting_src = 'http://'.$host.'/'.$bbs.'/SETTING.TXT';
	$setting_file = $datdir_host.'/'.$bbs.'/SETTING.TXT';
	$setting_cache = $datdir_host.'/'.$bbs.'/p2_kb_setting.inc';

	if (file_exists($setting_cache) || file_exists($setting_file)) {
		$setting = parse_setting_txt($setting_file, $setting_cache, 1);
		if (isset($setting['BBS_NONAME_NAME']) && strlen($setting['BBS_NONAME_NAME']) > 0) {
			$nonames[$id] = $setting['BBS_NONAME_NAME'];
			return $setting['BBS_NONAME_NAME'];
		}
	}

	return FALSE;
}

//========================================================================
// parse_setting_txt -- 板設定ファイルをパース、キャッシュする関数
//========================================================================
function parse_setting_txt($setting_file, $setting_cache, $cache_data)
{
	if ($cache_data == 2 || !file_exists($setting_cache)) {
		$setting = array();
		$cache = '';
		// SETTING.TXTを読み込む
		$setting_row = file($setting_file);
		// EUC-SJIS変換
		if (preg_match('{/(2channel/be|\w+\.(machibbs\.com|machi\.to)|jbbs\.(shitaraba\.com|livedoor\.(com|jp)))/}', $setting_file)) {
			mb_convert_variables('SJIS-win', 'UTF-8,eucJP-win,SJIS-win', $setting_row);
		}
		// パース
		foreach ($setting_row as $line) {
			if (strstr($line, '=')) {
				list($key, $value) = explode('=', $line, 2);
				$key = trim($key);
				$value = trim($value);
				$setting[$key] = $value;
				$cache .= "\$setting['{$key}'] = \"" . addslashes($value) . "\";\n";
			}
		}
		// 変数をテキストとして保存
		$fp = @fopen($setting_cache, 'wb');
		if ($fp) {
			fwrite($fp, "<?php\n");
			fwrite($fp, "\$p2_expack_rev = \"");
			fwrite($fp, $GLOBALS['_conf']['p2expack']);
			fwrite($fp, "\";\n");
			fwrite($fp, "\$setting = array();\n");
			fwrite($fp, $cache);
			fwrite($fp, "?>\n");
			fclose($fp);
		}
	} else {
		// テキストから変数を読み込む
		include ($setting_cache);
		// バージョンチェック
		if (!isset($p2_expack_rev) || floatval($p2_expack_rev) < 0) {
			$setting = parse_setting_txt($setting_file, $setting_cache, 2);
		}
	}

	return $setting;
}

//========================================================================
// parse_head_txt -- ローカルルールをダウンロード、パース、キャッシュする関数
//========================================================================
function parse_head_txt($rule_file, $rule_cache, $cache_data)
{
	global $_conf;

	if ($cache_data == 2 || !file_exists($rule_cache)) {
		//head.txtをパース
		$local_rule = implode(' ', array_map('trim', file($rule_file)));

		// EUC-SJIS変換
		if (preg_match('{/(2channel/be|\w+\.(machibbs\.com|machi\.to)|jbbs\.(shitaraba\.com|livedoor\.(com|jp)))/}', $rule_file)) {
			$local_rule = mb_convert_encoding($local_rule, 'SJIS-win', 'eucJP-win');
		}

		//タグの整理
		//タグを小文字に変換。
		$local_rule = preg_replace_callback('/<[\/a-zA-Z\s]+/', 'strtolower_callback', $local_rule);
		//ヘッダタグを消去（たまさば等）
		$local_rule = preg_replace('/^.*<body[^>]*>|<base .+?>|<\/body>.*<\/html>.*$/', '', $local_rule);
		//無効タグの除去
		$local_rule = str_replace('<ahref=', '<a href=', $local_rule);
		$local_rule = strip_tags($local_rule, '<h1><h2><h3><h4><h5><h6><p><div><center><dl><dt><dd><ul><ol><li><br><hr><a><b><i><u><strong><em>');
		//リンクを絶対URLに。
		$local_rule = preg_replace_callback('/<a href=([\w\/\.\?\-+=~@#%&:;"]+)/', 'absurl_callback', $local_rule);


		/* リンクの書き換え ---- ShowThreadPC の transMsg の サブセット */

		// 板サーバ内リンクはp2表示で
		// 2ch bbspink
		// http://choco.2ch.net/test/read.cgi/event/1027770702/
		$local_rule = preg_replace_callback("{<a href=\"http://([^/]+\.(2ch\.net|bbspink\.com))/test/read\.cgi/([^/]+)/([0-9]+)(/)?([^/]+)?\"( target=\"\w+\")?>}", 'link2ch_callback_le', $local_rule);

		// まちBBS / JBBS＠したらば
		// http://kanto.machibbs.com/bbs/read.pl?BBS=kana&KEY=1034515019
		// http://jbbs.shitaraba.com/study/bbs/read.cgi?BBS=389&KEY=1036227774&LAST=100
		$local_rule = preg_replace_callback("{<a href=\"http://([^/]+\.machibbs\.com|[^/]+\.machi\.to|jbbs\.(?:shitaraba\.com|livedoor\.(?:com|jp))(/[^/]+)?)/bbs/read\.(pl|cgi)\?BBS=([^&]+)(&|&amp;)KEY=([0-9]+)((&|&amp;)START=([0-9]+))?((&|&amp;)END=([0-9]+))?[^\"]*\"{$_conf['ext_win_target_at']}>}", 'linkMachi_callback_le', $local_rule);
		$local_rule = preg_replace_callback("{<a href=\"http://(jbbs\.(?:shitaraba\.com|livedoor\.(?:com|jp)))/bbs/read\.cgi/(\w+)/(\d+)/(\d+)/((\d+)?-(\d+)?)?[^\"]*?\"{$_conf['ext_win_target_at']}>(h?t?tp://[^<>]+)</a>}", 'linkJBBS_callback_le', $local_rule);

		// 2chとbbspinkの板
		$local_rule = preg_replace("{<a href=\"http://([^/]+\.(2ch\.net|bbspink\.com))/([^/]+)/\"( target=\"\w+\")?>}", "<a href=\"{$_conf['subject_php']}?host=\\1&amp;bbs=\\3\" target=\"subject\">", $local_rule);

		//2chとbbspinkの過去ログ
		$local_rule = preg_replace_callback("{<a href=\"(http://([^/]+\.(2ch\.net|bbspink\.com))(/[^/]+)?/([^/]+)/kako/\d+(/\d+)?/(\d+)).html\"( target=\"\w+\")?>}", 'link2chkako_callback_le', $local_rule);


		//書式を整える
		//スタイルを無効に
		$local_rule = preg_replace('/ (class|style)=(\'[^\']+\'|"[^"]+"|[^ >]+)/', '', $local_rule);
		//改行の整理
		$local_rule = preg_replace('/<br[^>]*>/', '<br>', $local_rule);
		$local_rule = preg_replace('/<(br|\/p|\/center|\/ul|\/dl)>/', "$0\n", $local_rule);
		$local_rule = preg_replace('/<(li|dt|dd)>/', "\n$0", $local_rule);
		//無駄なホワイトスペースを削除
		$local_rule = trim($local_rule);
		$local_rule = str_replace("\t", ' ', $local_rule);
		$local_rule = preg_replace('/ {2,}/', ' ', $local_rule);
		$local_rule = preg_replace('/(<(h1|h2|h3|h4|h5|h6|p|div|center|dl|dt|dd|ul|ol|li)[^>]*>) /', '$1', $local_rule);
		$local_rule = preg_replace('/ (<\/(h1|h2|h3|h4|h5|h6|p|div|center|dl|dt|dd|ul|ol|li|br|hr)[^>]*>)/', '$1', $local_rule);
		$local_rule = preg_replace('/(\s*\n\s*)+/', "\n", $local_rule);
		while (substr($local_rule, 0, 4) == '<br>') { $local_rule = ltrim(substr($local_rule, 6)); }
		while (substr($local_rule, -4) == '<br>') { $local_rule = rtrim(substr($local_rule, 0, -6)); }

		//変数をテキストとして保存
		$fp = @fopen($rule_cache, 'wb');
		if ($fp) {
			fwrite($fp, "<!-- P2_EXPACK_REV:");
			fwrite($fp, $GLOBALS['_conf']['p2expack']);
			fwrite($fp, " -->\n");
			fwrite($fp, $local_rule);
			fclose($fp);
		}
		$local_rule = str_replace("\n", "\n\t\t", $local_rule);
	} else {
		//テキストに保存した変数を読み込む
		$local_rule = implode("\t\t", file($rule_cache));
		//バージョンチェック
		if (preg_match('/<!-- P2_EXPACK_REV:([\d.]+) -->/', $local_rule, $matches)) {
			if (floatval($matches[0]) < 0) {
				$local_rule = parse_head_txt($rule_file, $rule_cache, 2);
			}
		} else {
			$local_rule = parse_head_txt($rule_file, $rule_cache, 2);
		}
	}
	if ($local_rule) { $local_rule = "\n\t\t" . $local_rule . "\n\t"; }

	return $local_rule;
}

//========================================================================
//コールバックメソッド（ShowThreadPCのコールバックメソッドのサブセット＋α）
//========================================================================

//小文字にする
function strtolower_callback($s)
{
	return strtolower($s[0]);
}

//リンクの先頭が"/", "./", "../"のとき
function absurl_callback($s)
{
	global $ptitle_url;

	$s[1] = str_replace('"', '', $s[1]);
	$abs_url = getAbsoluteURL($s[1], $ptitle_url);

	return '<a href="' . $abs_url . '"';
}

//2ch bbspink 内リンク===========================
function link2ch_callback_le($s)
{
	global $_conf;

	$read_url = "{$_conf['read_php']}?host={$s[1]}&amp;bbs={$s[3]}&amp;key={$s[4]}";
	if (isset($s[6])) {
		$read_url .= "&amp;ls={$s[6]}";
	}

	return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>";
}

// まちBBS / JBBS＠したらば  内リンク===========================
function linkMachi_callback_le($s)
{
	global $_conf;

	return "<a href=\"{$_conf['read_php']}?host={$s[1]}&amp;bbs={$s[4]}&amp;key={$s[6]}&amp;ls={$s[9]}-{$s[12]}\"{$_conf['bbs_win_target_at']}>";
}

// JBBS＠したらば  内リンク===========================
function linkJBBS_callback_le($s)
{
	global $_conf;

	return "<a href=\"{$_conf['read_php']}?host=jbbs.livedoor.jp%2F{$s[2]}&amp;bbs={$s[3]}&amp;key={$s[4]}&amp;ls={$s[5]}\"{$_conf['bbs_win_target_at']}>{$s[8]}</a>";
}

// 2ch過去ログhtml =============================
function link2chkako_callback_le($s)
{
	global $_conf;

	$kakolog_uri = $s[1];
	$kakolog_uri_en = urlencode($kakolog_uri);
	$host = $s[2]; $bbs = $s[5]; $key = $s[7];
	$read_url = "{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}&amp;kakolog={$kakolog_uri_en}";

	return "<a href=\"{$read_url}\"{$_conf['bbs_win_target_at']}>";
}

?>
