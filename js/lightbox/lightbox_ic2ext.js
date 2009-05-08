/*
 * ImageCache2::LightBox_Plus
 */

/**
 * ランク表示用コンテナを作成する
 */
LightBox.prototype._ic2_create_elements = function()
{
	var self = this;

	var rankbox = document.createElement('span');
	rankbox.id = 'lightboxIC2Rank';
	rankbox.style.display = 'none';
	rankbox.style.position = 'absolute';
	rankbox.style.zIndex = '70';

	var ngimg = document.createElement('img');
	ngimg.setAttribute('src', 'img/sn0.png');
	ngimg.setAttribute('width', '16');
	ngimg.setAttribute('height', '16');
	ngimg.setAttribute('alt', '-1');
	ngimg.onclick = self._ic2GenRanker(-1);
	rankbox.appendChild(ngimg);

	var zeroimg = document.createElement('img');
	zeroimg.setAttribute('src', 'img/sz1.png');
	zeroimg.setAttribute('width', '10');
	zeroimg.setAttribute('height', '16');
	zeroimg.setAttribute('alt', '0');
	zeroimg.onclick = self._ic2GenRanker(0);
	rankbox.appendChild(zeroimg);

	for (var i = 1; i <= 5; i++) {
		var rankimg = document.createElement('img');
		rankimg.setAttribute('src', 'img/s0.png');
		rankimg.setAttribute('width', '16');
		rankimg.setAttribute('height', '16');
		rankimg.setAttribute('alt', String(i));
		rankimg.onclick = self._ic2GenRanker(i);
		rankbox.appendChild(rankimg);
	}

	return rankbox;
};

/**
 * ランク表示をトグルする
 */
LightBox.prototype._ic2_show_rank = function(enable)
{
	var self = this;
	var rankbox = document.getElementById('lightboxIC2Rank');
	if (!rankbox) {
		return;
	}

	if (!enable || rankbox.childNodes.length == 0) {
		rankbox.style.display = 'none';
	} else {
		// now display rankbox
		rankbox.style.top = [10 + self._img.height - (16 + 2 * 2 + 1), 'px'].join('');
		rankbox.style.left = '10px';
		rankbox.style.width = [16 + 10 + 16 * 5, 'px'].join('');
		rankbox.style.height = '16px';
		rankbox.style.display = 'block';

		var rank;
		if (self._open == -1 || !self._imgs[self._open].id) {
			rank = 0;
		} else {
			rank = self._ic2_get_rank(self._imgs[self._open].id);
		}
		self._ic2_draw_rank(rank);
	}
};

/**
 * ランク描画
 */
LightBox.prototype._ic2_draw_rank = function(rank)
{
	var rankbox = document.getElementById('lightboxIC2Rank');
	var pos = rank + 1;
	if (!rankbox) {
		return;
	}

	var rankimgs = rankbox.getElementsByTagName('img');
	rankimgs[0].setAttribute('src', 'img/sn' + ((rank == -1) ? '1' : '0') + '.png');
	for (var i = 2; i < rankimgs.length; i++) {
		rankimgs[i].setAttribute('src', 'img/s' + ((i > pos) ? '0' : '1') + '.png');
	}
};

/**
 * ランク取得
 */
LightBox.prototype._ic2_get_rank = function(id)
{
	var info  = getImageInfo('id', id);
	if (!info) {
		alert('画像情報を取得できませんでした');
		return 0;
	}

	var info_array = info.split(',');
	if (info_array.length < 6) {
		alert('画像情報を取得できませんでした');
		return 0;
	}

	return parseInt(info_array[4]);
};

/**
 * ランク変更
 */
LightBox.prototype._ic2_set_rank = function(rank)
{
	var self = this;
	if (self._open == -1 || !self._imgs[self._open].id) {
		return;
	}

	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
	}
	var url = 'ic2_setrank.php?id=' + self._imgs[self._open].id.toString() + '&rank=' + rank.toString();
	var res = getResponseTextHttp(objHTTP, url, 'nc');
	if (res == '1') {
		self._ic2_draw_rank(rank);
		return true;
	}
	alert("Error: 画像のランクを変更できませんでした。") ;
	return false;
};

/**
 * ランク変更をコールする関数を生成する
 */
LightBox.prototype._ic2GenRanker = function(rank)
{
	var self = this;
	return (function(){
		self._ic2_set_rank(rank);
		return false;
	});
};

/**
 * カーソルキー, ESDX, HJKL で上下左右の画像に切り替える
 */
LightBox.prototype._keydown = function(evt, num, len)
{
	var self = this;
	var show = true;
	var forward = true;
	var vertical = false;

	if (typeof ic2cols !== 'number' || ic2cols < 1 || len == 0) {
		return true;
	}
	if (evt.altKey || evt.ctrlKey || evt.metaKey || evt.shiftKey) {
		return true;
	}

	switch (evt.keyCode) {
		// 左
		case 37: // LEFT
		case 72: // 'H'
		case 83: // 'S'
			forward = false;
			vertical = false;
			break;

		// 上
		case 38: // UP
		case 75: // 'K'
		case 69: // 'E'
			forward = false;
			vertical = true;
			break;

		// 右
		case 39: // RIGHT
		case 76: // 'L'
		case 68: // 'D'
			forward = true;
			vertical = false;
			break;

		// 下
		case 40: // DOWN
		case 74: // 'J'
		case 88: // 'X'
			forward = true;
			vertical = true;
			break;

		// Lightboxを閉じる
		case 27: // ESC
			self._close(null);
			show = false;
			break;

		// 何もしない
		default:
			show = false;
	}

	// 別の画像を表示
	if (show) {
		var last = len - 1;
		var direction = 0;
		if (vertical) {
			var x, y, z, arr, pos, rows;
			arr = [];
			rows = Math.ceil(len / ic2cols);
			for (x = 0; x < ic2cols; x++) {
				for (y = 0; y < rows; y++) {
					z = x + y * ic2cols;
					if (z < len) {
						if (z == num) {
							pos = arr.length;
						}
						arr.push(z);
					}
				}
			}
			if (forward) {
				direction = (pos == last) ? arr[0] : arr[pos+1];
			} else {
				direction = (pos == 0) ? arr[last] : arr[pos-1];
			}
			direction -= num;
		} else {
			if (forward) {
				direction = (num == last) ? -last : 1;
			} else {
				direction = (num == 0) ? last : -1;
			}
		}
		if (direction) {
			self._show_next(direction);
		}
	}

	return Event.stop(evt);
};

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
