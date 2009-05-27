<?php
// p2 - スレッドサブジェクトHTML表示関数 (PC用)
// for subject.php

/**
 * スレッド一覧をHTML表示する (PC用 <tr>～</tr>)
 *
 * @access  public
 * @return  void
 */
function sb_print($aThreadList)
{
    global $_conf, $sb_view, $p2_setting, $STYLE;
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('sb_print()');
    
    if (!$aThreadList->threads) {
        echo "<tr><td>　該当サブジェクトはなかったぽ</td></tr>";
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_print()');
        return;
    }
    
    // 変数 ================================================
    
    // >>1 表示
    $onlyone_bool = false;
    if (($_conf['sb_show_one'] == 1) or ($_conf['sb_show_one'] == 2 and ereg("news", $aThreadList->bbs) || $aThreadList->bbs == "bizplus")) {
        // spmodeは除く
        if (empty($aThreadList->spmode)) {
            $onlyone_bool = true;
        }
    }
    
    // チェックボックス
    if ($aThreadList->spmode == "taborn" or $aThreadList->spmode == "soko") {
        $checkbox_bool = true;
    } else {
        $checkbox_bool = false;
    }
    
    // 板名
    if ($aThreadList->spmode and $aThreadList->spmode != "taborn" and $aThreadList->spmode != "soko") {
        $ita_name_bool = true;
    } else {
        $ita_name_bool = false;
    }

    $norefresh_q = "&amp;norefresh=1";

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
    if ($GLOBALS['now_sort']) {
        $nowsort_code = <<<EOP
\$class_sort_{$GLOBALS['now_sort']}=' class="now_sort"';
EOP;
        eval($nowsort_code);
    }

    $sortq_spmode = '';
    $sortq_host   = '';
    $sortq_ita    = '';
    // spmode時
    if ($aThreadList->spmode) { 
        $sortq_spmode = "&amp;spmode={$aThreadList->spmode}";
    }
    // spmodeでない、または、spmodeがあぼーん or dat倉庫なら
    if (!$aThreadList->spmode || $aThreadList->spmode == "taborn" || $aThreadList->spmode == "soko") { 
        $sortq_host = "&amp;host={$aThreadList->host}";
        $sortq_ita = "&amp;bbs={$aThreadList->bbs}";
    }
    
    $midoku_sort_ht = "<td class=\"tu\" nowrap><a{$class_sort_midoku} href=\"{$_conf['subject_php']}?sort=midoku{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\" style=\"white-space:nowrap;\"><nobr>新着</nobr></a></td>";

    //=====================================================
    // テーブルヘッダHTML表示
    //=====================================================
    echo '<tr class="tableheader">' . "\n";
    
    // 並替
    if ($sb_view == "edit") { echo '<td class="te">&nbsp;</td>'; }
    
    // 新着
    if ($sb_view != "edit") { echo $midoku_sort_ht; }
    
    // レス数
    if ($sb_view != "edit") {
        echo "<td class=\"tn\" nowrap><a{$class_sort_res} href=\"{$_conf['subject_php']}?sort=res{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">レス</a></td>";
    }
    
    // >>1
    if ($onlyone_bool) {
        echo '<td class="t">&nbsp;</td>';
    }
    
    // チェックボックス
    if ($checkbox_bool) {
        echo '<td class="tc"><input id="allbox" name="allbox" type="checkbox" onClick="checkAll();" title="すべての項目を選択、または選択解除"></td>';
    }
    
    // No.
    $title = empty($aThreadList->spmode) ? " title=\"2ch標準の並び順番号\"" : '';
    echo "<td class=\"to\"><a{$class_sort_no} href=\"{$_conf['subject_php']}?sort=no{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\"{$title}>No.</a></td>";
    
    // タイトル
    echo "<td class=\"tl\"><a{$class_sort_title} href=\"{$_conf['subject_php']}?sort=title{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">タイトル</a></td>";
    
    // 板
    if ($ita_name_bool) {
        echo "<td class=\"t\"><a{$class_sort_ita} href=\"{$_conf['subject_php']}?sort=ita{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">板</a></td>";
    }
    
    // すばやさ
    if ($_conf['sb_show_spd']) {
        echo "<td class=\"ts\"><a{$class_sort_spd} href=\"{$_conf['subject_php']}?sort=spd{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">すばやさ</a></td>";
    }
    
    // 勢い
    if ($_conf['sb_show_ikioi']) {
        echo "<td class=\"ti\"><a{$class_sort_ikioi} href=\"{$_conf['subject_php']}?sort=ikioi{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\" title=\"一日あたりのレス数\">勢い</a></td>";
    }
    
    // Birthday
    echo "<td class=\"t\"><a{$class_sort_bd} href=\"{$_conf['subject_php']}?sort=bd{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\">Birthday</a></td>";
    
    // お気に入り
    if ($_conf['sb_show_fav'] and $aThreadList->spmode != "taborn") {
        echo "<td class=\"t\"><a{$class_sort_fav} href=\"{$_conf['subject_php']}?sort=fav{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}\" target=\"_self\" title=\"お気にスレ\">☆</a></td>";
    }
    
    echo "\n</tr>\n";

    //=====================================================
    //テーブルボディ
    //=====================================================

    //spmodeがあればクエリー追加
    if ($aThreadList->spmode) {
        $spmode_q = "&amp;spmode={$aThreadList->spmode}";
    } else {
        $spmode_q = '';
    }
    
    $sid_q = (defined('SID') && strlen(SID)) ? '&amp;' . hs(SID) : '';
    
    $i = 0;
    foreach ($aThreadList->threads as $aThread) {
        $i++;
        $midoku_ari = '';
        $anum_ht = ''; // #r1
        
        $offline_q = '';
        
        $bbs_q = "&amp;bbs=" . $aThread->bbs;
        $key_q = "&amp;key=" . $aThread->key;

        if ($aThreadList->spmode != 'taborn') {
            if (!$aThread->torder) { $aThread->torder = $i; }
        }

        // td欄 cssクラス
        if (($i % 2) == 0) {
            $class_t  = ' class="t"';     // 基本
            $class_te = ' class="te"';    // 並び替え
            $class_tu = ' class="tu"';    // 新着レス数
            $class_tn = ' class="tn"';    // レス数
            $class_tc = ' class="tc"';    // チェックボックス
            $class_to = ' class="to"';    // オーダー番号
            $class_tl = ' class="tl"';    // タイトル
            $class_ts = ' class="ts"';    // すばやさ
            $class_ti = ' class="ti"';    // 勢い
        } else {
            $class_t  = ' class="t2"';
            $class_te = ' class="te2"';
            $class_tu = ' class="tu2"';
            $class_tn = ' class="tn2"';
            $class_tc = ' class="tc2"';
            $class_to = ' class="to2"';
            $class_tl = ' class="tl2"';
            $class_ts = ' class="ts2"';
            $class_ti = ' class="ti2"';
        }
    
        // 新着レス数 =============================================
        $unum_ht_c = "&nbsp;";
        
        // 既得済み
        if ($aThread->isKitoku()) {
            
            // $ttitle_en_q は節減省略
            $onclick_at = " onClick=\"return deleLog('host={$aThread->host}{$bbs_q}{$key_q}{$sid_q}', {$STYLE['info_pop_size']}, 'subject', this);\"";
            $title_at = ' title="クリックするとログ削除"';
            
            $unum_ht_c = "<a class=\"un\" href=\"{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$spmode_q}&amp;dele=1\" target=\"_self\"{$onclick_at}{$title_at}>{$aThread->unum}</a>";
        
            $anum = $aThread->rescount - $aThread->unum + 1 - $_conf['respointer'];
            if ($anum > $aThread->rescount) {
                $anum = $aThread->rescount;
            }
            $anum_ht = '#r' . $anum;
            
            // {{{ 新着あり
            
            if ($aThread->unum > 0) {
                $midoku_ari = true;
                
                $dele_log_qs = $thread_qs = array(
                    'host' => $aThread->host, 'bbs' => $aThread->bbs, 'key' => $aThread->key
                );
                if (defined('SID') && strlen(SID)) {
                    $dele_log_qs[session_name()] = session_id();
                }
                $dele_log_q = P2Util::buildQuery($dele_log_qs);

                $unum_ht_c = P2View::tagA(
                    P2Util::buildQueryUri($_conf['subject_php'],
                        array_merge(
                            $thread_qs,
                            array(
                                'spmode' => $aThreadList->spmode, 'dele' => '1',
                                UA::getQueryKey() => UA::getQueryValue()
                            )
                        )
                    ),
                    hs($aThread->unum),
                    array(
                        'id' => "un{$i}", 'class' => 'un_a', 'target' => '_self', 'title' => 'クリックするとログ削除',
                        'onClick' => sprintf(
                            "return deleLog('%s', %s, 'subject', this);",
                            str_replace("'", "\\'", $dele_log_q), $STYLE['info_pop_size']
                        )
                    )
                );
            }
            
            // }}}
            
            // subject.txtにない時
            if (!$aThread->isonline) {
                // JavaScriptでの確認ダイアログあり
                $unum_ht_c = "<a class=\"un_n\" href=\"{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$spmode_q}&amp;dele=true\" target=\"_self\" onClick=\"if (!window.confirm('ログを削除しますか？')) {return false;} return deleLog('host={$aThread->host}{$bbs_q}{$key_q}{$sid_q}', {$STYLE['info_pop_size']}, 'subject', this)\"{$title_at}>-</a>";
            }

        }
        
        $unum_ht = "<td{$class_tu}>" . $unum_ht_c . "</td>";
        
        // 総レス数
        $rescount_ht = "<td{$class_tn}>{$aThread->rescount}</td>";

        // {{{ 板名
        
        $ita_td_ht = '';
        if ($ita_name_bool) {
            $ita_name = $aThread->itaj ? $aThread->itaj : $aThread->bbs;
            $ita_atag = P2View::tagA(
                P2Util::buildQueryUri($_conf['subject_php'],
                    array(
                        'host' => $aThread->host,
                        'bbs'  => $aThread->bbs
                    )
                ),
                $ita_name,
                array('target' => '_self')
            );
            $ita_td_ht = "<td{$class_t} nowrap>{$ita_atag}</td>";
        }
        
        // }}}
        
        // お気に入り
        if ($_conf['sb_show_fav'] and $aThreadList->spmode != 'taborn') {
            
            $favmark    = !empty($aThread->fav) ? '★' : '+';
            $favvalue   = !empty($aThread->fav) ? 0 : 1;
            $favtitle   = $favvalue ? 'お気にスレに追加' : 'お気にスレから外す';
            $setfav_q   = '&amp;setfav=' . $favvalue;

            // $ttitle_en_q も付けた方がいいが、節約のため省略する
            $fav_ht = <<<EOP
<td{$class_t}><a class="fav" href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$setfav_q}" target="info" onClick="return setFavJs('host={$aThread->host}{$bbs_q}{$key_q}', '{$favvalue}', {$STYLE['info_pop_size']}, 'subject', this);" title="{$favtitle}">{$favmark}</a></td>
EOP;
        }
        
        // torder(info) =================================================
        // お気にスレ
        if ($aThread->fav) {
            $torder_st = "<b>{$aThread->torder}</b>";
        } else {
            $torder_st = $aThread->torder;
        }
        $torder_ht = "<a id=\"to{$i}\" class=\"info\" href=\"info.php?host={$aThread->host}{$bbs_q}{$key_q}\" target=\"_self\" onClick=\"return !openSubWin('info.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;popup=1{$sid_q}',{$STYLE['info_pop_size']},0,0)\">{$torder_st}</a>";
        
        // title =================================================
        $chUnColor_ht = '';
        
        $rescount_q = "&amp;rc=" . $aThread->rescount;
        
        // dat倉庫 or 殿堂なら
        if ($aThreadList->spmode == "soko" || $aThreadList->spmode == "palace") { 
            $rescount_q = '';
            $offline_q  = "&amp;offline=1";
            $anum_ht    = '';
        }
        
        // タイトル未取得or全角空白なら（IEで全角空白もリンククリックできないので）
        if (!$aThread->ttitle_ht or $aThread->ttitle_ht == '　') { 
            $aThread->ttitle_ht = "http://{$aThread->host}/test/read.cgi/{$aThread->bbs}/{$aThread->key}/";
        }
        
        if ($aThread->similarity) {
            $aThread->ttitle_ht .= sprintf(' <var>(%0.1f)</var>', $aThread->similarity * 100);
        }
        
        // 元スレ
        $moto_thre_ht = '';
        if ($_conf['sb_show_motothre']) {
            if (!$aThread->isKitoku()) {
                $moto_thre_ht = '<a class="thre_title" href="' . hs($aThread->getMotoThread()) . '">・</a> ';
            }
        }
        
        // 新規スレ
        if ($aThread->new) { 
            $classtitle_q = ' class="thre_title_new"';
        } else {
            $classtitle_q = ' class="thre_title"';
        }
        
        // スレリンク
        if (!empty($_REQUEST['find_cont']) && strlen($GLOBALS['word_fm'])) {
            $word_q = "&amp;word=" . urlencode($GLOBALS['word']) . "&amp;method=" . urlencode($GLOBALS['sb_filter']['method']);
            $rescount_q = '';
            $offline_q  = '&amp;offline=1';
            $anum_ht    = '';
        } else {
            $word_q = '';
        }
        $thre_url = "{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$rescount_q}{$offline_q}{$word_q}{$anum_ht}";
        
        
        if ($midoku_ari) {
            $chUnColor_ht = "chUnColor('{$i}');";
        }
        $change_color = " onClick=\"chTtColor('{$i}');{$chUnColor_ht}\"";
        
        // オンリー>>1
        if ($onlyone_bool) {
            $one_ht = "<td{$class_t}><a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$rescount_q}&amp;onlyone=true\">&gt;&gt;1</a></td>";
        } else {
            $one_ht = '';
        }
        
        // チェックボックス
        $checkbox_ht = '';
        if ($checkbox_bool) {
            $checked_ht = '';
            if ($aThreadList->spmode == "taborn") {
                if (!$aThread->isonline) { $checked_ht=" checked"; } // or ($aThread->rescount >= 1000)
            }
            $checkbox_ht = "<td{$class_tc}><input name=\"checkedkeys[]\" type=\"checkbox\" value=\"{$aThread->key}\"$checked_ht></td>";
        }
        
        // 並替
        $edit_ht = '';
        if ($sb_view == "edit") {
            $unum_ht = '';
            $rescount_ht = '';
            if ($aThreadList->spmode == "fav") {
                $setkey = "setfav";
            } elseif ($aThreadList->spmode == "palace") {
                $setkey = "setpal";
            }
            
            $narabikae_a = P2Util::buildQueryUri(
                $_conf['subject_php'],
                array(
                    'host'    => $aThread->host,
                    'bbs'     => $aThread->bbs,
                    'key'     => $aThread->key,
                    'spmode'  => $aThreadList->spmode,
                    'sb_view' => 'edit'
                )
            );
            
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
        $spd_ht = '';
        if ($_conf['sb_show_spd']) {
            if ($spd_st = $aThread->getTimePerRes()) {
                $spd_ht = "<td{$class_ts}>{$spd_st}</td>";
            }        
        }
        
        // 勢い
        $ikioi_ht = '';
        if ($_conf['sb_show_ikioi']) {
            if ($aThread->dayres > 0) {
                // 0.0 とならないように小数点第2位で切り上げ
                $dayres = ceil($aThread->dayres * 10) / 10;
                $dayres_st = sprintf("%01.1f", $dayres);
            } else {
                $dayres_st = "-";
            }
            $ikioi_ht = "<td{$class_ti}>" . hs($dayres_st) . "</td>";
        }
        
        // Birthday
        //if (preg_match('/^\d{9,10}$/', $aThread->key) {
        if (631119600 < $aThread->key && $aThread->key < time() + 1000) { // 1990年-
            $birthday = date("y/m/d", $aThread->key); // (y/m/d H:i)
        } else {
            $birthday = '-';
        }
        $birth_ht = "<td{$class_t}>{$birthday}</td>";


        // スレッド一覧 table ボディ HTMLプリント <tr></tr> 
        echo "<tr>
$edit_ht
$unum_ht
$rescount_ht
$one_ht
$checkbox_ht
<td{$class_to}>{$torder_ht}</td>
<td{$class_tl} nowrap>$moto_thre_ht<a id=\"tt{$i}\" href=\"{$thre_url}\"{$classtitle_q}{$change_color}>{$aThread->ttitle_ht}</a></td>
$ita_td_ht
$spd_ht
$ikioi_ht
$birth_ht
$fav_ht
</tr>
";
            ob_flush(); flush();
    }

    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_print()');
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
