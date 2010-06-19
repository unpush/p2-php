(function() {

var createSPMmenu = function (aThread, idval) {
	var amenu = document.createElement('div');
	amenu.id = idval;
	amenu.className = 'spm';
	amenu.appendItem = function()
	{
		this.appendChild(SPM.createMenuItem.apply(this, arguments));
	}
	SPM.setOnPopUp(amenu, amenu.id, true);

	return amenu;
};

var getSPMmenu = function (aThread) {
	var menu = document.getElementById(aThread.objName + '_wikitools');
	if (menu == null) {
		menu = createSPMmenu(aThread, aThread.objName + '_wikitools');
		document.getElementById('popUpContainer').appendChild(menu);
		document.getElementById(aThread.objName + '_spm').appendItem(
				'外部ツール', null, aThread.objName + '_wikitools');
	}
	return menu;
};

var addMimizun = function (aThread) {
	var amenu = getSPMmenu(aThread);
	amenu.appendItem('みみずんID検索', function(event) {
		stophide = true;
		showHtmlPopUp('mimizun.php?bbs=' + aThread.bbs + '&key=' + aThread.key + '&host=' + aThread.host + '&resnum=' + spmResNum, event ? event : window.event, 0);
	});
};

var addHissi = function (aThread) {
	var amenu = getSPMmenu(aThread);
	amenu.appendItem('必死チェッカー', function(event) {
		stophide = true;
		showHtmlPopUp('hissi.php?bbs=' + aThread.bbs + '&key=' + aThread.key + '&host=' + aThread.host + '&resnum=' + spmResNum, event ? event : window.event, 0);
	});
};

var addStalker = function (aThread) {
	var amenu = getSPMmenu(aThread);
	amenu.appendItem('IDストーカー', function(event) {
		stophide = true;
		showHtmlPopUp('stalker.php?bbs=' + aThread.bbs + '&key=' + aThread.key + '&host=' + aThread.host + '&resnum=' + spmResNum, event ? event : window.event, 0);
	});
};

if (!this['WikiTools']) {
	WikiTools = {
		addMimizun : addMimizun,
		addHissi : addHissi,
		addStalker : addStalker
	};
}

})();
/* vim: set syn=javascript fenc=cp932 ai noet ts=2 sw=2 sts=2: */
