/**
 * ImageCache2::FitImage
 */

// {{{ FitImage オブジェクト

/*
 * コンストラクタ
 *
 * @param String id     画像のid
 * @param Number width  画像の幅
 * @param Number height 画像の高さ
 */
function FitImage(id, width, height)
{
	// {{{ properties

	this.picture = document.getElementById(id);
	this.imgX = width;
	this.imgY = height;
	this.currentMode = '_init_';

	// }}}
	// {{{ FitImage.fitTo()

	/*
	 * 画像をウインドウにフィットさせるメソッド
	 */
	this.fitTo = function(mode)
	{
		var winX, winY, cssX, cssY;

		if (document.all) { //IE用
			var _body = (document.compatMode == 'CSS1Compat') ? document.documentElement : document.body;
			winX = _body.clientWidth;
			winY = _body.clientHeight;
		} else {
			winX = window.innerWidth;
			winY = window.innerHeight;
		}

		if (this.currentMode == mode) {
			this.currentMode = 'auto';
			this.picture.style.width = 'auto';
			this.picture.style.height = 'auto';
		} else {
			var autofit = false;

			if (mode == 'autofit') {
				autofit = true;
				if (winX / winY > this.imgX / this.imgY) {
					mode = 'height'
					this.currentMode = (winY < this.imgY) ? 'height' : 'auto';
				} else {
					mode = 'width'
					this.currentMode = (winX < this.imgX) ? 'width' : 'auto';
				}
			} else {
				this.currentMode = mode;
			}

			if (autofit) {
				cssX = Math.min(winX, this.imgX).toString() + 'px';
				cssY = Math.min(winY, this.imgY).toString() + 'px';
			} else {
				cssX = winX.toString() + 'px';
				cssY = winY.toString() + 'px';
			}

			switch (mode) {
				/*
				case 'full':
					this.picture.style.width = cssX;
					this.picture.style.height = cssY;
					break;
				*/
				case 'width':
					this.picture.style.width = cssX;
					this.picture.style.height = 'auto';
					break;
				case 'height':
					this.picture.style.width = 'auto';
					this.picture.style.height = cssY;
					break;
				default:
			}
		}
	}

	// }}}
}

// }}}
// {{{ fiShowHide()

/*
 * ボタンの表示・非表示を切り替える
 */
function fiShowHide()
{
	var sw = document.getElementById('btn');
	if (!sw) {
		return;
	}
	if (sw.style.display == 'block') {
		sw.style.display = 'none';
	} else {
		sw.style.display = 'block';
	}
}

// }}}
// {{{ fiTrigger() (disabled)

/*
 * キー操作で他の関数を呼び出す (封印中)
 */
/*
function fiTrigger(evt)
{
	var evt = (evt) ? evt : ((window.event) ? event : null);
	if (!evt || !evt.keyCode) {
		return;
	}
	focus();
	switch (evt.keyCode) {
		case 16: // Shift
		case 73: // I
			fiShowHide(); // スイッチ表示をOn/Off
			break;
		case 65: // A
			fitimage(psize); // 元のサイズで表示
			break;
		case 70: // F
			fitimage("full"); // 画像サイズをウインドウサイズにフィット
			break;
		case 87: // W
			fitimage("width"); // 画像サイズをウインドウ幅にフィット
			break;
		case 72: // H
			fitimage("height"); // 画像サイズをウインドウ高さにフィット
			break;
		case 82: // R
			switch (psize) { // 画像サイズを順番に切り替え
				case "auto":
				case "full":
					fitimage("width");
					break;
				case "width":
					fitimage("height");
					break;
				case "height":
					fitimage("full");
					break;
				default:
					fitimage(psize);
			}
			break;
		default:
			//alert(evt.keyCode);
	}
}
*/
// }}}
// {{{ fiGetImageInfo()

/*
 * データベースから画像情報を取得する
 */
function fiGetImageInfo(type, value)
{
	var info = getImageInfo(type, value);
	if (!info) {
		alert('画像情報を取得できませんでした');
		return;
	}

	var info_array = info.split(',');

	if (info_array.length < 6) {
		alert('画像情報を取得できませんでした');
		return;
	}

	var id     = parseInt(info_array[0]);
	var width  = parseInt(info_array[1]);
	var height = parseInt(info_array[2]);
	var size   = parseInt(info_array[3]);
	var rank   = parseInt(info_array[4]);
	var memo   = info_array[5];

	for (var i = 6; i < info_array.length; i++) {
		memo += ',' + info_array[i];
	}

	fiSetRank(rank);
	document.getElementById('fi_id').value = id.toString();
	//document.getElementById('fi_memo').value = memo;
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

//イベントハンドラを設定・・・しない
//document.onkeydown = fiTrigger;

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
