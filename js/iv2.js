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
