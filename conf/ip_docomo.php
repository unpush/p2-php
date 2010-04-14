<?php

/**
 * NTTドコモ iモード端末のリモートホスト正規表現とIPアドレス帯域 (2009/11 時点)
 *
 * @link http://www.nttdocomo.co.jp/service/imode/make/content/ip/index.html
 */

$reghost = '/^proxy[0-9a-f]\\d\\d\\.docomo\\.ne\\.jp$/';

$bands = array(
    //'124.146.174.0/24',
    //'124.146.175.0/24',
    '124.146.174.0/23', // 上二つを統一
    '210.136.161.0/24',
    '210.153.84.0/24',
    '210.153.86.0/24',
    //'202.229.176.0/24',
    //'202.229.177.0/24',
    '202.229.176.0/23', // 上二つを統一
    '202.229.178.0/24',
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
