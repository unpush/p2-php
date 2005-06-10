<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

//板設定ファイル
if (file_exists($setting_file)) {
	$setting_lastmod = date('Y/m/d H:i:s', filemtime($setting_file));
	if (isset($setting_res)) {
		$setting_exists = "{$setting_res->code} {$setting_res->message}";
	} elseif (!isset($setting_exists) || $setting_exists === TRUE) {
		$setting_exists = 'No Renewal';
	}
	if (!isset($setting)) {
		//板設定情報を取得
		$setting = parse_setting_txt($setting_file, $setting_cache, $_exconf['kanban']['cache']);
	}
} else {
	$setting_exists = 'Not Found';
	$setting_lastmod = NULL;
}

//ローカルルール
if (file_exists($rule_file)) {
	$rule_lastmod = date('Y/m/d H:i:s', filemtime($rule_file));
	if (isset($rule_res)) {
		$rule_exists = "{$rule_res->code} {$rule_res->message}";
	} elseif (!isset($rule_exists) || $rule_exists === TRUE) {
		$rule_exists = 'No Renewal';
	}
	if (!$local_rule) {
		//ローカルルールを取得
		$local_rule = parse_head_txt($rule_file, $rule_cache, $_exconf['kanban']['cache']);
	}
} else {
	$rule_exists = 'Not Found';
	$rule_lastmod = NULL;
}
if (!$local_rule) { $_exconf['kanban']['disp_rule'] = 0; } //head.txtが空か、存在しない場合はローカルルールを表示しない

//看板と背景画像
if ($kb_src == $kb_url) {
	$kb_exists = "未取得（{$kb_src}）";
	$kb_lastmod = NULL;
} else {
	$kb_exists = '取得済（' . basename($kb_path) . '）';
	$kb_lastmod = date('Y/m/d H:i:s', filemtime($kb_path));
}
if ($bg_src == $bg_url) {
	$bg_exists = "未取得（{$bg_src}）";
	$bg_lastmod = NULL;
} else {
	$bg_exists = '取得済（' . basename($bg_path) . '）';
	$bg_lastmod = date('Y/m/d H:i:s', filemtime($bg_path));
}

if (isset($_GET['mode']) && $_GET['mode'] == 'info') {
	//ログ(dat)の数をカウントする。また指示があった場合、ログ(dat,idx)の削除も。
	$dats = 0;
	$dirObj = dir($datdir_bbs);
	while (($ent = $dirObj->read()) !== FALSE) {
		$file = $datdir_bbs . '/' . $ent;
		if (preg_match('/^(\d+)\.(dat(\.gz)?|idx)$/i', $ent, $matches)) {
			$pdat = $datdir_bbs . '/p2_parsed_dat/' . $matches[1] . '.pdat';
			if (!empty($_GET['remove_all_dat'])) {
				unlink($file);
				if (file_exists($pdat)) {
					unlink($pdat);
				}
			} elseif (!empty($_GET['remove_old_dat']) && ((time() - filemtime($file)) > (30 * 86400))) {
				unlink($file);
				if (file_exists($pdat)) {
					unlink($pdat);
				}
			} elseif (preg_match('/^\d+\.dat(\.gz)?$/i', $ent)) {
				$dats++;
			}
		} elseif (preg_match('/\.(gif|jpe?g|png)$/i', $ent)) {
			unlink($file); //旧看板ポップアップ・キャッシュを削除
		}
	}

	//dat削除の確認ウインドウを作成
	global $o_link;
	$onclick_mode = 'info';
	$link = str_replace('{#mode#}', $onclick_mode, $o_link);
	$onclick_rmall = "if (confirm('本当に &quot;{$kanban['title']}&quot; の全ログを削除してよろしいですか？')) location.href='{$link}&amp;remove_all_dat=1';";
	$remove_all = "<a href=\"javascript:;\" onclick=\"{$onclick_rmall}\">全てのログを削除</a>";
	$onclick_rmold = "if (confirm('本当に &quot;{$kanban['title']}&quot; の古いログを削除してよろしいですか？')) location.href='{$link}&amp;remove_old_dat=1';";
	$remove_old = "<a href=\"javascript:;\" onclick=\"{$onclick_rmold}\">古いログを削除</a>";

	//dat数と削除リンク
	$dats = strval($dats) . " <small>[{$remove_all}] [{$remove_old}]</small>";

	//詳細情報
	$kanban_info = array(
		'板情報' => array('板名' => $kanban['title'], '板URL' => $ptitle_url,
			'ログ保存先' => realpath($datdir_bbs) . '/', 'ログ取得済<br>スレッド数' => $dats,
			'看板' => $kb_exists, '背景画像' => $bg_exists, '背景色' => $kanban['bgcolor']),
		'ローカルルール' => $local_rule, '板の設定' => $setting,
		'看板ソースURL' => $kb_src, '看板リンクURL' => $kb_url,
		'背景ソースURL' => $bg_src, '背景リンクURL' => $bg_url,
		'看板Cache更新' => $wap_res_kb, '〃 確認日時' => $kb_lastmod,
		'背景Cache更新' => $wap_res_bg, ' 〃 確認日時' => $bg_lastmod,
		'SETTING.TXT更新' => $setting_exists, '  〃 確認日時' => $setting_lastmod,
		'head.txt更新' => $rule_exists, '   〃 確認日時' => $rule_lastmod);
} else {
	if ($_GET['mode'] == 'delete' || $_GET['mode'] == 'reload') {
		$_exconf['kanban']['disp_rule'] = 0;
	}
	$kanban_info = array();
	//ローカルルール
	if ($_exconf['kanban']['disp_rule']) {
		if ($_exconf['kanban']['disp_img_result'] || $_exconf['kanban']['disp_file_result']) {
			$kanban_info['ローカルルール'] = $local_rule;
		} else {
			$kanban_info = array($local_rule); //単独表示のとき
		}
	}
	//画像キャッシュの更新に関わる情報
	if ($_exconf['kanban']['disp_img_result']) {
		$kanban_info['看板ソースURL'] = $kb_src;
		$kanban_info['看板リンクURL'] = $kb_url;
		$kanban_info['背景ソースURL'] = $bg_src;
		$kanban_info['背景リンクURL'] = $bg_url;
		$kanban_info['看板Cache更新'] = $wap_res_kb;
		$kanban_info['〃 確認日時'] = $kb_lastmod;
		$kanban_info['背景Cache更新'] = $wap_res_bg;
		$kanban_info[' 〃 確認日時'] = $bg_lastmod;
	}
	//設定ファイルの更新に関わる情報
	if ($_exconf['kanban']['disp_file_result']) {
		$kanban_info['SETTING.TXT更新'] = $setting_exists;
		$kanban_info['  〃 確認日時'] = $setting_lastmod;
		$kanban_info['head.txt更新'] = $rule_exists;
		$kanban_info['   〃 確認日時'] = $rule_lastmod;
	}
}

// GETで渡せる長さは Apache 1.3.9 では 8190 文字まで。
// それを越えると 414 Request-URI Too Large となるのでチェックする。
if ($return_popup && count($kanban_info) > 0) {
	$popup_test_array = $kanban;
	$popup_test_array['info'] = $kanban_info;
	$popup = makePopUpURL($popup_test_array, $datdir_host, $bbs, $ptitle_url);
	if (strlen($popup) > 8000) {
		$popup = makePopUpURL($kanban, $datdir_host, $bbs, $ptitle_url);
	}
}
?>
