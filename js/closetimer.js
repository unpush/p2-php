/* p2 - ポップアップウィンドウ、クローズタイマーのJavaScript */

var delay = 5 // ページが変わるまでの時間（秒単位）

var _swForm=0;
var _swElem=0;
var _run = 1;	// 1:run 0:stop
var _start;
var _now;

var ibtimer;

function startTimer(obj){
	ibtimer=obj;
	_start = new Date();
	closeTimer();
}
		
function closeTimer() {		// スクリプトの本体
	_now = new Date();
	if (_run == 1) {
		nowtime=delay - ( _now.getTime() - _start.getTime() ) / 1000;
		nowtime=Math.ceil(nowtime);
		if(nowtime < 0){
			window.close();
		}else if(nowtime < delay-1){
			//document.forms[_swForm].elements[_swElem].value = "         " + nowtime + "         ";
			ibtimer.value = "         " + nowtime + "         ";
		}
		setTimeout("closeTimer()", 100);
	}
}

function stopTimer(obj) {  // 設定
	if (_run == 1) {		// タイマー中なら
		_run = 0;
		obj.value = "ウィンドウを閉じる";
		//closeTimer();
	} else if (_run == 0) {	// ストップしてたなら
		window.close();
	}
}
