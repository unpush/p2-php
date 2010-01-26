<?php
/**
 * p2 - ボードメニューをHTML表示するクラス(携帯)
 */
class ShowBrdMenuK
{
    var $cate_id = 1; // カテゴリーID
    
    /**
     * @constructor
     */
    function ShowBrdMenuK()
    {
    }

    /**
     * 板メニューカテゴリをHTML表示する for 携帯
     *
     * @access  public
     * @return  void
     */
    function printCate($categories)
    {
        global $_conf, $list_navi_ht;

        if (!$categories) {
            return;
        }
        
        // 表示数制限
        if (isset($_GET['from'])) {
            $list_disp_from = intval($_GET['from']);
        } else {
            $list_disp_from = 1;
        }
        $list_disp_all_num = sizeof($categories);
        $disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['k_sb_disp_range'], $list_disp_all_num);
    
        if ($disp_navi['from'] > 1) {
            $mae_ht = <<<EOP
<a href="{$_conf['menu_k_php']}?view=cate&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}" {$_conf['accesskey_for_k']}="{$_conf['k_accesskey']['prev']}">{$_conf['k_accesskey']['prev']}.前</a>
EOP;
        } else {
            $mae_ht = '';
        }
        
        if ($disp_navi['end'] < $list_disp_all_num) {
            $tugi_ht = <<<EOP
<a href="{$_conf['menu_k_php']}?view=cate&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}" {$_conf['accesskey_for_k']}="{$_conf['k_accesskey']['next']}">{$_conf['k_accesskey']['next']}.次</a>
EOP;
        } else {
            $tugi_ht = '';
        }
        
        if (!$disp_navi['all_once']) {
            $list_navi_ht = <<<EOP
{$disp_navi['range_st']}{$mae_ht} {$tugi_ht}<br>
EOP;
        } else {
            $list_navi_ht = '';
        }
        
