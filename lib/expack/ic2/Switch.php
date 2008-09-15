<?php
/**
 * ImagCache2::ON/OFF
 */

// {{{ IC2_Switch

/**
 * ImageCache2 の一時的な有効・無効切替クラス
 *
 * @static
 */
class IC2_Switch
{
    // {{{ constants

    /**
     * PCは有効
     */
    const ENABLED_PC = 1; // 1 << 0

    /**
     * 携帯は有効
     */
    const ENABLED_MOBILE = 2; // 1 << 1

    /**
     * すべて有効
     */
    const ENABLED_ALL = 3; // self::ENABLED_PC | self::ENABLED_MOBILE

    // }}}
    // {{{ get()

    /**
     * ImageCache2 の一時的な有効・無効を取得する
     *
     * @param bool $mobile
     * @return bool
     */
    static public function get($mobile = false)
    {
        global $_conf;

        $switch_file = $_conf['expack.ic2.switch_path'];
        if (!file_exists($switch_file)) {
            return true;
        }

        $flags = filesize($switch_file);
        if ($mobile) {
            return (bool)($flags & self::ENABLED_MOBILE);
        } else {
            return (bool)($flags & self::ENABLED_PC);
        }
    }

    // }}}
    // {{{ set()

    /**
     * ImageCache2 の一時的な有効・無効を切り替える
     *
     * @param bool $switch
     * @param bool $mobile
     * @return bool
     */
    static public function set($switch, $mobile = false)
    {
        global $_conf;

        $switch_file = $_conf['expack.ic2.switch_path'];
        if (!file_exists($switch_file)) {
            FileCtl::make_datafile($switch_file, $_conf['p2_perm']);
            $flags = self::ENABLED_ALL;
        } else {
            $flags = self::ENABLED_ALL & filesize($switch_file);
        }

        if ($switch) {
            if ($mobile) {
                $flags |= self::ENABLED_MOBILE;
            } else {
                $flags |= self::ENABLED_PC;
            }
        } else {
            if ($mobile) {
                $flags &= ~self::ENABLED_MOBILE;
            } else {
                $flags &= ~self::ENABLED_PC;
            }
        }

        if ($flags > 0) {
            $data = str_repeat('*', $flags);
        } else {
            $data = '';
        }

        return (file_put_contents($switch_file, $data, LOCK_EX) === $flags);
    }

    // }}}
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
