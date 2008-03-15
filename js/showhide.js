/* p2 - メニューカテゴリの開閉のためのJavaScript */

var gHideClassName = 'itas_hide';

if (document.getElementById) {
	document.writeln('<style type="text/css" media="all">');
	document.writeln('<!--');
	document.writeln('.' + gHideClassName + '{ display:none; }');
	document.writeln('-->');
	document.writeln('</style>');
}

function showHide(id) {
	
	var obj = document.getElementById(id);
		
	if (!obj) {
		return false;
	}

	if (obj.style.display == 'block') {
		obj.style.display = "none";
	} else if(obj.style.display == 'none') {
		obj.style.display = "block";
	} else {
		if (obj.className != gHideClassName) {
			obj.style.display = "none";
		} else {
			obj.style.display = "block";
		}
	}
	return false;
}
