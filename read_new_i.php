<?php
/*
    p2 - スレッド表示スクリプト - 新着まとめ読み（携帯）
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

// まとめよみのキャッシュ読み
if (!empty($_GET['cview'])) {
    $cnum = isset($_GET['cnum']) ? intval($_GET['cnum']) : NULL;
    if ($cont = getMatomeCache($cnum)) {
        echo $cont;
    } else {
        echo 'p2 error: 新着まとめ読みのキャッシュがないよ';
    }
    exit;
}

//==================================================================
// 変数
//==================================================================
$GLOBALS['rnum_all_range'] = $_conf['k_rnum_range'];

$sb_view = "shinchaku";
$newtime = date("gis");

$host   = geti($_GET['host'],   geti($_POST['host']));
$bbs    = geti($_GET['bbs'],    geti($_POST['bbs']));
$spmode = geti($_GET['spmode'], geti($_POST['spmode']));

if ((empty($host) || !isset($bbs)) && !isset($spmode)) {
    p2die('必要な引数が指定されていません');
}

if (($host) && P2Validate::host($host) || ($bbs) && P2Validate::bbs($bbs) || ($spmode) && P2Validate::spmode($spmode)) {
    p2die('不正な引数です');
}

$hr = P2View::getHrHtmlK();

//====================================================================
// メイン
//====================================================================

register_shutdown_function('saveMatomeCache');

$GLOBALS['_read_new_html'] = '';
ob_start();

$aThreadList = new ThreadList;

// 板とモードのセット
if ($spmode) {
    if ($spmode == "taborn" or $spmode == "soko") {
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
$ptitle_ht = sprintf('%s の 新着まとめ読み', hs($aThreadList->ptitle));

$ptitle_uri = P2Util::buildQueryUri($_conf['subject_php'],
    array(
        'host'   => $aThreadList->host,
        'bbs'    => $aThreadList->bbs,
        'spmode' => $aThreadList->spmode,
        UA::getQueryKey() => UA::getQueryValue()
    )
);

$ptitle_atag = P2View::tagA(
    $ptitle_uri,
    hs($aThreadList->ptitle)
);

$ptitle_btm_atag = P2View::tagA(
    $ptitle_uri,
    hs("{$_conf['k_accesskey']['up']}.$aThreadList->ptitle"),
    array(
        $_conf['accesskey'] => $_conf['k_accesskey']['up']
    )
);

$body_at = P2View::getBodyAttrK();
/*
// ページヘッダ表示 ===================================
$ptitle_hs = htmlspecialchars($aThreadList->ptitle, ENT_QUOTES);
$ptitle_ht = "{$ptitle_hs} の 新着まとめ読み";

// &amp;sb_view={$sb_view}
if ($aThreadList->spmode) {
    $sb_ht = <<<EOP
        <a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$_conf['k_at_a']}">{$ptitle_hs}</a>
EOP;
    $sb_ht_btm = <<<EOP
        <a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$_conf['k_at_a']}">{$ptitle_hs}</a>
EOP;
} else {
    $sb_ht = <<<EOP
        <a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}">{$ptitle_hs}</a>
EOP;
    $sb_ht_btm = <<<EOP
        <a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}">{$ptitle_hs}</a>
EOP;
}

$body_at = '';
if (!empty($STYLE['read_k_bgcolor'])) {
    $body_at .= " bgcolor=\"{$STYLE['read_k_bgcolor']}\"";
}
if (!empty($STYLE['read_k_color'])) {
    $body_at .= " text=\"{$STYLE['read_k_color']}\"";
}*/

// ========================================================
// require_once P2_LIB_DIR . '/read_header.inc.php';

P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
echo <<<EOHEADER
    <title>{$ptitle_ht}</title>\n
EOHEADER;

/*
    <script type="text/javascript" src="js/basic.js?v=20061209"></script>
    <script type="text/javascript" src="js/respopup.js?v=20061206"></script>
    <script type="text/javascript" src="js/setfavjs.js?v=20061206"></script>
    <script type="text/javascript" src="js/delelog.js?v=20061206"></script>
*/

$onload_script = '';

