<?php
// {{{ ShowBrdMenuK

/**
 * rep2 - ボードメニューを表示する クラス(携帯)
 */
class ShowBrdMenuK
{
    // {{{ properties

    private $_cate_id; // カテゴリーID

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->_cate_id = 1;
    }

    // }}}
    // {{{ printCate()

    /**
     * ■板メニューカテゴリをプリントする for 携帯
     */
    public function printCate(array $categories)
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
            $disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['mobile.sb_disp_range'], $list_disp_all_num);

            if ($disp_navi['from'] > 1) {
                $mae_ht = <<<EOP
<a href="menu_k.php?view=cate&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}"{$_conf['k_accesskey_at']['prev']}>{$_conf['k_accesskey_st']['prev']}前</a>
EOP;
            }
            if ($disp_navi['end'] < $list_disp_all_num) {
                $tugi_ht = <<<EOP
<a href="menu_k.php?view=cate&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}"{$_conf['k_accesskey_at']['next']}>{$_conf['k_accesskey_st']['next']}次</a>
EOP;
            }

            if (!$disp_navi['all_once']) {
                $list_navi_ht = "{$disp_navi['range_st']}{$mae_ht} {$tugi_ht}<br>";
            }

            foreach ($categories as $cate) {
                if ($this->_cate_id >= $disp_navi['from'] and $this->_cate_id <= $disp_navi['end']) {
                    echo "<a href=\"menu_k.php?cateid={$this->_cate_id}&amp;nr=1{$_conf['k_at_a']}\">{$cate->name}</a>($cate->num)<br>\n";//$this->_cate_id
                }
                $this->_cate_id++;
            }
        }
    }

    // }}}
    // {{{ printIta()

    /**
     * 板メニューカテゴリの板をプリントする for 携帯
     */
    public function printIta(array $categories)
    {
        global $_conf, $list_navi_ht;

        if ($categories) {

            foreach ($categories as $cate) {
                if ($cate->num > 0) {
                    if($this->_cate_id == $_GET['cateid']){

                        echo "{$cate->name}<hr>\n";

                        // 表示数制限 ====================
                        if ($_GET['from']) {
                            $list_disp_from = $_GET['from'];
                        } else {
                            $list_disp_from = 1;
                        }
                        $list_disp_all_num = $cate->num;
                        $disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['mobile.sb_disp_range'], $list_disp_all_num);

                        if ($disp_navi['from'] > 1) {
                            $mae_ht = <<<EOP
<a href="menu_k.php?cateid={$this->_cate_id}&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}">前</a>
EOP;
                        }
                        if ($disp_navi['end'] < $list_disp_all_num) {
                            $tugi_ht = <<<EOP
<a href="menu_k.php?cateid={$this->_cate_id}&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}">次</a>
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
                                $accesskey_at = $_conf['k_accesskey_at'][$i];
                                $accesskey_st = $_conf['k_accesskey_st'][$i];
                            } else {
                                $accesskey_at = '';
                                $accesskey_st = '';
                            }
                            // 板名プリント
                            if ($i >= $disp_navi['from'] and $i <= $disp_navi['end']) {
                                echo "<a href=\"{$_SERVER['SCRIPT_NAME']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}&amp;setfavita=1&amp;view=favita{$_conf['k_at_a']}\">+</a> <a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}{$_conf['k_at_a']}\"{$accesskey_at}>{$accesskey_st}{$mita->itaj_ht}</a><br>\n";
                            }
                        }

                    }
                }
                $this->_cate_id++;
            }
        }
    }

    // }}}
    // {{{ printItaSearch()

    /**
     * 板名を検索してプリントする for 携帯
     */
    public function printItaSearch(array $categories)
    {
        global $_conf, $_info_msg_ht, $word;
        global $list_navi_ht;

        if ($categories) {
            // {{{ 表示数制限
            if ($_GET['from']) {
                $list_disp_from = $_GET['from'];
            } else {
                $list_disp_from = 1;
            }
            $list_disp_all_num = $GLOBALS['ita_mikke']['num']; //
            $disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['mobile.sb_disp_range'], $list_disp_all_num);

            $word_en = rawurlencode($word);

            if ($disp_navi['from'] > 1) {
                $mae_ht = <<<EOP
<a href="menu_k.php?word={$word_en}&amp;from={$disp_navi['mae_from']}&amp;nr=1&amp;{$_conf['detect_hint_q']}{$_conf['k_at_a']}">前</a>
EOP;
            }
            if ($disp_navi['end'] < $list_disp_all_num) {
                $tugi_ht = <<<EOP
<a href="menu_k.php?word={$word_en}&amp;from={$disp_navi['tugi_from']}&amp;nr=1&amp;{$_conf['detect_hint_q']}{$_conf['k_at_a']}">次</a>
EOP;
            }

            if (!$disp_navi['all_once']) {
                $list_navi_ht = "{$disp_navi['range_st']} {$mae_ht} {$tugi_ht}<br>";
            }
            // }}}

            $i = 0;
            foreach ($categories as $cate) {
                if ($cate->num > 0) {

                    $t = false;
                    foreach ($cate->menuitas as $mita) {
                        $GLOBALS['menu_show_ita_num']++;
                        if ($GLOBALS['menu_show_ita_num'] >= $disp_navi['from'] and $GLOBALS['menu_show_ita_num'] <= $disp_navi['end']) {
                            if (!$t) {
                                echo "<b>{$cate->name}</b><br>\n";
                            }
                            $t = true;
                            echo "　<a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}{$_conf['k_at_a']}\">{$mita->itaj_ht}</a><br>\n";
                        }
                    }

                }
                $this->_cate_id++;
            }
        }
    }

    // }}}
    // {{{ printFavIta()

    /**
     * お気に板をプリントする for 携帯
     */
    public function printFavIta()
    {
        global $_conf;

        $show_flag = false;

        // favita読み込み
        if ($lines = FileCtl::file_read_lines($_conf['favita_brd'], FILE_IGNORE_NEW_LINES)) {
            if ($_conf['expack.misc.multi_favs']) {
                $favset_title = FavSetManager::getFavSetPageTitleHt('m_favita_set', 'お気に板');
            } else {
                $favset_title = 'お気に板';
            }

            echo "<div>{$favset_title}";
            if ($_conf['merge_favita']) {
                echo " (<a href=\"{$_conf['subject_php']}?spmode=merge_favita{$_conf['k_at_a']}{$_conf['m_favita_set_at_a']}\">まとめ</a>)";
            }
            echo " [<a href=\"editfavita.php{$_conf['k_at_q']}{$_conf['m_favita_set_at_a']}\">編集</a>]<hr>";

            $i = 0;
            foreach ($lines as $l) {
                $i++;
                if (preg_match("/^\t?(.+)\t(.+)\t(.+)\$/", $l, $matches)) {
                    $itaj = rtrim($matches[3]);
                    $itaj_view = htmlspecialchars($itaj, ENT_QUOTES);
                    $itaj_en = UrlSafeBase64::encode($itaj);
                    if ($i <= 9) {
                        $accesskey_at = $_conf['k_accesskey_at'][$i];
                        $accesskey_st = $_conf['k_accesskey_st'][$i];
                    } else {
                        $accesskey_at = '';
                        $accesskey_st = '';
                    }
                    echo <<<EOP
<a href="{$_conf['subject_php']}?host={$matches[1]}&amp;bbs={$matches[2]}&amp;itaj_en={$itaj_en}{$_conf['k_at_a']}"{$accesskey_at}>{$accesskey_st}{$itaj_view}</a><br>
EOP;
                    //  [<a href="{$_SERVER['SCRIPT_NAME']}?host={$matches[1]}&amp;bbs={$matches[2]}&amp;setfavita=0&amp;view=favita{$_conf['k_at_a']}{$_conf['m_favita_set_at_a']}">削</a>]
                    $show_flag = true;
                }
            }

            echo "</div>";
        }

        if (empty($show_flag)) {
            echo "<p>お気に板はまだないようだ</p>";
        }
    }

    // }}}
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
