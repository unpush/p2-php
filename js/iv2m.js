/*
 * ImageCache2::Viewer-Manager
 */

// {{{ updateDB()

function updateDB(tgtId)
{
	document.getElementById(tgtId+'_change').checked = true;
}

// }}}
// {{{ resetRow()

function resetRow(tgtId)
{
	var a, b, c, d, e, f, g, h, i, j, k, l;
	a = document.getElementById(tgtId+'_aborn');
	b = document.getElementById(tgtId+'_rank0');
	c = document.getElementById(tgtId+'_rank1');
	d = document.getElementById(tgtId+'_rank2');
	e = document.getElementById(tgtId+'_rank3');
	f = document.getElementById(tgtId+'_rank4');
	g = document.getElementById(tgtId+'_rank5');
	h = document.getElementById(tgtId+'_hidden_rank');
	i = document.getElementById(tgtId+'_memo');
	j = document.getElementById(tgtId+'_hidden_msg');
	k = document.getElementById(tgtId+'_remove');
	l = document.getElementById(tgtId+'_black');
	var rank = h.value;
	a.checked = false;
	b.checked = (rank == 0) ? true : false;
	c.checked = (rank == 1) ? true : false;
	d.checked = (rank == 2) ? true : false;
	e.checked = (rank == 3) ? true : false;
	f.checked = (rank == 4) ? true : false;
	g.checked = (rank == 5) ? true : false;
	i.value = j.value;
	k.checked = false;
	l.checked = false;
	l.disabled = true;
}

// }}}
// {{{ prePost()

function prePost()
{
	var checkboxes = document.getElementsByName('change[]');
	var i, tgtId;
	for (i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].checked == false) {
			checkboxes[i].disabled = true;
			tgtId = 'img' + checkboxes[i].value;
			document.getElementById(tgtId+'_aborn').disabled = true;
			document.getElementById(tgtId+'_rank0').disabled = true;
			document.getElementById(tgtId+'_rank1').disabled = true;
			document.getElementById(tgtId+'_rank2').disabled = true;
			document.getElementById(tgtId+'_rank3').disabled = true;
			document.getElementById(tgtId+'_rank4').disabled = true;
			document.getElementById(tgtId+'_rank5').disabled = true;
			document.getElementById(tgtId+'_hidden_rank').disabled = true;
			document.getElementById(tgtId+'_memo').disabled = true;
			document.getElementById(tgtId+'_hidden_msg').disabled = true;
			document.getElementById(tgtId+'_remove').disabled = true;
			document.getElementById(tgtId+'_black').disabled = true;
		}
	}
	return true;
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
