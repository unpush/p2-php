<?php
// p2 スレッドサブジェクト表示関数
// for subject.php

/**
 * sb_print - スレッド一覧を表示する (<tr>～</tr>)
 */
function sb_print(&$aThreadList)
{
    global $_conf, $browser, $_conf, $sb_view, $p2_setting, $STYLE;

    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('sb_print()');

    if (!$aThreadList->threads) {
        print "<tr><td>　該当サブジェクトはなかったぽ</td></tr>";
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_print()');
        return;
    }

    // 変数 ================================================

    // >>1 表示
    if (($_conf['sb_show_one'] == 1) or ($_conf['sb_show_one'] == 2 and ereg("news", $aThreadList->bbs) || $aThreadList->bbs == "bizplus")) {
        // spmodeは除く
        if (empty($aThreadList->spmode)) {
            $only_one_bool = true;
        }
    }

    // チェックボックス
    if ($aThreadList->spmode == "taborn" or $aThreadList->spmode == "soko") {
        $checkbox_bool = true;
    }

    // 板名
    if ($aThreadList->spmode and $aThreadList->spmode != "taborn" and $aThreadList->spmode != "soko") {
        $ita_name_bool = true;
    }

    $norefresh_q = "&amp;norefresh=true";

    // ソート ==================================================

    // 現在のソート形式をclass指定でCSSカラーリング ======================
    $class_sort_midoku = "";    // 新着
    $class_sort_res = "";       // レス
    $class_sort_no = "";        // No.
    $class_sort_title = "";     // タイトル
    $class_sort_ita = "";       // 板
    $class_sort_spd = "";       // すばやさ
    $class_sort_ikioi = "";     // 勢い
    $class_sort_bd = "";        // Birthday
    $class_sort_fav = "";       // お気に入り
    if ($GLOBALS['now_sort']) {
        $nowsort_code = <<<EOP
\$class_sort_{$GLOBALS['now_sort']}=' class="now_sort"';
EOP;
        eval($nowsort_code);
    }

    $sortq_spmode = '';
    $sortq_host = '';
    $sortq_ita = '';
    // spmode時
    if ($aThreadList->spmode) {
        $sortq_spmode = "&amp;spmode={$aThreadList->spmode}";
    }
    // spmodeでない、または、spmodeがあぼーん or dat倉庫なら
    if (!$aThreadList->spmode || $aThreadList->spmode == "taborn" || $aThreadList->spmode == "soko") {
        $sortq_host = "&amp;host={$aThreadList->host}";
        $sortq_ita = "&amp;bbs={$aThreadList->bbs}";
    }

    $midoku_sort_ht = "<td id=\"sb_th_midoku\" class=\"tu\" nowrap><a{$class_sort_midoku} href=\"{$_conf['subject_php']}?sort=midoku{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">新着</a></td>";

    //=====================================================
    // テーブルヘッダ
    //=====================================================
    echo "<tr class=\"tableheader\">\n";

    // 並替
    if ($sb_view == "edit") { echo "<td class=\"te\">&nbsp;</td>"; }
    // 新着
    if ($sb_view != "edit") { echo $midoku_sort_ht; }
    // レス数
    if ($sb_view != "edit") {
        echo "<td id=\"sb_th_res\" class=\"tn\" nowrap><a{$class_sort_res} href=\"{$_conf['subject_php']}?sort=res{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">レス</a></td>";
    }
    // >>1
    if ($only_one_bool) { echo "<td class=\"t\">&nbsp;</td>"; }
    // チェックボックス
    if ($checkbox_bool) {
        echo "<td class=\"tc\"><input id=\"allbox\" name=\"allbox\" type=\"checkbox\" onClick=\"checkAll();\" title=\"すべての項目を選択、または選択解除\"></td>";
    }
    // No.
    $title = empty($aThreadList->spmode) ? " title=\"2ch標準の並び順番号\"" : '';
    echo "<td id=\"sb_th_no\" class=\"to\"><a{$class_sort_no} href=\"{$_conf['subject_php']}?sort=no{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\"{$title}>No.</a></td>";
    // タイトル
    echo "<td id=\"sb_th_title\" class=\"tl\"><a{$class_sort_title} href=\"{$_conf['subject_php']}?sort=title{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">タイトル</a></td>";
    // 板
    if ($ita_name_bool) {
        echo "<td id=\"sb_th_ita\" class=\"t\"><a{$class_sort_ita} href=\"{$_conf['subject_php']}?sort=ita{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">板</a></td>";
    }
    // すばやさ
    if ($_conf['sb_show_spd']) {
        echo "<td id=\"sb_th_spd\" class=\"ts\"><a{$class_sort_spd} href=\"{$_conf['subject_php']}?sort=spd{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">すばやさ</a></td>";
    }
    // 勢い
    if ($_conf['sb_show_ikioi']) {
        echo "<td id=\"sb_th_ikioi\" class=\"ti\"><a{$class_sort_ikioi} href=\"{$_conf['subject_php']}?sort=ikioi{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">勢い</a></td>";
    }
    // Birthday
    echo "<td id=\"sb_th_bd\" class=\"t\"><a{$class_sort_bd} href=\"{$_conf['subject_php']}?sort=bd{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">Birthday</a></td>";
    // お気に入り
    if ($_conf['sb_show_fav'] and $aThreadList->spmode != "taborn") {
        echo "<td id=\"sb_th_fav\" class=\"t\"><a{$class_sort_fav} href=\"{$_conf['subject_php']}?sort=fav{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\" title=\"お気にスレ\">☆</a></td>";
    }

    echo "\n</tr>\n";

    //=====================================================
    //テーブルボディ
    //=====================================================

    //spmodeがあればクエリー追加
    if ($aThreadList->spmode) {
        $spmode_q = "&amp;spmode={$aThreadList->spmode}";
    }
    $sid_q = (defined('SID')) ? '&amp;'.strip_tags(SID) : '';

    // td欄 cssクラス
    $class_t = ' class="t"';    // 基本
    $class_te = ' class="te"';  // 並び替え
    $class_tu = ' class="tu"';  // 新着レス数
    $class_tn = ' class="tn"';  // レス数
    $class_tc = ' class="tc"';  // チェックボックス
    $class_to = ' class="to"';  // オーダー番号
    $class_tl = ' class="tl"';  // タイトル
    $class_ts = ' class="ts"';  // すばやさ
    $class_ti = ' class="ti"';  // 勢い

    $i = 0;
    foreach ($aThreadList->threads as $aThread) {
        $i++;
        $midoku_ari = "";
        $anum_ht = ""; // #r1

        $bbs_q = "&amp;bbs=".$aThread->bbs;
        $key_q = "&amp;key=".$aThread->key;

        if ($aThreadList->spmode != "taborn") {
            if (!$aThread->torder) { $aThread->torder = $i; }
        }

        // tr欄 cssクラス
        if ($i % 2) {
            $class_r = ' class="r1"';   // 奇数行
        } else {
            $class_r = ' class="r2"';   // 偶数行
        }

        //新着レス数 =============================================
        $unum_ht_c = "&nbsp;";
        // 既得済み
        if ($aThread->isKitoku()) {

            // $ttitle_en_q は節減省略
            $onclick_at = " onClick=\"return deleLog('host={$aThread->host}{$bbs_q}{$key_q}{$sid_q}', {$STYLE['info_pop_size']}, 'subject', this);\"";
            $title_at = " title=\"クリックするとログ削除\"";

            $unum_ht_c = "<a class=\"un\" href=\"{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$spmode_q}&amp;dele=true\" target=\"_self\"{$onclick_at}{$title_at}>{$aThread->unum}</a>";

            $anum = $aThread->rescount - $aThread->unum + 1 - $_conf['respointer'];
            if ($anum > $aThread->rescount) { $anum = $aThread->rescount; }
            $anum_ht = "#r".$anum;

            // 新着あり
            if ($aThread->unum > 0) {
                $midoku_ari = true;
                $unum_ht_c = "<a id=\"un{$i}\" class=\"un_a\" href=\"{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$spmode_q}&amp;dele=true\" target=\"_self\"{$onclick_at}{$title_at}>$aThread->unum</a>";
            }

            // subject.txtにない時
            if (!$aThread->isonline) {
                // JavaScriptでの確認ダイアログあり
                $unum_ht_c = "<a class=\"un_n\" href=\"{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$spmode_q}&amp;dele=true\" target=\"_self\" onClick=\"if (!window.confirm('ログを削除しますか？')) {return false;} return deleLog('host={$aThread->host}{$bbs_q}{$key_q}{$sid_q}', {$STYLE['info_pop_size']}, 'subject', this)\"{$title_at}>-</a>";
            }

        }

        $unum_ht = "<td{$class_tu}>".$unum_ht_c."</td>";

        // 総レス数 =============================================
        $rescount_ht = "<td{$class_tn}>{$aThread->rescount}</td>";

        // 板名 ============================================
        if ($ita_name_bool) {
            $ita_name = $aThread->itaj ? $aThread->itaj : $aThread->bbs;
            $htm['ita_td'] = "<td{$class_t} nowrap><a href=\"{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}\" target=\"_self\">" . htmlspecialchars($ita_name, ENT_QUOTES) . "</a></td>";
        }


        // お気に入り ========================================
        if ($_conf['sb_show_fav']) {
            if ($aThreadList->spmode != "taborn") {

                $favmark = (!empty($aThread->favs[$_SESSION['m_favlist_set']])) ? '★' : '+';
                $favdo = (!empty($aThread->favs[$_SESSION['m_favlist_set']])) ? 0 : 1;
                $favtitle = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
                $favdo_q = '&amp;setfav='.$favdo;

                // $ttitle_en_q も付けた方がいいが、節約のため省略する
                $fav_ht = <<<EOP
<td{$class_t}><a class="fav" href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$favdo_q}" target="info" onClick="return setFavJs('host={$aThread->host}{$bbs_q}{$key_q}', '{$favdo}', {$STYLE['info_pop_size']}, 'subject', this);" title="{$favtitle}">{$favmark}</a></td>
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
        $torder_ht = "<a id=\"to{$i}\" class=\"info\" href=\"info.php?host={$aThread->host}{$bbs_q}{$key_q}\" target=\"_self\" onClick=\"return OpenSubWin('info.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;popup=1{$sid_q}',{$STYLE['info_pop_size']},0,0)\">{$torder_st}</a>";

        // title =================================================
        $chUnColor_ht = "";

        $rescount_q = "&amp;rescount=" . $aThread->rescount;

        // dat倉庫 or 殿堂なら
        if ($aThreadList->spmode == "soko" || $aThreadList->spmode == "palace") {
            $rescount_q = "";
            $offline_q = "&amp;offline=true";
            $anum_ht = "";
        }

        // タイトル未取得なら
        if (!$aThread->ttitle_ht) {
            $aThread->ttitle_ht = "http://{$aThread->host}/test/read.cgi/{$aThread->bbs}/{$aThread->key}/";
        }

        if ($aThread->similarity) {
            $aThread->ttitle_ht .= sprintf(' <var>(%0.1f)</var>', $aThread->similarity * 100);
        }

        // 元スレ
        $moto_thre_ht = "";
        if ($_conf['sb_show_motothre']) {
            if (!$aThread->isKitoku()) {
                $moto_thre_ht = '<a class="thre_title" href="'.$aThread->getMotoThread().'">・</a> ';
            }
        }

        // 新規スレ
        if ($aThread->new) {
            $classtitle_q = " class=\"thre_title_new\"";
        } else {
            $classtitle_q = " class=\"thre_title\"";
        }

        // スレリンク
        if (!empty($_REQUEST['find_cont']) && strlen($GLOBALS['word_fm']) > 0) {
            $word_q = "&amp;word=".urlencode($GLOBALS['word'])."&amp;method=".urlencode($GLOBALS['sb_filter']['method']);
            $rescount_q = "";
            $offline_q = "&amp;offline=true";
            $anum_ht = '';
        } else {
            $word_q = '';
        }
        $thre_url = "{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$rescount_q}{$offline_q}{$word_q}{$anum_ht}";


        if ($midoku_ari) {
            $chUnColor_ht = "chUnColor('{$i}');";
        }
        $change_color = " onClick=\"chTtColor('{$i}');{$chUnColor_ht}\"";

        // オンリー>>1 =============================================
        if ($only_one_bool) {
            $one_ht = "<td{$class_t}><a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;one=true\">&gt;&gt;1</a></td>";
        }

        // チェックボックス =============================================
        if ($checkbox_bool) {
            $checked_ht = "";
            if ($aThreadList->spmode == "taborn") {
                if (!$aThread->isonline) { $checked_ht=" checked"; } // or ($aThread->rescount >= 1000)
            }
            $checkbox_ht = "<td{$class_tc}><input name=\"checkedkeys[]\" type=\"checkbox\" value=\"{$aThread->key}\"$checked_ht></td>";
        }

        // 並替 =============================================
        if ($sb_view == "edit") {
            $unum_ht = "";
            $rescount_ht = "";
            $sb_view_q = "&amp;sb_view=edit";
            if ($aThreadList->spmode == "fav") {
                $setkey = "setfav";
            } elseif ($aThreadList->spmode == "palace") {
                $setkey = "setpal";
            }
            $narabikae_a = "{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$spmode_q}{$sb_view_q}";

            $edit_ht = <<<EOP
        <td{$class_te}>
            <a class="te" href="{$narabikae_a}&amp;{$setkey}=top" target="_self">▲</a>
            <a class="te" href="{$narabikae_a}&amp;{$setkey}=up" target="_self">↑</a>
            <a class="te" href="{$narabikae_a}&amp;{$setkey}=down" target="_self">↓</a>
            <a class="te" href="{$narabikae_a}&amp;{$setkey}=bottom" target="_self">▼</a>
        </td>
EOP;
        }

        // すばやさ（＝ 時間/レス ＝ レス間隔）
        $spd_ht = "";
        if ($_conf['sb_show_spd']) {
            if ($spd_st = $aThread->getTimePerRes()) {
                $spd_ht = "<td{$class_ts}>{$spd_st}</td>";
            }
        }

        // 勢い
        $ikioi_ht = "";
        if ($_conf['sb_show_ikioi']) {
            if ($aThread->dayres > 0) {
                // 0.0 とならないように小数点第2位で切り上げ
                $dayres = ceil($aThread->dayres * 10) / 10;
                $dayres_st = sprintf("%01.1f", $dayres);
            } else {
                $dayres_st = "-";
            }
            $ikioi_ht = "<td{$class_ti}>".$dayres_st."</td>";
        }

        // Birthday
        $birthday = date("y/m/d", $aThread->key); // (y/m/d H:i)
        $birth_ht = "<td{$class_t}>{$birthday}</td>";

        //====================================================================================
        // スレッド一覧 table ボディ HTMLプリント <tr></tr>
        //====================================================================================

        // ボディ
        echo "<tr{$class_r}>{$edit_ht}{$unum_ht}{$rescount_ht}{$one_ht}{$checkbox_ht}
<td{$class_to}>{$torder_ht}</td>
<td{$class_tl} nowrap>$moto_thre_ht<a id=\"tt{$i}\" href=\"{$thre_url}\" title=\"{$aThread->ttitle_hd}\"{$classtitle_q}{$change_color}>{$aThread->ttitle_ht}</a></td>
{$htm['ita_td']}{$spd_ht}{$ikioi_ht}{$birth_ht}{$fav_ht}</tr>\n";

    }

    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_print()');
    return true;
}
