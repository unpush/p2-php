<?php
// p2 携帯TOPメニューの編集

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/filectl.class.php';

require_once P2_LIB_DIR . '/UA.php';

$_login->authorize(); // ユーザ認証

// {{{ 特殊な前処理

// 並び替え
if (isset($_GET['code']) && isset($_GET['set'])) {
    _setOrderIndexMenuK($_GET['code'], $_GET['set']);

} elseif (isset($_REQUEST['setfrom1'])) {
    P2Util::setConfUser('index_menu_k_from1', (int)$_REQUEST['setfrom1']);

// デフォルトに戻す
} elseif (isset($_GET['setdef'])) {
    P2Util::setConfUser('index_menu_k', $conf_user_def['index_menu_k']);
    P2Util::setConfUser('index_menu_k_from1', $conf_user_def['index_menu_k_from1']);
}

// }}}

require_once P2_LIB_DIR . '/index_print_k.inc.php';

$setfrom1 = (int) !$_conf['index_menu_k_from1'];

$menuKLinkHtmls = getMenuKLinkHtmls($_conf['menuKIni'], $noLink = true);

$body_at    = P2View::getBodyAttrK();
$hr         = P2View::getHrHtmlK();

//================================================================
// ヘッダHTML表示
//================================================================
P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
    <title>rep2 - 携帯TOPﾒﾆｭｰの並び替え</title>
<?php

if (!$_conf['ktai']) {
    P2View::printIncludeCssHtml('style');
    P2View::printIncludeCssHtml('editfavita');
}

echo <<<EOP
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body{$body_at}>\n
EOP;

P2Util::printInfoHtml();


// 並び替え メインHTMLを出力する


if (UA::isK()) {
    echo P2View::getBackToIndexKATag();
    echo '<hr>';
}
?>
<strong>携帯TOPﾒﾆｭｰの並び替え</strong><br>
<table>
<?php
foreach ($menuKLinkHtmls as $code => $html) {
    echo <<<EOP
    <tr>
        <td>$html</td>
        <td>[ <a class="te" href="{$_SERVER['SCRIPT_NAME']}?code={$code}&amp;set=top{$_conf['k_at_a']}" title="一番上に移動">▲</a></td>
        <td><a class="te" href="{$_SERVER['SCRIPT_NAME']}?code={$code}&amp;set=up{$_conf['k_at_a']}" title="一つ上に移動">↑</a></td>
        <td><a class="te" href="{$_SERVER['SCRIPT_NAME']}?code={$code}&amp;set=down{$_conf['k_at_a']}" title="一つ下に移動">↓</a></td>
        <td><a class="te" href="{$_SERVER['SCRIPT_NAME']}?code={$code}&amp;set=bottom{$_conf['k_at_a']}" title="一番下に移動">▼</a> ]</td>
    </tr>
EOP;
}
?></table>
<br>
[<a href="<?php eh($_SERVER['SCRIPT_NAME']); ?>?setfrom1=<?php echo $setfrom1; ?><?php echo $_conf['k_at_a']; ?>">ｱｸｾｽｷｰを<?php echo $setfrom1; ?>からの連番とする</a>]<br>
[<a href="<?php eh($_SERVER['SCRIPT_NAME']); ?>?setdef=1<?php echo $_conf['k_at_a']; ?>">ﾃﾞﾌｫﾙﾄに戻す</a>]
<?php
// フッタHTMLを表示する
if (UA::isK()) {
    echo $hr . P2View::getBackToIndexKATag();
}

?></body></html><?php

exit;

//======================================================================
// 関数（このファイル内でのみ利用）
//======================================================================
/**
 * 携帯TOPメニューの順番を変更する関数
 *
 * @access  public
 * @param   string  $code
 * @param   string  $set  top, up, down, bottom
 * @return  boolean
 */
function _setOrderIndexMenuK($code, $set)
{
    global $_conf;

    if (!preg_match('/^[\\w]+$/', $code) || !preg_match('/^[\\w]+$/', $set)) {
        P2Util::pushInfoHtml('<p>p2 error: 引数が変です</p>');
        return false;
    }
    
    /*
$_conf['index_menu_k'] = array(
    'recent_shinchaku', // 0.最近読んだｽﾚの新着
    'recent',           // 1.最近読んだｽﾚの全て
    'fav_shinchaku',    // 2.お気にｽﾚの新着
    'fav',              // 3.お気にｽﾚの全て
    'favita',           // 4.お気に板
    'cate',             // 5.板ﾘｽﾄ
    'res_hist',         // 6.書込履歴 #.ﾛｸﾞ
    'palace',           // 7.ｽﾚの殿堂
    'setting',          // 8.ﾛｸﾞｲﾝ管理
    'editpref'          // 9.設定管理
);
*/
    /*
    // 無効なコード
    if (!in_array($code, $_conf['index_menu_k'])) {
        return false;
    }
    */
    $menu = $_conf['index_menu_k'];
    if (false === $menu = _getMenuKMovedIndex($menu, $code, $set)) {
        return false;
    }

    if (false === P2Util::setConfUser('index_menu_k', $menu)) {
        return false;
    }
    
    return true;
}

/**
 * @param  array   $menu
 * @param  integer $to      0-
 * @param  string  $code
 * @return array
 */
function _getMenuKMovedIndex($menu, $code, $set)
{
    if (false === $r = _getMenuKIndexToMove($menu, $code, $set)) {
        return false;
    }
    list($from, $to) = $r;
    
    return _getArrayMovedIndex($menu, $from, $to);
}

/**
 * @param  array   $menu
 * @param  string  $code
 * @param  string  $set
 * return  integer|false
 */
function _getMenuKIndexToMove($menu, $code, $set)
{
    if (false === $from = array_search($code, $menu)) {
        return false;
    }
    if ($set == 'top') {
        $to = 0;
    } elseif ($set == 'up') {
        $to = max(0, $from - 1);
    } elseif ($set == 'down') {
        $to = min($from + 1, count($menu));
    } elseif ($set == 'bottom') {
        $to = count($menu);
    } else {
        return false;
    }
    return array($from, $to);
}

/**
 * @param  array   $array
 * @param  integer $from    0-
 * @param  integer $to      0-
 * @return array
 */
function _getArrayMovedIndex($array, $from, $to)
{
    if ($from == $to) {
        return $array;
    }
    $item = $array[$from];
    $post = $array;
    $div = ($from < $to) ? $to + 1 : $to;
    $pre  = array_splice($post, 0, $div);
    if ($from < $to) {
        unset($pre[$from]);
    } elseif ($to < $from) {
        unset($post[$from - $to]);
    }
    $newArray = array_merge($pre, array($item), $post);
    $newArray = array_unique($newArray);
    return $newArray;
}
