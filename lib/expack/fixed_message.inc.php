<?php
/**
 * rep2expack - 本文の定型文
 */

require_once P2_LIB_DIR . '/SjisPersister.php';

// {{{ fixed_message_get_persister()

/**
 * 定型文を保存するストレージを取得する
 *
 * @param void
 * @return SjisPersister
 */
function fixed_message_get_persister()
{
    global $_conf;
    $filename = $_conf['pref_dir'] . DIRECTORY_SEPARATOR . 'fixed_message.db';
    return KeyValuePersister::getPersister($filename, 'SjisPersister');
}

// }}}
// {{{ fixed_name_get_select_element()

/**
 * 定型文を選択するselect要素を取得する
 *
 * @param   string  $name       select要素のid属性値・兼・name属性値 (デフォルトは'fixed_message')
 * @param   string  $onchange   option要素が選択されたときのイベントハンドラ (デフォルトはなし)
 * @return  string  select要素のHTML
 */
function fixed_name_get_select_element($name = 'fixed_message', $onchange = null)
{
    $name_ht = htmlspecialchars($name, ENT_QUOTES, 'Shift_JIS');
    if ($onchange !== null) {
        $onchange_ht = htmlspecialchars($onchange, ENT_QUOTES, 'Shift_JIS');
        $select = "<select id=\"{$name_ht}\" name=\"{$name_ht}\" onchange=\"{$onchange_ht}\">\n";
    } else {
        $select = "<select id=\"{$name_ht}\" name=\"{$name_ht}\">\n";
    }
    $select .= "<option value=\"\">定型文</option>\n";
    foreach (fixed_message_get_persister()->getKeys() as $key) {
        $key_ht = htmlspecialchars($onchange, ENT_QUOTES, 'Shift_JIS');
        $select .= "<option value=\"{$key_ht}\">{$key_ht}</option>\n";
    }
    $select .= "</option>";
    return $select;
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
