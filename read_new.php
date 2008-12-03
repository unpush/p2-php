<?php
/*
    p2 - スレッド表示スクリプト - 新着まとめ読み
    フレーム分割画面、右下部分
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/ThreadList.php';
require_once P2_LIB_DIR . '/Thread.php';
require_once P2_LIB_DIR . '/ThreadRead.php';
require_once P2_LIB_DIR . '/NgAbornCtl.php';
require_once P2_LIB_DIR . '/read_new.inc.php';
require_once P2_LIB_DIR . '/P2Validate.php';

$_login->authorize(); // ユーザ認証

// {{{ 新着まとめよみのキャッシュ読み

if (!empty($_GET['cview'])) {
    $cnum = isset($_GET['cnum']) ? intval($_GET['cnum']) : NULL;
    if ($cont = getMatomeCache($cnum)) {
        echo $cont;
    } else {
        echo 'p2 error: 新着まとめ読みのキャッシュがないよ';
    }
    exit;
}

// }}}

//==================================================================
// 変数
//==================================================================
if (isset($_conf['rnum_all_range']) and $_conf['rnum_all_range'] > 0) {
    $GLOBALS['rnum_all_range'] = $_conf['rnum_all_range'];
}

$sb_view = "shinchaku";
$newtime = date("gis");

$sid_q = (defined('SID') && strlen(SID)) ? '&amp;' . hs(SID) : '';

$_newthre_num = 0;

//=================================================
// 板の指定
//=================================================
$host   = geti($_GET['host'],   geti($_POST['host']));
$bbs    = geti($_GET['bbs'],    geti($_POST['bbs']));
$spmode = geti($_GET['spmode'], geti($_POST['spmode']));

if ((!$host || !isset($bbs)) && !isset($spmode)) {
    p2die('必要な引数が指定されていません');
}

if (($host) && P2Validate::host($host) || ($bbs) && P2Validate::bbs($bbs) || ($spmode) && P2Validate::spmode($spmode)) {
    p2die('不正な引数です');
}

//====================================================================
// メイン
//====================================================================

register_shutdown_function('saveMatomeCache');

$GLOBALS['_read_new_html'] = '';
ob_start();

$aThreadList = new ThreadList;

// 板とモードのセット===================================
$ta_keys = array();
if ($spmode) {
    if ($spmode == 'taborn' or $spmode == 'soko') {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }
    $aThreadList->setSpMode($spmode);
    
} else {
    $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));

    // スレッドあぼーんリスト読込
    $ta_keys = P2Util::getThreadAbornKeys($host, $bbs);
    $ta_num = sizeOf($ta_keys);
}

// ソースリスト読込
$lines = $aThreadList->readList();

// ページヘッダ表示 ===================================
$ptitle_ht = hs($aThreadList->ptitle) . " の 新着まとめ読み";

$qs = array(
    'host' => $aThreadList->host,
    'bbs'  => $aThreadList->bbs
);
if ($aThreadList->spmode) {
    $qs['spmode'] = $aThreadList->spmode;
}
$sb_ht = P2View::tagA(
    P2Util::buildQueryUri($_conf['subject_php'], $qs),
    hs($aThreadList->ptitle),
    array('target' => 'subject')
);

// require_once P2_LIB_DIR . '/read_header.inc.php';

P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
    <title><?php echo $ptitle_ht; ?></title>
<?php
P2View::printIncludeCssHtml('style');
P2View::printIncludeCssHtml('read');
?>
	<script type="text/javascript" src="js/basic.js?v=20061209"></script>
	<script type="text/javascript" src="js/respopup.js?v=20061206"></script>
	<script type="text/javascript" src="js/htmlpopup.js?v=20061206"></script>
	<script type="text/javascript" src="js/setfavjs.js?v=20061206"></script>
	<script type="text/javascript" src="js/delelog.js?v=20061206"></script>
	<script type="text/javascript" src="js/showhide.js"></script>
	
	<script type="text/javascript" src="./js/yui-ext/yui.js"></script>
	<script type="text/javascript" src="./js/yui-ext/yui-ext-nogrid.js"></script>
	<link rel="stylesheet" type="text/css" href="./js/yui-ext/resources/css/resizable.css" />

	<script type="text/javascript">
	<!--
	gIsReadNew = true;
	gFade = false;
	gExistWord = false;
	gIsPageLoaded = false;
	addLoadEvent(function() {
		gIsPageLoaded = true;
		setWinTitle();
	});
	-->
	</script>
<?php
if ($_conf['enable_spm']) {
    ?><script type="text/javascript" src="js/smartpopup.js?v=20070308"></script><?php
}
?>
</head>
<body id="read" onclick="hideHtmlPopUp(event);">
<div id="popUpContainer"></div>
<?php
P2Util::printInfoHtml();

//echo $ptitle_ht . "<br>";

//==============================================================
// それぞれの行解析
//==============================================================

$online_num = 0;

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize ; $x++) {

    if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] <= 0) {
        break;
    }
    
    $l = $lines[$x];
    $aThread = new ThreadRead;
    
    $aThread->torder = $x + 1;

    // データ読み込み
    // spmodeなら
    if ($aThreadList->spmode) {
        switch ($aThreadList->spmode) {
        case "recent": // 履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "res_hist": // 書き込み履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "fav": // お気に
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "taborn": // スレッドあぼーん
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "palace": // スレの殿堂
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        }
    // subject (not spmode)の場合
    } else {
        $aThread->getThreadInfoFromSubjectTxtLine($l);
        $aThread->host = $aThreadList->host;
        $aThread->bbs = $aThreadList->bbs;
    }
    
    // hostもbbsも不明ならスキップ
    if (!($aThread->host && $aThread->bbs)) {
        unset($aThread);
        continue;
    }
    
    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    
    // 既得スレッドデータをidxから取得
    $aThread->getThreadInfoFromIdx();
        
    // 新着のみ(for subject)
    if (!$aThreadList->spmode and $sb_view == "shinchaku" and !isset($GLOBALS['word'])) { 
        if ($aThread->unum < 1) {
            unset($aThread);
            continue;
        }
    }

    // スレッドあぼーんチェック
    if ($aThreadList->spmode != 'taborn' and !empty($ta_keys[$aThread->key])) { 
        unset($ta_keys[$aThread->key]);
        continue; // あぼーんスレはスキップ
    }

    // spmode(殿堂入りを除く)なら ====================================
    if ($aThreadList->spmode && $sb_view != "edit") { 
        
        // subject.txt が未DLなら落としてデータを配列に格納
        if (empty($subject_txts["$aThread->host/$aThread->bbs"])) {

            require_once P2_LIB_DIR . '/SubjectTxt.php';
            $aSubjectTxt = new SubjectTxt($aThread->host, $aThread->bbs);

            $subject_txts["$aThread->host/$aThread->bbs"] = $aSubjectTxt->subject_lines;
        }
        
        // スレ情報取得
        if ($subject_txts["$aThread->host/$aThread->bbs"]) {
            foreach ($subject_txts["$aThread->host/$aThread->bbs"] as $l) {
                if (@preg_match("/^{$aThread->key}/", $l)) {
                    $aThread->getThreadInfoFromSubjectTxtLine($l); // subject.txt からスレ情報取得
                    break;
                }
            }
        }
        
        // 新着のみ(for spmode)
        if ($sb_view == "shinchaku" and !isset($GLOBALS['word'])) { 
            if ($aThread->unum < 1) {
                unset($aThread);
                continue;
            }
        }
    }
    
    if ($aThread->isonline) { $online_num++; } // 生存数set
    
    P2Util::printInfoHtml();
    
    $GLOBALS['_read_new_html'] .= ob_get_flush();
    ob_start();
    
    if (($aThread->readnum < 1) || $aThread->unum) {
        _readNew($aThread);
    } elseif ($aThread->diedat) {
        echo $aThread->getdat_error_msg_ht;
        echo "<hr>\n";
    }

    $GLOBALS['_read_new_html'] .= ob_get_flush();
    ob_start();

    // リストに追加
    // $aThreadList->addThread($aThread);
    $aThreadList->num++;
    unset($aThread);
}

// $aThread = new ThreadRead;

/**
 * スレッドの新着部分を読み込んで表示する
 *
 * @return  void
 */
