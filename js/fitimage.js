/**
 * ImageCache2::FitImage
 */

// {{{ FitImage オブジェクト

/*
 * コンストラクタ
 *
 * @param String id     画像のidまたはDOM要素
 * @param Number width  画像の幅
 * @param Number height 画像の高さ
 */
function FitImage(id, width, height)
{
	this.picture = (typeof id == 'string') ? document.getElementById(id) : id;
	this.imgX = width;
	this.imgY = height;
	this.ratio = width / height;
	this.currentMode = 'init';
	this.defaultMode = (getWindowWidth() > width && getWindowHeight() > height) ? 'expand' : 'contract';
}

// }}}
// {{{ FitImage.fitTo()

/*
 * 画像をウインドウにフィットさせる
 *
 * @param String mode
 * @return void
 */
FitImage.prototype.fitTo = function(mode)
{
	if (this.currentMode == mode || (this.currentMode == 'init' && this.defaultMode == 'expand')) {
		// 元の大きさに戻す
		this.currentMode = 'auto';
		this.picture.style.width = 'auto';
		this.picture.style.height = 'auto';
	} else {
		var winX, winY, cssX, cssY;

		winX = getWindowWidth();
		winY = getWindowHeight();

		// ウインドウに合わせて拡大・縮小判定
		switch (mode) {
		  case 'contract':
			if (winX / winY > this.ratio) {
				mode = 'height'
				this.currentMode = (winY < this.imgY) ? 'height' : 'auto';
			} else {
				mode = 'width'
				this.currentMode = (winX < this.imgX) ? 'width' : 'auto';
			}
			cssX = Math.min(winX, this.imgX).toString() + 'px';
			cssY = Math.min(winY, this.imgY).toString() + 'px';
			break;

		  case 'expand':
			if (winX / winY > this.ratio) {
				mode = 'height'
				this.currentMode = (winY > this.imgY) ? 'height' : 'auto';
			} else {
				mode = 'width'
				this.currentMode = (winX > this.imgX) ? 'width' : 'auto';
			}
			cssX = Math.max(winX, this.imgX).toString() + 'px';
			cssY = Math.max(winY, this.imgY).toString() + 'px';
			break;

		  default:
			this.currentMode = mode;
			cssX = winX.toString() + 'px';
			cssY = winY.toString() + 'px';
		}

		// 実際にリサイズ
		switch (mode) {
		  case 'full':
			this.picture.style.width = cssX;
			this.picture.style.height = cssY;
			break;

		  case 'width':
			this.picture.style.width = cssX;
			this.picture.style.height = 'auto';
			break;

		  case 'height':
			this.picture.style.width = 'auto';
			this.picture.style.height = cssY;
			break;

		  default:
			break;
		}
	}
}

// }}}
// {{{ fiShowHide()

/*
 * ボタンの表示・非表示を切り替える
 */
function fiShowHide(display)
{
	var sw = document.getElementById('btn');
	if (!sw) {
		return;
	}
	if (typeof display == 'undefined') {
		if (sw.style.display == 'block') {
			sw.style.display = 'none';
		} else {
			sw.style.display = 'block';
		}
	} else {
		if (display) {
			sw.style.display = 'block';
		} else {
			sw.style.display = 'none';
		}
	}
}

// }}}
// {{{ fiGetImageInfo()

/*
 * データベースから画像情報を取得する
 */
function fiGetImageInfo(type, value)
{
	var info = ic2_getinfo(type, value);
	if (!info) {
		alert('画像情報を取得できませんでした');
		return;
	}

	fiSetRank(info.rank);
	document.getElementById('fi_id').value = info.id.toString();
	//document.getElementById('fi_memo').value = info.memo;
}

// }}}
// {{{ fiSetRank()

/*
 * ランク表示を更新する
 *
 * @param Number rank
 * @return void
 */
function fiSetRank(rank)
{
	var images = document.getElementById('fi_stars').getElementsByTagName('img');
	var pos = rank + 1;
	images[0].setAttribute('src', 'img/sn' + ((rank == -1) ? '1' : '0') + '.png');
	for (var i = 2; i < images.length; i++) {
		images[i].setAttribute('src', 'img/s' + ((i > pos) ? '0' : '1') + '.png');
	}
}

// }}}
// {{{ fiUpdateRank()

/*
 * データベースに記録されているランクを更新する
 *
 * @param Number rank
 * @return Boolean  always returns false.
 */
function fiUpdateRank(rank)
{
	var id = document.getElementById('fi_id').value;
	if (!id) {
		alert('画像IDが設定されていません');
		return false;
	}

	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		alert('Error: XMLHTTP 通信オブジェクトの作成に失敗しました。') ;
		return false;
	}
	var url = 'ic2_setrank.php?id=' + id + '&rank=' + rank.toString();
	var res = getResponseTextHttp(objHTTP, url, 'nc');
	if (res == '1') {
		fiSetRank(rank);
	}
	return false;
}

// }}}
// {{{ fiOnKeyDown()

/*
 * キー操作でランクを変更
 *
 * @param Event evt
 * @return Boolean
 */
function fiOnKeyDown(evt)
{
	var evt = (evt) ? evt : ((window.event) ? window.event : null);
	var rank = null;
	if (evt === null || typeof evt.keyCode == 'undefined') {
		return true;
	}
	if (evt.altKey || evt.ctrlKey || evt.metaKey || evt.shiftKey) {
		return true;
	}
	window.focus();

	switch (evt.keyCode) {
		// ランクを変更
		case 48: // '0'
		case 49: // '1'
		case 50: // '2'
		case 51: // '3'
		case 52: // '4'
		case 53: // '5'
			rank = evt.keyCode - 48;
			break;

		// ランクを変更 (テンキー)
		case  96: // '0'
		case  97: // '1'
		case  98: // '2'
		case  99: // '3'
		case 100: // '4'
		case 101: // '5'
			rank = evt.keyCode - 96;
			break;

		// あぼーん
		case   8: // BS
		case 127: // DEL
			rank = -1;
			break;
	}

	if (rank !== null) {
		fiUpdateRank(rank);
		fiShowHide(true);
	}

	if (typeof evt.stopPropagation != 'undefined') {
		evt.stopPropagation();
	} else {
		evt.cancelBubble = true;
	}
	if (typeof evt.preventDefault != 'undefined') {
		evt.preventDefault();
	} else {
		evt.returnValue = false;
	}

	return false;
};

// キー押下イベントに登録
if (typeof document.addEventListener != 'undefined') {
	document.addEventListener('keydown', fiOnKeyDown, false);
} else if (typeof document.attachEvent != 'undefined') {
	document.attachEvent('onkeydown', fiOnKeyDown);
} else {
	document.onkeydown = fiOnKeyDown;
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
