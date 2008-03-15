<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

/* ImageCache2 - 行列管理クラス */

class MatrixManager
{
    var $cols;
    var $rows;
    var $cells;
    function MatrixManager($cols, $rows, $cells = NULL)
    {
        if ($cells) {
            $this->max = $cells;
        } else {
            $this->max = $cols * $rows;
        }
        $this->cols = $cols;
        $this->rows = $rows;
    }
    function isFirstColumn($i)
    {
        return ($i % $this->cols == 0);
    }
    function isLastColumn($i)
    {
        return ($i % $this->cols == $this->cols - 1 || $i + 1 == $this->max);
    }
    function isFirstRow($i)
    {
        return ($i < $this->cols);
    }
    function isLastRow($i)
    {
        return ($this->max - ($i + 1) < $this->cols);
    }
}

?>
