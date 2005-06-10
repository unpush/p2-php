/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

//画像サイズフラグを初期化
var psize = "auto";

//画像をウインドウにフィットさせる関数
function fitimage(mode)
{
	var picture = document.getElementById("picture");
	if (!picture) {
		return;
	}
	if (psize == mode) {
		psize = "auto";
		picture.style.width = "auto";
		picture.style.height = "auto";
	} else {
		psize = mode;
		switch (mode) {
			case "full":
				picture.style.width = "100%";
				picture.style.height = "100%";
				break;
			case "width":
				picture.style.width = "100%";
				picture.style.height = "auto";
				break;
			case "height":
				picture.style.width = "auto";
				picture.style.height = "100%";
				break;
			default:
		}
	}
}


//読み込んだときに自動で画像をウインドウにフィットさせる関数
function autofitimage(mode, imgX, imgY)
{
	if (document.all) { //IE用
		var body = (document.compatMode == 'CSS1Compat') ? document.documentElement : document.body;
		var winX = body.clientWidth;
		var winY = body.clientHeight;
	} else if (document.getElementById) {
		var winX = window.innerWidth
		var winY = window.innerHeight;
	} else {
		return;
	}
	if (!imgX || !imgY) {
		return;
	}
	if (mode == "auto") {
		if (winX / winY > imgX / imgY) {
			mode = "height"
		} else {
			mode = "width"
		}
	}
	if ((mode == "width" && imgX <= winX) || (mode == "height" && imgY <= winY)) {
		return;
	}
	fitimage(mode);
}

//ボタンの表示・非表示を切り替える関数
function fiShowHide()
{
	var sw = document.getElementById("btn");
	if (!sw) {
		return;
	}
	if (sw.style.display == "block") {
		sw.style.display = "none";
	} else {
		sw.style.display = "block";
	}
}

//キー操作で他の関数を呼び出す関数
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

//イベントハンドラを定義
document.onkeydown = fiTrigger;
