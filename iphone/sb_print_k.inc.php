<?php
// p2 スレッドサブジェクト表示関数 携帯用
// for subject.php

/**
 * sb_print - スレッド一覧を表示する (<tr>～</tr>)
 *
 * @return  void
 */
function sb_print_k(&$aThreadList)
{
    global $_conf, $sb_view, $p2_setting, $STYLE;
    global $sb_view;
    
    if (!$aThreadList->threads) {
        if ($aThreadList->spmode == "fav" && $sb_view == "shinchaku") {
            echo "<p>お気にスレに新着なかったぽ</p>";
        } else {
            echo "<p>該当ｻﾌﾞｼﾞｪｸﾄはなかったぽ</p>";
        }
        return;
    }
    
    // 変数 ================================================
    
    // >>1
    $onlyone_bool = false;
    /*
    if (ereg("news", $aThreadList->bbs) || $aThreadList->bbs == "bizplus" || $aThreadList->spmode == "news") {
        // 倉庫は除く
        if ($aThreadList->spmode != "soko") {
            $onlyone_bool = true;
        }
    }
    */
    
    // 板名
    if ($aThreadList->spmode and $aThreadList->spmode != "taborn" and $aThreadList->spmode != "soko") {
        $ita_name_bool = true;
    } else {
        $ita_name_bool = false;
    }

    /*
    // {{{ 新着ソート（現在未使用）
    
    $qs = array(
        'sort'      => 'midoku',
        'norefresh' => '1',
        UA::getQueryKey() => UA::getQueryValue()
    );
    // スペシャルモード時
    if ($aThreadList->spmode) {
        $qs['spmode'] = $aThreadList->spmode;
        // あぼーんなら
        if ($aThreadList->spmode == "taborn" or $aThreadList->spmode == "soko") {
            $qs['host'] = $aThreadList->host;
            $qs['bbs']  = $aThreadList->bbs;
        }
    } else {
        $qs['host'] = $aThreadList->host;
        $qs['bbs']  = $aThreadList->bbs;
    }

    $midoku_sort_ht = P2View::tagA(
        P2Util::buildQueryUri($_conf['subject_php'], $qs),
        '新着'
    );

    // }}}
    */

    //=====================================================
    // ボディ
    //=====================================================

    // spmodeがあればクエリー追加
    if ($aThreadList->spmode) {$spmode_q = "&amp;spmode={$aThreadList->spmode}";}

    $i = 0;
    foreach ($aThreadList->threads as $aThread) {
    
        $i++;
        $midoku_ari = "";
        $anum_ht = ""; //#r1
        echo '<li>';
        $bbs_q = "&amp;bbs=" . $aThread->bbs;
        $key_q = "&amp;key=" . $aThread->key;

        if ($aThreadList->spmode!="taborn") {
            if (!$aThread->torder) {$aThread->torder=$i;}
        }

        // 新着レス数 =============================================
        $unum_ht = "";
        // 既得済み
        if ($aThread->isKitoku()) { 
            $unum_ht="{$aThread->unum}";
        
            $anum = $aThread->rescount - $aThread->unum +1 - $_conf['respointer'];
            if ($anum > $aThread->rescount) { $anum = $aThread->rescount; }
            $anum_ht = "#r{$anum}";
            
            // 新着あり
            if ($aThread->unum > 0) { 
                $midoku_ari = true;
                $unum_ht = "{$aThread->unum}";
            }
        
            // subject.txtにない時
            if (!$aThread->isonline) {
                // 誤動作防止のためログ削除操作をロック
                $unum_ht = "-"; 
            }    

            $unum_ht = '<font class="unum">' . $unum_ht . '</font>';
        }
        
        // 新規スレ
        if ($aThread->new) { 
            //$unum_ht = "<font color=\"#0000ff\">●</font>";
            $unum_new_ht = "<img class=\"unew\" src=\"iui/icon_new.png\">";
            $unum_ht = "";
         }else{
            $unum_new_ht = "";
        }
                
        // 総レス数
        $rescount_ht = "{$aThread->rescount}";

        // 板名
        $ita_name_ht = '';
        if ($ita_name_bool) {
            $ita_name = $aThread->itaj ? $aThread->itaj : $aThread->bbs;
            
            // 全角英数カナスペースを半角に
            if ($_conf['k_save_packet']) {
                $ita_name = mb_convert_kana($ita_name, 'rnsk');
            }
            
            $ita_name_hs = htmlspecialchars($ita_name, ENT_QUOTES);
            
            // $ita_name_ht = "(<a href=\"{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$_conf['k_at_a']}\">{$ita_name_hs}</a>)";
            $ita_name_ht = "({$ita_name_hs})";
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
        $rescount_q = "&amp;rc=".$aThread->rescount;
        
        // dat倉庫 or 殿堂なら
        if ($aThreadList->spmode == "soko" || $aThreadList->spmode == "palace") { 
            $rescount_q = "";
            $offline_q = "&amp;offline=true";
            $anum_ht = "";
        } else {
            $offline_q = '';
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
        if ($_conf['k_save_packet']) {
            $aThread->ttitle_ht = mb_convert_kana($aThread->ttitle_ht, 'rnsk');
        }
        
        $aThread->ttitle_ht = $aThread->ttitle_ht . ' <font class="sbnum">' . $rescount_ht . "</font>";
        if ($aThread->similarity) {
            $aThread->ttitle_ht .= sprintf(' %0.1f%%', $aThread->similarity * 100);
        }
        
        // 新規スレ
        if ($aThread->new) { 
            $classtitle_q = " class=\"thre_title_new\"";
        } else {
            $classtitle_q = " class=\"thre_title\"";
        }

        $thre_url = "{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$rescount_q}{$offline_q}{$_conf['k_at_a']}{$anum_ht}";
    
        // オンリー>>1
        $onlyone_url = "{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$rescount_q}&amp;onlyone=true&amp;k_continue=1{$_conf['k_at_a']}";
        if ($onlyone_bool) {
            $one_ht = "<a href=\"{$onlyone_url}\">&gt;&gt;1</a>";
        }
        
        if (P2Util::isHost2chs($aThreadList->host) and !$aThread->isKitoku()) {
            if ($GLOBALS['_conf']['k_sb_show_first'] == 1) {
                $thre_url = $onlyone_url;
            } elseif ($GLOBALS['_conf']['k_sb_show_first'] == 2) {
                $thre_url .= '&amp;ls=1-';
            }
        }
        
        // アクセスキー
        /*
        $access_ht = "";
        if ($aThread->torder >= 1 and $aThread->torder <= 9) {
            $access_ht = " {$_conf['accesskey']}=\"{$aThread->torder}\"";
        }
        */

        // お気にマーク設定
        $favdo      = !empty($aThread->fav) ? 0 : 1;
        $favtitle   = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
        $itaj_hs = htmlspecialchars($aThread->itaj, ENT_QUOTES);

        $favmark    = !empty($aThread->fav) ? '★' : '+';
        if ($favmark  == '★') {
            $favmark = '<img src="iui/icon_del.png">';
        } else {
            $favmark = '<img src="iui/icon_add.png">';
        }

        $sid_qs = array();
        $sid_q = '';
        if (defined('SID')) {
            $sid_qs[session_name()] = session_id();
            $sid_q = hs('&' . session_name() . '=' . session_id());
        }
        
        $setFavUri = P2Util::buildQueryUri('info_i.php',
            array_merge(array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'ttitle_en' => base64_encode($aThread->ttitle),
                'setfav' => $favdo
            ), $sid_qs)
        );
        $setFavUri_hs = hs($setFavUri);
        
        //====================================================================================
        // スレッド一覧 table ボディ HTMLプリント <tr></tr> 
        //====================================================================================
        echo "<span class=\"plus\" id=\"{$aThread->torder}\" ><a href=\"{$setFavUri_hs}\" target=\"info\" onClick=\"return setFavJsNoStr('host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}', '{$favdo}', {$STYLE['info_pop_size']}, 'read', 'this',{$aThread->torder});\" title=\"{$favtitle}\">{$favmark}</a></span>";

        // ボディ
        echo <<<EOP
 <a href="{$thre_url}">{$unum_new_ht}{$aThread->ttitle_ht}</a>{$ita_name_ht}{$unum_ht}</li>
EOP;
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
