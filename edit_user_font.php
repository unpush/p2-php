<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    expack - フォント設定編集インタフェース
*/

// {{{ 初期化

// 初期設定読み込み & ユーザ認証
require_once 'conf/conf.inc.php';
$_login->authorize();

require_once 'HTML/Template/Flexy.php';

$flexy_options = array(
    'templateDir' => './skin',
    'compileDir'  => $_conf['cache_dir'],
    'locale' => 'ja',
    'charset' => 'cp932',
);

$fontconfig_types = array(
    'windows'   => 'Windows',
    'safari2'   => 'Safari >= 2.0',
    'safari1'   => 'Safari < 2.0',
    'macosx'    => 'Mac OS X (Safari以外)',
    'macos9'    => 'Mac OS classic',
//  'pda'       => 'PDA, 携帯フルブラウザ', // 情報不足のため判定ルーチンが書けない
    'other'     => 'その他',
);
$fontconfig_params = array('fontfamily', 'fontfamily_bold', 'fontfamily_aa', 'fontsize', 'menu_fontsize', 'sb_fontsize', 'read_fontsize', 'respop_fontsize', 'infowin_fontsize', 'form_fontsize');

$fontconfig_sizes = array('' => '', '6px' => '6', '8px' => '8', '9px' => '9', '10px' => '10', '11px' => '11', '12px' => '12', '13px' => '13', '14px' => '14', '16px' => '16', '18px' => '18', '21px' => '21', '24px' => '24');

$controllerObject = new StdClass;
$controllerObject->fontconfig_types = $fontconfig_types;
$controllerObject->fontconfig_params = $fontconfig_params;
$controllerObject->skindata = fontconfig_load_skin_setting();
$controllerObject->safari2 = false;
$controllerObject->macos = false;

if (file_exists($_conf['expack.skin.fontconfig_path'])) {
    $current_fontconfig = unserialize(file_get_contents($_conf['expack.skin.fontconfig_path']));
    if (!is_array($current_fontconfig)) {
        $current_fontconfig = array('enabled' => false, 'custom' => array());
    }
} else {
    require_once P2_LIBRARY_DIR . '/filectl.class.php';
    FileCtl::make_datafile($_conf['expack.skin.fontconfig_path'], $_conf['expack.skin.fontconfig_perm']);
    $current_fontconfig = array('enabled' => false, 'custom' => array());
}
$fontconfig_hash = md5(serialize($current_fontconfig));
$updated_fontconfig = array('enabled' => false, 'custom' => array());

// Mac はブラウザによって文字のレンダリング結果が大きく変わり
// その種類もそこそこ多いので現在のブラウザにマッチしないものを隠す
$ft = &$controllerObject->fontconfig_types;
$type = fontconfig_detect_agent();
switch ($type) {
    case 'safari2':
        $controllerObject->safari2 = true;
        unset($ft['safari1'], $ft['macosx'], $ft['macos9']);
        break;
    case 'safari1':
        unset($ft['safari2'], $ft['macosx'], $ft['macos9']);
        break;
    case 'macosx':
        $controllerObject->macos = true;
        unset($ft['safari2'], $ft['safari1'], $ft['macos9']);
        break;
    case 'macos9':
        $controllerObject->macos = true;
        unset($ft['safari2'], $ft['safari1'], $ft['macosx']);
        break;
    default:
        unset($ft['safari1'], $ft['macosx'], $ft['macos9']);
}

// }}}

// テンプレートをコンパイル
$flexy = new HTML_Template_Flexy($flexy_options);
if (!is_dir($_conf['cache_dir'])) {
    FileCtl::mkdir_for($_conf['cache_dir'] . '/dummy_filename');
}
$flexy->compile('edit_user_font.tpl.html');
$elements = $flexy->getElements();

// カスタム設定を利用するか否かを切り替える
if (isset($_POST['use_skin'])) {
    $use_skin = is_array($_POST['use_skin']) ? current($_POST['use_skin']) : $_POST['use_skin'];
} else {
    $use_skin = !$current_fontconfig['enabled'];
}
if ($use_skin) {
    $elements['use_skin']->setAttributes(array('checked' => true));
    $elements['use_user']->setAttributes(array('checked' => false));
    $updated_fontconfig['enabled'] = false;
} else {
    $elements['use_skin']->setAttributes(array('checked' => false));
    $elements['use_user']->setAttributes(array('checked' => true));
    $updated_fontconfig['enabled'] = true;
}