echo <<<EOHEADER
	<script type="text/javascript" src="js/basic.js?v=20061209"></script>
	<script type="text/javascript" src="iphone/js/respopup.iPhone.js?v=20061206"></script>
	<script type="text/javascript" src="iphone/js/setfavjs.js?v=20061206"></script>
	<script type="text/javascript" src="js/post_form.js?v=20061209"></script>
	<script type="text/javascript" src="iphone/js/smartpopup.iPhone.js?v=20070308"></script>
	<script type="text/javascript"> 
	<!-- 
		// iPhoneのURL編集部分を表示しないようスクロールする
		window.onload = function() { 
		setTimeout(scrollTo, 100, 0, 1); 
		}
		
		// ページ読み込み完了時コールバック関数
		gIsPageLoaded = false;
	    addLoadEvent(function() {			// basic.jsのメソッド
	        gIsPageLoaded = true;			// ページロード完了フラグ(trueじゃないとお気に入り変更javascriptが動かない)
	        {$onload_script}				// ページ読み込み完了時に実行するスクリプト群
	        
	    });
	    
	    // レス範囲のフォームの内容をリセットしてからページ移行するメソッド
	    var onArreyt = 2;
	    function formReset() {
		    var uriValue = "{$_conf['read_php']}?"
		    			 + "offline=1&"
		    			 + "b=i&"
		    			 + "host=" + document.frmresrange.host.value + "&"
		    			 + "bbs=" + document.frmresrange.bbs.value + "&"
		    			 + "key=" + document.frmresrange.key.value + "&"
		    			 + "rescount=" + document.frmresrange.rescount.value + "&"
		    			 + "ttitle_en=" + document.frmresrange.ttitle_en.value + "&"
		    			 + "ls=" + document.frmresrange.ls.value + "&";
		    document.frmresrange.reset();
		    window.location.assign(uriValue);
		}
		// フッターのレスフィルター表示フォームのポップアップを表示するメソッド
		// Edit 080727 by 240
		function footbarFormPopUp(arrayNum, resetFlag) {
			var formStyles = new Array(2);
			var liElement = new Array(2);
			formStyles[0] = document.getElementById('searchForm').style;
			formStyles[1] = document.getElementById('writeForm').style;
			liElement[0]  = document.getElementById('serchId');
			liElement[1]  = document.getElementById('writeId');

			for (var i = 0; i < 2; i++) {
				if (i != arrayNum)
					liElement[i].setAttribute('title', 'off');
				liElement[i].style.backgroundPositionY = '0';
				formStyles[i].display = 'none';
			}
			if (liElement[arrayNum].getAttribute('title') == 'on' || resetFlag) {
				liElement[arrayNum].setAttribute('title', 'off');
				return;
			}

			liElement[arrayNum].setAttribute('title', 'on');
			liElement[arrayNum].style.backgroundPositionY = '-50px';
//			formStyles[arrayNum].top = (document.height - 480).toString(); + "px"
			formStyles[arrayNum].display = 'block';
		}
		

	// --> 
	</script> 
<link rel="stylesheet" type="text/css" href="./iui/read.css"> 
EOHEADER;

echo <<<EOP
</head>
<body{$body_at}>\n
EOP;

echo <<<EOP
<div class="toolbar">
    <h1>{$ptitle_atag}の新まとめ</h1>
</div>
EOP;
P2Util::printInfoHtml();

//==============================================================
// それぞれの行解析
//==============================================================

