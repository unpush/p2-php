<?php

/**
 * イー・モバイル端末のリモートホスト正規表現とIPアドレス帯域 (2008/02/26 時点)
 *
 * JPNIC Whois Gatewayで調べたIPアドレス帯域はイー・モバイルに割り当てられている
 * 帯域すべてで、ネットワーク「EM-INFRA」「EM-USER」以外も含む。
 *
 * @link http://developer.emnet.ne.jp/ipaddress.html
 * @link http://whois.nic.ad.jp/cgi-bin/whois_gw
 */

$reghost = '/\\.pool\\.e(?:mnet|-mobile)\\.ne\\.jp$/';

$bands = array(
    '60.254.192.0/18',      // JPNIC Whois Gateway
    '114.48.0.0/14',        // JPNIC Whois Gateway
    '117.55.0.0/17',        // JPNIC Whois Gateway
    //'117.55.1.224/27',    // http://developer.emnet.ne.jp/ipaddress.html
    '119.72.0.0/16',        // JPNIC Whois Gateway
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
