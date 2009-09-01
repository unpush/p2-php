<?php

/**
 * イーモバイル端末のリモートホスト正規表現とIPアドレス帯域 (2008/02/26 時点)
 *
 * @link http://developer.emnet.ne.jp/ipaddress.html
 */

$host = '/\\.pool\\.e(?:mnet|-?mobile)\\.ne\\.jp$/';

$band = array(
    '60.254.192.0/18',      // JPNIC Whois Gateway
    '117.55.0.0/17',        // JPNIC Whois Gateway
    //'117.55.1.224/27',    // http://developer.emnet.ne.jp/ipaddress.html
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
