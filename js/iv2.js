/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */
/*
	ImageCache2::Viewer
*/

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
		setRank(itemId, rank);
	}
}

function rankUp(itemId)
{
	itemId = itemId.toString();
	var rank = getRank(itemId);
	if (rank < 5) {
		rank++;
		setRank(itemId, rank);
	}
}

function getRank(itemId)
{
	return parseInt(document.getElementById('rank'+itemId).innerText, 10);
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
		document.getElementById('rank'+itemId).innerText = rank.toString();
	}
}
