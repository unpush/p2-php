/* p2 - メニューカテゴリの開閉のためのJavaScript */

if(document.getElementById){
	document.writeln('<style type="text/css" media="all">');
	document.writeln('<!--');
	document.writeln('.itas_hide{display:none;}');
	document.writeln('-->');
	document.writeln('</style>');
}

function showHide(id){
	var disp = document.getElementById(id).style.display;

	if(disp == "block"){
		document.getElementById(id).style.display = "none";
	}else{
		document.getElementById(id).style.display = "block";
	}
	return false;
}