// 変更の適用と、フォームへ値を代入
if (!empty($_POST['clear'])) {
    $_POST = array();
    $current_fontconfig['custom'] = array();
}
foreach ($fontconfig_params as $pname) {
    $elemName = $pname . '[%s]';
    if (isset($elements[$elemName])) {
        foreach ($fontconfig_types as $tname => $ttitle) {
            $newElemName = sprintf($elemName, $tname);
            if (!isset($elements[$newElemName])) {
                $elements[$newElemName] = clone($elements[$elemName]);
            }
            if (!is_array($updated_fontconfig['custom'][$tname])) {
                $updated_fontconfig['custom'][$tname] = array();
            }
            if (isset($_POST[$pname][$tname])) {
                $value = trim($_POST[$pname][$tname]);
            } elseif (isset($current_fontconfig['custom'][$tname][$pname])) {
                $value = $current_fontconfig['custom'][$tname][$pname];
            } else {
                $value = '';
            }
            if ($elements[$newElemName]->tag == 'select' && strpos($pname, 'fontsize') !== false) {
                $elements[$newElemName]->setOptions($fontconfig_sizes);
                if (!array_key_exists($value, $fontconfig_sizes)) {
                    $elements[$newElemName]->setOptions(array($value => $value));
                }
            }
            if ($value) {
                $updated_fontconfig['custom'][$tname][$pname] = $value;
            }
            $elements[$newElemName]->setValue($value);
        }
    }
}

// 保存
$fontconfig_data = serialize($updated_fontconfig);
$fontconfig_new_hath = md5($fontconfig_data);
if (strcmp($fontconfig_hash, $fontconfig_new_hath) != 0) {
    FileCtl::file_write_contents($_conf['expack.skin.fontconfig_path'], $fontconfig_data);
}

// スタイルシートをリセット
unset($STYLE);
include($skin);
if ($updated_fontconfig['enabled']) {
    fontconfig_apply_custom();
} else {
    $skin_en = preg_replace('/&amp;hash=[0-9a-f]{32}\b/', '', $skin_en);
}
$controllerObject->STYLE = $STYLE;
$controllerObject->skin = $skin_en;

// 出力
$flexy->outputObject($controllerObject, $elements);

/**
 * カスタム設定で上書きされていないスキン設定を読み込む
 */
function fontconfig_load_skin_setting()
{
    global $_conf, $STYLE;

    $skindata = array();

    $fontfamily = (isset($STYLE['fontfamily.orig']))
        ? $STYLE['fontfamily.orig']
        : ((isset($STYLE['fontfamily'])) ? $STYLE['fontfamily'] : '');
    $skindata['fontfamily'] = is_array($fontfamily)
        ? implode_fonts($fontfamily)
        : (string) $fontfamily;

    $fontfamily_bold = (isset($STYLE['fontfamily_bold.orig']))
        ? $STYLE['fontfamily_bold.orig']
        : ((isset($STYLE['fontfamily_bold'])) ? $STYLE['fontfamily_bold'] : '');
    $skindata['fontfamily_bold'] = is_array($fontfamily_bold)
        ? implode_fonts($fontfamily_bold)
        : (string) $fontfamily_bold;

    $fontfamily_aa = (isset($_conf['expack.am.fontfamily.orig']))
        ? $_conf['expack.am.fontfamily.orig']
        : ((isset($_conf['expack.am.fontfamily'])) ? $_conf['expack.am.fontfamily'] : '');
    $skindata['fontfamily_aa'] = is_array($fontfamily_aa)
        ? implode_fonts($fontfamily_aa)
        : (string) $fontfamily_aa;

    $sizes = array(
        'fontsize', 'menu_fontsize', 'sb_fontsize', 'read_fontsize',
        'form_fontsize', 'respop_fontsize', 'infowin_fontsize'
    );
    foreach ($sizes as $size) {
        $skindata[$size] = (isset($STYLE[$size])) ? $STYLE[$size] : '';
        $skindata["{$size}_nu"] = preg_replace('/px$/', '', $skindata[$size]);
    }

    return $skindata;
}

function implode_fonts($fonts)
{
    return '"' . implode('","', $fonts) . '"';
}

?>
