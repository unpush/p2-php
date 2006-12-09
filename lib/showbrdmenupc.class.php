<?php
/**
 * p2 - ボードメニューを表示する クラス
 */
class ShowBrdMenuPc
{
    var $cate_id; // カテゴリーID（連番数字）

    /**
     * @constructor
     */
    function ShowBrdMenuPc()
    {
        $this->cate_id = 1;
    }

    /**
     * 板メニューをHTML表示する
     *
     * @access  public
     * @return  void
     */
    function printBrdMenu(&$categories)
    {
        global $_conf;

        if (!$categories) {
            return;
        }

        $menu_php_ht = htmlspecialchars((isset($GLOBALS['menu_php_self'])) ? $GLOBALS['menu_php_self'] : $_SERVER['SCRIPT_NAME']);

        foreach ($categories as $cate) {
            if ($cate->num > 0) {
                echo "<div class=\"menu_cate\">\n";
                echo " <b><a class=\"menu_cate\" href=\"javascript:void(0);\" onClick=\"showHide('c{$this->cate_id}');\" target=\"_self\">{$cate->name}</a></b>\n";
                if ($cate->is_open or $cate->ita_match_num) {
                    echo " <div class=\"itas\" id=\"c{$this->cate_id}\">\n";
                } else {
                    echo " <div class=\"itas_hide\" id=\"c{$this->cate_id}\">\n";
                }
                foreach ($cate->menuitas as $mita) {
                    echo "  <a href=\"{$menu_php_ht}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}&amp;setfavita=1\" target=\"_self\" class=\"fav\">+</a> <a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}\">{$mita->itaj_ht}</a><br>\n";
                }
                $cate_name_en = rawurlencode($cate->name);
                $itaj_en = $cate_name_en . rawurlencode('のスレッド一覧');
                echo "  [<a href=\"{$_conf['subject_php']}?spmode=cate&amp;cate_name={$cate_name_en}&amp;itaj_en={$itaj_en}\">スレ一覧</a>]\n";
                echo " </div>\n";
                echo "</div>\n";
            }
            $this->cate_id++;
        }
    }

    /**
     * お気に板をHTML表示する
     *
     * @access  public
     * @return  void
     */
    function printFavItaHtml()
    {
        global $_conf, $matome_i, $STYLE;

        $menu_php_ht = htmlspecialchars((isset($GLOBALS['menu_php_self'])) ? $GLOBALS['menu_php_self'] : $_SERVER['SCRIPT_NAME']);

        echo <<<EOP
<div class="menu_cate"><b><a class="menu_cate" href="javascript:void(0);" onClick="showHide('c_favita');" target="_self">お気に板</a></b> [<a href="editfavita.php" target="subject">編集</a>]
EOP;
        // お気に板切り替え
        if ($_conf['expack.favset.enabled'] && $_conf['favita_set_num'] > 0) {
            echo "<br>\n";
            echo FavSetManager::makeFavSetSwitchElem('m_favita_set', 'お気に板', true, "replaceMenuItem('c_favita', 'm_favita_set', this.options[this.selectedIndex].value);");
        }
        echo <<<EOP
 <div class="itas" id="c_favita">
EOP;

        $lines= @file($_conf['favita_path']); // favita読み込み

        // 空っぽなら
        if (!$lines) {
            echo '　（空っぽ）';
            echo " </div>\n";
            echo "</div>\n";
            return;
        }

        foreach ($lines as $l) {
            if (preg_match('/^\t?(.+)\t(.+)\t(.+)$/', rtrim($l), $matches)) {
                $itaj = rtrim($matches[3]);
                $itaj_view = htmlspecialchars($itaj, ENT_QUOTES);
                $itaj_en = rawurlencode(base64_encode($itaj));
                $itaj_js = addslashes($itaj_view);

                $p_htm['star'] = <<<EOP
<a href="{$menu_php_ht}?host={$matches[1]}&amp;bbs={$matches[2]}&amp;setfavita=0" target="_self" class="fav" title="「{$itaj_view}」をお気に板から外す" onclick="return window.confirm('「{$itaj_js}」をお気に板から外してよろしいですか？');">★</a>
EOP;
                // onClick="return confirmSetFavIta('{$itaj_ht}');"
                // 新着数を表示する場合
                if ($_conf['enable_menu_new'] && $_GET['new']) {
                    $matome_i++;
                    $host = $matches[1];
                    $bbs = $matches[2];
                    $spmode = '';
                    $shinchaku_num = 0;
                    $_newthre_num = 0;
                    $newthre_ht = '';
                    include './subject_new.php'; // $shinchaku_num, $_newthre_num をセット
                    if ($shinchaku_num > 0) {
                        $class_newres_num = " class=\"newres_num\"";
                    } else {
                        $class_newres_num = " class=\"newres_num_zero\"";
                    }
                    if ($_newthre_num) {
                        $newthre_ht = (string)$_newthre_num;
                    }
                    echo <<<EOP
            {$p_htm['star']}
  <a href="{$_conf['subject_php']}?host={$matches[1]}&amp;bbs={$matches[2]}&amp;itaj_en={$itaj_en}" onClick="chMenuColor({$matome_i});">{$itaj_view}</a> <span id="newthre{$matome_i}" class="newthre_num">{$newthre_ht}</span> (<a href="{$_conf['read_new_php']}?host={$matches[1]}&amp;bbs={$matches[2]}" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;

                // 新着数を表示しない場合
                } else {
                    echo <<<EOP
            {$p_htm['star']}
  <a href="{$_conf['subject_php']}?host={$matches[1]}&amp;bbs={$matches[2]}&amp;itaj_en={$itaj_en}">{$itaj_view}</a><br>
EOP;

                }

            }

            flush();

        } // foreach

        echo " </div>\n";
        echo "</div>\n";
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
