<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
	p2 - BrdCtl -- 板リストコントロールクラス for menu.php
*/

require_once (P2_LIBRARY_DIR . '/p2util.class.php');
require_once (P2_LIBRARY_DIR . '/filectl.class.php');
require_once (P2_LIBRARY_DIR . '/brdmenu.class.php');

/**
* 板リスト コントロール クラス
*/
class BrdCtl{

	/**
	 * boardを全て読み込む
	 */
	function read_brds()
	{
		$brd_menus_dir = BrdCtl::read_brd_dir();
		$brd_menus_online = BrdCtl::read_brd_online();
		if (is_array($brd_menus_dir) && is_array($brd_menus_online)) {
			$brd_menus = array_merge($brd_menus_dir, $brd_menus_online);
		} elseif (is_array($brd_menus_dir)) {
			$brd_menus = $brd_menus_dir;
		} elseif (is_array($brd_menus_online)) {
			$brd_menus = $brd_menus_online;
		} else {
			$brd_menus = array();
		}
		return $brd_menus;
	}

	/**
	 * boardディレクトリを走査して読み込む
	 */
	function read_brd_dir()
	{
		global $_info_msg_ht;

		$brd_menus = array();
		$brd_dir = './board';

		if (is_dir($brd_dir) && ($cdir = dir($brd_dir))) {
			// ディレクトリ走査
			while ($entry = $cdir->read()) {
				if (preg_match('/^\./', $entry)) {
					continue;
				}
				$filepath = $brd_dir.'/'.$entry;
				if ($data = file($filepath)) {
					$aBrdMenu = &new BrdMenu;	// クラス BrdMenu のオブジェクトを生成
					$aBrdMenu->setBrdMatch($filepath);	// パターンマッチ形式を登録
					$aBrdMenu->setBrdList($data);	// カテゴリーと板をセット
					$brd_menus[] = &$aBrdMenu;
				} else {
					$_info_msg_ht .= "<p>p2 error: 板リスト {$entry} が読み込めませんでした。</p>\n";
				}
			}
			$cdir->close();
		}

		return $brd_menus;
	}

	/**
	 * オンライン板リストを読込む
	 */
	function read_brd_online()
	{
		global $_conf, $_info_msg_ht;

		$brd_menus = array();

		if ($_conf['brdfile_online']) {
			$cachefile = P2Util::cacheFileForDL($_conf['brdfile_online']);

			$noDL = false;
			$isNewDL = false;
			$read_html_flag = false;

			// キャッシュがある場合
			if (file_exists($cachefile.'.p2.brd')) {
				// norefreshならDLしない
				if (!empty($_GET['nr'])) {
					$noDL = true;
				// キャッシュの更新が指定時間以内ならDLしない
				} elseif (@filemtime($cachefile.'.p2.brd') > time() - 60 * 60 * $_conf['menu_dl_interval']) {
					$noDL = true;
				}
			}

			// DLしない
			if ($noDL ) {
				;
			// DLする
			} else {
				//echo 'DL!<br>';//
				$brdfile_online_res = &P2Util::fileDownload($_conf['brdfile_online'], $cachefile);
				if ($brdfile_online_res->is_success() && $brdfile_online_res->code != '304') {
					$isNewDL = true;
				}
			}

			// html形式なら
			if (preg_match('/html?$/', $_conf['brdfile_online'])) {

				// 更新されていたら新規キャッシュ作成
				if ($isNewDL) {
					//echo 'NEW!<br>';//
					$aBrdMenu = &new BrdMenu; // クラス BrdMenu のオブジェクトを生成
					$aBrdMenu->makeBrdFile($cachefile); // .p2.brdファイルを生成
					$brd_menus[] = &$aBrdMenu;
					$read_html_flag = true;
				}

				if (file_exists($cachefile.'.p2.brd')) {
					$cashe_brd = $cachefile.'.p2.brd';
				} else {
					$cashe_brd = $cachefile;
				}

			} else {
				$cashe_brd = $cachefile;
			}

			if (!$read_html_flag) {
				if ($data = @file($cashe_brd)) {
					$aBrdMenu = &new BrdMenu; // クラス BrdMenu のオブジェクトを生成
					$aBrdMenu->setBrdMatch($cashe_brd); // パターンマッチ形式を登録
					$aBrdMenu->setBrdList($data); // カテゴリーと板をセット
					if ($aBrdMenu->num) {
						$brd_menus[] = &$aBrdMenu;
					} else {
						$_info_msg_ht .=  "<p>p2 エラー: {$cashe_brd} から板メニューを生成することはできませんでした。</p>\n";
					}
				} else {
					$_info_msg_ht .=  "<p>p2 エラー: {$cachefile} は読み込めませんでした。</p>\n";
				}
			}

		}

		return $brd_menus;
	}

}

?>
