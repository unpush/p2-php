<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// $search_pathから実行ファイル$commandを検索する
// 見つかればパスをエスケープして返す（$escapeが偽ならそのまま返す）
// 見つからなければFALSEを返す
function findexec($command, $search_path = '', $escape=TRUE)
{
    // Windowsか、その他のOSか
    if (strstr(PHP_OS, 'WIN')) {
        $ext = '.exe';
        $chk = 'file_exists'; // PHP5未満はWindows上でis_executable()が使えない
    } else {
        $ext = '';
        $chk = 'is_executable';
    }
    // $search_pathが空のときは環境変数PATHから検索する
    if (!$search_path) {
        $search_path = explode(PATH_SEPARATOR, getenv('PATH'));
    }
    // 検索
    if (is_string($search_path) && is_dir($search_path)) {
        if ($ext !== '' && !preg_match('/'.preg_quote($ext).'$/i', $command)) {
            $cmd = $search_path . DIRECTORY_SEPARATOR . $command . $ext;
        } else {
            $cmd = $search_path . DIRECTORY_SEPARATOR . $command;
        }
        if (call_user_func($chk, $cmd)) {
            return ($escape ? escapeshellarg($cmd) : $cmd);
        }
    } elseif (is_array($search_path)) {
        foreach ($search_path as $path) {
            $path = realpath($path);
            if ($path === FALSE || !is_dir($path)) {
                continue;
            }
            if (($found = findexec($command, $path, $escape)) !== FALSE) {
                return $found;
            }
        }
    }
    // 見つからなかった
    return FALSE;
}

?>
