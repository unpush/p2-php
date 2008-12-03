<?php
/*
    p2 -  スレッド表示 -  フッタ部分 -  for read.php
*/

require_once P2_LIB_DIR . '/DataPhp.php';

//=====================================================================
// フッタ
//=====================================================================

$res_form_html ='';
$onmouse_showform_attrs = array();

if ($_conf['bottom_res_form'] and empty($diedat_msg_ht)) {

    $bbs        = $aThread->bbs;
    $key        = $aThread->key;
    $host       = $aThread->host;
    $rescount   = $aThread->rescount;
    $ttitle_en  = base64_encode($aThread->ttitle);
    
    $submit_value = '書き込む';

    // フォームのオプション読み込み
    require_once P2_LIB_DIR . '/post_options_loader.inc.php';

    $htm['resform_ttitle'] = sprintf(
        '<div style="padding:4px 0px;"><b class="thre_title">%s </b></div>',
        hs($aThread->ttitle_hc)
    );
    
    require_once P2_LIB_DIR . '/post_form.inc.php';

    // フォーム
    $res_form_html = <<<EOP
<div id="kakiko">
{$htm['post_form']}
</div>\n
EOP;

    // onMouseover="showResbar(event, true);"
    $onmouse_showform_attrs = array('onMouseover' => "document.getElementById('kakiko').style.display = 'block';");
}


// ============================================================
$dores_html = '';
$res_form_html_pb = '';

if ($aThread->rescount or (!empty($_GET['onlyone']) && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

    if (!$aThread->diedat) {
        if (!empty($_conf['disable_res'])) {
            $dores_atag = P2View::tagA(
                $motothre_url,
                hs($dores_st),
                array('target' => '_blank', 'accesskey' => 'p', 'title' => 'アクセスキー[p]')
            );
            
        } else {
            $dores_qs = array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'rescount' => $aThread->rescount,
                'ttitle_en' => base64_encode($aThread->ttitle)
            );
            $dores_uri = P2Util::buildQueryUri('post_form.php', $dores_qs);
            
            $dores_onclick_qs = array_merge($dores_qs, array(
                'popup' => '1',
            ));
            if (defined('SID') && strlen(SID)) {
                $dores_onclick_qs[session_name()] = session_id();
            }
            $dores_onclick_uri = P2Util::buildQueryUri('post_form.php', $dores_onclick_qs);
            
            $dores_atag = P2View::tagA(
                $dores_uri,
                hs($dores_st),
                array_merge(
                    array(
                        'accesskey' => 'p',
                        'title'     => 'アクセスキー[p]',
                        'target'    => '_self',
                        'onClick'   => sprintf(
                            "return !openSubWin('%s',%s,1,0)",
                            str_replace("'", "\\'", $dores_onclick_uri), $STYLE['post_pop_size']
                        )
                    ),
                    $onmouse_showform_attrs
                )
            );
        }
        $dores_html = '<span style="white-space: nowrap;">' . $dores_atag . '</span>';
        $res_form_html_pb = $res_form_html;
    }
    
    $q_ichi_ht = '';
    if (isset($res1['body'])) {
        $q_ichi_ht = $res1['body'] . " | ";
    }
    
    // レスのすばやさ
    $htm['spd'] = '';
    if ($spd_st = $aThread->getTimePerRes() and $spd_st != '-') {
        $htm['spd'] = '<span class="spd" style="white-space: nowrap;" title="すばやさ＝時間/レス">' . "" . $spd_st."".'</span>';
    }

    // {{{ フィルタヒットがあった場合、次Xと続きを読むを更新する
    
    if (!empty($GLOBALS['last_hit_resnum'])) {
        $read_navi_next_anchor = "";
        if ($GLOBALS['last_hit_resnum'] == $aThread->rescount) {
            $read_navi_next_anchor = "#r{$aThread->rescount}";
        }
        $after_rnum = $GLOBALS['last_hit_resnum'] + $rnum_range;
        $read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$GLOBALS['last_hit_resnum']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";

        // 「続きを読む」
        $read_footer_navi_new = _getTudukiATag($aThread, $tuduki_st);
    }
    // }}}
    
    $all_atag = _getAllATag($aThread, $all_st);
    
    $latest_atag = _getLatestATag($aThread, $latest_st, $latest_show_res_num);
    
    // フッタHTML出力
    echo <<<EOP
<hr>
<table id="footer" class="toolbar" width="100%" style="padding:0px 10px 0px 0px;">
    <tr>
        <td align="left">
            {$q_ichi_ht}
            $all_atag 
            {$read_navi_previous} 
            {$read_navi_next} 
            $latest_atag
            {$goto_ht}
            | {$read_footer_navi_new}
            | {$dores_html}
            {$htm['spd']}
        </td>
        <td align="right">
            {$p2frame_ht}
            {$toolbar_right_ht}
        </td>
        <td align="right">
            <a href="#header" title="ページ上部へ移動">▲</a>
        </td>
    </tr>
</table>
{$res_form_html_pb}
EOP;

    if ($diedat_msg_ht) {
        echo "<hr>$diedat_msg_ht<p>$motothre_ht</p>";
    }
}

if (!empty($_GET['showres'])) {
?>
	<script type="text/javascript">
	<!--
	document.getElementById('kakiko').style.display = 'block';
	//-->
	</script>
<?php
}
?>
</body></html>
<?php


//==============================================================================
// 関数（このファイル内でのみ利用）
//==============================================================================
/**
 * 全部 <a>
 *
 * @return  string  HTML
 */
function _getAllATag($aThread, $all_st)
{
    global $_conf;
    
    return $all_atag = P2View::tagA(
        P2Util::buildQueryUri(
            $_conf['read_php'],
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'ls'   => 'all'
            )
        ),
        hs($all_st),
        array('title' => 'アクセスキー[a]')
    );
}

/**
 * 最新N <a>
 *
 * @return  string  HTML
 */
function _getLatestATag($aThread, $latest_st, $latest_show_res_num)
{
    global $_conf;
    
    return $latest_atag = P2View::tagA(
        P2Util::buildQueryUri(
            $_conf['read_php'],
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'ls'   => "l{$latest_show_res_num}"
            )
        ),
        hs("$latest_st{$latest_show_res_num}")
    );
}

/**
 * 「続きを読む」 <a>
 *
 * @return  string  HTML
 */
function _getTudukiATag($aThread, $tuduki_st)
{
    global $_conf;
    
    return P2View::tagA(
        P2Util::buildQueryUri(
            $_conf['read_php'],
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'ls'   => $GLOBALS['last_hit_resnum'] . '-',
                'offline' => '1'
            )
        ),
        hs($tuduki_st),
        array(
            'accesskey' => 'r',
            'title' => 'アクセスキー[r]',
            'style' => 'white-space: nowrap;'
        )
    );
}
