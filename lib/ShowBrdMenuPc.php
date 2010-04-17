<?php
// {{{ ShowBrdMenuPc

/**
 * rep2 - ボードメニューを表示する クラス
 */
class ShowBrdMenuPc
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
    // {{{ printBrdMenu()

    /**
     * 板メニューをプリントする
     */
    public function printBrdMenu(array $categories)
    {
        global $_conf;

        if ($categories) {
            $menu_php_ht = htmlspecialchars((isset($GLOBALS['menu_php_self'])) ? $GLOBALS['menu_php_self'] : $_SERVER['SCRIPT_NAME']);

            foreach ($categories as $cate) {
                if ($cate->num > 0) {
                    echo "<div class=\"menu_cate\">\n";
                    echo "  <b><a class=\"menu_cate\" href=\"javascript:void(0);\" onclick=\"showHide('c{$this->_cate_id}');\" target=\"_self\">{$cate->name}</a></b>\n";
                    if ($cate->is_open or $cate->ita_match_num) {
                        echo "  <div class=\"itas\" id=\"c{$this->_cate_id}\">\n";
                    } else {
                        echo "  <div class=\"itas_hide\" id=\"c{$this->_cate_id}\">\n";
                    }
                    foreach ($cate->menuitas as $mita) {
                        echo "    <a href=\"{$menu_php_ht}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}&amp;setfavita=1\" target=\"_self\" class=\"fav\">+</a> <a href=\"{$_conf['subject_php']}?host={$mita->host}&amp;bbs={$mita->bbs}&amp;itaj_en={$mita->itaj_en}\">{$mita->itaj_ht}</a><br>\n";
                    }
                    echo "  </div>\n";
                    echo "</div>\n";
                }
                $this->_cate_id++;
            }
        }
    }

    // }}}
    // {{{ printFavIta()

    /**
     * お気に板をプリントする
     */
    public function printFavIta()
    {
        global $_conf, $matome_i, $STYLE;

        $menu_php_ht = htmlspecialchars((isset($GLOBALS['menu_php_self'])) ? $GLOBALS['menu_php_self'] : $_SERVER['SCRIPT_NAME']);

        echo <<<EOP
<div class="menu_cate">
  <b><a class="menu_cate" href="javascript:void(0);" onclick="showHide('c_favita');" target="_self">お気に板</a></b> [<a href="editfavita.php" target="subject">編集</a>]
EOP;
        // お気に板切り替え
        if ($_conf['expack.misc.multi_favs']) {
            echo "<br>\n";
            echo FavSetManager::makeFavSetSwitchElem('m_favita_set', 'お気に板', TRUE, "replaceMenuItem('c_favita', 'm_favita_set', this.options[this.selectedIndex].value);");
        }

        if ($_conf['expack.misc.multi_favs']) {
            $favset_title = FavSetManager::getFavSetPageTitleHt('m_favita_set', 'お気に板');
        } else {
            $favset_title = 'お気に板';
        }

        echo "  <div class=\"itas\" id=\"c_favita\">\n";

        if ($_conf['merge_favita']) {
            echo <<<EOP
    　 <a href="{$_conf['subject_php']}?spmode=merge_favita{$_conf['m_favita_set_at_a']}">{$favset_title} (まとめ)</a><br>\n
EOP;
        }

        // favita読み込み
        $favitas = array();
        if ($lines = FileCtl::file_read_lines($_conf['favita_brd'], FILE_IGNORE_NEW_LINES)) {
            foreach ($lines as $l) {
                if (preg_match("/^\t?(.+)\t(.+)\t(.+)\$/", $l, $matches)) {
                    $favitas[] = array(
                        'host' => $matches[1],
                        'bbs'  => $matches[2],
                        'itaj' => $matches[3],
                    );
                }
            }
        }

        if ($favitas) {
            // 新着数を表示する場合・まとめてプリフェッチ
            if ($_conf['enable_menu_new'] && !empty($_GET['new'])) {
                if ($_conf['expack.use_pecl_http'] == 1) {
                    P2HttpExt::activate();
                    P2HttpRequestPool::fetchSubjectTxt($favitas);
                    $GLOBALS['expack.subject.multi-threaded-download.done'] = true;
                } elseif ($_conf['expack.use_pecl_http'] == 2) {
                    if (P2CommandRunner::fetchSubjectTxt('merge_favita', $_conf)) {
                        $GLOBALS['expack.subject.multi-threaded-download.done'] = true;
                    }
                }
            }

            foreach ($favitas as $favita) {
                extract($favita);
                $itaj_view = htmlspecialchars($itaj, ENT_QUOTES);
                $itaj_en = UrlSafeBase64::encode($itaj);
                $itaj_js = addslashes($itaj_view);

                $p_htm['star'] = <<<EOP
<a href="{$menu_php_ht}?host={$host}&amp;bbs={$bbs}&amp;setfavita=0{$_conf['m_favita_set_at_a']}" target="_self" class="fav" title="「{$itaj_view}」をお気に板から外す" onclick="return window.confirm('「{$itaj_js}」をお気に板から外してよろしいですか？');">★</a>
EOP;
                //  onclick="return confirmSetFavIta('{$itaj_ht}');"
                // 新着数を表示する場合
                if ($_conf['enable_menu_new'] && !empty($_GET['new'])) {
                    $matome_i++;
                    $spmode = null;

                    // $shinchaku_num, $_newthre_num をセット
                    include P2_LIB_DIR . '/subject_new.inc.php';

                    if ($shinchaku_num > 0) {
                        $class_newres_num = ' class="newres_num"';
                    } else {
                        $class_newres_num = ' class="newres_num_zero"';
                    }
                    if ($_newthre_num) {
                        $newthre_ht = "{$_newthre_num}";
                    } else {
                        $newthre_ht = '';
                    }
                    echo <<<EOP
    {$p_htm['star']} <a href="{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}&amp;itaj_en={$itaj_en}" onclick="chMenuColor({$matome_i});">{$itaj_view}</a> <span id="newthre{$matome_i}" class="newthre_num">{$newthre_ht}</span> (<a href="{$_conf['read_new_php']}?host={$host}&amp;bbs={$bbs}" target="read" id="un{$matome_i}" onclick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>\n
EOP;

                // 新着数を表示しない場合
                } else {
                    echo <<<EOP
    {$p_htm['star']} <a href="{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}&amp;itaj_en={$itaj_en}">{$itaj_view}</a><br>\n
EOP;

                }
                flush();
            } // foreach

        // 空っぽなら
        } else {
            echo '　（空っぽ）';
        }

        echo "  </div>\n</div>\n";
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
