/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

// PHPの同名関数を模したJavaScript関数

function nl2br(str) {
	return str.replace(/\r\n|\r|\n/g, "<br />");
}

function htmlspecialchars(str) {
	return str.replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
