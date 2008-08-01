/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
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
	document.getElementById('fi_memo').value = memo;
}

function fiSetRank(rank)
{
	var images = document.getElementById('fi_stars').getElementsByTagName('img');
	var pos = rank + 1;
	images[0].setAttribute('src', 'img/sn' + ((rank == -1) ? '1' : '0') + '.png');
	for (var i = 2; i < images.length; i++) {
		images[i].setAttribute('src', 'img/s' + ((i > pos) ? '0' : '1') + '.png');
	}
}

function fiUpdateRank(rank)
{
	var id = document.getElementById('fi_id').value;
	if (!id) {
		alert('画像IDが設定されていません');
		return;
	}

	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
	}
	var url = 'ic2_setrank.php?id=' + id + '&rank=' + rank.toString();
	var res = getResponseTextHttp(objHTTP, url, 'nc');
	if (res == '1') {
		fiSetRank(rank);
	}
	return false;
}

//イベントハンドラを定義
document.onkeydown = fiTrigger;