        if (UA::isIPhoneGroup()) {
            ?><ul id="home"><li class="group">板一覧</li><?php
        }
        foreach ($categories as $cate) {
            if ($this->cate_id >= $disp_navi['from'] and $this->cate_id <= $disp_navi['end']) {
                echo "<a href=\"{$_conf['menu_k_php']}?cateid={$this->cate_id}&amp;nr=1{$_conf['k_at_a']}\">{$cate->name}</a>($cate->num)<br>\n"; // $this->cate_id
            }
            $this->cate_id++;
        }
        if (UA::isIPhoneGroup()) {
            ?></ul><?php
        }
    }

    /**
     * 板メニューカテゴリの板をHTML表示する for 携帯
     *
     * @access  public
     * @return  void
     */
    function printIta($categories)
    {
        global $_conf, $list_navi_ht;

        if (!$categories) {
            return;
        }
        
        $csrfid = P2Util::getCsrfId();
        $hr = P2View::getHrHtmlK();
        
        $list_navi_ht = '';
        
        // 表示数制限
        if (isset($_GET['from'])) {
            $list_disp_from = intval($_GET['from']);
        } else {
            $list_disp_from = 1;
        }
        
        foreach ($categories as $cate) {
            if ($cate->num and $this->cate_id == $_GET['cateid']) {
                
                if (!UA::isIPhoneGroup()) {
                    echo "{$cate->name}<hr>\n";
                }

                $list_disp_all_num = $cate->num;
                $disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['k_sb_disp_range'], $list_disp_all_num);
                
                if ($disp_navi['from'] > 1) {
                    $mae_ht = <<<EOP
<a href="{$_conf['menu_k_php']}?cateid={$this->cate_id}&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}">前</a>
EOP;
                } else {
                    $mae_ht = '';
                }
                
                if ($disp_navi['end'] < $list_disp_all_num) {
                    $tugi_ht = <<<EOP
<a href="{$_conf['menu_k_php']}?cateid={$this->cate_id}&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}">次</a>
EOP;
                } else {
                    $tugi_ht = '';
                }
                
                if (!$disp_navi['all_once']) {
                    $list_navi_ht = <<<EOP
{$disp_navi['range_st']}{$mae_ht} {$tugi_ht}<br>
EOP;
                }

                if (UA::isIPhoneGroup()) {
                    echo '<ul>';
                    echo '<li class="group">板一覧</li>';
                }
                
                $i = 0;
                foreach ($cate->menuitas as $mita) {
                    $i++;
                    
                    $subject_attr = array();
                    $access_num_st = '';
                    
                    if ($i <= 9) {
                        $access_num_st = "$i.";
                        $subject_attr[$_conf['accesskey_for_k']] = $i;
                    }
                    
                    // 板名プリント
                    if ($i >= $disp_navi['from'] and $i <= $disp_navi['end']) {
                        
                        $uri = UriUtil::buildQueryUri($_SERVER['SCRIPT_NAME'], array(
                            'host'    => $mita->host,
                            'bbs'     => $mita->bbs,
                            'itaj_en' => $mita->itaj_en,
                            'setfavita' => '1',
                            'csrfid'  => $csrfid,
                            'view'    => 'favita',
                            UA::getQueryKey() => UA::getQueryValue()
                        ));
                        $add_atag = P2View::tagA($uri, '+');
                        
                        $uri = UriUtil::buildQueryUri($_conf['subject_php'], array(
                            'host'    => $mita->host,
                            'bbs'     => $mita->bbs,
                            'itaj_en' => $mita->itaj_en,
                            UA::getQueryKey() => UA::getQueryValue()
                        ));
                        $subject_atag = P2View::tagA($uri, "{$access_num_st}{$mita->itaj_ht}", $subject_attr);
                        
                        echo $add_atag . ' ' . $subject_atag . "<br>\n";
                    }
                }
            
            }
            $this->cate_id++;
        }
        if (UA::isIPhoneGroup()) {
            ?></ul><?php
        }
    }

    /**
     * 板名を検索してHTML表示する for 携帯
     *
     * @access  public
     * @return  void
     */
    function printItaSearch($categories)
    {
        global $_conf;
        global $list_navi_ht;
    
        if (!$categories) {
            return;
        }
        
        // {{{ 表示数制限
        
        $list_disp_from = empty($_GET['from']) ? 1 : intval($_GET['from']);
        
        $list_disp_all_num = $GLOBALS['ita_mikke']['num']; //
        $disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['k_sb_disp_range'], $list_disp_all_num);
        
        $detect_hint_q = 'detect_hint=' . urlencode('◎◇');
        $word_q = '&amp;word=' . rawurlencode($GLOBALS['word']);
        
        if ($disp_navi['from'] > 1) {
            $mae_ht = <<<EOP
<a href="{$_conf['menu_k_php']}?w{$detect_hint_q}{$word_q}&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}">前</a>
EOP;
        } else {
            $mae_ht = '';
        }
        
        if ($disp_navi['end'] < $list_disp_all_num) {
            $tugi_ht = <<<EOP
<a href="{$_conf['menu_k_php']}?{$detect_hint_q}{$word_q}&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}">次</a>
EOP;
        } else {
            $tugi_ht = '';
        }
        
        if (!$disp_navi['all_once']) {
            $list_navi_ht = <<<EOP
{$disp_navi['range_st']}{$mae_ht} {$tugi_ht}<br>
EOP;
        } else {
            $list_navi_ht = '';
        }
        
        // }}}
        
        if (UA::isIPhoneGroup()) {
            ?><ul><?php
        }
        foreach ($categories as $cate) {
            
            if ($cate->num > 0) {
                $t = false;
                foreach ($cate->menuitas as $mita) {
                    
                    $GLOBALS['menu_show_ita_num']++;
                    if (
                        $GLOBALS['menu_show_ita_num'] >= $disp_navi['from']
                        and $GLOBALS['menu_show_ita_num'] <= $disp_navi['end']
                    ) {

                        if (!$t) {
                            echo "<b>{$cate->name}</b><br>\n";
                        }
                        $t = true;
                        
                        $uri = UriUtil::buildQueryUri($_conf['subject_php'], array(
                            'host' => $mita->host,
                            'bbs'  => $mita->bbs,
                            'itaj_en' => $mita->itaj_en,
                            UA::getQueryKey() => UA::getQueryValue()
                        ));
                        $atag = P2View::tagA($uri, $mita->itaj_ht);
                        
                        if (UA::isIPhoneGroup()) {
                            echo "<li>{$atag}</li>\n";
                        } else {
                            echo '&nbsp;' . $atag . "<br>\n";
                        }
                    }
                }

            }
            $this->cate_id++;
        }
        if (UA::isIPhoneGroup()) {
            ?></ul><?php
        }
    }

    /**
     * お気に板をHTML表示する for 携帯
     *
     * @access  public
     * @return  void
     */
    function printFavItaHtml()
    {
        global $_conf;
        
        $csrfid = P2Util::getCsrfId();
        $hr = P2View::getHrHtmlK();
        
        $show_flag = false;
        
        if (file_exists($_conf['favita_path']) and $lines = file($_conf['favita_path'])) {
            echo 'お気に板 [<a href="editfavita.php?b=k">編集</a>]' . $hr;
            $i = 0;
            foreach ($lines as $l) {
                $i++;
                $l = rtrim($l);
                if (preg_match("/^\t?(.+)\t(.+)\t(.+)$/", $l, $matches)) {
                    $itaj = rtrim($matches[3]);
                    $attr = array();
                    $key_num_st = '';

                    if ($i <= 9) {
                        $attr[$_conf['accesskey_for_k']] = $i;
                        $key_num_st = "$i.";
                    }

                    $atag = P2View::tagA(
                        UriUtil::buildQueryUri($_conf['subject_php'],
                            array(
                                'host' => $matches[1],
                                'bbs'  => $matches[2],
                                'itaj_en' => base64_encode($itaj),
                                UA::getQueryKey() => UA::getQueryValue()
                            )
                        ),
                        UA::isIPhoneGroup() ? hs($itaj) : hs("$key_num_st$itaj"),
                        $attr
                    );

                    if (UA::isIPhoneGroup()) {
                        echo '<li>' . $atag . '</li>';
                    } else {
                        echo $atag . '<br>';
                    }

                    //  [<a href="{$_SERVER['SCRIPT_NAME']}?host={$matches[1]}&amp;bbs={$matches[2]}&amp;setfavita=0&amp;csrfid={$csrfid}&amp;view=favita{$_conf['k_at_a']}">削</a>]
                    $show_flag = true;
                }
            }
            if (UA::isIPhoneGroup()) {
                ?></ul><?php
            }
        }
        
        if (!$show_flag) {
            ?><p>お気に板はまだないようだ</p><?php
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
