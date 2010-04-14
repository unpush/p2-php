<?php

// {{{ BrdMenuCate

/**
* ボードメニューカテゴリークラス
*/
class BrdMenuCate
{
    // {{{ properties

    public $name;          // カテゴリーの名前
    public $menuitas;      // クラスBrdMenuItaのオブジェクトを格納する配列
    public $num;           // 格納されたBrdMenuItaオブジェクトの数
    public $is_open;       // 開閉状態(bool)
    public $ita_match_num; // 検索にヒットした板の数

    // }}}
    // {{{ constructor

    /**
    * コンストラクタ
    */
    public function __construct($name)
    {
        $this->num = 0;
        $this->menuitas = array();
        $this->ita_match_num = 0;

        $this->name = $name;
    }

    // }}}
    // {{{ addBrdMenuIta()

    /**
     * 板を追加する
     */
    public function addBrdMenuIta(BrdMenuIta $aBrdMenuIta)
    {
        $this->menuitas[] = $aBrdMenuIta;
        $this->num++;
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
