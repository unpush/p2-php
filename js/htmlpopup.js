/* p2 - HTMLをポップアップするためのJavaScript */

//showHtmlDelaySec = 0.2 * 1000; //HTML表示ディレイタイム。マイクロ秒。

showHtmlTimerID=0;
node_div=false;
node_close=false;
tUrl=""; //URLテンポラリ変数
gUrl=""; //URLグローバル変数
gX=0;
gY=0;
ecX=0;
ecY=0;

//==============================================================
// showHtmlPopUp -- HTMLプアップを表示する関数
// 引用レス番に onMouseover で呼び出される
//==============================================================

function showHtmlPopUp(url,ev,showHtmlDelaySec){
	if(! document.createElement){return;} //DOM非対応
	
	showHtmlDelaySec = showHtmlDelaySec * 1000;

	if(! node_div || url!=gUrl){
		tUrl=url;
		gX=ev.pageX;
		gY=ev.pageY;
		if(document.all){ //IE
			ecX = event.clientX;
			ecY = event.clientY;
		}
		showHtmlTimerID = setTimeout("showHtmlPopUpDo()", showHtmlDelaySec); //HTML表示ディレイタイマー
	}
}

function showHtmlPopUpDo(){

	hideHtmlPopUp();

	gUrl=tUrl;
	var x_adjust=7; //x軸位置調整
	var y_adjust=-46;//y軸位置調整
	var closebox_width=18;
	
	if(! node_div){
		node_div=document.createElement('div');
		node_div.setAttribute('id', "iframespace");

		node_close=document.createElement('div');
		node_close.setAttribute('id', "closebox");
		//node_close.setAttribute('onMouseover', "hideHtmlPopUp()");

		if(document.all){ //IE用
			var body = (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
			gX = body.scrollLeft+ecX; //現在のマウス位置のX座標
			gY = body.scrollTop+ecY; //現在のマウス位置のY座標
			node_div.style.pixelLeft  = gX + x_adjust; //ポップアップ位置
			node_div.style.pixelTop  = body.scrollTop; //gY + y_adjust;
			var cX = gX + x_adjust - closebox_width;
			node_close.style.pixelLeft  = cX; //ポップアップ位置
			node_close.style.pixelTop  = body.scrollTop; //gY + y_adjust;
			var yokohaba = body.clientWidth - node_div.style.pixelLeft -20; //微調整付
			var tatehaba = body.clientHeight -20;
			
		}else if(document.getElementById){ //DOM対応用（Mozilla）
			node_div.style.left = gX + x_adjust + "px"; //ポップアップ位置
			node_div.style.top = window.pageYOffset + "px"; //gY + y_adjust + "px";
			var cX = gX + x_adjust - closebox_width;
			node_close.style.left = cX + "px"; //ポップアップ位置
			node_close.style.top = window.pageYOffset + "px"; //gY + y_adjust + "px";
			var yokohaba = window.innerWidth - gX - x_adjust -20; //微調整付
			var tatehaba = window.innerHeight -20;
		}

		pageMargin="";
		if( gUrl.match(/(jpg|jpeg|gif|png)$/) ){ //画像の場合はマージンをゼロに
			pageMargin=" marginheight=\"0\" marginwidth=\"0\" hspace=\"0\" vspace=\"0\"";
		}
		node_div.innerHTML = "<iframe src=\""+gUrl+"\" frameborder=\"1\" border=\"1\" style=\"background-color:#fff;\" width=" + yokohaba + " height=" + tatehaba + pageMargin +">&nbsp;</iframe>";
		
		node_close.innerHTML = "<b onMouseover=\"hideHtmlPopUp()\">×</b>";
		
		document.body.appendChild(node_div);
		document.body.appendChild(node_close);
	}
}

//==============================================================
// hideHtmlPopUp -- HTMLポップアップを非表示にする関数
// 引用レス番から onMouseout で呼び出される
//==============================================================

function hideHtmlPopUp(){

	if(! document.createElement){return;} //DOM非対応
	if(showHtmlTimerID){clearTimeout(showHtmlTimerID);} //HTML表示ディレイタイマーを解除
	if(node_div){
		node_div.style.visibility = "hidden";
		document.body.removeChild(node_div);
		node_div=false;
	}
	if(node_close){
		node_close.style.visibility = "hidden";
		document.body.removeChild(node_close);
		node_close=false;
	}
}

//==============================================================
// HTML表示タイマーを解除する関数
//==============================================================
function offHtmlPopUp(){
	if(showHtmlTimerID){clearTimeout(showHtmlTimerID);} //HTML表示ディレイタイマーを解除
}