$online_num = 0;

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize; $x++) {

    if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] <= 0) {
        break;
    }

    $l = $lines[$x];
    $aThread = new ThreadRead();
    
    $aThread->torder = $x + 1;

    // データ読み込み
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
        case "palace": // 殿堂入り
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        }
    // subject (not spmode)
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
    $aThread->getThreadInfoFromIdx(); // 既得スレッドデータをidxから取得

    // 新着のみ(for subject)
    if (!$aThreadList->spmode and $sb_view == "shinchaku" and !isset($GLOBALS['word'])) { 
        if ($aThread->unum < 1) {
            unset($aThread);
            continue;
        }
    }

    // スレッドあぼーんチェック
    if ($aThreadList->spmode != "taborn" and !empty($ta_keys[$aThread->key])) { 
        unset($ta_keys[$aThread->key]);
        continue; // あぼーんスレはスキップ
    }

    // spmode(殿堂入りを除く)なら ====================================
    if ($aThreadList->spmode && $sb_view != 'edit') { 
        
        // subject.txtが未DLなら落としてデータを配列に格納
        if (empty($subject_txts) || !array_key_exists("$aThread->host/$aThread->bbs", $subject_txts)) {

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
        if ($sb_view == 'shinchaku' and !isset($GLOBALS['word'])) {
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
         if (!isset($GLOBALS['_read_new_order'])) {
            $GLOBALS['_read_new_i_order'] = 1;
         } else {
            $GLOBALS['_read_new_i_order']++;
         }
         if ($GLOBALS['_read_new_i_order'] > 1) {
            echo "{$hr}\n";
         }
        _readNew($aThread);
    } elseif ($aThread->diedat) {
        echo $aThread->getdat_error_msg_ht;
        echo "{$hr}\n";
    }
    
    $GLOBALS['_read_new_html'] .= ob_get_flush();
    ob_start();
    
    // リストに追加
    // $aThreadList->addThread($aThread);
    $aThreadList->num++;
    unset($aThread);
}

//$aThread = new ThreadRead();

/**
 * スレッドの新着部分を読み込んで表示する
 */
function _readNew(&$aThread)
{
    global $_conf, $_newthre_num, $STYLE;
    global $spmode;

    $_newthre_num++;
    
    $hr = P2View::getHrHtmlK();
    
    //==========================================================
    // idxの読み込み
    //==========================================================
    
    //hostを分解してidxファイルのパスを求める
    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    
    //FileCtl::mkdirFor($aThread->keyidx); //板ディレクトリが無ければ作る //この操作はおそらく不要

    $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
    if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

    // idxファイルがあれば読み込む
    if (is_readable($aThread->keyidx)) {
        $lines = file($aThread->keyidx);
        $data = explode('<>', rtrim($lines[0]));
    }
    $aThread->getThreadInfoFromIdx();
    //$aThread->readDatInfoFromFile();
    

    // DATのダウンロード
    if (!(strlen(geti($word)) and file_exists($aThread->keydat))) {
        $aThread->downloadDat();
    }
    
    // DATを読み込み
    $aThread->readDat();
    $aThread->setTitleFromLocal(); // ローカルからタイトルを取得して設定
    
    //===========================================================
    // 表示レス番の範囲を設定
    //===========================================================
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
    
    //==================================================================
    // ヘッダ 表示
    //==================================================================
    $motothre_url = $aThread->getMotoThread();
    
    $ttitle_en = base64_encode($aThread->ttitle);
    $ttitle_en_q = "&amp;ttitle_en=".$ttitle_en;
    $bbs_q = "&amp;bbs=".$aThread->bbs;
    $key_q = "&amp;key=".$aThread->key;
    $popup_q = "&amp;popup=1";
    
    // require_once P2_LIB_DIR . '/read_header.inc.php';
    
    $prev_thre_num = $_newthre_num - 1;
    $next_thre_num = $_newthre_num + 1;
    if ($prev_thre_num != 0) {
        $prev_thre_ht = "<a href=\"#ntt{$prev_thre_num}\">▲</a>";
    }
    //$next_thre_ht = "<a href=\"#ntt{$next_thre_num}\">▼</a> ";
    $next_thre_ht = "<a class=\"button\" href=\"#ntt_bt{$_newthre_num}\">▼</a> ";
    
    if ($spmode) {
        $read_header_itaj_ht = sprintf(' (%s)', hs($aThread->itaj));
        if ($_conf['k_save_packet']) {
            $read_header_itaj_ht = mb_convert_kana($read_header_itaj_ht, 'rnsk');
        }
    }
    
    P2Util::printInfoHtml();
    
    $ttitle_hs = hs($aThread->ttitle_hc);
    if ($_conf['k_save_packet']) {
        $ttitle_hs = mb_convert_kana($ttitle_hs, 'rnsk');
    }
    
    $read_header_ht = <<<EOP
	<p id="ntt{$_newthre_num}" name="ntt{$_newthre_num}"><font color="{$STYLE['read_k_thread_title_color']}"><b>{$ttitle_hs}</b></font>{$read_header_itaj_ht} {$next_thre_ht}</p>
	$hr\n
EOP;

    // {{{ ローカルDatを読み込んでHTML表示

    $aThread->resrange['nofirst'] = true;
    $GLOBALS['newres_to_show_flag'] = false;
    $read_cont_ht = '';
    if ($aThread->rescount) {
        //$aThread->datToHtml(); // dat を html に変換表示
        require_once P2_IPHONE_LIB_DIR . '/ShowThreadK.php';
        $aShowThread = new ShowThreadK($aThread);
        
        $read_cont_ht = $aShowThread->getDatToHtml();
        
        unset($aShowThread);
    }
    
    // }}}
    
    //==================================================================
    // フッタ 表示
    //==================================================================
    // require_once P2_LIB_DIR . '/read_footer.inc.php';
    
    //----------------------------------------------
    // $read_footer_navi_new  続きを読む 新着レスの表示
    $newtime = date("gis");  // リンクをクリックしても再読込しない仕様に対抗するダミークエリー
    
    $info_st = "情";
    $dele_st = "削";
    $prev_st = "前";
    $next_st = "次";

    // 表示範囲
    if ($aThread->resrange['start'] == $aThread->resrange['to']) {
        $read_range_on = $aThread->resrange['start'];
    } else {
        $read_range_on = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
    }
    $read_range_ht = "{$read_range_on}/{$aThread->rescount}<br>";

    /*
    $read_footer_navi_new = P2View::tagA(
        P2Util::buildQueryUri(
            $_conf['read_php'],
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'ls'   => "$aThread->rescount-",
                'nt'   => $newtime,
                UA::getQueryKey() => UA::getQueryValue()
            ) . "#r{$aThread->rescount}"
        ),
        '新着ﾚｽの表示'
    );

    $dores_ht _getDoResATag($aThread, $motothre_url);
    */
    
    // {{{ ツールバー部分HTML
    
    if ($spmode) {
        $ita_atag = _getItaATag($aThread);
        $toolbar_itaj_ht = " ($ita_atag)";
        if ($_conf['k_save_packet']) {
            $toolbar_itaj_ht = mb_convert_kana($toolbar_itaj_ht, 'rnsk');
        }
    }
    
    /*
    $info_atag = _getInfoATag($aThread, $info_st);
    $dele_atag = _getDeleATag($aThread, $dele_st);
    $motothre_atag = P2View::tagA($motothre_url, '元ｽﾚ')
    $toolbar_right_ht = "{$info_atag} {$dele_atag} {$motothre_atag}\n";
    */
    
    // }}}
    
    $read_atag = _getReadATag($aThread);
    
    $read_footer_ht = <<<EOP
        <div id="ntt_bt{$_newthre_num}" name="ntt_bt{$_newthre_num}">
            $read_range_ht 
            $read_atag{$toolbar_itaj_ht} 
            <a href="#ntt{$_newthre_num}">▲</a>
        </div>
EOP;

    // 透明あぼーんや表示数制限で新しいレス表示がない場合はスキップ
    if ($GLOBALS['newres_to_show_flag']) {
        echo $read_header_ht;
        echo $read_cont_ht;
        echo $read_footer_ht;
    }

    // {{{ key.idxの値設定

    if ($aThread->rescount) {
    
        $aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to']));
        
        $newline = $aThread->readnum + 1; // $newlineは廃止予定だが、後方互換用に念のため

        $sar = array(
            $aThread->ttitle, $aThread->key, $data[2], $aThread->rescount, '',
            $aThread->readnum, $data[6], $data[7], $data[8], $newline,
            $data[10], $data[11], $aThread->datochiok
        );
        P2Util::recKeyIdx($aThread->keyidx, $sar); // key.idxに記録
    }
    
    // }}}
    
    unset($aThread);
}

//==================================================================
// ページフッタ HTML表示
//==================================================================
$_newthre_num++;

if (!$aThreadList->num) {
    $GLOBALS['_is_matome_shinchaku_naipo'] = true;
    echo "新着レスはないぽ";
}


echo <<<EOP
<div id="footbar01">
<div class="footbar">
<ul>
<li class="home"><a name="ntt_bt1" href="iphone.php">TOP</a></li>
<li class="other"><a onclick="all.item('footbar02').style.visibility='visible';">その他</a></li>
EOP;
if (!isset($GLOBALS['rnum_all_range']) or $GLOBALS['rnum_all_range'] > 0 or !empty($GLOBALS['limit_to_eq_to'])) {
    if (!empty($GLOBALS['limit_to_eq_to'])) {
        $str = '新着まとめの更新or続き';
    } else {
        $str = '新まとめを更新';
    }
    echo <<<EOP
    <li class="new">
        <a href="{$_conf['read_new_k_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}&nt={$newtime}{$_conf['k_at_a']}">{$str}</a>
</li>\n
<li id="blank" class="next"></li> 
EOP;
} else {
    echo <<<EOP
    <li id="blank" class="new"></li> 
    <li class="next">
        <a href="{$_conf['read_new_k_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}&nt={$newtime}&amp;norefresh=1{$_conf['k_at_a']}">新まとめの続き</a>
    </li>\n
EOP;
}
//{$sb_ht_btm}の
//echo '<hr>' . $_conf['k_to_index_ht'] . "\n";
//iphone 080801
echo <<<EOP
 </ul>
</div></div>
<div id="footbar02" class="dialog_other">
<filedset>
 <ul>
 <li class="whiteButton">{$ptitle_btm_atag}</li> 
 <li class="grayButton" onclick="all.item('footbar02').style.visibility='hidden'">キャンセル</li>
 </ul>
 </filedset>
</div>
</body></html>
EOP;
$GLOBALS['_read_new_html'] .= ob_get_flush();

// 後処理

// NGあぼーんを記録
NgAbornCtl::saveNgAborns();

exit;


//==========================================================================
// 関数（このファイル内でのみ利用）
//==========================================================================
/**
 * @return  string  HTML
 */
function _getItaATag($aThread)
{
    global $_conf;
    
    return $ita_atag = P2View::tagA(
        P2Util::buildQueryUri(
            $_conf['subject_php'],
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                UA::getQueryKey() => UA::getQueryValue()
            )
        ),
        hs($aThread->itaj)
    );
}

