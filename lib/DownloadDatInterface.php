<?php
/**
 * rep2expack - datダウンロード用インターフェイス
 */

// {{{ DownloadDatInterface

interface DownloadDatInterface
{
    // {{{ invoke()

    /**
     * スレッドのdatをダウンロードし、保存する
     *
     * @param ThreadRead $aThread
     * @return bool
     */
    static public function invoke(ThreadRead $aThread);

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
