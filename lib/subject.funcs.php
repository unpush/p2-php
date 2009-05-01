<?php
/**
 * @return  array
 */
function sbLoadP2SettingTxt($p2_setting_txt)
{
    $p2_setting = array();
    if ($p2_setting_cont = @file_get_contents($p2_setting_txt)) {
        $p2_setting = unserialize($p2_setting_cont);
    }
    
    !isset($p2_setting['viewnum']) and $p2_setting['viewnum'] = null;
    !isset($p2_setting['sort'])    and $p2_setting['sort']    = null;
    !isset($p2_setting['itaj'])    and $p2_setting['itaj']    = null;
    
    return $p2_setting;
}

/**
 * @return  array
 */
function sbSetP2SettingWithQuery($p2_setting)
{
    global $_conf;
    
    isset($_GET['viewnum'])  and $p2_setting['viewnum'] = $_GET['viewnum'];
    isset($_POST['viewnum']) and $p2_setting['viewnum'] = $_POST['viewnum'];
    
    if (!isset($p2_setting['viewnum'])) {
        $p2_setting['viewnum'] = $_conf['display_threads_num']; // デフォルト値
    }

    if (isset($_GET['itaj_en'])) {
        $p2_setting['itaj'] = base64_decode($_GET['itaj_en']);
    }
    return $p2_setting;
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
