<?php

/**
 * ソフトバンク端末のリモートホスト正規表現とIPアドレス帯域
 * (Yahoo!ケータイ・PCサイトブラウザは 2009/08/18 時点、Xシリーズは 2009/10/23 時点)
 *
 * @link http://creation.mb.softbank.jp/web/web_ip.html
 * @link http://creation.mb.softbank.jp/xseries/xseries_ip.html
 */

$reghost = '/\\.(?:jp-[a-z]|[a-z]\\.vodafone|softbank|openmobile|pcsitebrowser)\\.ne\\.jp$/';

$bands = array(
    // Yahoo!ケータイ
    '123.108.236.0/24',
    '123.108.237.0/27',
    '202.179.204.0/24',
    '202.253.96.224/27',
    '210.146.7.192/26',
    '210.146.60.192/26',
    '210.151.9.128/26',
    '210.175.1.128/25',
    '211.8.159.128/25',
    // PCサイトブラウザ
    '123.108.237.224/27',
    '202.253.96.0/28',
    // Xシリーズ (IE)
    '123.108.237.224/27',
    '202.253.96.0/28',
    // Xシリーズ (他アプリ)
    '126.243.0.0/16',
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
