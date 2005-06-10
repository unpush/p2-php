/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

/* p2 - 引用レス番をポップアップするためのJavaScript */

/*
document.open;
document.writeln('<style type="text/css" media="all">');
document.writeln('<!--');
document.writeln('.respopup{visibility: hidden;}');
document.writeln('-->');
document.writeln('</style>');
document.close;
*/

delaySec = 0.3 * 1000;	//レスポップアップを非表示にする遅延時間。秒。
zNum=0;

//==============================================================
// POPS -- ResPopUp オブジェクトを格納する配列。
// 配列 POPS の要素数が、現在生きている ResPopUp オブジェクトの数となる。
//==============================================================
POPS = new Array(); 

theResPopCtl = new ResPopCtl();

//==============================================================
// showResPopUp -- レスポップアップを表示する関数
// 引用レス番に onMouseover で呼び出される
//==============================================================

function showResPopUp(divID,ev){
	if( divID.indexOf("-") != -1 ){return;} //連番(>>1-100)は非対応
	aResPopUp = theResPopCtl.getResPopUp(divID);
	if(aResPopUp){
	
		/*
		//再表示時の zIndex 処理------------------------
		// しかしなぜか期待通りの動作をしてくれない。
		// IEとMozillaで挙動も違う。よって非アクティブ。
		aResPopUp.zNum=zNum;
		aResPopUp.popOBJ.style.zIndex=aResPopUp.zNum;
		//----------------------------------------
		*/
		
	}else{
		zNum++;
		theResPopCtl.addResPopUp(divID); //新しいポップアップを追加
	}
	if(aResPopUp.timerID){clearTimeout(aResPopUp.timerID);} //非表示タイマーを解除

	var mode = divID.charAt(0);

	aResPopUp.showResPopUp(ev ,mode);
}

//==============================================================
// hideResPopUp -- レスポップアップを非表示タイマーする関数
// 引用レス番から onMouseout で呼び出される
//==============================================================

function hideResPopUp(divID){
	if( divID.indexOf("-") != -1 ){return;} //連番(>>1-100)は非対応
	aResPopUp = theResPopCtl.getResPopUp(divID);
	if(aResPopUp){
		aResPopUp.hideResPopUp();
	}
}

//==============================================================
// hideResPopUp2 -- レスポップアップを非表示にする関数
//==============================================================

function hideResPopUp2(divID){
	aResPopUp = theResPopCtl.getResPopUp(divID);
	aResPopUp.hideResPopUp2();
}


//==============================================================
// ResPopCtl  -- オブジェクトデータをコントロールするクラス
//==============================================================

function ResPopCtl(){

	//==================================================
	// 配列 POPS に新規 ResPopUp オブジェクト を追加する関数
	//==================================================
	function ResPopCtl_addResPopUp(divID){
		aResPopUp = new ResPopUp(divID);
		//POPS.push(aResPopUp); Array.push はIE5.5未満未対応なので代替処理
		POPS[POPS.length] = aResPopUp;
	}
	ResPopCtl.prototype.addResPopUp = ResPopCtl_addResPopUp;
	
	//==================================================
	// 配列 POPS から 指定の ResPopUp オブジェクト を削除する関数
	//==================================================
	function ResPopCtl_rmResPopUp(divID){
		for (i = 0; i < POPS.length; i++) {
	    	if(POPS[i].divID == divID){
				//POPS.splice(i, 1); Array.splice はIE5.5未満未対応なので代替処理
				
				POPS2 = new Array();
				for(j=0; j < POPS.length; j++){
					if(j != i){
						POPS2[POPS2.length]=POPS[j];
					}
				}
				POPS=POPS2;
				
				return true;
			}
		}
		return false;
	}
	ResPopCtl.prototype.rmResPopUp = ResPopCtl_rmResPopUp;

	//==================================================
	// 配列 POPS で指定 divID の ResPopUp オブジェクトを返す関数
	//==================================================
	function ResPopCtl_getResPopUp(divID){
		for (i = 0; i < POPS.length; i++) {
	    	if(POPS[i].divID == divID){
				return POPS[i];
			}
		}
		return false;
	}
	ResPopCtl.prototype.getResPopUp = ResPopCtl_getResPopUp;
	
	return this;
}


