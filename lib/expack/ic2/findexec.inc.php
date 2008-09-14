<?php
/**
 * ImageCache2 - 実行ファイル検索関数
 */

// {{{ findexec()

/**
 * $search_pathから実行ファイル$commandを検索する
 * 見つかればパスをエスケープして返す（$escapeが偽ならそのまま返す）
 * 見つからなければfalseを返す
 */
function findexec($command, $search_path = '', $escape = true)
{
    // Windowsか、その他のOSか
    if (P2_OS_WINDOWS) {
        if (strtolower(strrchr($command, '.')) != '.exe') {
            $command .= '.exe';
        }
        $check = function_exists('is_executable') ? 'is_executable' : 'file_exists';
    } else {
        $check = 'is_executable';
    }

    // $search_pathが空のときは環境変数PATHから検索する
    if ($search_path == '') {
        $search_dirs = explode(PATH_SEPARATOR, getenv('PATH'));
    } else {
        $search_dirs = explode(PATH_SEPARATOR, $search_path);
    }

    // 検索
    foreach ($search_dirs as $path) {
        $path = realpath($path);
        if ($path === false || !is_dir($path)) {
            continue;
        }
        if ($check($path . DIRECTORY_SEPARATOR . $command)) {
            return ($escape ? escapeshellarg($command) : $command);
        }
    }

    // 見つからなかった
    return false;
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
