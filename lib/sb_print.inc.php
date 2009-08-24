<?php
/**
 * rep2 スレッドサブジェクト表示関数
 * for subject.php
 */

// {{{ sb_print()

/**
 * sb_print - スレッド一覧を表示する (<tr>～</tr>)
 */
function sb_print($aThreadList)
{
    global $_conf, $sb_view, $p2_setting, $STYLE;

    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('sb_print()');

    if (!$aThreadList->threads) {
        echo '<tbody><tr><td>　該当サブジェクトはなかったぽ</td></tr></tbody>';
        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_print()');
        return;
    }

    // 変数 ================================================

    // >>1 表示 (spmodeは除く)
    $only_one_bool = false;
    if (!$aThreadList->spmode && ($_conf['sb_show_one'] == 1 || ($_conf['sb_show_one'] == 2 &&
        (strpos($aThreadList->bbs, 'news') !== false || $aThreadList->bbs == 'bizplus')
    ))) {
        $only_one_bool = true;
    }

    // チェックボックス
    if ($aThreadList->spmode == 'taborn' || $aThreadList->spmode == 'soko') {
        $checkbox_bool = true;
    } else {
        $checkbox_bool = false;
    }

    // 板名
    if ($aThreadList->spmode && $aThreadList->spmode != 'taborn' && $aThreadList->spmode != 'soko') {
        $ita_name_bool = true;
    } else {
        $ita_name_bool = false;
    }

    $norefresh_q = '&amp;norefresh=true';

    $td = array('edit' => '', 'offrec' => '', 'unum' => '', 'rescount' => '',
                'one' => '', 'checkbox' => '', 'ita' => '', 'spd' => '',
                'ikioi' => '', 'birth' => '', 'fav' => '');

    // td欄 cssクラス
    $class_t  = ' class="t"';   // 基本
    $class_te = ' class="te"';  // 並び替え
    $class_tu = ' class="tu"';  // 新着レス数
    $class_tn = ' class="tn"';  // レス数
    $class_tc = ' class="tc"';  // チェックボックス
    $class_to = ' class="to"';  // オーダー番号
    $class_tl = ' class="tl"';  // タイトル
    $class_ts = ' class="ts"';  // すばやさ
    $class_ti = ' class="ti"';  // 勢い

    // ソート ==================================================

    // 現在のソート形式をclass指定でCSSカラーリング
    $class_sort_midoku  = '';   // 新着
    $class_sort_res     = '';   // レス
    $class_sort_no      = '';   // No.
    $class_sort_title   = '';   // タイトル
    $class_sort_ita     = '';   // 板
    $class_sort_spd     = '';   // すばやさ
    $class_sort_ikioi   = '';   // 勢い
    $class_sort_bd      = '';   // Birthday
    $class_sort_fav     = '';   // お気に入り
    if (empty($_REQUEST['rsort'])) {
        ${'class_sort_' . $GLOBALS['now_sort']} = ' class="now_sort"';
    } else {
        ${'class_sort_' . $GLOBALS['now_sort']} = ' class="now_sort rsort"';
    }

    // 逆順ソート用クエリ
    $rsortq_midoku  = '';   // 新着
    $rsortq_res     = '';   // レス
    $rsortq_no      = '';   // No.
    $rsortq_title   = '';   // タイトル
    $rsortq_ita     = '';   // 板
    $rsortq_spd     = '';   // すばやさ
    $rsortq_ikioi   = '';   // 勢い
    $rsortq_bd      = '';   // Birthday
    $rsortq_fav     = '';   // お気に入り
    if (empty($_REQUEST['rsort'])) {
        ${'rsortq_' . $GLOBALS['now_sort']} = '&amp;rsort=1';
    }

    $sortq_spmode = '';
    $sortq_host = '';
    $sortq_ita = '';
    // spmode時
    if ($aThreadList->spmode) {
        $sortq_spmode = "&amp;spmode={$aThreadList->spmode}";
    }
    // spmodeでない、または、spmodeがあぼーん or dat倉庫なら
    if (!$aThreadList->spmode || $aThreadList->spmode == 'taborn' || $aThreadList->spmode == 'soko') {
        $sortq_host = "&amp;host={$aThreadList->host}";
        $sortq_ita = "&amp;bbs={$aThreadList->bbs}";
    }

    $sortq_common = $sortq_spmode . $sortq_host . $sortq_ita;

    //=====================================================
    // テーブルヘッダ
    //=====================================================
    echo "<thead>\n<tr class=\"tableheader\">\n";

    // 並替
    if ($sb_view == 'edit') {
        echo "<th{$class_te}>&nbsp;</th>\n";
    }
    // 履歴の解除
    if ($aThreadList->spmode == 'recent') {
        echo "<th{$class_t}>&nbsp;</th>\n";
    }
    // 新着
    if ($sb_view != 'edit') {
        echo <<<EOP
<th{$class_tu} id="sb_th_midoku"><a{$class_sort_midoku} href="{$_conf['subject_php']}?sort=midoku{$sortq_common}{$rsortq_midoku}{$norefresh_q}" target="_self">新着</a></th>\n
EOP;
    }
    // レス数
    if ($sb_view != 'edit') {
        echo <<<EOP
<th{$class_tn} id="sb_th_res"><a{$class_sort_res} href="{$_conf['subject_php']}?sort=res{$sortq_common}{$rsortq_res}{$norefresh_q}" target="_self">レス</a></th>\n
EOP;
    }
    // >>1
    if ($only_one_bool) {
        echo "<th{$class_t}>&nbsp;</th>\n";
    }
    // チェックボックス
    if ($checkbox_bool) {
        echo <<<EOP
<th{$class_tc}><input id="allbox" name="allbox" type="checkbox" onclick="checkAll();" title="すべての項目を選択、または選択解除"></th>\n
EOP;
    }
    // No.
    $title = empty($aThreadList->spmode) ? ' title="2ch標準の並び順番号"' : '';
    echo <<<EOP
<th{$class_to} id="sb_th_no"><a{$class_sort_no} href="{$_conf['subject_php']}?sort=no{$sortq_common}{$rsortq_no}{$norefresh_q}" target="_self"{$title}>No.</a></th>\n
EOP;
    // タイトル
    echo <<<EOP
<th{$class_tl} id="sb_th_title"><a{$class_sort_title} href="{$_conf['subject_php']}?sort=title{$sortq_common}{$rsortq_title}{$norefresh_q}" target="_self">タイトル</a></th>\n
EOP;
    // 板
    if ($ita_name_bool) {
        echo <<<EOP
<th{$class_t} id="sb_th_ita"><a{$class_sort_ita} href="{$_conf['subject_php']}?sort=ita{$sortq_common}{$rsortq_ita}{$norefresh_q}" target="_self">板</a></th>\n
EOP;
    }
    // すばやさ
    if ($_conf['sb_show_spd']) {
        echo <<<EOP
<th{$class_ts} id="sb_th_spd"><a{$class_sort_spd} href="{$_conf['subject_php']}?sort=spd{$sortq_common}{$rsortq_spd}{$norefresh_q}" target="_self">すばやさ</a></th>\n
EOP;
    }
    // 勢い
    if ($_conf['sb_show_ikioi']) {
        echo <<<EOP
<th{$class_ti} id="sb_th_ikioi"><a{$class_sort_ikioi} href="{$_conf['subject_php']}?sort=ikioi{$sortq_common}{$rsortq_ikioi}{$norefresh_q}" target="_self">勢い</a></th>\n
EOP;
    }
    // Birthday
    echo <<<EOP
<th{$class_t} id="sb_th_bd"><a{$class_sort_bd} href="{$_conf['subject_php']}?sort=bd{$sortq_common}{$rsortq_bd}{$norefresh_q}" target="_self">Birthday</a></th>\n
EOP;
    // お気に入り
    if ($_conf['sb_show_fav'] && $aThreadList->spmode != 'taborn') {
        echo <<<EOP
<th{$class_t} id="sb_th_fav"><a{$class_sort_fav} href="{$_conf['subject_php']}?sort=fav{$sortq_common}{$rsortq_fav}{$norefresh_q}" target="_self" title="お気にスレ">☆</a></th>\n
EOP;
    }

    echo "</tr>\n</thead>\n";

    //=====================================================
    //テーブルボディ
    //=====================================================

    echo "<tbody>\n";

    //spmodeがあればクエリー追加
    if ($aThreadList->spmode) {
        $spmode_q = "&amp;spmode={$aThreadList->spmode}";
    } else {
        $spmode_q = '';
    }
    $sid = defined('SID') ? strip_tags(SID) : '';
    if ($sid === '') {
        $sid_q = $sid_js = '';
    } else {
        $sid_q = "&amp;{$sid}";
        $sid_js = "+'{$sid_q}'";
    }

    $i = 0;
    foreach ($aThreadList->threads as $aThread) {
        $i++;
        $midoku_ari = false;
        $anum_ht = ''; // #r1

        $base_q = "host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}";

        if ($aThreadList->spmode != 'taborn') {
            if (!$aThread->torder) { $aThread->torder = $i; }
        }

        // tr欄 cssクラス
        if ($i % 2) {
            $class_r = ' class="r1"';   // 奇数行
        } else {
            $class_r = ' class="r2"';   // 偶数行
        }

        //新着レス数 =============================================
        $unum_ht_c = '&nbsp;';
        // 既得済み
        if ($aThread->isKitoku()) {

            // $ttitle_en_q は節減省略
            $delelog_js = "return wrapDeleLog('{$base_q}{$sid_q}',this);";
            $title_at = ' title="クリックするとログ削除"';

            $anum_ht = sprintf('#r%d', min($aThread->rescount, $aThread->rescount - $aThread->nunum + 1 - $_conf['respointer']));

            // subject.txtにない時
            if (!$aThread->isonline) {
                // JavaScriptでの確認ダイアログあり
                $unum_ht_c = <<<EOP
<a class="un_n" href="{$_conf['subject_php']}?{$base_q}{$spmode_q}&amp;dele=true" target="_self" onclick="if (!window.confirm('ログを削除しますか？')) {return false;} {$delelog_js}"{$title_at}>-</a>
EOP;
                $class_r = ' class="nosubject"';

            // 新着あり
            } elseif ($aThread->unum > 0) {
                $midoku_ari = true;
                $unum_ht_c = <<<EOP
<a id="un{$i}" class="un_a" href="{$_conf['subject_php']}?{$base_q}{$spmode_q}&amp;dele=true" target="_self" onclick="{$delelog_js}"{$title_at}>{$aThread->unum}</a>
EOP;

            // subject.txtにはあるが、新着なし
            } else {
                $unum_ht_c = <<<EOP
<a class="un" href="{$_conf['subject_php']}?{$base_q}{$spmode_q}&amp;dele=true" target="_self" onclick="{$delelog_js}"{$title_at}>{$aThread->unum}</a>
EOP;
            }
        }

        $td['unum'] = "<td{$class_tu}>{$unum_ht_c}</td>\n";

        // 総レス数 =============================================
        $td['rescount'] = "<td{$class_tn}>{$aThread->rescount}</td>\n";

        // 板名 ============================================
        if ($ita_name_bool) {
            $ita_name_ht = htmlspecialchars($aThread->itaj ? $aThread->itaj : $aThread->bbs, ENT_QUOTES);
            $td['ita'] = <<<EOP
<td{$class_t}><a href="{$_conf['subject_php']}?host={$aThread->host}&amp;bbs={$aThread->bbs}" target="_self">{$ita_name_ht}</a></td>\n
EOP;
        }


        // お気に入り ========================================
        if ($_conf['sb_show_fav']) {
            if ($aThreadList->spmode != 'taborn') {

                if ($_conf['expack.misc.multi_favs']) {
                    $favmark = (!empty($aThread->favs[$_SESSION['m_favlist_set']])) ? '★' : '+';
                    $favdo = (!empty($aThread->favs[$_SESSION['m_favlist_set']])) ? 0 : 1;
                } else {
                    $favmark = (!empty($aThread->fav)) ? '★' : '+';
                    $favdo = (!empty($aThread->fav)) ? 0 : 1;
                }
                $favtitle = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
                $favdo_q = '&amp;setfav='.$favdo;

                // $ttitle_en_q も付けた方がいいが、節約のため省略する
                $td['fav'] = <<<EOP
<td{$class_t}><a class="fav" href="info.php?{$base_q}{$favdo_q}" target="info" onclick="return wrapSetFavJs('{$base_q}','{$favdo}',this);" title="{$favtitle}">{$favmark}</a></td>\n
EOP;
            }
        }

        // torder(info) =================================================
        // お気にスレ
        if ($aThread->fav) {
            $torder_st = "<b>{$aThread->torder}</b>";
        } else {
            $torder_st = $aThread->torder;
        }
        $torder_ht = <<<EOP
<a id="to{$i}" class="info" href="info.php?{$base_q}" target="_self" onclick="return wrapOpenSubWin(this.href.toString(){$sid_js})">{$torder_st}</a>
EOP;

        // title =================================================
        $rescount_q = '&amp;rescount=' . $aThread->rescount;

        // dat倉庫 or 殿堂なら
        if ($aThreadList->spmode == 'soko' || $aThreadList->spmode == 'palace') {
            $rescount_q = '';
            $offline_q = '&amp;offline=true';
            $anum_ht = '';
        // subject.txt にない場合
        } elseif (!$aThread->isonline) {
            $offline_q = '&amp;offline=true';
        } else {
            $offline_q = '';
        }

        // タイトル未取得なら
        $ttitle_ht = $aThread->ttitle_ht;
        if (strlen($ttitle_ht) == 0) {
            $ttitle_ht = "http://{$aThread->host}/test/read.cgi/{$aThread->bbs}/{$aThread->key}/";
        }

        if ($aThread->similarity) {
            $ttitle_ht .= sprintf(' <var>(%0.1f)</var>', $aThread->similarity * 100);
        }

        // 元スレ
        $moto_thre_ht = "";
        if ($_conf['sb_show_motothre']) {
            if (!$aThread->isKitoku()) {
                $moto_thre_ht = '<a class="thre_title" href="' . htmlspecialchars($aThread->getMotoThread()) . '">・</a> ';
            }
        }

        // 新規スレ
        if ($aThread->new) {
            $classtitle_q = ' class="thre_title_new"';
        } else {
            $classtitle_q = ' class="thre_title"';
        }

        // スレリンク
        if (!empty($_REQUEST['find_cont']) && strlen($GLOBALS['word_fm']) > 0) {
            $word_q = '&amp;word=' . rawurlencode($GLOBALS['word']) . '&amp;method=' . rawurlencode($GLOBALS['sb_filter']['method']);
            $rescount_q = '';
            $offline_q = '&amp;offline=true';
            $anum_ht = '';
        } else {
            $word_q = '';
        }
        $thre_url = "{$_conf['read_php']}?{$base_q}{$rescount_q}{$offline_q}{$word_q}{$anum_ht}";


        $chUnColor_js = ($midoku_ari) ? "chUnColor('{$i}');" : '';
        $change_color = " onclick=\"chTtColor('{$i}');{$chUnColor_js}\"";

        // オンリー>>1
        if ($only_one_bool) {
            $td['one'] = <<<EOP
<td{$class_t}><a href="{$_conf['read_php']}?{$base_q}&amp;one=true">&gt;&gt;1</a></td>\n
EOP;
        }

        // チェックボックス
        if ($checkbox_bool) {
            $checked_ht = '';
            if ($aThreadList->spmode == 'taborn') {
                if (!$aThread->isonline) { // or ($aThread->rescount >= 1000)
                    $checked_ht = ' checked';
                }
            }
            $td['checkbox'] = <<<EOP
<td{$class_tc}><input name="checkedkeys[]" type="checkbox" value="{$aThread->key}"{$checked_ht}></td>\n
EOP;
        }

        // 並替
        if ($sb_view == 'edit') {
            $td['unum'] = '';
            $td['rescount'] = '';
            $sb_view_q = '&amp;sb_view=edit';
            if ($aThreadList->spmode == 'fav') {
                $setkey = 'setfav';
            } elseif ($aThreadList->spmode == 'palace') {
                $setkey = 'setpal';
            }
            $narabikae_a = "{$_conf['subject_php']}?{$base_q}{$spmode_q}{$sb_view_q}";

            $td['edit'] = <<<EOP
<td{$class_te}>
    <a class="te" href="{$narabikae_a}&amp;{$setkey}=top" target="_self">▲</a>
    <a class="te" href="{$narabikae_a}&amp;{$setkey}=up" target="_self">↑</a>
    <a class="te" href="{$narabikae_a}&amp;{$setkey}=down" target="_self">↓</a>
    <a class="te" href="{$narabikae_a}&amp;{$setkey}=bottom" target="_self">▼</a>
</td>\n
EOP;
        }

        // 最近読んだスレの解除
        if ($aThreadList->spmode == 'recent') {
            $td['offrec'] = <<<EOP
<td{$class_tc}><a href="info.php?{$base_q}&amp;offrec=true" target="_self" onclick="return offrec_ajax(this);">×</a></td>\n
EOP;
        }

        // すばやさ（＝ 時間/レス ＝ レス間隔）
        if ($_conf['sb_show_spd']) {
            if ($spd_st = $aThread->getTimePerRes()) {
                $td['spd'] = "<td{$class_ts}>{$spd_st}</td>\n";
            }
        }

        // 勢い
        if ($_conf['sb_show_ikioi']) {
            if ($aThread->dayres > 0) {
                // 0.0 とならないように小数点第2位で切り上げ
                $dayres = ceil($aThread->dayres * 10) / 10;
                $dayres_st = sprintf("%01.1f", $dayres);
            } else {
                $dayres_st = '-';
            }
            $td['ikioi'] = "<td{$class_ti}>{$dayres_st}</td>\n";
        }

        // Birthday
        $birthday = date('y/m/d', $aThread->key); // (y/m/d H:i)
        $td['birth'] = "<td{$class_t}>{$birthday}</td>\n";

        //====================================================================================
        // スレッド一覧 table ボディ HTMLプリント <tr></tr>
        //====================================================================================

        // ボディ
        echo <<<EOR
<tr{$class_r}>
{$td['edit']}{$td['offrec']}{$td['unum']}{$td['rescount']}{$td['one']}{$td['checkbox']}<td{$class_to}>{$torder_ht}</td>
<td{$class_tl}>{$moto_thre_ht}<a id="tt{$i}" href="{$thre_url}" title="{$aThread->ttitle_hd}"{$classtitle_q}{$change_color}>{$ttitle_ht}</a></td>
{$td['ita']}{$td['spd']}{$td['ikioi']}{$td['birth']}{$td['fav']}</tr>\n
EOR;

    }

    echo "</tbody>\n";

    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_print()');
    return true;
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