function _readNew(&$aThread)
{
    global $_conf, $_newthre_num, $STYLE, $sid_q;

    $_newthre_num++;
    
    //==========================================================
    // idxの読み込み
    //==========================================================
    
    // hostを分解してidxファイルのパスを求める
    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    
    // FileCtl::mkdirFor($aThread->keyidx); // 板ディレクトリが無ければ作る // この操作はおそらく不要

    $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
    if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

    // idxファイルがあれば読み込む
    if (file_exists($aThread->keyidx)) {
        $lines = file($aThread->keyidx);
        $data = explode('<>', rtrim($lines[0]));
    }
    $aThread->getThreadInfoFromIdx();
    

    // DATのダウンロード
    if (!(isset($GLOBALS['word']) && file_exists($aThread->keydat))) {
        $aThread->downloadDat();
    }
    
    // DATを読み込み
    $aThread->readDat();
    $aThread->setTitleFromLocal(); // ローカルからタイトルを取得して設定
    
    // {{{ 表示レス番の範囲を設定
    
    // 取得済みなら
    if ($aThread->isKitoku()) {
        $from_num = $aThread->readnum +1 - $_conf['respointer'] - $_conf['before_respointer_new'];
        if ($from_num > $aThread->rescount) {
            $from_num = $aThread->rescount - $_conf['respointer'] - $_conf['before_respointer_new'];
        }
        if ($from_num < 1) {
            $from_num = 1;
        }

        //if (!$aThread->ls) {
            $aThread->ls = "$from_num-";
        //}
    }
    
    $aThread->lsToPoint();
    
    // }}}
    
    //==================================================================
    // ヘッダ 表示
    //==================================================================
    $motothre_url = $aThread->getMotoThread();
    
    $ttitle_en = base64_encode($aThread->ttitle);
    $popup_q = "&amp;popup=1";
    
    // require_once P2_LIB_DIR . '/read_header.inc.php';
    
    $prev_thre_num = $_newthre_num - 1;
    $next_thre_num = $_newthre_num + 1;
    $prev_thre_ht = '';
    if ($prev_thre_num != 0) {
        $prev_thre_ht = "<a href=\"#ntt{$prev_thre_num}\">▲</a>";
    }
    $next_thre_ht = "<a href=\"#ntt{$next_thre_num}\">▼</a> ";
    
    P2Util::printInfoHtml();
    
    // ヘッダ部分HTML
    $read_header_ht = <<<EOP
	<table id="ntt{$_newthre_num}" class="toolbar" width="100%" style="padding:0px 10px 0px 0px;">
		<tr>
			<td align="left">
				<h3 class="thread_title">{$aThread->ttitle_hs} </h3>
			</td>
			<td align="right">
				{$prev_thre_ht}
				{$next_thre_ht}
			</td>
		</tr>
	</table>\n
EOP;
    
    // スマートポップアップメニュー
    if ($_conf['enable_spm']) {
        $aThread->showSmartPopUpMenuJs();
    }
    
    //==================================================================
    // ローカルDatを読み込んでHTML表示
    //==================================================================
    $aThread->resrange['nofirst'] = true;
    $GLOBALS['newres_to_show_flag'] = false;
    if ($aThread->rescount) {
        // $aThread->datToHtml(); // dat を html に変換表示
        require_once P2_LIB_DIR . '/ShowThreadPc.php';
        $aShowThread = new ShowThreadPc($aThread);

        $res1 = $aShowThread->quoteOne();
        $read_cont_ht = $res1['q'];
        
        $read_cont_ht .= $aShowThread->getDatToHtml();
        
        unset($aShowThread);
    }
    
    //==================================================================
    // フッタ 表示
    //==================================================================
    // require_once P2_LIB_DIR . '/read_footer.inc.php';
    
    //----------------------------------------------
    // $read_footer_navi_new  続きを読む 新着レスの表示
    $newtime = date("gis");  // リンクをクリックしても再読込しない仕様に対抗するダミークエリー
    
    $info_st   = "情報";
    $dele_st   = "削除";
    $prev_st   = "前";
    $next_st   = "次";
    
    $thread_qs = array(
        'host' => $aThread->host,
        'bbs'  => $aThread->bbs,
        'key'  => $aThread->key,
        UA::getQueryKey() => UA::getQueryValue()
    );
    $sid_qs = array();
    if (defined('SID') && strlen(SID)) {
        $sid_qs[session_name()] = session_id();
    }
    
    /*
    $read_footer_navi_new = P2View::tagA(
        P2Util::buildQueryUri($_conf['read_php'],
            array_merge($thread_qs, array(
                'ls'   => "$aThread->rescount-",
                'nt'   => $newtime
            ))
        ) . '#r' . rawurlencode($aThread->rescount),
        '新着レスの表示'
    );
    */
    
    if ($_conf['disable_res']) {
        $dores_ht = P2View::tagA($motothre_url, '書込', array('target' => '_blank'));
    } else {
        $post_form_uri = P2Util::buildQueryUri('post_form.php',
            array_merge($thread_qs, array(
                'rescount' => $aThread->rescount,
                'ttitle_en' => $ttitle_en,
            ))
        );
        $post_form_uri_hs = hs($post_form_uri);
        
        $post_from_openwin_uri = P2Util::buildQueryUri('post_form.php',
            array_merge($thread_qs, array(
                'rescount' => $aThread->rescount,
                'ttitle_en' => $ttitle_en,
                'popup' => '1',
                'from_read_new' => '1'
            ), $sid_qs)
        );
        $post_from_openwin_uri_hs = hs($post_from_openwin_uri);
        
        $dores_ht = <<<EOP
        <a href="{$post_form_uri_hs}" target='_self' onClick="return !openSubWin('{$post_from_openwin_uri_hs}',{$STYLE['post_pop_size']},1,0)">書込</a>
EOP;
    }
    $dores_ht = '<span style="white-space: nowrap;">' . $dores_ht . '</span>';
    
    // ツールバー部分HTML =======
    
    // お気にマーク設定
    $favmark    = !empty($aThread->fav) ? '★' : '+';
    $favdo      = !empty($aThread->fav) ? 0 : 1;
    $favtitle   = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
    $favdo_q    = '&amp;setfav=' . $favdo;
    
    $itaj_hs    = hs($aThread->itaj);
    
    $similar_qs = array(
        'detect_hint' => '◎◇',
        'itaj_en'     => base64_encode($aThread->itaj),
        'method'      => 'similar',
        'word'        => $aThread->ttitle_hc
        // 'refresh' => 1
    );
    
    $b_qs = array(
        UA::getQueryKey() => UA::getQueryValue()
    );
    
    $info_qs = array_merge($thread_qs, $b_qs, array('ttitle_en' => $ttitle_en));
    
    $ita_atag = P2View::tagA(
        P2Util::buildQueryUri($_conf['subject_php'], array_merge($thread_qs, $b_qs)),
        hs($aThread->itaj),
        array('style' => 'white-space: nowrap;', 'target' => 'subject', 'title' => '板を開く')
    );
    
    $similar_atag =  P2View::tagA(
        P2Util::buildQueryUri(
            $_conf['subject_php'],
            array_merge($similar_qs, $thread_qs, $b_qs, array('refresh' => 1))
        ),
        hs('似スレ'),
        array('style' => 'white-space: nowrap;', 'target' => 'subject', 'title' => '同じ板からタイトルが似ているスレッドを検索する')
    );

    $info_url = P2Util::buildQueryUri('info.php', $info_qs);
    $info_url_hs = hs($info_url);
    
    $info_hs = hs($info_st);
    
    $js_q_hs = hs(P2Util::buildQuery(array_merge($info_qs, $sid_qs)));
    
    $motothre_atag = P2View::tagA(
        $motothre_url,
        '元スレ',
        array('style' => 'white-space: nowrap;', 'title' => '板サーバ上のオリジナルスレを表示')
    );
    
    $toolbar_right_ht = <<<EOTOOLBAR
            $ita_atag
            $similar_atag

		<a style="white-space: nowrap;" href="{$info_url_hs}" target="info" onClick="return !openSubWin('{$info_url_hs}{$popup_q}{$sid_q}',{$STYLE['info_pop_size']},0,0)" title="スレッド情報を表示">{$info_hs}</a>

		<span class="favdo" style="white-space: nowrap;"><a href="{$info_url_hs}{$favdo_q}{$sid_q}" target="info" onClick="return setFavJs('{$js_q_hs}', '{$favdo}', {$STYLE['info_pop_size']}, 'read_new', this);" title="{$favtitle}">お気に{$favmark}</a></span>

		<span style="white-space: nowrap;"><a href="{$info_url_hs}&amp;dele=1" target="info" onClick="return deleLog('{$js_q_hs}', {$STYLE['info_pop_size']}, 'read_new',  this);" title="ログを削除する。自動で「お気にスレ」「殿堂」からも外れます。">{$dele_st}</a></span>

<!--		<a style="white-space: nowrap;" href="{$info_url_hs}&amp;taborn=2" target="info" onClick="return !openSubWin('{$info_url_hs}&amp;popup=2&amp;taborn=2{$sid_q}',{$STYLE['info_pop_size']},0,0)" title="スレッドのあぼーん状態をトグルする">あぼん</a> -->

		$motothre_atag
EOTOOLBAR;

    // レスのすばやさ
    $spd_ht = "";
    if ($spd_st = $aThread->getTimePerRes() and $spd_st != "-") {
        $spd_ht = '<span class="spd" style="white-space: nowrap;" title="すばやさ＝時間/レス">' . $spd_st . '</span>';
    }

    // フッタ部分HTML
    $read_atag = P2View::tagA(
        P2Util::buildQueryUri($_conf['read_php'],
            array_merge($thread_qs, array(
                'offline' => '1',
                'rescount' => $aThread->rescount
            ))
        ) . '#r' . rawurlencode($aThread->rescount),
        hs($aThread->ttitle_hc)
    );
    $read_footer_ht = <<<EOP
	<table class="toolbar" width="100%" style="padding:0px 10px 0px 0px;">
		<tr>
			<td align="left">
				{$res1['body']} | $read_atag | {$dores_ht} {$spd_ht}
			</td>
			<td align="right">
				{$toolbar_right_ht}
			</td>
			<td align="right">
				<a href="#ntt{$_newthre_num}">▲</a>
			</td>
		</tr>
	</table>\n
EOP;

    // 透明あぼーんで表示がない場合はスキップ
    if ($GLOBALS['newres_to_show_flag']) {
        echo '<div style="width:100%;">' . "\n"; // ほぼIE ActiveXのGray()のためだけに囲ってある
        echo $read_header_ht;
        echo $read_cont_ht;
        echo $read_footer_ht;
        echo '</div>' . "\n\n";
        echo '<hr>' . "\n\n";
    }

    // key.idx の値設定
    if ($aThread->rescount) {
    
        $aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to']));
        
        $newline = $aThread->readnum + 1; // $newlineは廃止予定だが、後方互換用に念のため

        $sar = array(
            $aThread->ttitle, $aThread->key, $data[2], $aThread->rescount, $aThread->modified,
            $aThread->readnum, $data[6], $data[7], $data[8], $newline,
            $data[10], $data[11], $aThread->datochiok
        );
        P2Util::recKeyIdx($aThread->keyidx, $sar); // key.idx に記録
    }

    unset($aThread);
}

