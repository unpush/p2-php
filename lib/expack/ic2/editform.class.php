<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once 'HTML/Template/Flexy/Element.php';

class EditForm
{

    function header($hiddens, $mode)
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mf_head = new HTML_Template_Flexy_Element('form', array(
            'name' => 'edit',
            'id' => 'edit',
            'action' => $_SERVER['SCRIPT_NAME'],
            'method' => 'post',
            'accept-charset' => $GLOBALS['_conf']['accept_charset'],
        ));
        if ($mode == 2) {
            $mf_head->setAttributes('onsubmit="return prePost();"');
        }
        $hiddens['mode'] = $mode;
        foreach ($hiddens as $key => $value) {
            $mf_head->children[] = new HTML_Template_Flexy_Element('input', array(
                'type' => 'hidden',
                'name' => $key,
                'id' => 'edit_' . $key,
                'value' => $value,
                'flexy:xhtml' => $is_xhtml,
                '/' => $is_xhtml,
            ));
        }
        return $mf_head->toHtmlnoClose();
    }


    function submit($id = '')
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mf_submit = new HTML_Template_Flexy_Element('input', array(
            'type' => 'submit',
            'name' => 'edit_submit',
            'id' => 'edit_submit' . $id,
            'value' => '変更',
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        ));
        return $mf_submit->toHtml();
    }


    function remove($id = '')
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mf_remove = new HTML_Template_Flexy_Element('input', array(
            'type' => 'submit',
            'name' => 'edit_remove',
            'id' => 'edit_remove' . $id,
            'value' => '削除',
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        ));
        return $mf_remove->toHtml();
    }


    function toblack($id = '')
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mf_toblack = new HTML_Template_Flexy_Element('input', array(
            'type' => 'checkbox',
            'name' => 'edit_toblack',
            'id' => 'edit_toblack' . $id,
            'value' => '1',
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        ));
        return $mf_toblack->toHtml();
    }


    function reset($id = '')
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mf_reset = new HTML_Template_Flexy_Element('input', array(
            'type' => 'reset',
            'name' => 'edit_reset',
            'id' => 'edit_reset' . $id,
            'value' => '取消',
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        ));
        return $mf_reset->toHtml();
    }


    function checkAllOn($id = '')
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mf_allon = new HTML_Template_Flexy_Element('input', array(
            'type' => 'button',
            'id' => 'edit_checkAllOn' . $id,
            'value' => '選択',
            'onclick' => "iv2_checkAll('on')",
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        ));
        return $mf_allon->toHtml();
    }


    function checkAllOff($id = '')
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mf_alloff = new HTML_Template_Flexy_Element('input', array(
            'type' => 'button',
            'id' => 'edit_checkAllOff' . $id,
            'value' => '解除',
            'onclick' => "iv2_checkAll('off')",
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        ));
        return $mf_alloff->toHtml();
    }


    function checkAllReverse($id = '')
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mf_allreverse = new HTML_Template_Flexy_Element('input', array(
            'type' => 'button',
            'id' => 'edit_checkAllReverse' . $id,
            'value' => '反転',
            'onclick' => "iv2_checkAll('reverse')",
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        ));
        return $mf_allreverse->toHtml();
    }


    function selectRank($range, $id = '')
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mfa_select = array(
            'name' => 'setrank',
            'id' => 'edit_rank' . $id,
            'flexy:xhtml' => $is_xhtml,
        );
        $mfa_option = array(
            'flexy:xhtml' => $is_xhtml,
        );
        $mf_select = &new HTML_Template_Flexy_Element('select', $mfa_select);
        $i = 0;
        foreach ($range as $key => $value) {
            $mf_select->children[$i] = &new HTML_Template_Flexy_Element('option', $mfa_option);
            $mf_select->children[$i]->setAttributes(array('value' =>$key));
            if ($key == 0) {
                $mf_select->children[$i]->setAttributes('selected');
            }
            $mf_select->children[$i]->children[] = $value;
            if ($key == -1) {
                $mf_select->children[$i]->children[] = '(あぼーん)';
            }
            $i ++;
        }
        return $mf_select->toHtml();
    }


    function textMemo($id = '')
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $mfa_text = array(
            'type' => 'text',
            'name' => 'addmemo',
            'id' => 'edit_memo' . $id,
            'size' => '24',
            'value' => '',
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        );
        if ($_conf['ktai']) {
            unset($mfa_text['id'], $mfa_text['size']);
        }
        $mf_text = &new HTML_Template_Flexy_Element('input', $mfa_text);
        return $mf_text->toHtml();
    }


    function imgManager(&$img, &$status)
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        global $ini;

        $mng = array();

        // フォーム要素の属性
        $mfa_input = array(
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        );
        $mfa_checkbox = array_merge($mfa_input, array(
            'type' => 'checkbox',
            'name' => 'change[]',
            'id' => "img{$img['id']}_change",
            'value' => $img['id'],
            'onclick' => "if(this.checked==false){resetRow('img{$img['id']}', {$status['rank']})}",
        ));
        $mfa_remove = array_merge($mfa_input, array(
            'type' => 'checkbox',
            'name' => "img[{$img['id']}][remove]",
            'id' => "img{$img['id']}_remove",
            'value' => 1,
            'onclick' => "var b=document.getElementById('img{$img['id']}_black');if(this.checked==true){b.disabled=false;updateDB('img{$img['id']}');}else{b.disabled=true;}",
        ));
        $mfa_black = array_merge($mfa_input, array(
            'type' => 'checkbox',
            'name' => "img[{$img['id']}][black]",
            'id' => "img{$img['id']}_black",
            'value' => 1,
            'onclick' => "updateDB('img{$img['id']}')",
            'disabled' => true,
        ));
        $mfa_radio = array_merge($mfa_input, array(
            'type' => 'radio',
            'onclick' => "updateDB('img{$img['id']}')",
        ));
        $mfa_hidden_rank = array_merge($mfa_input, array(
            'type' => 'hidden',
            'name' => "img[{$img['id']}][hidden_rank]",
            'id' => "img{$img['id']}_hidden_rank",
            'disabled' => true,
        ));
        $mfa_hidden_msg = array_merge($mfa_input, array(
            'type' => 'hidden',
            'name' => "img[{$img['id']}][hidden_msg]",
            'id' => "img{$img['id']}_hidden_msg",
            'disabled' => true,
        ));
        $mfa_textarea = array(
            'name' => "img[{$img['id']}][memo]",
            'id' => "img{$img['id']}_memo",
            'cols' => $ini['Manager']['cols'],
            'rows' => $ini['Manager']['rows'],
            'onchange' => "updateDB('img{$img['id']}')",
            'flexy:xhtml' => $is_xhtml,
        );

        // DBを更新するチェックボックス
        $mf_change = &new HTML_Template_Flexy_Element('input', $mfa_checkbox);
        $mng['f_change'] = $mf_change->toHtml();

        // あぼーん（rankを-1にして画像を削除）するラジオボタン
        $mf_aborn = &new HTML_Template_Flexy_Element('input', $mfa_radio);
        $mf_aborn->setAttributes(array(
            'name' => "img[{$img['id']}][rank]",
            'id' => "img{$img['id']}_aborn",
            'value' => -1,
        ));
            if ($status['rank'] == -1) {
                $mf_aborn->setAttributes('checked');
            }
        $mng['f_aborn'] = $mf_aborn->toHtml();

        // ランクを変更するラジオボタン
        $mng['f_rank'] = array();
        for ($i = 0; $i < 6; $i ++) {
            $mf_rank = &new HTML_Template_Flexy_Element('input', $mfa_radio);
            $mf_rank->setAttributes(array(
                'name' => "img[{$img['id']}][rank]",
                'id' => "img{$img['id']}_rank{$i}",
                'value' => $i,
            ));
            if ($status['rank'] == $i) {
                $mf_rank->setAttributes('checked');
                $mfa_hidden_rank['value'] = $i;
            }
            $mng['f_rank'][] = $mf_rank->toHtml();
        }
        $mf_hidden_rank = &new HTML_Template_Flexy_Element('input', $mfa_hidden_rank);
        $mng['f_hidden_rank'] = $mf_hidden_rank->toHtml();

        // メモ内容を変更するテキストエリア
        $mf_memo = &new HTML_Template_Flexy_Element('textarea', $mfa_textarea);
        $mf_memo->setValue($status['memo']);
        $mng['f_memo'] = $mf_memo->toHtml();

        // メモ内容の初期状態を保存する隠し要素
        $mf_hidden_msg = &new HTML_Template_Flexy_Element('input', $mfa_hidden_msg);
        $mf_hidden_msg->setValue($status['memo']);
        $mng['f_hidden_msg'] = $mf_hidden_msg->toHtml();

        // 画像を削除するチェックボックス
        $mf_remove = &new HTML_Template_Flexy_Element('input', $mfa_remove);
        $mng['f_remove'] = $mf_remove->toHtml();
        $mf_black = &new HTML_Template_Flexy_Element('input', $mfa_black);
        $mng['f_black'] = $mf_black->toHtml();

        return $mng;
    }


    function imgChecker(&$img)
    {
        global $_conf;
        $is_xhtml = empty($_conf['ktai']);
        $chk = array();

        $mfa_checkbox = array(
            'type' => 'checkbox',
            'name' => 'change[]',
            'id' => "img{$img['id']}_change",
            'value' => $img['id'],
            'flexy:xhtml' => $is_xhtml,
            '/' => $is_xhtml,
        );
        if ($_conf['ktai']) {
            unset($mfa_checkbox['id']);
        }
        $mf_change = &new HTML_Template_Flexy_Element('input', $mfa_checkbox);
        $chk['f_change'] = $mf_change->toHtml();

        return $chk;
    }


}

?>
