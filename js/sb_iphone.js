/*
 * rep2expack - スレ一覧用関数 for iPhone
 */

// {{{ toggle_sb_show_info()

/*
 * リンクをクリックしたとき、スレッドを開く代わりに
 * 新しいタブでスレッド情報を表示するようにする
 *
 * @param Boolean onoff
 * @return void
 */
function toggle_sb_show_info(onoff)
{
	var anchors = document.evaluate('.//ul[@class="subject"]/li/a[@href]',
	                                document.body,
	                                null,
	                                XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
	                                null
	                                );

	if (onoff) {
		var make_callback = (function(url){
			return (function(){ window.open(url); return false; });
		});

		for (var i = 0; i < anchors.snapshotLength; i++) {
			var node = anchors.snapshotItem(i);
			var info = node.parentNode.getAttribute('title').split(',');
			var url = 'info.php?host=' + info[3] + '&bbs=' + info[2]
			        + '&key=' + info[1] + '&ttitle_en=' + info[0];
			node.onclick = make_callback(url);
		}
	} else {
		for (var i = 0; i < anchors.snapshotLength; i++) {
			anchors.snapshotItem(i).onclick = null;
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
/* vim: set syn=javascript fenc=cp932 ai noet ts=4 sw=4 sts=4 fdm=marker: */
