<?php

/**
 * iPhoneのリモートホスト正規表現とIPアドレス帯域
 *
 * 126.240.0.0/12 を /16 ごとに調べてみると...
 * 126.240.*.* -> pw126240*.0.tik.panda-world.ne.jp
 * 126.241.*.* -> pw126241*.1.tik.panda-world.ne.jp
 * 126.242.*.* -> ai126242*.tss.access-internet.ne.jp
 * 126.243.*.* -> om126243*.openmobile.ne.jp
 * 126.244.*.* -> pw126244*.4.tik.panda-world.ne.jp
 * 126.245.*.* -> pw126245*.5.tik.panda-world.ne.jp
 * 126.246.*.* -> pw126246*.6.tik.panda-world.ne.jp
 * 126.247.*.* -> pw126247*.7.tik.panda-world.ne.jp
 * 126.247.*.* -> pw126247*.7.tik.panda-world.ne.jp
 * 126.248.*.* -> pw126248*.8.tss.panda-world.ne.jp
 * 126.249.*.* -> pw126249*.9.tss.panda-world.ne.jp
 * 126.250.*.* -> pw126250*.10.tss.panda-world.ne.jp
 * 126.251.*.* -> pw126251*.11.tss.panda-world.ne.jp
 * 126.252.*.* -> pw126252*.12.tss.panda-world.ne.jp
 * 126.253.*.* -> pw126253*.13.tss.panda-world.ne.jp
 * 126.254.*.* -> pw126254*.14.tss.panda-world.ne.jp
 * 126.255.*.* -> pw126255*.15.tss.panda-world.ne.jp
 */

$reghost = '/\\.panda-world\\.ne\\.jp$/';

$bands = array(
    '126.240.0.0/12', // ソフトバンクBBが 126.0.0.0/8 を持っており、
                      // iPhone以外のスマートフォン用の帯域も含む
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
