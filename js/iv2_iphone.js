/*
 * IC2::Viewer - DOMを操作してiPhoneに最適化する
 */

// {{{ window.onload()

window.addEventListener('load', function(evt){
	// サムネイルをタップしたとき、新しいタブで開くようにする
	/*
	var anchors = document.evaluate('.//td[@class="iv2-image-thumb"]/a[@href]',
	                                document.body,
	                                null,
	                                XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
	                                null
	                                );

	for (var i = 0; i < anchors.snapshotLength; i++) {
		anchors.snapshotItem(i).setAttribute('target', '_blank');
	}
	*/

	if (typeof window.orientation != 'undefined') {
		// テーブルの大きさを調整
		resize_image_table();

		// 回転時のイベントハンドラを追加
		document.body.addEventListener('orientationchange', function(evt){
			resize_image_table();
		});
	} else {
		// 回転をサポートしないブラウザ
		var table = document.getElementById('iv2-images');
		if (table) {
			var width = document.body.clientWidth;
			var css = document.styleSheets[document.styleSheets.length - 3];
			css.insertRule('table#iv2-images { width: ' + width.toString() + 'px; }');

			var cells = table.getElementsByTagName('td');
			if (cells && cells.length) {
				width -= (cells[0].clientWidth + 20);
				css.insertRule('div.iv2-image-title { width: ' + width.toString() + 'px; }');
			}
		}

		document.styleSheets[document.styleSheets.length - 2].disabled = true;
		document.styleSheets[document.styleSheets.length - 1].disabled = true;
	}
});

// }}}
// {{{ resize_image_table()

/*
 * 画像テーブルのサイズを調整する
 *
 * @return void
 */
function resize_image_table()
{
	if (window.orientation % 180 == 0) {
		document.styleSheets[document.styleSheets.length - 1].disabled = false;
		document.styleSheets[document.styleSheets.length - 2].disabled = true;
	} else {
		document.styleSheets[document.styleSheets.length - 2].disabled = false;
		document.styleSheets[document.styleSheets.length - 1].disabled = true;
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
