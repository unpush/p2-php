<?php
// {{{ IC2_Matrix

/**
 * ImageCache2 - s—ñŠÇ—ƒNƒ‰ƒX
 */
class IC2_Matrix
{
    // {{{ properties

    private $_max;
    private $_cols;
    private $_rows;

    // }}}
    // {{{ constructor

    public function __construct($cols, $rows, $cells = null)
    {
        if ($cells) {
            $this->_max = $cells;
        } else {
            $this->_max = $cols * $rows;
        }
        $this->_cols = $cols;
        $this->_rows = $rows;
    }

    // }}}
    // {{{ methods

    public function isFirstColumn($i)
    {
        return ($i % $this->_cols == 0);
    }

    public function isLastColumn($i)
    {
        return ($i % $this->_cols == $this->_cols - 1 || $i + 1 == $this->_max);
    }

    public function isFirstRow($i)
    {
        return ($i < $this->_cols);
    }

    public function isLastRow($i)
    {
        return ($this->_max - ($i + 1) < $this->_cols);
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
