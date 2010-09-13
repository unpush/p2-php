<?php

/**
 * ソフトバンク端末のリモートホスト正規表現とIPアドレス帯域
 * (Yahoo!ケータイ・PCサイトブラウザは 2010/06/25 時点、Xシリーズは 2010/06/25 時点)
 *
 * @link http://creation.mb.softbank.jp/web/web_ip.html
 * @link http://creation.mb.softbank.jp/xseries/xseries_ip.html
 */

$reghost = '/\\.(?:jp-[a-z]|[a-z]\\.vodafone|softbank|openmobile|pcsitebrowser)\\.ne\\.jp$/';

$bands = array(
    // Yahoo!ケータイ
    '123.108.237.0/27',
    '123.108.237.224/27',
    '202.253.96.0/27',
    '202.253.96.224/27',
    '210.146.7.192/26',
    '210.175.1.128/25',
    // PCサイトブラウザ・Xシリーズ (IE)
    '123.108.237.224/27',
    '202.253.96.0/27',
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
