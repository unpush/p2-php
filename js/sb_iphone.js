/*
 * rep2expack - スレ一覧用JavaScript
 * menu_i.js のサブセット
 */

(function(){
	// {{{ createPop()

	/**
	 * リンクスライド時にリンクの下に表示される要素を生成する
	 *
	 * @param {String} type
	 * @return {Element}
	 */
	var createPop = function(type) {
		var pop, div, button, table;

		pop = document.createElement('li');
		pop.className = 'info-pop';

		// ボタン類
		div = pop.appendChild(document.createElement('div'));
		div.className = 'info-pop-buttons';

		button = div.appendChild(document.createElement('input'));
		button.setAttribute('type', 'button');
		button.value = type + 'を開く';
		button.onclick = window.iutil.sliding.openUri;

		div.appendChild(document.createTextNode('\u3000'));

		button = div.appendChild(document.createElement('input'));
		button.setAttribute('type', 'button');
		button.value = 'タブで開く';
		button.onclick = window.iutil.sliding.openUriInTab;

		div.appendChild(document.createTextNode('\u3000'));

		button = div.appendChild(document.createElement('input'));
		button.setAttribute('type', 'button');
		button.value = '閉じる';
		button.onclick = window.iutil.sliding.hideDialog;

		// お気に入りの登録・解除
		table = pop.appendChild(document.createElement('table'));
		table.className = 'info-pop-fav';
		table.setAttribute('cellspacing', '0');
		//table.appendChild(document.createElement('caption'))
		//     .appendChild(document.createTextNode('お気に' + type));
		table.appendChild(document.createElement('tbody'));

		return pop;
	};

	// }}}
	// {{{ createFavRow()

	/**
	 * お気に板・お気にスレの登録・解除スイッチを生成する
	 *
	 * @param {String} label
	 * @param {String} klass
	 * @param {Boolean} toggled
	 * @param {function} onclick
	 * @return {Element}
	 */
	var createFavRow = function(label, klass, toggled, onclick) {
		var tr, div, span;

		/*
		<tr>
			<td>{label}</td>
			<td>
				<div class="toggle {klass}" onclick="{onclick}" toggled="{toggled}">
					<span class="thumb"></span>
					<span class="toggleOn">&#x2713;</span>
					<span class="toggleOff">-</span>
				</div>
			</td>
		</tr>
		*/

		tr = document.createElement('tr');
		tr.appendChild(document.createElement('td'))
		  .appendChild(document.createTextNode(label));

		div = tr.appendChild(document.createElement('td'))
		        .appendChild(document.createElement('div'));
		div.className = 'toggle ' + klass;
		div.setAttribute('toggled', toggled ? 'true' : 'false');
		div.addEventListener('click', onclick, false);

		span = div.appendChild(document.createElement('span'));
		span.className = 'thumb';

		span = div.appendChild(document.createElement('span'));
		span.className = 'toggleOn';
		span.appendChild(document.createTextNode('\u2713')); // U+2713 CHECK MARK

		span = div.appendChild(document.createElement('span'));
		span.className = 'toggleOff';
		span.appendChild(document.createTextNode('-'));

		return tr;
	};

	// }}}
	// {{{ updateFavRow()

	/**
	 * お気に板・お気にスレの登録・解除スイッチを更新する
	 *
	 * @param {Element} tr
	 * @param {String} label
	 * @param {Boolean} toggled
	 * @return void
	 */
	var updateFavRow = function(tr, label, toggled) {
		tr.childNodes[0].firstChild.nodeValue = label;
		tr.childNodes[1].firstChild.setAttribute('toggled', toggled ? 'true' : 'false');
	};

	// }}}
	// {{{ setFav

	/**
	 * お気にスレの登録・解除を行う
	 *
	 * @param {Element} div
	 * @return void
	 */
	var setFav = function(div) {
		var toggled, req, uri, setnum;

		toggled = div.getAttribute('toggled') === 'true';
		setnum = parseInt(div.className.substring(div.className.indexOf('fav') + 3));
		uri = 'httpcmd.php?cmd=setfav&' + window.iutil.sliding.query + '&setnum=' + setnum;
		// menu_i.jsと逆
		if (!toggled) {
			uri += '&setfav=1';
		} else {
			uri += '&setfav=0';
		}

		req = new XMLHttpRequest();
		req.open('GET', uri, true);
		req.onreadystatechange = generateOnToggle(req, div, toggled);
		req.send(null);
	};

	// }}}
	// {{{ setPal

	/**
	 * 殿堂入りの登録・解除を行う
	 *
	 * @param {Element} div
	 * @return void
	 */
	var setPal = function(div) {
		var toggled, req, uri;

		toggled = div.getAttribute('toggled') === 'true';
		uri = 'httpcmd.php?cmd=setpal&' + window.iutil.sliding.query;
		// menu_i.jsと逆
		if (!toggled) {
			uri += '&setpal=1';
		} else {
			uri += '&setpal=0';
		}

		req = new XMLHttpRequest();
		req.open('GET', uri, true);
		req.onreadystatechange = generateOnToggle(req, div, toggled);
		req.send(null);
	};

	// }}}
	// {{{ onFavToggled

	/**
	 * お気にスレスイッチがトグルされたときに実行されるコールバック関数
	 *
	 * @param {Event} event
	 * @return void
	 */
	var onFavToggled = function(event) {
		setFav(this);
	};

	// }}}
	// {{{ onPalToggled

	/**
	 * 殿堂入りスイッチがトグルされたときに実行されるコールバック関数
	 *
	 * @param {Event} event
	 * @return void
	 */
	var onPalToggled = function(event) {
		setPal(this);
	};

	// }}}
	// {{{ showFavList

	/**
	 * お気にスレの登録状況リストを更新・表示する
	 *
	 * @param {Element} table
	 * @param {Array} favs
	 * @param {Boolean} palace
	 * @return void
	 */
	var showFavList = function(table, favs, palace) {
		var bodies, tbody, tr, i, l;

		bodies = table.getElementsByTagName('tbody');
		tbody = bodies[0];
		l = favs.length;

		// 初期化時または取得したお気にスレのセット数と既存の行数が異なる場合
		if (tbody.childNodes.length != l) {
			// 余分な行を削除
			while (tbody.childNodes.length > l) {
				tr = tbody.childNodes[tbody.childNodes.length - 1];
				tr.childNodes[1].firstChild.removeEventListener('click', onFavToggled, false);
				tbody.removeChild(tr);
			}

			// 既存の行を更新
			l = tbody.childNodes.length;
			for (i = 0; i < l; i++) {
				updateFavRow(tbody.childNodes[i], favs[i].title, favs[i].set);
			}

			// 行を追加
			l = favs.length;
			for (; i < l; i++) {
				tr = createFavRow(favs[i].title, 'fav' + i, favs[i].set, onFavToggled);
				tbody.appendChild(tr);
			}
		} else {
			// 既存の行を更新
			for (i = 0; i < l; i++) {
				updateFavRow(tbody.childNodes[i], favs[i].title, favs[i].set);
			}
		}

		// 殿堂入り
		if (table.getElementsByTagName('tbody').length === 1) {
			tr = createFavRow('殿堂入り', 'palace', palace, onPalToggled);
			table.appendChild(document.createElement('tbody')).appendChild(tr);
		} else {
			updateFavRow(bodies[1].firstChild, '殿堂入り', palace);
		}

		table.style.display = 'table';
	};

	// }}}
	// {{{ generateOnThreadInfoGet()

	/**
	 * 非同期リクエストでスレッド情報を取得した際に
	 * 実行されるコールバック関数を生成する
	 *
	 * @param {XMLHttpRequest} req
	 * @param {Function} parse
	 * @param {Element} pop
	 * @param {Element} table
	 * @return void
	 */
	var generateOnThreadInfoGet = function(req, parse, pop, table) {
		return function() {
			var data, err;

			if (req.readyState == 4) {
				if (req.status == 200) {
					try {
						data = parse(req.responseText);
						showFavList(table, data.favs, data.palace);
					} catch (err) {
						window.alert(err.toString());
					}
				} else {
					window.alert('HTTP Error: ' + req.status);
				}
			}
		};
	};

	// }}}
	// {{{ generateOnToggle()

	/**
	 * 非同期リクエストでお気にスレ・殿堂入りの操作をした際に
	 * 実行されるコールバック関数を生成する
	 *
	 * まずiui側でtoggle属性が設定されるmenu_i.jsのものとは異なり、
	 * 更新成功時にここで初めてtoggle属性を切り替える。
	 *
	 * @param {XMLHttpRequest} req
	 * @param {Element} toggle
	 * @param {Boolean} toggled
	 * @return void
	 */
	var generateOnToggle = function(req, toggle, toggled) {
		return function() {
			if (req.readyState == 4) {
				if (req.status == 200) {
					if (req.responseText === '1') {
						// toggle属性をセットする
						toggle.setAttribute('toggled', (toggled) ? 'false' : 'true');
					} else if (req.responseText === '0') {
						window.alert('更新に失敗しました');
					} else {
						window.alert('予期しないレスポンス');
					}
				} else {
					window.alert('HTTP Error: ' + req.status);
				}
			}
		};
	};

	// }}}
	// {{{ setup()

	/**
	 * スレッドリンクスライド時のアクションを設定する
	 *
	 * @param {Object} iutil
	 * @param {Object} JSON
	 * @return void
	 */
	var setup = function(iutil, JSON) {
		var sliding, i, l, s;

		sliding = iutil.sliding;

		// 現在、iphone.jsで自動iutil.modifyInternalLink()を無効にしているので
		// ここでiutil.sliding.bind()する。
		s = document.evaluate('.//ul[@class = "subject"]/li/a[starts-with(@href, "read.php?")]',
							  document.body,
							  null,
							  XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
							  null);
		l = s.snapshotLength;
		for (i = 0; i < l; i++) {
			sliding.bind(s.snapshotItem(i));
		}

		delete i, l, s;

		// {{{ override sliding.callbacks.read()

		/**
		 * スレッドリンクスライド時に実行される関数
		 *
		 * @param {Element} anchor
		 * @param {Event} event
		 * @return void
		 */
		sliding.callbacks.read = function(anchor, event) {
			var pop, div, ul, li, m, req, table;

			// 要素を取得
			if (typeof sliding.dialogs.menuRead === 'undefined') {
				pop = createPop('スレ');

				div = pop.appendChild(document.createElement('div'));
				div.className = 'info-pop-buttons';

				button = div.appendChild(document.createElement('input'));
				button.setAttribute('type', 'button');
				button.value = 'スレッド情報';
				button.onclick = function() {
					window.open('info.php?' + sliding.query, null);
				};
			} else {
				pop = sliding.dialogs.menuRead;
				pop = pop.parentNode.removeChild(pop);
			}
			sliding.dialogs.menuRead = pop;
			sliding.setActiveDialog(pop);

			// お気にスレの登録状況を取得
			table = pop.childNodes[1];
			table.style.display = 'none';
			req = new XMLHttpRequest();
			req.open('GET', 'info_js.php?' + sliding.query, true);
			req.onreadystatechange = generateOnThreadInfoGet(req, JSON.parse, pop, table);
			req.send(null);

			// 要素をスライドされたリンクの後に挿入
			li = anchor.parentNode;
			ul = li.parentNode;
			if (li.nextSibling) {
				ul.insertBefore(pop, li.nextSibling);
			} else {
				ul.appendChild(pop);
			}
			pop.style.display = 'list-item';
		};

		// }}}
	};

	// }}}
	// {{{ on DOMContentLoaded

	window.addEventListener('DOMContentLoaded', function(event) {
		// iutil/JSONが利用可能になるまで待つ
		if (typeof window.iutil === 'undefined' ||
			typeof window.JSON  === 'undefined')
		{
			window.setTimeout(arguments.callee, 50);
		} else {
			setup(window.iutil, window.JSON);
			window.removeEventListener('DOMContentLoaded', arguments.callee, false);
		}
	}, false);

	// }}}
})();

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
