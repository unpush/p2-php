<?php
/**
 * rep2expack - 書き込みフォームへの追加要素 for Mobile
 */

// 定型文の初期化と読み込み
$CONSTANT = array();
$CONSTAREA = '';
@include './conf/conf_constant.php';

if (basename($_SERVER['SCRIPT_NAME']) == 'post_form.php') {
    $CONST_TARGET = $_SERVER['SCRIPT_NAME'];
} else {
    $CONST_TARGET = dirname($_SERVER['SCRIPT_NAME']) . '/post_form.php';
}

// フォームの生成
$htm['options_k'] .= "<form method=\"get\" action=\"{$CONST_TARGET}\" accept-charset=\"{$_conf['accept_charset']}\">";
foreach ($_GET as $get_key => $get_value) {
    if ($get_key == 'disp' || $get_key == 'CONSTANT') {
        continue;
    }
    $htm['options_k'] .= "<input type=\"hidden\" name=\"{$get_key}\" value=\"{$get_value}\">";
}

$htm['options_k'] .= '<select name="CONSTANT">';
$htm['options_k'] .= '<option value="">定型文</option>';
foreach ($CONSTANT as $constant_key => $constant_value) {
    $htm['options_k'] .= "<option value=\"{$constant_key}\">{$constant_key}</option>";
    if ($_GET['CONSTANT'] == $constant_key) {
        $CONSTAREA = $constant_value;
    }
}
$htm['options_k'] .= '</select>';

$htm['options_k'] .= '<input type="submit" name="disp" value="表示" title="表示">';
$htm['options_k'] .= $_conf['detect_hint_input_ht'];
$htm['options_k'] .= $_conf['k_input_ht'];
$htm['options_k'] .= '</form>';

if ($CONSTAREA) {
    $htm['options_k'] .= "<textarea>{$CONSTAREA}</textarea>";
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
