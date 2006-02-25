/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */
/*
	ImageCache2::View-DB-Table
*/

function vt_checkAll(mode)
{
	var cboxes = document.getElementsByName('target[]');
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
