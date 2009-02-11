<?php

/**
 * NTTドコモ iモード端末のリモートホスト正規表現とIPアドレス帯域 (2008/09 時点)
 *
 * @link http://www.nttdocomo.co.jp/service/imode/make/content/ip/index.html
 */

$host = '/^proxy[0-9a-f]\\d\\d\\.docomo\\.ne\\.jp$/';

$band = array(
    '210.153.84.0/24',
    '210.136.161.0/24',
    '210.153.86.0/24',
    '124.146.174.0/24',
    '124.146.175.0/24',
    // フルブラウザ
    '210.153.87.0/24',
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
