/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */
/*
	ImageCache2::Viewer
*/

var last_checked_box = null, last_unchecked_box = null;

function showToolbarExtra()
{
	var ext = document.getElementById('toolbarExtra');
	var swa = document.getElementById('toolbarSwitchA');
	var swb = document.getElementById('toolbarSwitchB');
	ext.style.visibility = 'visible';
	swa.style.display = 'none';
	swb.style.display = 'inline';
	return false;
}

function hideToolbarExtra()
{
	var ext = document.getElementById('toolbarExtra');
	var swa = document.getElementById('toolbarSwitchA');
	var swb = document.getElementById('toolbarSwitchB');
	ext.style.visibility = 'hidden';
	swa.style.display = 'inline';
	swb.style.display = 'none';
	return false;
}

function iv2_checkAll(mode)
{
	var cboxes = document.getElementsByName('change[]');
	for (var i = 0; i < cboxes.length; i++) {
		switch (mode) {
			case 'on':
				cboxes[i].checked = true;
				break;
			case 'off':
				cboxes[i].checked = false;
				break;
			case 'reverse':
				cboxes[i].checked = !cboxes[i].checked;
				break;
		}
	}
}

function iv2_checked(cbox, evt)
{
	var evt = (evt) ? evt : ((window.event) ? window.event : null);
	var chk = cbox.checked;

	if (evt && evt.shiftKey) {
		var tgt = null;

		if (last_checked_box) {
			tgt = last_checked_box;
			chk = true;
		} else if (last_unchecked_box) {
			tgt = last_unchecked_box;
			chk = false;
		}

		if (tgt) {
			var cboxes = document.getElementsByName('change[]');
			var i = 0, j = -1, k = -1, l = cboxes.length;

			while (i < l) {
				if (cboxes[i] == cbox) {
					j = i;
					if (k != -1) break;
				} else if (cboxes[i] == tgt) {
					k = i;
					if (j != -1) break;
				}
				i++;
			}

			if (i < l) {
				if (j > k) {
					while (j >= k) cboxes[j--].checked = chk;
				} else {
					while (j <= k) cboxes[j++].checked = chk;
				}
			}
		}
	}

	if (chk) {
		last_checked_box = cbox;
		last_unchecked_box = null;
	} else {
		last_checked_box = null;
		last_unchecked_box = cbox;
	}

	return true;
}

function pageJump(page)
{
	location.href = document.getElementById('current_page').value.replace(/page=[0-9]*/, 'page='+page);
}

function rankDown(itemId)
{
	itemId = itemId.toString();
	var rank = getRank(itemId);
	if (rank > -1) {
		rank--;
		return setRank(itemId, rank);
	}
	return false;
}

function rankUp(itemId)
{
	itemId = itemId.toString();
	var rank = getRank(itemId);
	if (rank < 5) {
		rank++;
		return setRank(itemId, rank);
	}
	return false;
}

function getRank(itemId)
{
	return parseInt(document.getElementById('rank'+itemId).innerHTML, 10);
}

function setRank(itemId, rank)
{
	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
	}
	var url = 'ic2_setrank.php?id=' + itemId + '&rank=' + rank.toString();
	var res = getResponseTextHttp(objHTTP, url, 'nc');
	if (res == '1') {
		document.getElementById('rank'+itemId).innerHTML = rank.toString();
		return true;
	}
	return false;
}

function getImageInfo(type, value)
{
	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
	}
	var url = 'ic2_getinfo.php?';
	if (type == 'id') {
		url += 'id=' + parseInt(value).toString();
	} else {
		url += encodeURIComponent(type) + '=' + encodeURIComponent(value);
	}
	var res = getResponseTextHttp(objHTTP, url, 'nc');
	if (res == '-1') {
		return false;
	}
	return res;
}
