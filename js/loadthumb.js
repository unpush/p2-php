/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

/**
 * 非表示状態のサムネイルを読み込む
 * 
 * 読み込み判定には置換対象オブジェクトの有無を利用。
 * 返り値は画像が読み込み済みか否か。
 */
function loadThumb(thumb_url, thumb_id)
{
	var tmp_thumb = document.getElementById(thumb_id);
	if (!tmp_thumb) {
		return true;
	}

	var thumb = document.createElement('img');
	// IEでCSSを適用させるにはsetAttribute()せずにclassNameプロパティを設定しないといけない
	thumb.className = 'thumbnail';
	thumb.setAttribute('src', thumb_url);
	thumb.setAttribute('hspace', 4);
	thumb.setAttribute('vspace', 4);
	thumb.setAttribute('align', 'middle');

	tmp_thumb.parentNode.replaceChild(thumb, tmp_thumb);

	// IEでは読み込み完了してからリサイズしないと変な挙動になるので
	if (navigator.userAgent.indexOf('MSIE' != -1)) {
		thumb.onload = function() {
			autoImgSize(thumb_id);
		}
	// その他
	} else {
		autoImgSize(thumb_id);
	}

	return false;
}

/**
 * 読み込みが完了したサムネイルを本来のサイズで表示する
 */
function autoImgSize(thumb_id)
{
	var thumb = document.getElementById(thumb_id);
	if (!thumb) {
		return;
	}

	thumb.style.width = 'auto';
	thumb.style.height = 'auto';
}