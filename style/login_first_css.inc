<?php
// p2 ースタイル設定
// for printLoginFirst()

echo <<<EOSTYLE

body,td{
	font-size:{$STYLE['fontsize']};
}

body{
	background-image:url('img/rep2_loginbg.gif');
	background-repeat: no-repeat;
	background-color: #ccc;
	padding:24px;
	color:#333;
}

p.infomsg{
	color:#369;
}

EOSTYLE;


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
