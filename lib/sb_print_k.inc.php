<?php
// p2 スレッドサブジェクト表示関数 携帯用
// for subject.php

/**
 * sb_print - スレッド一覧を表示する (<tr>～</tr>)
 */
function sb_print_k(&$aThreadList)
{
    global $_conf, $browser, $_conf, $sb_view, $p2_setting, $STYLE;
    global $sb_view;

    //=================================================

    if (!$aThreadList->threads) {
        if ($aThreadList->spmode == "fav" && $sb_view == "shinchaku") {
            echo '<p class="empty-subject">お気にｽﾚに新着なかったぽ</p>';
        } else {
            echo '<p class="empty-subject">該当ｻﾌﾞｼﾞｪｸﾄはなかったぽ</p>';
        }
        return;
    }

    // 変数 ================================================

    // >>1
    if (ereg("news", $aThreadList->bbs) || $aThreadList->bbs=="bizplus" || $aThreadList->spmode=="news") {
        // 倉庫は除く
        if ($aThreadList->spmode != "soko") {
            $only_one_bool = true;
        }
    }

    // 板名
    if ($aThreadList->spmode and $aThreadList->spmode != "taborn" and $aThreadList->spmode != "soko") {
        $ita_name_bool = true;
    }

    $norefresh_q = "&amp;norefresh=1";

    // ソート ==================================================

    // スペシャルモード時
    if ($aThreadList->spmode) {
        $sortq_spmode = "&amp;spmode={$aThreadList->spmode}";
        // あぼーんなら
        if ($aThreadList->spmode == "taborn" or $aThreadList->spmode == "soko") {
            $sortq_host = "&amp;host={$aThreadList->host}";
            $sortq_ita = "&amp;bbs={$aThreadList->bbs}";
        }
    } else {
        $sortq_host = "&amp;host={$aThreadList->host}";
        $sortq_ita = "&amp;bbs={$aThreadList->bbs}";
    }

    $midoku_sort_ht = "<a href=\"{$_conf['subject_php']}?sort=midoku{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}{$_conf['k_at_a']}\">新着</a>";

    //=====================================================
    // ボディ
    //=====================================================

    // spmodeがあればクエリー追加
    if ($aThreadList->spmode) {$spmode_q = "&amp;spmode={$aThreadList->spmode}";}

    if ($_conf['iphone']) {
        echo '<ul class="subject">';
    }

    $i = 0;
    foreach ($aThreadList->threads as $aThread) {
        $i++;
        $midoku_ari = "";
        $anum_ht = ""; //#r1
        $htm = array('ita' => '', 'rnum' => '', 'unum' => '', 'sim' => '');

        $bbs_q = "&amp;bbs=".$aThread->bbs;
        $key_q = "&amp;key=".$aThread->key;

        if ($aThreadList->spmode!="taborn") {
            if (!$aThread->torder) {$aThread->torder=$i;}
        }

        // 新着レス数 =============================================
        // 既得済み
        if ($aThread->isKitoku()) {
            $htm['unum'] = "{$aThread->unum}";

            $anum = $aThread->rescount - $aThread->unum +1 - $_conf['respointer'];
            if ($anum > $aThread->rescount) { $anum = $aThread->rescount; }
            $anum_ht = "#r{$anum}";

            // iPhone用
            if ($_conf['iphone']) {
                $classunum = 'num unread';

                // 新着あり
                if ($aThread->unum >= 1) {
                    $midoku_ari = true;
                    $classunum .= ' new';
                }

                // subject.txtにない時
                if (!$aThread->isonline) {
                    $htm['unum'] = '-';
                    $classunum .= ' offline';
                }

                $htm['unum'] = "<span class=\"{$classunum}\">{$htm['unum']}</span>";
            } else {
                // 新着あり
                if ($aThread->unum >= 1) {
                    $midoku_ari = true;
                    $htm['unum'] = "<font color=\"{$STYLE['mobile_subject_newres_color']}\">{$aThread->unum}</font>";
                }

                // subject.txtにない時
                if (!$aThread->isonline) {
                    $htm['unum'] = '-';
                }

                $htm['unum'] = "[{$htm['unum']}]";
            }
        }

        // 新規スレ
        if ($_conf['iphone']) {
            $classtitle = 'title';
            if ($aThread->new) {
                $classtitle .= ' new';
            }
        } else {
            if ($aThread->new) {
                $htm['unum'] = "<font color=\"{$STYLE['mobile_subject_newthre_color']}\">新</font>";
            }
        }

        // 総レス数 =============================================
        $rescount_ht = "{$aThread->rescount}";

        // 板名 ============================================
        if ($ita_name_bool) {
            $ita_name = $aThread->itaj ? $aThread->itaj : $aThread->bbs;
            $ita_name_hd = htmlspecialchars($ita_name, ENT_QUOTES);

            // 全角英数カナスペースを半角に
            if (!empty($_conf['k_save_packet'])) {
                $ita_name_hd = mb_convert_kana($ita_name_hd, 'rnsk');
            }

            //$htm['ita'] = "<a href=\"{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$_conf['k_at_a']}\">{$ita_name_hd}</a>)";
            //$htm['ita'] = " ({$ita_name_hd})";
            $htm['ita'] = $ita_name_hd;
        }

        // torder(info) =================================================
        /*
        if ($aThread->fav) { //お気にスレ
            $torder_st = "<b>{$aThread->torder}</b>";
        } else {
            $torder_st = $aThread->torder;
        }
        $torder_ht = "<a id=\"to{$i}\" class=\"info\" href=\"info.php?host={$aThread->host}{$bbs_q}{$key_q}{$_conf['k_at_a']}\">{$torder_st}</a>";
        */
        $torder_ht = $aThread->torder;

        // title =================================================
        $rescount_q = "&amp;rescount=" . $aThread->rescount;

        // dat倉庫 or 殿堂なら
        if ($aThreadList->spmode == "soko" || $aThreadList->spmode == "palace") {
            $rescount_q = "";
            $offline_q = "&amp;offline=true";
            $anum_ht = "";
        }

        // タイトル未取得なら
        if (!$aThread->ttitle_ht) {
            // 見かけ上のタイトルなので携帯対応URLである必要はない
            //if (P2Util::isHost2chs($aThread->host)) {
            //    $aThread->ttitle_ht = "http://c.2ch.net/z/-/{$aThread->bbs}/{$aThread->key}/";
            //}else{
                $aThread->ttitle_ht = "http://{$aThread->host}/test/read.cgi/{$aThread->bbs}/{$aThread->key}/";
            //}
        }

        // 全角英数カナスペースを半角に
        if (!empty($_conf['k_save_packet'])) {
            $aThread->ttitle_ht = mb_convert_kana($aThread->ttitle_ht, 'rnsk');
        }

        if ($_conf['iphone']) {
            $htm['rnum'] = "<span class=\"num count\">{$rescount_ht}</span>";
            if ($aThread->similarity) {
                $htm['sim'] .= sprintf(' <span class=\"num score\">%0.1f%%</span>', $aThread->similarity * 100);
            }
        } else {
            $htm['rnum'] = "({$rescount_ht})";
            if ($aThread->similarity) {
                $htm['sim'] .= sprintf(' %0.1f%%', $aThread->similarity * 100);
            }
        }

        $thre_url = "{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$rescount_q}{$offline_q}{$_conf['k_at_a']}{$anum_ht}";

        // オンリー>>1 =============================================
        if ($only_one_bool) {
            $one_ht = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;one=true{$_conf['k_at_a']}\">&gt;&gt;1</a>";
        }

        //アクセスキー=====
        /*
        $access_ht = "";
        if ($aThread->torder >= 1 and $aThread->torder <= 9) {
            $access_ht = " {$_conf['accesskey']}=\"{$aThread->torder}\"";
        }
        */

        //====================================================================================
        // スレッド一覧 table ボディ HTMLプリント <tr></tr>
        //====================================================================================

        //ボディ
        if ($_conf['iphone']) {
            // 勢い
            if ($_conf['sb_show_ikioi'] && $aThread->dayres > 0) {
                // 0.0 とならないように小数点第2位で切り上げ
                $htm['ikioi'] = sprintf(' / <span class="num speed">%01.1f</span>', ceil($aThread->dayres * 10) / 10);
            } else {
                $htm['ikioi'] = '';
            }

            if ($htm['ita'] !== '') {
                $htm['ita'] = "<span class=\"ita\">{$htm['ita']}</span>";
            }

            $htm['birthday'] = date('y/m/d', $aThread->key);

            echo <<<EOP
<li><a href="{$thre_url}">{$htm['unum']} <span class="{$classtitle}">{$aThread->ttitle_ht}</span> {$htm['rnum']} {$htm['sim']} {$htm['ita']}</a>
<div class="bg1"><span class="num order">{$aThread->torder}</span>{$htm['ikioi']}</div>
<div class="bg2"><span class="date">{$htm['birthday']}</span></div></li>\n
EOP;
        } else {
            echo <<<EOP
<div>{$htm['unum']}{$aThread->torder}.<a href="{$thre_url}">{$aThread->ttitle_ht}</a> {$htm['rnum']} {$htm['sim']} {$htm['ita']}</div>\n
EOP;
        }
    }

    if ($_conf['iphone']) {
        echo '</ul>';
    }
}
