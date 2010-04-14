<?php
/**
 * rep2expack - バージョンアップ時の移行支援
 */

// {{{ p2_migrate_yymmdd_hhmm()

/**
 * yymmdd_hhmm を実際のバージョン番号に置換して関数の中身を記述する
 *
 * @param array $core_config rep2コアの設定
 * @param array $user_config 古いユーザー設定
 * @return array 新しいユーザー設定
 */
function p2_migrate_yymmdd_hhmm(array $core_config, array $user_config)
{
    return $user_config;
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
