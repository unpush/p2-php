<?php
// for lib/sb_toolbar.inc.php

// @created 2008/09/28

/**
 * @return  integer
 */
function updateSbToolI()
{
    static $sb_tool_i_ = 0;
    
    ++$sb_tool_i_;
    
    return $sb_tool_i_;
}

/**
 * @return  string  HTML
 */
function getSbToolbarShinchakuMatomeHtml($aThreadList, $shinchaku_num)
{
    global $_conf;
    static $new_matome_i_;
    
    if (isset($new_matome_i_)) {
        $new_matome_i_++;
    } else {
        $new_matome_i_ = 0;
    }

    $shinchaku_matome_ht = '';

    // 倉庫でなければ
    if ($aThreadList->spmode != 'soko') { 
        $shinchaku_num_ht = '';
        if ($shinchaku_num) {
            $shinchaku_num_ht = " (<span id=\"smynum{$new_matome_i_}\" class=\"matome_num\">{$shinchaku_num}</span>)";
        }
    
        $shinchaku_matome_ht = P2View::tagA(
            P2Util::buildQueryUri(
                $_conf['read_new_php'],
                array(
                    'host'   => $aThreadList->host,
                    'bbs'    => $aThreadList->bbs,
                    'spmode' => $aThreadList->spmode,
                    'norefresh' => 1,
                    'nt'     => date('gis'),
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            '新着まとめ読み' . $shinchaku_num_ht,
            array(
                'id'      => "smy{$new_matome_i_}",
                'class'   => 'matome',
                'onClick' => 'chNewAllColor();'
            )
        );
    }
    return $shinchaku_matome_ht;
}

/**
 * @return  string  HTML
 */
function getSbToolAnchorHtml($sb_tool_i)
{
    $sb_tool_anchor_ht = '';

    if ($sb_tool_i == 1) {
        $sb_tool_anchor_ht = '<a class="toolanchor" href="#sbtoolbar2" target="_self" title="ページ下部へ移動">▼</a>';
    } elseif ($sb_tool_i == 2) {
        $sb_tool_anchor_ht = '<a class="toolanchor" href="#sbtoolbar1" target="_self" title="ページ上部へ移動">▲</a>';
    }
    return $sb_tool_anchor_ht;
}
