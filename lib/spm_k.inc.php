<?php
/**
 * rep2expack - 携帯から SPM 相当の機能を利用するための関数
 */

// {{{ kspform()

/**
 * レス番号を指定して 移動・コピー(+引用)・AAS するフォームを生成
 *
 * @return string
 */
function kspform($aThread, $default = '', $params = null)
{
    global $_conf;

    if ($_conf['iphone']) {
        $input_numeric_at = ' autocorrect="off" autocapitalize="off" placeholder="#"';
    } else {
        // 入力を4桁以下の数字に限定する
        //$input_numeric_at = ' maxlength="4" istyle="4" format="*N" mode="numeric"';
        $input_numeric_at = ' maxlength="4" istyle="4" format="4N" mode="numeric"';
    }

    // 選択可能なオプション
    $options = array();
    $options['goto'] = 'GO';
    $options['copy'] = 'ｺﾋﾟｰ';
    $options['copy_quote'] = '&gt;ｺﾋﾟｰ';
    $options['res']  = 'ﾚｽ';
    $options['res_quote']  = '&gt;ﾚｽ';
    if ($_conf['expack.aas.enabled']) {
        $options['aas']        = 'AAS';
        $options['aas_rotate'] = 'AAS*';
    }
    $options['aborn_res']  = 'ｱﾎﾞﾝ:ﾚｽ';
    $options['aborn_name'] = 'ｱﾎﾞﾝ:名前';
    $options['aborn_mail'] = 'ｱﾎﾞﾝ:ﾒｰﾙ';
    $options['aborn_id']   = 'ｱﾎﾞﾝ:ID';
    $options['aborn_msg']  = 'ｱﾎﾞﾝ:ﾒｯｾｰｼﾞ';
    $options['ng_name'] = 'NG:名前';
    $options['ng_mail'] = 'NG:ﾒｰﾙ';
    $options['ng_id']   = 'NG:ID';
    $options['ng_msg']  = 'NG:ﾒｯｾｰｼﾞ';

    // フォーム生成
    $form = "<form method=\"get\" action=\"spm_k.php\">";
    $form .= $_conf['k_input_ht'];

    // 隠しパラメータ
    $hidden = '<input type="hidden" name="%s" value="%s">';
    $form .= sprintf($hidden, 'host', htmlspecialchars($aThread->host, ENT_QUOTES));
    $form .= sprintf($hidden, 'bbs', htmlspecialchars($aThread->bbs, ENT_QUOTES));
    $form .= sprintf($hidden, 'key', htmlspecialchars($aThread->key, ENT_QUOTES));
    $form .= sprintf($hidden, 'rescount', $aThread->rescount);
    $form .= sprintf($hidden, 'offline', '1');

    // 追加の隠しパラメータ
    if (is_array($params)) {
        foreach ($params as $param_name => $param_value) {
            $form .= sprintf($hidden, $param_name, htmlspecialchars($param_value, ENT_QUOTES));
        }
    }

    // オプションを選択するメニュー
    $form .= '<select name="ktool_name">';
    foreach ($options as $opt_name => $opt_title) {
        $form .= "<option value=\"{$opt_name}\">{$opt_title}</option>";
    }
    $form .= '</select>';

    // 数値入力フォームと実行ボタン
    $form .= "<input type=\"text\" size=\"3\" name=\"ktool_value\" value=\"{$default}\"{$input_numeric_at}>";
    $form .= '<input type="submit" value="OK" title="OK">';

    $form .= '</form>';

    return $form;
}

// }}{
// {{{ kspDetectThread()

/**
 * スレッドを指定する
 */
function kspDetectThread()
{
    global $_conf, $host, $bbs, $key, $ls;

    list($nama_url, $host, $bbs, $key, $ls) = P2Util::detectThread();

    if (!($host && $bbs && $key)) {
        if ($nama_url) {
            $nama_url = htmlspecialchars($nama_url, ENT_QUOTES);
            p2die('スレッドの指定が変です。', "<a href=\"{$nama_url}\">{$nama_url}</a>", true);
        } else {
            p2die('スレッドの指定が変です。');
        }
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
