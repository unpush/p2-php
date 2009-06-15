<?php
/**
 * rep2 - ƒXƒ^ƒCƒ‹Ý’è
 * for editpref.php
 */

echo <<<EOSTYLE

p#pan_menu {
	border-bottom: solid 1px #ccc;
}

table#editpref td {
	text-align: left;
	vertical-align: top;
}

fieldset {
	padding: 6px;
	border: solid 1px #ccc;
}

fieldset legend {
	color: {$STYLE['textcolor']};
}

fieldset table {
	margin-left: auto;
	margin-right: auto;
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
