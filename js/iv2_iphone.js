/*
 * IC2::Viewer - DOMを操作してiPhoneに最適化する
 */

// {{{ GLOBALS

var _IV2_IPHONE_JS_OLD_ONLOAD = window.onload;
var _IV2_IPHONE_JS_OLD_ONORIENTATIONCHANGE;

// }}}
// {{{ window.onload()

/*
 * iPhone用に要素を調整する
 *
 * @return void
 */
window.onload = (function(){
	if (_IV2_IPHONE_JS_OLD_ONLOAD) {
		_IV2_IPHONE_JS_OLD_ONLOAD();
	}

	// サムネイルをタップしたとき、新しいタブで開くようにする
	var anchors = document.evaluate('.//td[@class="iv2-image-thumb"]/a[@href]',
	                                document.body,
	                                null,
	                                XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
	                                null
	                                );

	for (var i = 0; i < anchors.snapshotLength; i++) {
		anchors.snapshotItem(i).setAttribute('target', '_blank');
	}

	// テーブルの大きさを調整
	resize_image_table();

	// 古い回転時のイベントハンドラを保存
	_IV2_IPHONE_JS_OLD_ONORIENTATIONCHANGE = document.body.onorientationchange;

	// 回転時のイベントハンドラを設定
	document.body.onorientationchange = (function(){
		if (_IV2_IPHONE_JS_OLD_ONORIENTATIONCHANGE) {
			_IV2_IPHONE_JS_OLD_ONORIENTATIONCHANGE();
		}
		resize_image_table();
	});
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
	if (!window.orientation || window.orientation % 180 == 0) {
		document.styleSheets[document.styleSheets.length - 2].disabled = false;
		document.styleSheets[document.styleSheets.length - 1].disabled = true;
	} else {
		document.styleSheets[document.styleSheets.length - 2].disabled = true;
		document.styleSheets[document.styleSheets.length - 1].disabled = false;
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
