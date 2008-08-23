/*
	rep2expack - DOMを操作してiPhoneに最適化する
*/

var _IPHONE_JS_OLD_ONLOAD = window.onload;

// {{{ window.onload()

/*
 * iPhone用に要素を調整する
 */
window.onload = function(){
	if (_IPHONE_JS_OLD_ONLOAD) {
		_IPHONE_JS_OLD_ONLOAD();
	}

	// accesskey属性とキー番号表示を削除
	var anchors = document.evaluate('.//a[@accesskey]', document.body, null, 7, null);
	var re = new RegExp('^[0-9#*]\\.');

	for (var i = 0; i < anchors.snapshotLength; i++) {
		var node = anchors.snapshotItem(i);
		var txt = node.firstChild;

		if (txt && txt.nodeType == 3 && re.test(txt.nodeValue)) {
			// TOPへのリンクをボタン化
			if (txt.nodeValue == '0.TOP') {
				node.className = 'button';
				if (node.parentNode.childNodes.length == 1) {
					node.parentNode.style.textAlign = 'center';
				} else if (node.parentNode == document.body) {
					var container = document.createElement('div');
					container.style.textAlign = 'center';
					document.body.insertBefore(container, node);
					document.body.removeChild(node);
					container.appendChild(node);
				}
			}

			// キー番号表示を削除
			txt.nodeValue = txt.nodeValue.replace(re, '');
		}

		// accceskey属性を削除
		node.removeAttribute('accesskey');
	}

	// 外部リンクを書き換える
	rewrite_external_link(document.body);

	// ロケーションバーを隠す
	if (!location.hash) {
		scrollTo(0, 0);
	}
};

// }}}
// {{{ rewrite_external_link()

/*
 * 外部リンクを確認してから新しいタブで開くように変更する
 *
 * @param Element contextNode
 * @return void
 */
function rewrite_external_link(contextNode)
{
	var anchors = document.evaluate('.//a[@href and starts-with(@href, "http")]',
	                                contextNode, null, 7, null);
	var re = new RegExp('^https?://(.+?@)?([^:/]+)');

	for (var i = 0; i < anchors.snapshotLength; i++) {
		var node = anchors.snapshotItem(i);
		var url = node.getAttribute('href');
		var m = re.exec(url);

		if (m && m[2] != location.host) {
			if (!node.onclick) {
				node.onclick = (function(url){
					return (function(){ return confirm('外部サイトを開きます\n' + url); });
				})(url);
			}

			if (!node.hasAttribute('target')) {
				node.setAttribute('target', '_blank');
			}
		}
	}
}

// }}}

/*
 * Local Variables:
 * mode: javascript
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
/* vim: set syn=css fenc=cp932 ai noet ts=4 sw=4 sts=4 fdm=marker: */
