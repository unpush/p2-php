<?php
/*
    ファイルをブラウザで編集する
*/

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// 引数エラー
if (!isset($_POST['path'])) {
    die('Error: path が指定されていません');
}

// 変数 ==================================
$path       = geti($_POST['path']);
$modori_url = geti($_POST['modori_url']);
$encode     = geti($_POST['encode']);

$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 36;
$cols = isset($_POST['cols']) ? intval($_POST['cols']) : 128;

isset($_POST['filecont']) and $filecont = $_POST['filecont'];

//=========================================================
// 前処理
//=========================================================
// 書き込めるファイルを限定する
_checkWritableFiles($path); // void|exit

//=========================================================
// メイン 
//=========================================================
if (isset($filecont)) {
    if (_setFile($path, $filecont, $encode)) {
        P2Util::pushInfoHtml("saved, OK.");
    }
}

_printEditFileHtml($path, $encode);


//=========================================================
// 関数（このファイル内でのみ利用）
//=========================================================
/**
 * @return  void|exit
 */
function _checkWritableFiles($path)
{
    $writable_files = array(
        //'p2_aborn_name.txt', 'p2_aborn_mail.txt', 'p2_aborn_msg.txt', 'p2_aborn_id.txt',
        //'p2_ng_name.txt', 'p2_ng_mail.txt', 'p2_ng_msg.txt', 'p2_ng_id.txt',
        'p2_aborn_res.txt',
    );

    if ($writable_files and (!in_array(basename($path), $writable_files))) {
        $i = 0;
        foreach ($writable_files as $afile) {
            if ($i != 0) {
                $files_st .= "と";
            }
            $files_st .= "「" . $afile . "」";
            $i++;
        }
        die("Error: " . basename($_SERVER['SCRIPT_NAME']) . " 先生の書き込めるファイルは、" . $files_st . "だけ！");
    }
}

/**
 * ファイルに内容をセットする関数
 *
 * @return  boolean
 */
function _setFile($path, $cont, $encode)
{
    if ($path == '') {
        die('Error: path が指定されていません');
    }

    if ($encode == "EUC-JP") {
        $cont = mb_convert_encoding($cont, 'SJIS-win', 'eucJP-win');
    }
    // 書き込む
    if (false === file_put_contents($path, $cont, LOCK_EX)) {
        die("Error: cannot write. ( $path )");
        return false;
    }
    return true;
}

/**
 * ファイル内容を読み込んで編集のためのHTMLを表示する
 *
 * @return  void
 */
function _printEditFileHtml($path, $encode)
{
    global $_conf, $modori_url, $rows, $cols;
    
    $info_msg_ht = P2Util::getInfoHtml();
    
    if ($path == '') {
        die('Error: path が指定されていません');
    }
    
    $filename = basename($path);
    $ptitle = "Edit: " . $filename;
    
    if (false === FileCtl::make_datafile($path)) {
         die(sprintf("Error: cannot make file. ( %s )", hs($path)));
    }
    $cont = file_get_contents($path);
    
    if ($encode == 'EUC-JP') {
        $cont = mb_convert_encoding($cont, 'SJIS-win', 'eucJP-win');
    }
    
    $cont_area_ht = htmlspecialchars($cont, ENT_QUOTES);
    
    $modori_url_ht = '';
    if ($modori_url) {
        $modori_url_ht = sprintf('<p><a href="%s">Back</a></p>', hs($modori_url));
    }
    
    ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title><?php eh($ptitle); ?></title>
</head>
<body onLoad="top.document.title=self.document.title;">

<?php echo $modori_url_ht; ?>
Edit: <?php eh($path); ?>
<form action="<?php eh($_SERVER['SCRIPT_NAME']); ?>" method="post" accept-charset="<?php eh($_conf['accept_charset']); ?>">
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" name="path" value="<?php eh($path); ?>">
    <input type="hidden" name="modori_url" value="<?php eh($modori_url); ?>">
    <input type="hidden" name="encode" value="<?php eh($encode); ?>">
    <input type="hidden" name="rows" value="<?php eh($rows); ?>">
    <input type="hidden" name="cols" value="<?php eh($cols); ?>">
    <input type="submit" name="submit" value="Save"> <?php echo $info_msg_ht; ?><br>
    <textarea style="font-size:9pt;" id="filecont" name="filecont" rows="<?php eh($rows); ?>" cols="<?php eh($cols); ?>" wrap="off"><?php eh($cont); ?></textarea>
</form>

</body></html>
<?php
}
