<?php
/**
 * rep2expack - pager for Mobile
 */

// {{{ get_read_jump()

/**
 * ページ遷移用のHTML要素を取得する
 */
function get_read_jump(ThreadRead $aThread, $label, $use_onchange)
{
    global $_conf;

    if (isset($GLOBALS['word']) && strlen($GLOBALS['word']) > 0) {
        $jump = _get_read_jump_filter($aThread, $use_onchange);
    } else {
        $jump = _get_read_jump($aThread, $use_onchange);
    }

    if ($use_onchange) {
        return "<div>{$jump}/{$label}</div>";
    } else {
        return "<form method=\"get\" action=\"{$_conf['read_php']}\" accept-charset=\"{$_conf['accept_charset']}\">{$label}{$jump}</form>";
    }
}

// }}}
// {{{ _get_read_jump()

/**
 * ページ遷移用のHTML要素を取得する (通常時)
 */
function _get_read_jump(ThreadRead $aThread, $use_onchange)
{
    global $_conf;

    if ($_conf['mobile.rnum_range'] < 1) {
        $options = '<option value="1">$_conf[&#39;mobile.rnum_range&#39;] の値が不正です</option>';
    } else {
        //if ($aThread->resrange['start'] != 1 && $aThread->resrange['start'] % $_conf['mobile.rnum_range']) {
        if (($aThread->resrange['start'] - 1) % $_conf['mobile.rnum_range']) {
            $options = "<option value=\"{$aThread->ls}\" selected>{$aThread->ls}</option>";
        } else {
            $options = '';
        }

        /*$optgroup = $_conf['mobile.rnum_range'] * 5;
        if ($optgroup >= $aThread->rescount) {
            $optgroup = 0; 
        }*/

        $pages = ceil($aThread->rescount / $_conf['mobile.rnum_range']);

        for ($i = 0; $i < $pages; $i++) {
            $j = $i + 1;
            $k = $i * $_conf['mobile.rnum_range'] + 1;
            $l = $j * $_conf['mobile.rnum_range'] + 1;
            if ($l > $aThread->rescount) {
                $l = $aThread->rescount;
            }

            /*if ($k > 1) {
                $k--;
            }*/

            /*if ($optgroup && $i % $optgroup == 0) {
                if ($i) {
                    $options .= '</optgroup>';
                }
                $options .= "<optgroup label=\"{$j}-\">";
            }*/

            if ($k == $l) {
                $m = (string)$k;
                $n = "{$m}n";
            } else {
                $m = "{$k}-";
                $n = "{$m}{$l}n";
            }

            if ($k == $aThread->resrange['start']) {
                $options .= "<option value=\"{$n}\" selected>{$m}</option>";
            } else {
                $options .= "<option value=\"{$n}\">{$m}</option>";
            }
        }

        /*if ($optgroup) {
            $options .= '</optgroup>';
        }*/
    }

    if ($use_onchange) {
        return _get_read_jump_js($aThread, $options);
    } else {
        return _get_read_jump_form($aThread, $options);
    }
}

// }}}
// {{{ _get_read_jump_filter()

/**
 * ページ遷移用のHTML要素を取得する (検索時)
 */
function _get_read_jump_filter(ThreadRead $aThread, $use_onchange)
{
    global $_conf, $filter_range, $filter_hits;

    if ($_conf['mobile.rnum_range'] < 1) {
        $options = '<option value="1">$_conf[&#39;mobile.rnum_range&#39;] の値が不正です</option>';
    } else {
        $options = '';

        /*$optgroup = $_conf['mobile.rnum_range'] * 5;
        if ($optgroup >= $filter_hits) {
            $optgroup = 0; 
        }*/

        $pages = ceil($filter_hits / $_conf['mobile.rnum_range']);

        for ($i = 0; $i < $pages; $i++) {
            $j = $i + 1;
            $k = $i * $_conf['mobile.rnum_range'] + 1;
            $l = $j * $_conf['mobile.rnum_range'];
            if ($l > $filter_hits) {
                $l = $filter_hits;
            }

            /*if ($optgroup && $i % $optgroup == 0) {
                if ($i) {
                    $options .= '</optgroup>';
                }
                $options .= "<optgroup label=\"{$j}-\">";
            }*/

            $m = ($k == $l) ? "$k" : "{$k}-"; //"{$k}-{$l}";

            if ($j == $filter_range['page']) {
                $options .= "<option value=\"{$j}\" selected>{$m}</option>";
            } else {
                $options .= "<option value=\"{$j}\">{$m}</option>";
            }
        }

        /*if ($optgroup) {
            $options .= '</optgroup>';
        }*/
    }

    if ($use_onchange) {
        return _get_read_jump_filter_js($aThread, $options);
    } else {
        return _get_read_jump_filter_form($aThread, $options);
    }
}

// }}}
// {{{ _get_read_jump_form()

/**
 * ページ遷移用フォーム要素を取得する (通常時)
 */
function _get_read_jump_form(ThreadRead $aThread, $options)
{
    global $_conf;

    $word = htmlspecialchars($GLOBALS['word'], ENT_QUOTES);

    return <<<EOP
<input type="hidden" name="host" value="{$aThread->host}">
<input type="hidden" name="bbs" value="{$aThread->bbs}">
<input type="hidden" name="key" value="{$aThread->key}">
<select name="ls">{$options}</select><input type="submit" value="GO">
<input type="hidden" name="offline" value="1">{$_conf['k_input_ht']}
EOP;
}

// }}}
// {{{ _get_read_jump_filter_form()

/**
 * ページ遷移用フォーム要素を取得する (検索時)
 */
function _get_read_jump_filter_form(ThreadRead $aThread, $options)
{
    global $_conf, $hd;

    return <<<EOP
<input type="hidden" name="host" value="{$aThread->host}">
<input type="hidden" name="bbs" value="{$aThread->bbs}">
<input type="hidden" name="key" value="{$aThread->key}">
<input type="hidden" name="word" value="{$hd['word']}">
<input type="hidden" name="method" value="{$hd['method']}">
<input type="hidden" name="field" value="{$hd['field']}">
<input type="hidden" name="match" value="{$hd['match']}">
<select name="page">{$options}</select><input type="submit" value="GO">
<input type="hidden" name="offline" value="1">
{$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
EOP;
}

// }}}
// {{{ _get_read_jump_js()

/**
 * オプションが選択されたときに遷移するselect要素を取得する (通常時)
 */
function _get_read_jump_js(ThreadRead $aThread, $options)
{
    global $_conf;

    return <<<EOP
<select onchange="location.href = '{$_conf['read_php']}?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;ls=' + this.options[this.selectedIndex].value + '&amp;offline=1{$_conf['k_at_a']}';">{$options}</select>
EOP;
}

// }}}
// {{{ _get_read_jump_filter_js()

/**
 * オプションが選択されたときに遷移するselect要素 (検索時)
 */
function _get_read_jump_filter_js(ThreadRead $aThread, $options)
{
    global $_conf;

    return <<<EOP
<select onchange="location.href = '{$_conf['read_php']}{$_conf['filter_q']}' + this.options[this.selectedIndex].value + '{$_conf['k_at_a']}';">{$options}</select>
EOP;
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
