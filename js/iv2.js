/*
 * ImageCache2::Viewer
 */

// {{{ GLOBALS

var last_checked_box = null, last_unchecked_box = null;

// }}}
// {{{ showToolbarExtra()

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

// }}}
// {{{ hideToolbarExtra()

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

// }}}
// {{{ iv2_checkAll()

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

// }}}
// {{{ iv2_checked()

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

// }}}
// {{{ pageJump()

function pageJump(page)
{
	location.href = document.getElementById('current_page').value.replace(/page=[0-9]*/, 'page='+page);
}

// }}}
// {{{ rankDown()

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

// }}}
// {{{ rankUp()

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

// }}}
// {{{ getRank()

function getRank(itemId)
{
	var value = document.getElementById('rank'+itemId).innerHTML;
	if (value == 'あぼーん') {
		return -1;
	}
	return parseInt(value, 10);
}

// }}}
// {{{ setRank()

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
