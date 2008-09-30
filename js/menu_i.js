/*
 * rep2expack - メニュー用JavaScript, iphone.jsのサブセット
 */

// {{{ check_prev()

/*
 * 前のcheckbox要素をトグルする。疑似label効果
 *
 * @param Element elem
 * @return void
 */
function check_prev(elem)
{
	elem.previousSibling.checked = !elem.previousSibling.checked;
	if (elem.previousSibling.onclick) {
		elem.previousSibling.onclick();
	}
}

// }}}
// {{{ check_next()

/*
 * 次のcheckbox要素をトグルする。疑似label効果
 *
 * @return void
 */
function check_next(elem)
{
	elem.nextSibling.checked = !elem.nextSibling.checked;
	if (elem.nextSibling.onclick) {
		elem.nextSibling.onclick();
	}
}

// }}}
// {{{ change_link_target()

/*
 * リンクターゲットを切り替える
 *
 * @param String expr
 * @param Boolean toggle
 * @param Element contextNode
 * @param String target
 * @return void
 */
function change_link_target(expr, toggle)
{
	var contextNode = (arguments.length > 2 && arguments[2]) ? arguments[2] : document.body;
	var anchors = document.evaluate(expr,
	                                contextNode,
	                                null,
	                                XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
	                                null
	                                );

	if (toggle) {
		for (var i = 0; i < anchors.snapshotLength; i++) {
			anchors.snapshotItem(i).setAttribute('target', '_blank');
		}
	} else if (arguments.length > 3) {
		for (var i = 0; i < anchors.snapshotLength; i++) {
			anchors.snapshotItem(i).setAttribute('target', arguments[3]);
		}
	} else {
		for (var i = 0; i < anchors.snapshotLength; i++) {
			anchors.snapshotItem(i).removeAttribute('target');
		}
	}
}

// }}}
// {{{ toggle_open_in_tab()

/**
 * 同一階層のリンクを「新しいタブで開く」「同じタブで開く」切り替えをする
 *
 * <ul><li><checkbox/><label/></li><li>...</li></ul>の順で要素を配置しておく
 *
 * @param Element cbox
 */
function toggle_open_in_tab(cbox)
{
	change_link_target('./li/a[@href'
	                   + ' and not(starts-with(@href, "menu_i.php"))'
	                   + ' and not(starts-with(@href, "tgrepc.php"))'
	                   + ' and not(starts-with(@href, "#"))'
	                   + ']',
	                   cbox.checked,
	                   cbox.parentNode.parentNode,
	                   '_self');

	if (cbox.checked) {
		cbox.nextSibling.style.color = '#ff3333';
	} else {
		cbox.nextSibling.style.color = '#aaaaaa';
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
