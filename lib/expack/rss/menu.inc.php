<?php
/**
 * rep2expack - 登録したRSSをメニューに表示
 */

require_once P2EX_LIB_DIR . '/rss/common.inc.php';

if ($_conf['ktai']) {
    print_rss_list_k();
} else {
    print_rss_list();
}

// {{{ print_rss_list()

/**
 * 登録されているRSS一覧を表示
 */
function print_rss_list()
{
    global $_conf;

    echo "<div class=\"menu_cate\">\n";
    echo "<b class=\"menu_cate\" onclick=\"showHide('c_rss');\">RSS</b>\n";
    echo "[<a href=\"editrss.php\" target=\"subject\">編集</a>]\n";

    // RSS切り替え
    if ($_conf['expack.misc.multi_favs']) {
        echo "<br>\n";
        echo FavSetManager::makeFavSetSwitchElem('m_rss_set', 'RSS', TRUE, "replaceMenuItem('c_rss', 'm_rss_set', this.options[this.selectedIndex].value);");
    }

    echo "\t<div class=\"itas\" id=\"c_rss\">\n";

    if ($rss_list = FileCtl::file_read_lines($_conf['expack.rss.setting_path'], FILE_IGNORE_NEW_LINES)) {
        foreach ($rss_list as $rss_info) {
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
                    echo "\t　" . $site . ' ' . $localpath->getMessage() . "<br>\n";
                } else {
                    $mtime   = file_exists($localpath) ? filemtime($localpath) : 0;
                    $site_en = rawurlencode(base64_encode($site));
                    $xml_en = rawurlencode($xml);
                    $rss_q = sprintf('?xml=%s&site_en=%s%s&mt=%d', $xml_en, $site_en, $atom_q, $mtime);
                    $rss_q_ht = htmlspecialchars($rss_q, ENT_QUOTES);
                    echo "\t　<a href=\"subject_rss.php{$rss_q_ht}\">{$site}</a><br>\n";
                }
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

// }}}
// {{{ print_rss_list_k()

/**
 * 登録されているRSS一覧を表示（携帯用）
 */
function print_rss_list_k()
{
    global $_conf;

    $pageTitle = ($_conf['expack.misc.multi_favs']) ? FavSetManager::getFavSetPageTitleHt('m_rss_set', 'RSS') : 'RSS';
    echo $pageTitle;
    echo '<hr>';

    $i = 1;
    if ($rss_list = FileCtl::file_read_lines($_conf['expack.rss.setting_path'], FILE_IGNORE_NEW_LINES)) {
        foreach ($rss_list as $rss_info) {
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
                    $accesskey_at = $_conf['k_accesskey_at'][$i];
                    $accesskey_st = "{$i} ";
                } else {
                    $accesskey_at = '';
                    $accesskey_st = '';
                }
                $localpath = rss_get_save_path($xml);
                if (PEAR::isError($localpath)) {
                    echo $accesskey_st . $site . ' ' . $localpath->getMessage() . "<br>\n";
                } else {
                    $mtime   = file_exists($localpath) ? filemtime($localpath) : 0;
                    $site_en = rawurlencode(base64_encode($site));
                    $xml_en = rawurlencode($xml);
                    $rss_q = sprintf('?xml=%s&site_en=%s%s&mt=%d', $xml_en, $site_en, $atom_q, $mtime);
                    $rss_q_ht = htmlspecialchars($rss_q, ENT_QUOTES);
                    echo "{$accesskey_st}<a href=\"subject_rss.php{$rss_q_ht}\"{$accesskey_at}>{$site}</a><br>\n";
                }
                $i++;
            }
        }
    }

}

// }}}

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
