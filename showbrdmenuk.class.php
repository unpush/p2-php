<?php

require_once './p2util.class.php';	// p2用のユーティリティクラス

/**
 *	p2 - ボードメニューを表示する クラス(携帯)
 */
class ShowBrdMenuK{

	var $cate_id; // カテゴリーID
	
	/**
	 * コンストラクタ
	 */
	function ShowBrdMenuK()
	{
		$this->cate_id = 1;
	}

	/**
	 * ■板メニューカテゴリをプリントする for 携帯
	 */
	function printCate($categories)
	{
		global $_conf, $list_navi_ht;

		if ($categories) {
			
			// 表示数制限====================
			if ($_GET['from']) {
				$list_disp_from = $_GET['from'];
			} else {
				$list_disp_from = 1;
			}
			$list_disp_all_num = sizeof($categories);
			$disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['k_sb_disp_range'], $list_disp_all_num);
		
			if ($disp_navi['from'] > 1) {
				$mae_ht = <<<EOP
<a href="menu_k.php?view=cate&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['prev']}">{$_conf['k_accesskey']['prev']}.前</a>
EOP;
			}
			if ($disp_navi['end'] < $list_disp_all_num) {
				$tugi_ht = <<<EOP
<a href="menu_k.php?view=cate&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['next']}">{$_conf['k_accesskey']['next']}.次</a>
EOP;
			}
			
			if (!$disp_navi['all_once']) {
				$list_navi_ht = <<<EOP
{$disp_navi['range_st']}{$mae_ht} {$tugi_ht}<br>
EOP;
			}
						
			foreach ($categories as $cate) {
				if ($this->cate_id >= $disp_navi['from'] and $this->cate_id <= $disp_navi['end']) {
					echo "<a href=\"menu_k.php?cateid={$this->cate_id}&amp;nr=1{$_conf['k_at_a']}\">{$cate->name}</a>($cate->num)<br>\n";//$this->cate_id
				}
				$this->cate_id++;
			}
		}
	}

	/**
	 * 板メニューカテゴリの板をプリントする for 携帯
	 */
	function printIta($categories)
	{
		global $_conf, $list_navi_ht;

		if ($categories) {

			foreach ($categories as $cate) {
				if ($cate->num > 0) {
					if($this->cate_id == $_GET['cateid']){
					
						echo "{$cate->name}<hr>\n";

	
						// 表示数制限 ====================
						if ($_GET['from']) {
							$list_disp_from = $_GET['from'];
						} else {
							$list_disp_from = 1;
						}
						$list_disp_all_num = $cate->num;
						$disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['k_sb_disp_range'], $list_disp_all_num);
				
						if ($disp_navi['from'] > 1) {
							$mae_ht = <<<EOP
<a href="menu_k.php?cateid={$this->cate_id}&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}">前</a>
EOP;
						}
						if ($disp_navi['end'] < $list_disp_all_num) {
							$tugi_ht = <<<EOP
<a href="menu_k.php?cateid={$this->cate_id}&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}">次</a>
EOP;
						}
						
						if (!$disp_navi['all_once']) {
							$list_navi_ht = <<<EOP
{$disp_navi['range_st']}{$mae_ht} {$tugi_ht}<br>
EOP;
						}


						$i = 0;
						foreach ($cate->menuitas as $mita) {
							$i++;
							if ($i <= 9) {
								$access_num_st = "$i.";
								$akey_at = " {$_conf['accesskey']}=\"{$i}\"";
							} else {
								$access_num_st = "";
								$akey_at = "";
							}
							// 板名プリント
							if ($i >= $disp_navi['from'] and $i <= $disp_navi['end']) {
								echo "<a href=\"{$_SERVER['PHP_SELF']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}&amp;setfavita=1&amp;view=favita{$_conf['k_at_a']}\">+</a> <a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}{$_conf['k_at_a']}\"{$akey_at}>{$access_num_st}{$mita->itaj_ht}</a><br>\n";
							}
						}
					
					}
				}
				$this->cate_id++;
			}
		}
	}

	/**
	 * 板名を検索してプリントする for 携帯
	 */
	function printItaSearch($categories)
	{
		global $_conf, $_info_msg_ht, $word, $mikke;
		global $list_navi_ht;
	
		if ($categories) {
		
			// 表示数制限 ====================
			if ($_GET['from']) {
				$list_disp_from = $_GET['from'];
			} else {
				$list_disp_from = 1;
			}
			$list_disp_all_num = $mikke; //
			$disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['k_sb_disp_range'], $list_disp_all_num);
		
			if ($disp_navi['from'] > 1) {
				$mae_ht = <<<EOP
<a href="menu_k.php?word={$word}&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}">前</a>
EOP;
			}
			if ($disp_navi['end'] < $list_disp_all_num) {
				$tugi_ht = <<<EOP
<a href="menu_k.php?word={$word}&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}">次</a>
EOP;
			}
			
			if (!$disp_navi['all_once']) {
				$list_navi_ht = <<<EOP
{$disp_navi['range_st']}{$mae_ht} {$tugi_ht}<br>
EOP;
			}
	
			$i = 0;
			foreach ($categories as $cate) {
				if ($cate->num > 0) {

					$t = false;
					foreach ($cate->menuitas as $mita) {
						$i++;
						if ($i >= $disp_navi['from'] and $i <= $disp_navi['end']) {
							if (!$t) {
								echo "<b>{$cate->name}</b><br>\n";
							}
							$t = true;
							echo "　<a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}{$_conf['k_at_a']}\">{$mita->itaj_ht}</a><br>\n";
						}
					}

				}
				$this->cate_id++;
			}
		}
	}

	/**
	 * お気に板をプリントする for 携帯
	 */
	function print_favIta()
	{
		global $_conf;
		
		$show_flag = false;
		
		$lines = @file($_conf['favita_path']); // favita読み込み
		if ($lines) {
			echo "お気に板<hr>";
			$i = 0;
			foreach ($lines as $l) {
				$i++;
				$l = rtrim($l);
				if (preg_match("/^\t?(.+)\t(.+)\t(.+)$/", $l, $matches)) {
					$itaj = rtrim($matches[3]);
					$itaj_en = rawurlencode(base64_encode($itaj));
					if ($i <= 9) {
						$access_at = " {$_conf['accesskey']}={$i}";
						$key_num_st = "$i.";
					} else {
						$access_at = "";
						$key_num_st = "";
					}
					echo <<<EOP
	<a href="{$_conf['subject_php']}?host={$matches[1]}&amp;bbs={$matches[2]}&amp;itaj_en={$itaj_en}{$_conf['k_at_a']}"{$access_at}>{$key_num_st}{$matches[3]}</a> [<a href="{$_SERVER['PHP_SELF']}?host={$matches[1]}&amp;bbs={$matches[2]}&amp;setfavita=0&amp;view=favita{$_conf['k_at_a']}">削</a>]<br>
EOP;
					$show_flag = true;
				}
			}
		}
		
		if (empty($show_flag)) {
			echo "<p>お気に板はまだないようだ</p>";
		}
	}


}
?>
