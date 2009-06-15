<?php
/**
 * rep2 スレッドサブジェクト表示関数 携帯用
 * for subject.php
 */

// {{{ sb_print_k()

/**
 * sb_print - スレッド一覧を表示する (<tr>～</tr>)
 */
function sb_print_k($aThreadList)
{
    global $_conf, $sb_view, $p2_setting, $STYLE;
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

    $only_one_bool = false;
    $ita_name_bool = false;
    $sortq_spmode = '';
    $sortq_host = '';
    $sortq_ita = '';

    // >>1
    if (strpos($aThreadList->bbs, 'news') !== false || $aThreadList->bbs == 'bizplus') {
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
        $offline_q = '';

        if ($aThreadList->spmode!="taborn") {
            if (!$aThread->torder) {$aThread->torder=$i;}
        }

        // 新着レス数 =============================================
        // 既得済み
        if ($aThread->isKitoku()) {
            $htm['unum'] = "{$aThread->unum}";

            $anum_ht = sprintf('#r%d', min($aThread->rescount, $aThread->rescount - $aThread->nunum + 1 - $_conf['respointer']));

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
            if (!empty($_conf['mobile.save_packet'])) {
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
        $rescount_q = '&amp;rescount=' . $aThread->rescount;

        // dat倉庫 or 殿堂なら
        if ($aThreadList->spmode == 'soko' || $aThreadList->spmode == 'palace') {
            $rescount_q = '';
            $offline_q = '&amp;offline=true';
            $anum_ht = '';
        // subject.txt にない場合
        } elseif (!$aThread->isonline) {
            $offline_q = '&amp;offline=true';
        }

        // タイトル未取得なら
        $ttitle_ht = $aThread->ttitle_ht;
        if (strlen($ttitle_ht) == 0) {
            // 見かけ上のタイトルなので携帯対応URLである必要はない
            $ttitle_ht = htmlspecialchars($aThread->getMotoThread(true, ''));
        }

        // 全角英数カナスペースを半角に
        if (!empty($_conf['mobile.save_packet'])) {
            $ttitle_ht = mb_convert_kana($ttitle_ht, 'rnsk');
        }

        if ($_conf['iphone']) {
            $htm['rnum'] = "<span class=\"num count\">{$rescount_ht}</span>";
            if ($aThread->similarity) {
                $htm['sim'] .= sprintf(' <span class="num score">%0.1f%%</span>', $aThread->similarity * 100);
            }
        } else {
            $htm['rnum'] = "({$rescount_ht})";
            if ($aThread->similarity) {
                $htm['sim'] .= sprintf(' %0.1f%%', $aThread->similarity * 100);
            }
        }

        $thre_url = "{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}";
        if ($_conf['iphone']) {
            $thre_url .= '&amp;ttitle_en=' . rawurlencode(base64_encode($aThread->ttitle));
        }
        $thre_url .= "{$rescount_q}{$offline_q}{$_conf['k_at_a']}{$anum_ht}";

        // オンリー>>1
        $onlyone_url = "{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;one=true&amp;k_continue=1{$_conf['k_at_a']}";
        if ($only_one_bool) {
            $one_ht = "<a href=\"{$onlyone_url}\">&gt;&gt;1</a>";
        }

        // >>1のみ, >>1から
        if (P2Util::isHost2chs($aThreadList->host) && !$aThread->isKitoku()) {
            switch ($_conf['mobile.sb_show_first']) {
            case 1:
                $thre_url = $onlyone_url;
                break;
            case 2:
                $thre_url .= '&amp;ls=1-';
                break;
            default:
                $thre_url .= '&amp;ls=l' . $_conf['mobile.rnum_range'];
            }
        }

        // アクセスキー
        /*
        $access_ht = '';
        if ($aThread->torder >= 1 and $aThread->torder <= 9) {
            $access_ht = " {$_conf['accesskey']}=\"{$aThread->torder}\"";
        }
        */

        //====================================================================================
        // スレッド一覧 table ボディ HTMLプリント <tr></tr>
        //====================================================================================

        //ボディ
        if ($_conf['iphone']) {
            // information bubble用の隠しスレッド情報。
            // torderの指示子が"%d"でないのはmerge_favitaのため
            $thre_info = sprintf('[%s] %s (%01.1fレス/日)',
                                 $aThread->torder,                 // 順序
                                 date('y/m/d H:i', $aThread->key), // スレ立て日時
                                 $aThread->dayres                  // 勢い。切り上げなし
                                 );

            if ($_conf['iphone.subject.indicate-speed']) {
                // 勢い判定。なぜか不安定になるのでlog10()の結果で分岐はしない
                $dayres = (int)$aThread->dayres;
                if ($dayres > 9999) {
                    $classspeed_at = ' class="dayres-10000"';
                } elseif ($dayres > 999) {
                    $classspeed_at = ' class="dayres-1000"';
                } elseif ($dayres > 99) {
                    $classspeed_at = ' class="dayres-100"';
                } elseif ($dayres > 9) {
                    $classspeed_at = ' class="dayres-10"';
                } elseif ($dayres > 0) {
                    $classspeed_at = ' class="dayres-1"';
                } else {
                    $classspeed_at = ' class="dayres-0"';
                }
            } else {
                $classspeed_at = '';
            }

            if ($htm['ita'] !== '') {
                $htm['ita'] = "<span class=\"ita\">{$htm['ita']}</span>";
            }

            echo <<<EOP
<li><a href="{$thre_url}"{$classspeed_at}><span class="info">{$thre_info}</span> {$htm['unum']} <span class="{$classtitle}">{$ttitle_ht}</span> {$htm['rnum']} {$htm['sim']} {$htm['ita']}</a></li>\n
EOP;
        } else {
            echo <<<EOP
<div>{$htm['unum']}{$aThread->torder}.<a href="{$thre_url}">{$ttitle_ht}</a> {$htm['rnum']} {$htm['sim']} {$htm['ita']}</div>\n
EOP;
        }
    }

    if ($_conf['iphone']) {
        echo '</ul>';
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
