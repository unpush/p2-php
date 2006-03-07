/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

// コピペ用にスレ情報をポップアップする
function Invite(title, url, host, bbs, key, resnum)
{
	var msg;
	var winWidth  = 500;
	var winHeight = 100;
	var taCols = 64;
	var taRows = 3;
	var msg = '';

	if (host && bbs && key && resnum) {
		url += resnum;
		winHeight = 280;
		taRows = 15;
		var uri = 'read_async.php?host='+host+'&bbs='+bbs+'&key='+key+'&ls='+resnum+'n&q=2&offline=1';
		var req = getXmlHttp();
		req.open('get', uri, false);
		req.send(null);
		if (req.readyState == 4 && req.status == 200) {
			msg = '\n\n' + req.responseText.replace(/^<\?xml .+?\?>\n?/, '');
		}
	}

	var invite = window.open('', 'Invite', 'width=' + winWidth + ',height=' + winHeight + ',scrollbars=auto,resizable=yes');
	invite.document.writeln('<html>');
	invite.document.writeln('<head>');
	invite.document.writeln('<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">');
	invite.document.writeln('<title>' + InviteEscapeText(title) + '</title>');
	invite.document.writeln('<link rel="stylesheet" href="css.php?css=style" type="text/css">');
	invite.document.writeln('</head>');
	invite.document.writeln('<body>');
	invite.document.writeln('<center>');
	invite.document.write('<textarea id="forCopy" cols="' + taCols + '" rows="' + taRows + '" readonly>');
	invite.document.write(InviteEscapeText(title));
	invite.document.write('\n');
	invite.document.write(InviteEscapeText(url));
	invite.document.write(msg);
	invite.document.writeln('</textarea>');
	invite.document.writeln('<br>');
	invite.document.writeln('<input type="button" value="閉じる" onclick="window.close();">');
	invite.document.writeln('</center>');
	invite.document.writeln('</head>');
	invite.document.writeln('</html>');
	invite.document.close();
	invite.resizeTo(winWidth, winHeight);
	invite.focus();
}

// prototype.js 1.4.0 : string.js : escapeHTML + stripTags をワンライナーで
/*  Prototype JavaScript framework, version 1.4.0
 *  (c) 2005 Sam Stephenson <sam@conio.net>
 *
 *  Prototype is freely distributable under the terms of an MIT-style license.
 *  For details, see the Prototype web site: http://prototype.conio.net/
 */
function InviteEscapeText(cont)
{
	return document.createElement('div').appendChild(document.createTextNode(cont)).parentNode.innerHTML.replace(/<\/?[^>]+>/gi, '');
}