/**
 * @return  string  HTML
 */
function _getReadATag($aThread)
{
    global $_conf;
    
    $ttitle_hs = hs($aThread->ttitle_hc);
    if ($_conf['k_save_packet']) {
        $ttitle_hs = mb_convert_kana($ttitle_hs, 'rnsk');
    }
    
    return $read_atag = P2View::tagA(
        P2Util::buildQueryUri(
            $_conf['read_php'],
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'offline' => '1',
                'rescount' => $aThread->rescount,
                UA::getQueryKey() => UA::getQueryValue()
            )
        ) . '#r' . rawurlencode($aThread->rescount),
        $ttitle_hs
    );
}

/**
 * @return  string  HTML
 */
/*
function _getInfoATag($aThread, $info_st)
{
    return $info_atag = P2View::tagA(
        P2Util::buildQueryUri(
            'info.php',
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'ttitle_en' => base64_encode($aThread->ttitle),
                UA::getQueryKey() => UA::getQueryValue()
            ),
            hs($info_st)
        )
    );
}
*/

/**
 * @return  string  HTML
 */
/*
function _getDeleATag($aThread, $dele_st)
{
    return $dele_atag = P2View::tagA(
        P2Util::buildQueryUri(
            'info.php',
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'ttitle_en' => base64_encode($aThread->ttitle),
                'dele' => '1',
                UA::getQueryKey() => UA::getQueryValue()
            ),
            hs($dele_st)
        )
    );
}
*/

/**
 * @return  string  HTML
 */
/*
function _getDoResATag($aThread, $motothre_url)
{
    global $_conf;
    
    if (!empty($_conf['disable_res'])) {
        $dores_atag = P2View::tagA($motothre_url, '書', array('target' => '_blank'));
    } else {
        $dores_atag = P2View::tagA(
            P2Util::buildQueryUri(
                'post_form.php',
                array(
                    'host' => $aThread->host,
                    'bbs'  => $aThread->bbs,
                    'key'  => $aThread->key,
                    'rescount' => $aThread->rescount,
                    'ttitle_en' => base64_encode($aThread->ttitle),
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            '書'
        );
    }
    return $dores_atag;
}
*/
