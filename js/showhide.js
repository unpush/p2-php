/* p2 - メニューカテゴリの開閉のためのJavaScript */

var gHideClass = 'itas_hide';

if (document.getElementById) {
	document.writeln('<style type="text/css" media="all">');
	document.writeln('<!--');
	document.writeln('.' + gHideClass + '{ display:none; }');
	document.writeln('-->');
	document.writeln('</style>');
}

function showHide(id) {
	var obj = document.getElementById(id);

	if (obj.style.display == 'block') {
		obj.style.display = "none";
	} else if(obj.style.display == 'none') {
		obj.style.display = "block";
	} else {
		if (obj.className == gHideClass) {
			obj.style.display = "block";
		} else {
			obj.style.display = "none";
		}
	}
	return false;
}
