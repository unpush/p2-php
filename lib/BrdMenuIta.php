<?php

// {{{ BrdMenuIta

/**
* ボードメニュー板クラス
*/
class BrdMenuIta
{
    // {{{ properties

    public $host;
    public $bbs;
    public $itaj;    // 板名
    public $itaj_en;    // 板名をエンコードしたもの
    public $itaj_ht;    // HTMLで出力する板名（フィルタリングしたもの）

    // }}}
    // {{{ setItaj()

    public function setItaj($itaj)
    {
        $this->itaj = $itaj;
        $this->itaj_en = UrlSafeBase64::encode($this->itaj);
        $this->itaj_ht = htmlspecialchars($this->itaj, ENT_QUOTES);
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