//==============================================================
// ResPopUp -- レスポップアップクラス
//==============================================================

function ResPopUp(divID){
    this.divID = divID;
	this.zNum = zNum;
	this.timerID=0;
	 if(document.all){ //IE用
		this.popOBJ = document.all[this.divID];
	}else if(document.getElementById){ //DOM対応用（Mozilla）
		this.popOBJ = document.getElementById(this.divID);
	}
	
	//==================================================
	// showResPopUp -- レスポップアップを表示する関数
	//==================================================
	function ResPopUp_showResPopUp(ev, mode){
		var x_adjust=10; //x軸位置調整
		var y_adjust=-68; //y軸位置調整
		if(mode == "p" || mode == "a" || mode == "n"){ //スマートポップアップメニューのとき
			//x_adjust = 0;
			y_adjust = -10;
		}
		if(this.popOBJ.style.visibility != "visible"){
			this.popOBJ.style.zIndex = this.zNum;
			if(document.all){ //IE用
				var body = (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
				x = body.scrollLeft+event.clientX; //現在のマウス位置のX座標
				y = body.scrollTop+event.clientY; //現在のマウス位置のY座標
				this.popOBJ.style.pixelLeft  = x + x_adjust; //ポップアップ位置
				this.popOBJ.style.pixelTop  = y + y_adjust;
				
				if( (this.popOBJ.offsetTop + this.popOBJ.offsetHeight) > (body.scrollTop + body.clientHeight) ){
					this.popOBJ.style.pixelTop = body.scrollTop + body.clientHeight - this.popOBJ.offsetHeight -20;
				}
				if(this.popOBJ.offsetTop < body.scrollTop){
					this.popOBJ.style.pixelTop = body.scrollTop -2;
				}
				
			}else if(document.getElementById){ //DOM対応用（Mozilla）
				x = ev.pageX; //現在のマウス位置のX座標
				y = ev.pageY; //現在のマウス位置のY座標
				this.popOBJ.style.left = x + x_adjust + "px"; //ポップアップ位置
				this.popOBJ.style.top = y + y_adjust + "px";
				//alert(window.pageYOffset);
				//alert(this.popOBJ.offsetTop);
				
				if( (this.popOBJ.offsetTop + this.popOBJ.offsetHeight) > (window.pageYOffset + window.innerHeight) ){
					this.popOBJ.style.top = window.pageYOffset + window.innerHeight - this.popOBJ.offsetHeight -20 + "px";
				}
				if(this.popOBJ.offsetTop < window.pageYOffset){
					this.popOBJ.style.top = window.pageYOffset -2 + "px";
				}
				
			}
			this.popOBJ.style.visibility = "visible"; //レスポップアップ表示
		}
    }
	ResPopUp.prototype.showResPopUp = ResPopUp_showResPopUp;
	
	//==================================================
	// hideResPopUp -- レスポップアップを非表示タイマーする関数
	//==================================================
	function ResPopUp_hideResPopUp(){
		this.timerID = setTimeout("hideResPopUp2('"+this.divID+"')", delaySec); //一定時間表示したら消す
	}
	ResPopUp.prototype.hideResPopUp = ResPopUp_hideResPopUp;

	//==================================================
	// hideResPopUp2 -- レスポップアップを非表示にする関数
	//==================================================
	function ResPopUp_hideResPopUp2(){

		for(i=0; i < POPS.length; i++){
		
			if(this.zNum < POPS[i].zNum){
				//clearTimeout(this.timerID); //タイマーを解除
				this.timerID = setTimeout("hideResPopUp('"+this.divID+"')", delaySec); //一定時間表示したら消す
				return;
			}
		}
		
		this.popOBJ.style.visibility = "hidden"; //レスポップアップ非表示
		//clearTimeout(this.timerID); //タイマーを解除
		theResPopCtl.rmResPopUp(this.divID);
	}
	ResPopUp.prototype.hideResPopUp2 = ResPopUp_hideResPopUp2;
		
	return this;
}