//==================================================================
// ページフッタ表示
//==================================================================
$_newthre_num++;

if (!$aThreadList->num) {
    $GLOBALS['_is_matome_shinchaku_naipo'] = true;
    // （´・ω・）
    ?>新着レスはないぽ (*‘ω‘ *)<hr><?php
}

$shinmatome_qs =  array(
    'host'   => $aThreadList->host,
    'bbs'    => $aThreadList->bbs,
    'spmode' => $aThreadList->spmode,
    'nt'     => $newtime,
    UA::getQueryKey() => UA::getQueryValue()
);
$shinmatome_accesskey = 'r';
$shinmatome_accesskey_attrs = array(
    'accesskey' => $shinmatome_accesskey,
    'title' => sprintf('アクセスキー[%s]', $shinmatome_accesskey)
);

if (!isset($GLOBALS['rnum_all_range']) or $GLOBALS['rnum_all_range'] > 0 or !empty($GLOBALS['_is_eq_limit_to_and_to'])) {
    if (!empty($GLOBALS['_is_eq_limit_to_and_to'])) {
        $str = '新着まとめ読みの更新or続き';
    } else {
        $str = '新着まとめ読みを更新';
    }
    $atag = P2View::tagA(
        P2Util::buildQueryUri($_conf['read_new_php'], $shinmatome_qs),
        hs($str),
        $shinmatome_accesskey_attrs
    );

} else {
    $str = '新着まとめ読みの続き';
    $atag = P2View::tagA(
        P2Util::buildQueryUri($_conf['read_new_php'], array_merge($shinmatome_qs, array('norefresh' => '1'))),
        hs($str),
        $shinmatome_accesskey_attrs
    );
}
?>
<div id="ntt<?php eh($_newthre_num); ?>" align="center">
    <?php echo $sb_ht; ?> の <?php echo $atag; ?>
</div>

</body></html>
<?php

$GLOBALS['_read_new_html'] .= ob_get_flush();

//==================================================
// 後処理
//==================================================

// NGあぼーんを記録
NgAbornCtl::saveNgAborns();
