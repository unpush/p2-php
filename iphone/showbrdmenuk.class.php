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
    function printCate(&$categories)
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
<span class="mae"><a href="menu_i.php?view=cate&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}">前</a></span>
EOP;
        } else {
            $mae_ht = '';
        }
        
        if ($disp_navi['end'] < $list_disp_all_num) {
            $tugi_ht = <<<EOP
<span class="tugi"><a href="menu_i.php?view=cate&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}">次</a></span>
EOP;
        } else {
            $tugi_ht = '';
        }
        
        if (!$disp_navi['all_once']) {
            $list_navi_ht = <<<EOP
<div class="foot_sure" id="foot">\n{$mae_ht} \n{$tugi_ht}\n</div>
EOP;
        } else {
            $list_navi_ht = '';
        }
        
    echo '<ul id="home"><li class="group">板一覧</li>';
        foreach ($categories as $cate) {
            if ($this->cate_id >= $disp_navi['from'] and $this->cate_id <= $disp_navi['end']) {
                echo "<li><a href=\"menu_i.php?cateid={$this->cate_id}&amp;nr=1{$_conf['k_at_a']}\">{$cate->name}($cate->num)</a></li>\n"; // $this->cate_id
            }
            $this->cate_id++;
        }    echo '</ul>';
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
                
                //echo "{$cate->name}<hr>\n";

                $list_disp_all_num = $cate->num;
                $disp_navi = P2Util::getListNaviRange($list_disp_from, $_conf['k_sb_disp_range'], $list_disp_all_num);
                
                if ($disp_navi['from'] > 1) {
                    $mae_ht = <<<EOP
<span class="mae"><a href="menu_i.php?cateid={$this->cate_id}&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}">前</a></span>
EOP;
                } else {
                    $mae_ht = '';
                }
                
                if ($disp_navi['end'] < $list_disp_all_num) {
                    $tugi_ht = <<<EOP
<span class="tugi"><a href="menu_i.php?cateid={$this->cate_id}&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}">次</a><span>
EOP;
                } else {
                    $tugi_ht = '';
                }
                
                if (!$disp_navi['all_once']) {//{$disp_navi['range_st']}
                    $list_navi_ht = <<<EOP
<div id="foot" class="foot_sure">{$mae_ht} {$tugi_ht}</div>
EOP;
                }
echo '<ul>';
echo '<li class="group">板一覧</li>';
                $i = 0;
                foreach ($cate->menuitas as $mita) {
                    $i++;
                    
                    $subject_attr = array();
                    $access_num_st = '';
                    
                    if ($i <= 9) {
                        $access_num_st = "$i.";
                        $akey_at = " {$_conf['accesskey']}=\"{$i}\"";
                    } else {
                        $access_num_st = "";
                        $akey_at = "";
                    }
                    
                    // 板名プリント
                    if ($i >= $disp_navi['from'] and $i <= $disp_navi['end']) {
echo  "<li><a class=\"plus\"href=\"{$_SERVER['SCRIPT_NAME']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}&amp;setfavita=1&amp;view=favita{$_conf['k_at_a']}\" ><img src=\"iui/icon_add.png\"></a> <a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}{$_conf['k_at_a']}\" >{$mita->itaj_ht}</a></li>";
                   }
                }
            
            }
            $this->cate_id++;
        }echo'</ul>';
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
        $word_q = '&amp;word=' . rawurlencode($_REQUEST['word']);
        
        if ($disp_navi['from'] > 1) {
            $mae_ht = <<<EOP
<span class="mae"><a href="menu_i.php?w{$detect_hint_q}{$word_q}&amp;from={$disp_navi['mae_from']}&amp;nr=1{$_conf['k_at_a']}">前</a> </span>
EOP;
        } else {
            $mae_ht = '';
        }
        
        if ($disp_navi['end'] < $list_disp_all_num) {
            $tugi_ht = <<<EOP
<span class="tugi"><a href="menu_i.php?{$detect_hint_q}{$word_q}&amp;from={$disp_navi['tugi_from']}&amp;nr=1{$_conf['k_at_a']}">次</a> </span>
EOP;
        } else {
            $tugi_ht = '';
        }
        
        if (!$disp_navi['all_once']) {//{$disp_navi['range_st']} iphone
            $list_navi_ht = <<<EOP
<div class="foot_sure" id="foot">\n{$mae_ht}\n{$tugi_ht}\n</div>
EOP;
        } else {
            $list_navi_ht = '';
        }
        
        // }}}
        echo '<ul>';        
        $i = 0;
        foreach ($categories as $cate) {
            
            if ($cate->num > 0) {
                $t = false;
                foreach ($cate->menuitas as $mita) {
                    
                    $GLOBALS['menu_show_ita_num']++;
                    if ($GLOBALS['menu_show_ita_num'] >= $disp_navi['from'] and $GLOBALS['menu_show_ita_num'] <= $disp_navi['end']) {

                        if (!$t) {
                            echo "<li class=\"group\">{$cate->name}</li>\n";
                        }
                        $t = true;
                        echo "<li><a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}{$_conf['k_at_a']}\">{$mita->itaj_ht}</a></li>\n";
                    }
                }

            }
            $this->cate_id++;
        }echo '</ul>';
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
            echo '<ul id="home"><li class="group">お気に入り一覧</li>';
            echo '<a class="button" href="editfavita_i.php?b=i">編集</a>';
            /*echo '<ul><li><a href="editfavita.php?k=1">編集</a></li><li class="group">お気に入り一覧</li>';*/
            $i = 0;
            foreach ($lines as $l) {
                $i++;
                $l = rtrim($l);
                if (preg_match("/^\t?(.+)\t(.+)\t(.+)$/", $l, $matches)) {
                    $itaj = rtrim($matches[3]);
                    $attr = array();
                    $key_num_st = '';

                    if ($i <= 9) {
                        $attr[$_conf['accesskey']] = $i;
                        $key_num_st = "$i.";
                    }

                    $uri = P2Util::buildQueryUri($_conf['subject_php'], array(
                        'host' => $matches[1],
                        'bbs'  => $matches[2],
                        'itaj_en' => base64_encode($itaj),
                        UA::getQueryKey() => UA::getQueryValue()
                    ));
                    $atag = P2View::tagA($uri, hs($itaj), $attr);

                    echo '<li>' . $atag . '</li>';

                    //  [<a href="{$_SERVER['SCRIPT_NAME']}?host={$matches[1]}&amp;bbs={$matches[2]}&amp;setfavita=0&amp;csrfid={$csrfid}&amp;view=favita{$_conf['k_at_a']}">削</a>]
                    $show_flag = true;
                }
            }echo '</ul>';
        }
        
        if (!$show_flag) {
            echo "<p>お気に板はまだないようだ</p>";
        }
    }
}
