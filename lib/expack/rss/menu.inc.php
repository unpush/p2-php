<?php
/**
 * rep2expack - ìoò^ÇµÇΩRSSÇÉÅÉjÉÖÅ[Ç…ï\é¶
 */

require_once P2EX_LIBRARY_DIR . '/rss/common.inc.php';

if ($_conf['ktai']) {
    print_rss_list_k();
} else {
    print_rss_list();
}

/**
 * ìoò^Ç≥ÇÍÇƒÇ¢ÇÈRSSàÍóóÇï\é¶
 */
function print_rss_list()
{
    global $_conf;

    echo "<div class=\"menu_cate\">\n";
    echo "<b class=\"menu_cate\" onclick=\"showHide('c_rss');\">RSS</b>\n";
    echo "[<a href=\"editrss.php\" target=\"subject\">ï“èW</a>]\n";

    // RSSêÿÇËë÷Ç¶
    if ($_conf['expack.rss.set_num'] > 0) {
        echo "<br>\n";
        echo FavSetManager::makeFavSetSwitchElem('m_rss_set', 'RSS', true, "replaceMenuItem('c_rss', 'm_rss_set', this.options[this.selectedIndex].value);");
    }

    echo "\t<div class=\"itas\" id=\"c_rss\">\n";

    if ($rss_list = @file($_conf['expack.rss.setting_path'])) {
        foreach ($rss_list as $rss_info) {
            $rss_info = rtrim($rss_info);
            $p = explode("\t", $rss_info);
            if (count($p) > 1) {
                $site = $p[0];
                $xml  = $p[1];
                if (!empty($p[2])) {
                    $atom = 1;
                    $atom_q = '&atom=1';
                } else {
                    $atom = 0;
                    $atom_q = '';
                }
                $localpath = rss_get_save_path($xml);
                if (PEAR::isError($localpath)) {
                    echo "\tÅ@" . $site . ' ' . $localpath->getMessage() . "<br>\n";
                } else {
                    $mtime   = file_exists($localpath) ? filemtime($localpath) : 0;
                    $site_en = rawurlencode(base64_encode($site));
                    $xml_en = rawurlencode($xml);
                    $rss_q = sprintf('?xml=%s&site_en=%s%s&mt=%d', $xml_en, $site_en, $atom_q, $mtime);
                    $rss_q_ht = htmlspecialchars($rss_q, ENT_QUOTES);
                    echo "\tÅ@<a href=\"subject_rss.php{$rss_q_ht}\">{$site}</a><br>\n";
                }
                flush();
            }
        }
    } else {
        echo "\t\tÅ@ÅiãÛÇ¡Ç€Åj\n";
    }

    echo "\t</div>\n";
    echo "</div>\n";
    flush();

}

/**
 * ìoò^Ç≥ÇÍÇƒÇ¢ÇÈRSSàÍóóÇï\é¶Åiågë—ópÅj
 */
function print_rss_list_k()
{
    global $_conf;

    $pageTitle = ($_conf['expack.favset.enabled'] && $_conf['expack.rss.set_num'] > 0)
        ? FavSetManager::getFavSetPageTitleHt('m_rss_set', 'RSS') : 'RSS';
    echo $pageTitle;
    echo '<hr>';

    $i = 1;
    if ($rss_list = @file($_conf['expack.rss.setting_path'])) {
        foreach ($rss_list as $rss_info) {
            $rss_info = rtrim($rss_info);
            $p = explode("\t", $rss_info);
            if (count($p) > 1) {
                $site = $p[0];
                $xml  = $p[1];
                if (!empty($p[2])) {
                    $atom = 1;
                    $atom_q = '&atom=1';
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
                $localpath = rss_get_save_path($xml);
                if (PEAR::isError($localpath)) {
                    echo $key_num_st . $site . ' ' . $localpath->getMessage() . "<br>\n";
                } else {
                    $mtime   = file_exists($localpath) ? filemtime($localpath) : 0;
                    $site_en = rawurlencode(base64_encode($site));
                    $xml_en = rawurlencode($xml);
                    $rss_q = sprintf('?xml=%s&site_en=%s%s&mt=%d', $xml_en, $site_en, $atom_q, $mtime);
                    $rss_q_ht = htmlspecialchars($rss_q, ENT_QUOTES);
                    echo "{$key_num_st}<a href=\"subject_rss.php{$rss_q_ht}\"{$access_at}>{$site}</a><br>\n";
                }
                $i++;
            }
        }
    }

}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
