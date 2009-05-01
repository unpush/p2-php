/*
 * ImageCache2::LightBox_Plus
 */

/**
 * ランク表示をトグルする
 */
LightBox.prototype._ic2_show_rank = function(enable)
{
	var self = this;
	var rankbox = document.getElementById('lightboxIC2Rank');
	if (!rankbox) return;
	if (rankbox.childNodes.length == 0 || !enable)
	{
		rankbox.style.display = 'none';
	}
	else
	{ // now display rankbox
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
	if (!rankbox) return;

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
		return;
	}

	return parseInt(info_array[4]);
};

/**
 * ランク変更
 */
LightBox.prototype._ic2_set_rank = function(rank)
{
	var self = this;
	if (self._open == -1 || !self._imgs[self._open].id) return;

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
