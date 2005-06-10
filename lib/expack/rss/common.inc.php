<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2機能拡張パック - RSSユーティリティ関数

/**
 * RSSのURLからローカルに保存するファイルのパスを設定する
 */
function rss_get_save_path($remotefile)
{
	global $datdir;
	static $done = array();

	if (isset($done[$remotefile])) {
		return $done[$remotefile];
	}

	$pURL = @parse_url($remotefile);
	if (!$pURL || !isset($pURL['scheme']) || $pURL['scheme'] != 'http' || !isset($pURL['host'])) {
		$_info_msg_ht = ' <p>p2 error: 不正なRSSのURL (' . htmlspecialchars($remotefile) . ')</p>';
		return ($done[$remotefile] = FALSE);
	}

	$localname = '';
	if (isset($pURL['path']) && $pURL['path'] !== '') {
		$localname .= preg_replace('/[^\w.]/', '_', substr($pURL['path'], 1));
	}
	if (isset($pURL['query']) && $pURL['query'] !== '') {
		$localname .= '_' . preg_replace('/[^\w.%]/', '_', $pURL['query']) . '.rdf';
	}

	// 拡張子.cgiや.php等で保存するのを防ぐ
	if (!preg_match('/\.(rdf|rss|xml)$/i', $localname)) {
		// 古いバージョンで取得したRSSを削除
		if (file_exists($localname)) {
			@unlink($localname);
		}
		// 拡張子.rdfを付加
		$localname .= '.rdf';
	}
	// dotFileが作られるのを防ぐ
	if (substr($localname, 0, 1) == '.') {
		$localname = '_' . $localname;
	}

	$done[$remotefile] = $localpath = $datdir . '/p2_rss/' . $pURL['host'] . '/' . $localname;

	return $localpath;
}

?>
