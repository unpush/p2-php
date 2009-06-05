<?php
// p2 - スタイルシートを外部スタイルシートとして出力する
/*
<link rel="stylesheet" type="text/css" href="css.php?css=style&user=nanashi&skin=">
<link rel="stylesheet" type="text/css" href="css.php?css=read&user=nanashi&skin=">
*/

require_once './conf/conf.inc.php' ;

//$_login->authorize(); // ユーザ認証

// 妥当なCSSファイル指定か検証して取得する
if (!isset($_GET['css']) or !$cssFilePath = _getValidCssFilePath($_GET['css'])) {
    exit;
}

// CSS出力
_printCss($cssFilePath, geti($_GET['skin']));

exit;


//===============================================================================
// 関数（このファイル内でのみ利用）
//===============================================================================
/**
 * @return  void  CSS出力
 */
function _printCss($cssFilePath, $skin = null)
{
    global $_conf, $STYLE, $MYSTYLE;
    
/*
// クエリにユニークキーを埋め込んでいるいるので、キャッシュさせてよい
// ノーマルp2ではまだ含んでないよ
$now = time();
header('Expires: ' . http_date($now + 3600));
header('Last-Modified: ' . http_date($now));
header('Pragma: cache');
header('Content-Type: text/css; charset=Shift_JIS');
*/

    $mtime = max(filemtime($cssFilePath), filemtime(_getSkinFilePath($skin)));

    if (file_exists($_conf['conf_user_file'])) {
        $mtime = max($mtime, filemtime($_conf['conf_user_file']));
    }
    
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('Content-Type: text/css; charset=Shift_JIS');
    
    echo "@charset \"Shift_JIS\";\n\n";
    ob_start();
    include_once $cssFilePath;
    
    // $MYSTYLEでCSSを上書き表示
    require_once P2_LIB_DIR . '/mystyle_css.funcs.php';
    $MYSTYLE = parse_mystyle($MYSTYLE);
    printMystyleCssByFileName($cssFilePath);

    // 空スタイルを除去
    echo preg_replace('/[a-z\\-]+[ \\t]*:[ \\t]*;/', '', ob_get_clean());
}

/**
 * @return  string
 */
function _getCssFilePath($cssname)
{
    return P2_STYLE_DIR . DIRECTORY_SEPARATOR . rawurlencode($cssname) . '_css.inc';;
}

/**
 * 妥当なCSSファイル指定か検証して取得する
 *
 * @return  string|false
 */
function _getValidCssFilePath($cssname)
{
    if (preg_match('/^\\w+$/', $cssname)) {
        $cssFilePath = _getCssFilePath($cssname);
        if (file_exists($cssFilePath)) {
            return $cssFilePath;
        }
    }
    return false;
}

/**
 * @return  string
 */
function _getSkinFilePath($skin = null)
{
    global $_conf;
    
    $skinFilePath = '';
    
    if ($skin) {
        $skinFilePath = P2Util::getSkinFilePathBySkinName($skin);
        
    } elseif ($skin_setting_path = _getSkinSettingPath()) {
        $skinFilePath = P2Util::getSkinFilePathBySkinName($skin_setting_path);
    }
    
    if (!$skinFilePath || !is_file($skinFilePath)) {
        $skinFilePath = $_conf['conf_user_style_inc_php'];
    }
    return $skinFilePath;
}

/**
 * @return  string|null|false
 */
function _getSkinSettingPath()
{
    global $_conf;
    
    if (file_exists($_conf['skin_setting_path'])) {
        if (false === $cont = file_get_contents($_conf['skin_setting_path'])) {
            return false;
        }
        return rtrim($cont);
    }
    return null;
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
