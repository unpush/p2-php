/*
 * rep2expack - メニュー用JavaScript
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
	// {{{ setFavita

	/**
	 * お気に板の登録・解除を行う
	 *
	 * @param {Element} div
	 * @return void
	 */
	var setFavita = function(div) {
		var toggled, req, uri, setnum;

		setnum = parseInt(div.className.substring(div.className.indexOf('favita') + 6));
		uri = 'httpcmd.php?cmd=setfavita&' + window.iutil.sliding.query + '&setnum=' + setnum;
		if (toggled) {
			uri += '&setfavita=1';
		} else {
			uri += '&setfavita=0';
		}

		toggled = div.getAttribute('toggled') === 'true';
		req = new XMLHttpRequest();
		req.open('GET', uri, true);
		req.onreadystatechange = generateOnToggle(req, div, toggled);
		req.send(null);
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

		setnum = parseInt(div.className.substring(div.className.indexOf('fav') + 3));
		uri = 'httpcmd.php?cmd=setfav&' + window.iutil.sliding.query + '&setnum=' + setnum;
		if (toggled) {
			uri += '&setfav=1';
		} else {
			uri += '&setfav=0';
		}

		toggled = div.getAttribute('toggled') === 'true';
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
		if (toggled) {
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
	// {{{ onFavitaToggled

	/**
	 * お気に板スイッチがトグルされたときに実行されるコールバック関数
	 * setFavita()を遅延呼び出しする
	 *
	 * @param {Event} event
	 * @return void
	 */
	var onFavitaToggled = function(event) {
		window.setTimeout(setFavita, 10, this);
	};

	// }}}
	// {{{ onFavToggled

	/**
	 * お気にスレスイッチがトグルされたときに実行されるコールバック関数
	 * setFav()を遅延呼び出しする
	 *
	 * @param {Event} event
	 * @return void
	 */
	var onFavToggled = function(event) {
		window.setTimeout(setFav, 10, this);
	};

	// }}}
	// {{{ onPalToggled

	/**
	 * 殿堂入りスイッチがトグルされたときに実行されるコールバック関数
	 * setPal()を遅延呼び出しする
	 *
	 * @param {Event} event
	 * @return void
	 */
	var onPalToggled = function(event) {
		window.setTimeout(setPal, 10, this);
	};

	// }}}
	// {{{ showFavitaList

	/**
	 * お気に板の登録状況リストを更新・表示する
	 *
	 * @param {Element} table
	 * @param {Array} favs
	 * @return void
	 */
	var showFavitaList = function(table, favs) {
		var tbody, tr, i, l;

		tbody = table.getElementsByTagName('tbody')[0];
		l = favs.length;

		// 初期化時または取得したお気に板のセット数と既存の行数が異なる場合
		if (tbody.childNodes.length != l) {
			// 余分な行を削除
			while (tbody.childNodes.length > l) {
				tr = tbody.childNodes[tbody.childNodes.length - 1];
				tr.childNodes[1].firstChild.removeEventListener('click', onFavitaToggled, false);
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
				tr = createFavRow(favs[i].title, 'favita' + i, favs[i].set, onFavitaToggled);
				tbody.appendChild(tr);
			}
		} else {
			// 既存の行を更新
			for (i = 0; i < l; i++) {
				updateFavRow(tbody.childNodes[i], favs[i].title, favs[i].set);
			}
		}

		table.style.display = 'table';
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
	// {{{ generateOnBoardInfoGet()

	/**
	 * 非同期リクエストで板情報を取得した際に
	 * 実行されるコールバック関数を生成する
	 *
	 * @param {XMLHttpRequest} req
	 * @param {Function} parse
	 * @param {Element} pop
	 * @param {Element} table
	 * @return void
	 */
	var generateOnBoardInfoGet = function(req, parse, pop, table) {
		return function() {
			var data, err;

			if (req.readyState == 4) {
				if (req.status == 200) {
					try {
						data = parse(req.responseText);
						showFavitaList(table, data.favs);
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
	 * @param {XMLHttpRequest} req
	 * @param {Element} toggle
	 * @param {Boolean} toggled
	 * @return void
	 */
	var generateOnToggle = function(req, toggle, toggled) {
		return function() {
			if (req.readyState == 4) {
				if (req.status == 200) {
					if (req.responseText === '0') {
						// toggle属性をリセットする
						toggle.setAttribute('toggled', (toggled) ? 'false' : 'true');
						window.alert('更新に失敗しました');
					} else if (req.responseText !== '1') {
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
	 * リンクスライド時のアクション等を設定する
	 *
	 * @param {Object} iui
	 * @param {Object} iutil
	 * @param {Object} JSON
	 * @return void
	 */
	var setup = function(iui, iutil, JSON) {
		var i, bind, sliding, list, insertPages;

		sliding = iutil.sliding;

		// {{{ bind()

		/**
		 * 与えられたノード(ul要素)直下のリンクにスライド時のイベントハンドラを登録する
		 *
		 * @param {Node} node
		 * @return void
		 */
		bind = function(node) {
			var b, i, l, s;

			b = sliding.bind;
			s = document.evaluate('./li/a[@target = "_self" and'
			                      + ' (starts-with(@href, "read.php?")'
			                      + ' or starts-with(@href, "subject.php?"))]',
			                      node,
			                      null,
			                      XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
			                      null);
			l = s.snapshotLength;
			for (i = 0; i < l; i++) {
				b(s.snapshotItem(i));
			}
		};

		// }}}
		// {{{ override iui.insertPages()

		insertPages = iui.insertPages;

		/**
		 * AJAXでページを追加する際に実行される関数
		 * 後から読み込んだ板リストにもイベントハンドラを追加できるようにする
		 *
		 * @param {NodeList} nodes
		 * @return void
		 */
		iui.insertPages = function(nodes) {
			var i, l, child;

			l = nodes.length;
			for (i = 0; i < l; i++) {
				child = nodes[i];
				if (child.nodeType === 1 && child.nodeName.toLowerCase() === 'ul') {
					if (child.className.indexOf('tgrep-result') !== -1) {
						// スレッド検索
						bind(child);
					} else if (child.id) {
						if (child.id.indexOf('cate') === 0 && child.id !== 'cate0') {
							// 板リスト
							bind(child);
						} else if (child.id.indexOf('foundbrd') === 0) {
							// 板検索
							bind(child);
						}
					}
				}
			}

			insertPages(nodes);
		};

		// }}}
		// {{{ override sliding.callbacks.subject()

		/**
		 * 板リンクスライド時に実行される関数
		 *
		 * @param {Element} anchor
		 * @param {Event} event
		 * @return void
		 */
		sliding.callbacks.subject = function(anchor, event) {
			var pop, ul, li, m, req, table;

			// 要素を取得
			if (typeof sliding.dialogs.menuSubject === 'undefined') {
				pop = createPop('板');
			} else {
				pop = sliding.dialogs.menuSubject;
				pop = pop.parentNode.removeChild(pop);
			}
			sliding.dialogs.menuSubject = pop;
			sliding.setActiveDialog(pop);

			// お気に板の登録状況を取得
			table = pop.childNodes[1];
			table.style.display = 'none';
			if (anchor.href.indexOf('?spmode=merge_favita') === -1) {
				req = new XMLHttpRequest();
				req.open('GET', 'info_js.php?' + sliding.query, true);
				req.onreadystatechange = generateOnBoardInfoGet(req, JSON.parse, pop, table);
				req.send(null);
			}

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
		// {{{ override sliding.callbacks.read()

		/**
		 * スレッドリンクスライド時に実行される関数
		 *
		 * @param {Element} anchor
		 * @param {Event} event
		 * @return void
		 */
		sliding.callbacks.read = function(anchor, event) {
			var pop, ul, li, m, req, table;

			// 要素を取得
			if (typeof sliding.dialogs.menuRead === 'undefined') {
				pop = createPop('スレ');
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

		// 各お気に板グループごとに処理
		list = document.getElementById('favita0');
		if (list) {
			i = 0;
			do {
				bind(list);
				i++;
				list = document.getElementById('favita' + i);
			} while (list);
		} else {
			bind(document.getElementById('favita'));
		}
	};

	// }}}
	// {{{ on DOMContentLoaded

	window.addEventListener('DOMContentLoaded', function(event) {
		// iui/iutil/JSONが利用可能になるまで待つ
		if (typeof window.iui   === 'undefined' ||
			typeof window.iutil === 'undefined' ||
			typeof window.JSON  === 'undefined')
		{
			window.setTimeout(arguments.callee, 50);
		} else {
			setup(window.iui, window.iutil, window.JSON);
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
