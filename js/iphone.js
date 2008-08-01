window.onload = function(){
	var anchors = document.evaluate('//a[@accesskey]', document.body, null, 7, null);
	var re = new RegExp('^[0-9#*]\\.');
	for (var i = 0; i < anchors.snapshotLength; i++) {
		var node = anchors.snapshotItem(i);
		var txt = node.firstChild;
		if (txt && txt.nodeType == 3) {
			txt.nodeValue = txt.nodeValue.replace(re, '');
		}
		node.removeAttribute('accesskey');
	}
};
