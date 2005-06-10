<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - 登録したRSSをメニューに表示

require_once (P2EX_LIBRARY_DIR . '/rss/common.inc.php');

if ($_conf['ktai']) {
    print_rss_list_k();
} else {
    print_rss_list();
}

/**
 * 登録されているRSS一覧を表示
 */
function print_rss_list()
{
    global $_conf, $_exconf;

    echo "<div class=\"menu_cate\">\n";
    echo "<b class=\"menu_cate\" onclick=\"showHide('c_rss');\">RSS</b>\n";
    echo "[<a href=\"editrss.php\" target=\"subject\">編集</a>]\n";

    // RSS切り替え
    if ($_exconf['etc']['multi_favs']) {
        echo "<br>\n";
        echo FavSetManager::makeFavSetSwitchElem('m_rss_set', 'RSS', TRUE, "replaceMenuItem('c_rss', 'm_rss_set', this.options[this.selectedIndex].value);");
    }

    echo "\t<div class=\"itas\" id=\"c_rss\">\n";

    if ($rss_list = @file($_conf['rss_file'])) {
        foreach ($rss_list as $rss_info) {
            $rss_info = rtrim($rss_info);
            $p = explode("\t", $rss_info);
            if (count($p) > 1) {
                $site = $p[0];
                $xml  = $p[1];
                if (!empty($p[2])) {
                    $atom = 1;
                    $atom_q = '&amp;atom=1';
                } else {
                    $atom = 0;
                    $atom_q = '';
                }
                $site_en = rawurlencode(base64_encode($site));
                $xml_en  = rawurlencode($xml);
                $mtime   = filemtime(rss_get_save_path($xml));
                $rss_q = 'xml=' . $xml_en . '&amp;site_en=' . $site_en .$atom_q . '&amp;mt=' . $mtime;
                echo "\t　<a href=\"subject_rss.php?{$rss_q}\">{$site}</a><br>\n";
                flush();
            }
        }
    } else {
        echo "\t\t　（空っぽ）\n";
    }

    echo "\t</div>\n";
    echo "</div>\n";
    flush();

}

/**
 * 登録されているRSS一覧を表示（携帯用）
 */
function print_rss_list_k()
{
    global $_conf, $_exconf;

    $pageTitle = ($_exconf['etc']['multi_favs']) ? FavSetManager::getFavSetPageTitleHt('m_rss_set', 'RSS') : 'RSS';
    echo $pageTitle;
    echo '<hr>';

    $i = 1;
    if ($rss_list = @file($_conf['rss_file'])) {
        foreach ($rss_list as $rss_info) {
            $rss_info = rtrim($rss_info);
            $p = explode("\t", $rss_info);
            if (count($p) > 1) {
                $site = $p[0];
                $xml  = $p[1];
                if (!empty($p[2])) {
                    $atom = 1;
                    $atom_q = '&amp;atom=1';
                } else {
                    $atom = 0;
                    $atom_q = '';
                }
                if ($i <= 9) {
                    $access_at = " {$_conf['accesskey']}={$i}";
                    $key_num_st = "$i ";
                } else {
                    $access_at = '';
                    $key_num_st = '';
                }
                $site_en = rawurlencode(base64_encode($site));
                $xml_en = rawurlencode($xml);
                $mtime   = filemtime(rss_get_save_path($xml));
                $rss_q = 'xml=' . $xml_en . '&amp;site_en=' . $site_en . $atom_q . '&amp;mt=' . $mtime;
                echo "{$key_num_st}<a href=\"subject_rss.php?{$rss_q}\"{$access_at}>{$site}</a><br>\n";
                $i++;
            }
        }
    }

}

?>
