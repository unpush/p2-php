<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 -  インデックスページ

require_once 'conf/conf.php';   //基本設定ファイル読込

authorize(); // ユーザ認証

// アクセスログを記録
if ($_conf['login_log_rec']) {
    if (isset($_conf['login_log_rec_num'])) {
        P2Util::recAccessLog($_conf['login_log_file'], $_conf['login_log_rec_num']);
    } else {
        P2Util::recAccessLog($_conf['login_log_file']);
    }
}

$s = $_SERVER['HTTPS'] ? 's' : '';
$me_url = "http{$s}://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
$me_dir_url = dirname($me_url);

if ($_conf['ktai']) {

    //=========================================================
    // 携帯用 インデックス
    //=========================================================
    // url指定があれば、そのままスレッド読みへ飛ばす
    if (!empty($_GET['url']) || !empty($_GET['nama_url'])) {
        header('Location: '.$me_dir_url.'/read.php?'.$_SERVER['QUERY_STRING']);
        exit;
    }
    include (P2_LIBRARY_DIR . '/index_print_k.inc.php');
    index_print_k();

} else {
    //=========================================
    // PC用 変数
    //=========================================
    $htm['menu_page']  = 'menu.php';
    $htm['title_page'] = 'title.php';

    if (!empty($_GET['url']) || !empty($_GET['nama_url'])) {
        list($host, $bbs, $key, $ls) = P2Util::detectThread();
        $htm['read_page']  = $_conf['read_php'] . '?' . $_SERVER['QUERY_STRING'];
        $htm['title_page'] = $_conf['subject_php'] . '?host=' . $host . '&bbs=' . $bbs;
    } else {
        if (!empty($_conf['first_page'])) {
            $htm['read_page'] = $_conf['first_page'];
        } else {
            $htm['read_page'] = 'first_cont.php';
        }
    }

    $sidebar = !empty($_GET['sidebar']);

    $ptitle = 'p2';

    $frame_name['read']     = 'read';
    $frame_name['subject']  = 'subject';

    //======================================================
    // PC用 HTMLプリント
    //======================================================
    function get_frameset_open(&$tablevel, $frameborder, $border, $type, $typed)
    {
      $str  = str_repeat("\t", $tablevel);
      $str .= <<<EOS
<frameset {$type}="{$typed}" frameborder="{$frameborder}" border="{$border}">\n
EOS;
      $tablevel++;
      return $str;
    }
    function get_frameset_close(&$tablevel)
    {
      $tablevel--;
      $str  = str_repeat("\t", $tablevel);
      $str .= "</frameset>\n";
      return $str;
    }
    function get_frame(&$tablevel, $src, $name, $scrolling)
    {
      $str  = str_repeat("\t", $tablevel);
      $str .= <<<EOS
	<frame src="{$src}" name="{$name}" scrolling="{$scrolling}">\n
EOS;
      return $str;
    }
    function get_frame_menu(&$tablevel)
    {
      global $_conf, $htm, $frame_name;
      return get_frame($tablevel, $htm['menu_page'], "menu", "auto");
    }
    function get_frame_subject(&$tablevel)
    {
      global $_conf, $htm, $frame_name;
      return get_frame($tablevel, $htm['title_page'], $frame_name['subject'], "auto");
    }
    function get_frame_read(&$tablevel)
    {
      global $_conf, $htm, $frame_name;
      return get_frame($tablevel, $htm['read_page'], $frame_name['read'], "auto");
    }
    $tablevel=0;
    switch($_conf['frame_type']){
     default: // 0,1,4,5
      $frameset = "";
      if(!$sidebar){
        $frameset .= get_frameset_open($tablevel, 1, 1, "cols", $_conf['frame_cols']);
        if(!$_conf['frame_type'] % 2){
          $frameset .= get_frame_menu($tablevel);
        }
      }
      $frameset .= get_frameset_open($tablevel, 1, 2, "rows", $_conf['frame_rows']);
      $frameset .= get_frame_subject($tablevel);
      $frameset .= get_frame_read($tablevel);
      $frameset .= get_frameset_close($tablevel);
      if(!$sidebar){
        if($_conf['frame_type'] % 2){
          $frameset .= get_frame_menu($tablevel);
        }
        $frameset .= get_frameset_close($tablevel);
      }
      break;
     case 2: case 3:
      $frameset = get_frameset_open($tablevel, 1, 2, "rows", $_conf['frame_rows']);
      if(!$sidebar && $_conf['frame_type'] == 2){
        $frameset .= get_frameset_open($tablevel, 1, 1, "cols", $_conf['frame_cols']);
        $frameset .= get_frame_menu($tablevel);
      }
      $frameset .= get_frame_subject($tablevel);
      if(!$sidebar && $_conf['frame_type'] == 2){
        $frameset .= get_frameset_close($tablevel);
      }
      if(!$sidebar && $_conf['frame_type'] == 3){
        $frameset .= get_frameset_open($tablevel, 1, 1, "cols", $_conf['frame_cols']);
        $frameset .= get_frame_menu($tablevel);
      }
      $frameset .= get_frame_read($tablevel);
      if(!$sidebar && $_conf['frame_type'] == 3){
        $frameset .= get_frameset_close($tablevel);
      }
      $frameset .= get_frameset_close($tablevel);
      break;
    }

    P2Util::header_nocache();
    P2Util::header_content_type();
    echo <<<EOF
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
 "http://www.w3.org/TR/html4/frameset.dtd">
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>
    <link href="favicon.ico" type="image/x-icon" rel="shortcut icon">
</head>
{$frameset}
</html>
EOF;

}

?>
