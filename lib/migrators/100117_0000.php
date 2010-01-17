<?php
/**
 * rep2expack - バージョンアップ時の移行支援
 */

// {{{ p2_migrate_100117_0000()

/**
 * 不要になった新着まとめ読みキャッシュファイルを削除する
 *
 * @param array $core_config rep2コアの設定
 * @param array $user_config 古いユーザー設定
 * @return array 新しいユーザー設定
 */
function p2_migrate_100117_0000(array $core_config, array $user_config)
{
    $pref_dir = $core_config['pref_dir'];

    if (is_dir($pref_dir)) {
        $current_dir = getcwd();
        if ($current_dir === false) {
            $current_dir = P2_BASE_DIR;
        }
        if (chdir($pref_dir)) {
            _100117_0000_glob_unlink('./matome_cache.htm');
            _100117_0000_glob_unlink('./matome_cache.*.htm');
            _100117_0000_glob_unlink('./matome_cache.*.lck');
            chdir($current_dir);
        }
    }

    return $user_config;
}

// }}}
// {{{ _100117_0000_glob_unlink()

/**
 * glob()で見つかったファイルを削除する
 *
 * @param string $pattern
 * @return void
 */
function _100117_0000_glob_unlink($pattern)
{
    if ($files = glob($pattern, GLOB_NOSORT)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
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
