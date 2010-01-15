<?php

/**
 * iPhoneのリモートホスト正規表現とIPアドレス帯域
 */

$reghost = '/\\.(?:[0-9]|1[0-5])\\.tik\\.panda-world\\.ne\\.jp$/';

$bands = array(
    '126.240.0.0/12', // ソフトバンクBBが 126.0.0.0/8 なので
                      // iPhoneに限定できないおそれあり
);

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
