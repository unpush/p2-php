<?php

    /**
     * Merged from http://jiyuwiki.com/index.php?cmd=read&page=rep2%A4%C7%A3%C9%A3%C4%A4%CE%C7%D8%B7%CA%BF%A7%CA%D1%B9%B9&alias%5B%5D=pukiwiki%B4%D8%CF%A2
     *
     * @access  private
     * @return  string
     */
    function coloredIdStyle($idstr, $id, $count=0)
{
    global $STYLE;
    static $idcount = array();    
    static $idstyles = array(); 
    static $id_color_used= array() ;

if ($count >= 2) {
        //[$id] >= 2　ココの数字でスレに何個以上同じＩＤが出た時に背景色を変えるか決まる
        if (isset($idstyles[$id])) {
            return $idstyles[$id];
        } else {
            //	    	$alpha=0.8;	// アルファチャネル
            // IDから色の元を抽出

            $coldiv=64; // 色相環の分割数
            if (preg_match('/ID:/',$idstr)) { // IDが使える
                $rev_id=strrev(substr($id, 0, 8));
                $raw = base64_decode($rev_id);		// 8文字をバイナリデータ6文字分に変換
                $id_hex = unpack('H12', substr($raw, 0, 6));	// バイナリデータを16進文字列に変換
                $id_bin=base_convert($id_hex[1],16,2);	// さらに2進文字列に変換
                while ($id_bin) {
                    $arr[]=base_convert(substr($id_bin,-6),2,10);
                    $id_bin=substr($id_bin,0,-6);
                }

                $colors[0]=$arr[0];// % $coldiv;
                $idstr2=preg_split('/:/',$idstr,2); // コロンでID文字列を分割
                array_shift($idstr2);

                if ($id_color_used[$colors[0]]++) {
                    $colors[1]=$colors[0]+($id_color_used[$colors[0]]-1)+1;
                    $idstr2[1]=substr($idstr2[0],4);
                    $idstr2[0]=substr($idstr2[0],0,4); // コロンでID文字列を分割
                }
            } else { //シベリア板タイプ
                $ip_hex=preg_split('/\\./',$id);
                //var_dump($ip_hex);echo "<br>";
                $colors[1]=$ip_hex[1] % $coldiv;
                $idstr2=preg_split('/:/',$idstr,2); // コロンでID文字列を分割
                $idstr2[0].=':';

                if ($id_color_used[$colors[1]]++) {
                    $colors[2]=$colors[1]+($id_color_used[$colors[1]]-1)+1;
                    $idstr2[2]=".{$ip_hex[2]}.{$ip_hex[3]}";
                    $idstr2[1]="{$ip_hex[0]}.{$ip_hex[1]}"; // コロンでID文字列を分割
                }
            }
            $color_param=array();
            // HLS色空間
            // 色相H：値域0～360（角度）
            // 輝度L(HLS)：値域0（黒）～0.5（純色）～1（白）
            // 彩度S(HLS)：値域0（灰色）～1（純色）
            foreach ($colors as $key => $color) {
                //		    		var_dump(array(/*$raw,$id_hex,$arr,$col,*/$id_top,$c1,$c2));echo "<br>";
                $color_param[$key]=array();
                $angle=deg2rad($color*180/$coldiv);
            
                $color_param[$key]['H']=$color*360*4/$coldiv;
                while ($color_param[$key]['H']>360) {$color_param[$key]['H']-=360;}

                $color_param[$key]['L']=0.22+sin($angle)*0.08;
                $color_param[$key]['S']=0.4+sin($angle)*0.1;	    

                // RGBに変換
                $color_param[$key]=HLS2RGB($color_param[$key]);
                $color_param[$key]['Y']=(
                                         $color_param[$key]['R']*299+
                                         $color_param[$key]['G']*587+
                                         $color_param[$key]['B']*114
                                        )/1000;
 
            }

            // CSSで色をつける
            $uline=$STYLE['a_underline_none']==1 ? '' : "text-decoration:underline;";
            if ($count[$id]>=25 ) {     // 必死チェッカー発動
                $uline.="text-decoration:blink;";
            }
            $opacity=''; // "opacity:{$alpha};";
            foreach ($color_param as $area => $param) {
                $r=(int)$color_param[$area]['R'];
                $g=(int)$color_param[$area]['G'];
                $b=(int)$color_param[$area]['B'];
                if ($opacity || !$alpha) {
                    $bcolor[$area]="background-color:rgb({$r},{$g},{$b});";
                } else {
                    $bcolor[$area]="background-color:rgba({$r},{$g},{$b},{$alpha});";
                }

                // 背景色によって文字色を変える
              $y1=158;
              $y2=185; 
                if ($param['Y']>=$y1) {
                    $y=($param['Y']-($param['Y']>=$y2 ? $y2 : $y1))/$param['Y'];
                    
                        $r=(int)($r*$y);
                        $g=(int)($g*$y);
                        $b=(int)($b*$y);
                        $bcolor[$area].="color:rgb({$r},{$g},{$b});";
                } else {
                    $y1=140;
                    $y2=160;
                    if ($param['Y']<=255-$y1) {
                        $y=($param['Y']<=255-$y2 ? $y2 : $y1)/(255-$param['Y']);

                        $r+=(int)((255-$r)*$y);
                        $g+=(int)((255-$g)*$y);
                        $b+=(int)((255-$b)*$y);
                        $bcolor[$area].="color:rgb({$r},{$g},{$b});";
                    } else {
                        $bcolor[$area].="color:#fff;";
                    }
                }
                $idstr2[$area]="<span style=\"{$bcolor[$area]}{$border}{$uline}{$opacity}\">{$idstr2[$area]}</span>";
            }
//            var_dump(array('id'=>$id,'bcolor'=>$bcolor));echo "<br>";
            $idstr=join('',$idstr2);
            $idstyles[$id] = $bcolor;
            /*array(
                (isset($rgb[1]) ? "{$bcolor[1]}{$border}{$uline}" : ''),
                "{$bcolor[0]}{$border}{$uline}");
*/

        }
    }
//    var_dump(array('idstyles'=>$idstyles[$id]));echo "<br>";
    return $idstyles[$id];
}


/**
 * 色変換サブルーチン
 */
// 変換式参考資料　http://image-d.isp.jp/commentary/color_cformula/index.html
// Version.20081215 初版
// Version.20081216 L*C*h表色系の変換関数を追加
// Version.20081216.1 バグフィックス。
//   HSV2RGB,HSL2RGB,LCh2RGB,RGB2LChの戻り値に変換前および変換途中のパラメータを追加。
// Version.20081224 Lab2RGB,RGB2Lab追加
// Version.20081226 16進でカラーコードを生成
 
function RGB2ColorCode ($r,$g,$b) {
    if ($r>255) {$r=255;}
    if ($r<0) {$r=0;}
    if ($g>255) {$g=255;}
    if ($g<0) {$g=0;}
    if ($br>255) {$b=255;}
    if ($b<0) {$b=0;}

    return sprintf("#%02X%02X%02X",$r,$g,$b); 
    /*     return "#".
       substr("0".dechex($r),-2,2) .
       substr("0".dechex($g),-2,2) .
       substr("0".dechex($b),-2,2);*/
}
function HLS2RGB ($hls) {
    // HLS→RGB変換
    $h=$hls['H'];
    $l=$hls['L'];
    $s=$hls['S'];
 
    if ($s>1) {$s=1;}
    if ($s<0) {$s=0;}
    if ($l>1) {$l=1;}
    if ($l<0) {$l=0;}
    $h%=360;
 
    $max=($l<=0.5) ? $l*(1+$s) : $l*(1-$s)+$s;
    $min=2*$l-$max;
    if ($s==0) {
        $l2=floor($l*255);
        return array_merge(array('R'=>$l2,'G'=>$l2,'B'=>$l2,"type"=>"HLS",'color'=>RGB2ColorCode($l2,$l2,$l2)),$hls);
    }   else {
        $h_ary=array('R'=>$h+120,'G'=>$h,'B'=>$h-120);
        $rgb=array();
        //         for ($key=0;$key<3;$key++) {
        foreach ($h_ary as $key => $angle) {
            //             $angle=$h_ary[$key];
            while ($angle>=360) {$angle-=360;}
            while ($angle<0)    {$angle+=360;}
 
            if ($angle<60)         {$R=$min+($max-$min)*$angle/60;}
            else if ($angle<180)   {$R=$max;}
            else if ($angle<240)   {$R=$min+($max-$min)*(240-$angle)/60;}
            else                {$R=$min;}
 
            $rgb[$key]=floor($R*255);
            if ($rgb[$key]>255) {
                $rgb[$key]='255+'.$rgb[$key]-255;
            }
            if ($rgb[$key]<0) {
                $rgb[$key]='0'.$rgb[$key];
            }
        }
        $rgb=array_merge($rgb,$hls);
        $rgb['type']='HLS';
        $rgb{'color'}=RGB2ColorCode($rgb['R'],$rgb['G'],$rgb['B']);
        return $rgb;
 
    }
}
 
function HSV2RGB ($hsv) {
    // HSV→RGB変換
    $h=$hsv['H'];
    $s=$hsv['S'];
    $v=$hsv['V'];
    if ($s>1) {$s=1;}
    if ($s<0) {$s=0;}
    if ($v>1) {$v=1;}
    if ($v<0) {$v=0;}
    $h%=360;
  
    $hi=floor($h/60) % 6;
    $f=$h/60-$hi;
    $p=$v*(1-$s);
    $q=$v*(1-$f*$s);
    $t=$v*(1-(1-$f)*$s);
 
    switch ($hi) {
    case 0: $R=$v; $G=$t; $B=$p; break;
    case 1: $R=$q; $G=$v; $B=$p; break;
    case 2: $R=$p; $G=$v; $B=$t; break;
    case 3: $R=$p; $G=$q; $B=$v; break;
    case 4: $R=$t; $G=$p; $B=$v; break;
    case 5: $R=$v; $G=$p; $B=$q; break;
    }
    return array_merge(
                       array(
                             'R'=>floor($R*255),'G'=>floor($G*255),'B'=>floor($B*255),'type'=>'HSV',
                             'color'=>RGB2ColorCode($rgb['R'],$rgb['G'],$rgb['B'])
                            ),
                       $hsv
                      );
}
  
function Lab2RGB ($Lab) {
    $xyz=Lab2XYZ($Lab);
    $rgb=XYZ2RGB($xyz);
    $rgb=array_merge($rgb,$xyz,$Lab);
    $rgb['type']='L*a*b*';
    return $rgb;
}
function LCh2RGB ($LCh) {
    if ($LCh['L*']>100) {$LCh['L*']=100;}
    if ($LCh['L*']<0) {$LCh['L*']=0;}
    //     if ($LCh['C*']>100) {$LCh['C*']=100;}
    if ($LCh['C*']<0) {$LCh['C*']=0;}

    $LCh['h']%= 360;
 
    $Lab=LCh2Lab($LCh);
    $rgb=Lab2RGB($Lab);
    $rgb=array_merge($rgb,$LCh);
    $rgb['type']='L*C*h';
    return  $rgb;
}
function RGB2Lab ($rgb) {
    $xyz=RGB2XYZ($rgb);
    $Lab=XYZ2Lab($xyz);
    return array_merge($Lab,$xyz,$rgb);
}
function RGB2LCh ($rgb) {
    $Lab=RGB2Lab($rgb);
    $LCh=Lab2LCh($Lab);
    return array_merge($LCh,$Lab);
}
function RGB2XYZ ($rgb) {
    $linearRGB=array();
    foreach (array('R','G','B') as $i) {
        $c=$rgb[$i]/255;
        if ($c>1) {$c=1;}
        if ($c<0) {$c=0;} 
        if ($c<=0.04045) {$c/=12.92;}
        else {$c=pow(($c+0.055)/(1+0.055),2.4);}
        $linearRGB[$i]=$c;
    }
    $r=$linearRGB['R'];
    $g=$linearRGB['G'];
    $b=$linearRGB['B'];
 
    $x=0.412453*$r+0.35758*$g+0.180423*$b;
    $y=0.212671*$r+0.71516*$g+0.072169*$b;
    $z=0.019334*$r+0.119193*$g+0.950227*$b;
    return array('X'=>$x,'Y'=>$y,'Z'=>$z);
}
  
function XYZ2Lab ($xyz) {
    // D65光源補正
    $xyz['X']/=0.95045;
    $xyz['Z']/=1.08892;
 
    $f=array();
    foreach ($xyz as $key => $val) {
        if ($val>1) {$val=1;}
        if ($val<0) {$val=0;} 
        $f[$key]=($val>0.008856) ? pow($val,1/3) : (903.3*$val+16)/116;
    }
    $L=116*$f['Y']-16;
    $a=500*(($f['X']/0.95045)-$f['Y']);
    $b=200*($f['Y']-($f['Z']/1.08892));
 
    return array('L*'=>$L,'a*'=>$a,'b*'=>$b);     // L:[0..100],a:[-134..220],b:[-140..122]
}
 
function Lab2XYZ ($Lab) {
    //	if ($Lab[0]>=100) {$fy=1;}
    /*     if ($Lab[0]<7.9996) {
       $fy=$Lab[0]/903.3;
       $fx=$fy+$Lab[1]/3893.5;
       $fz=$fy-$Lab[2]/1557.4;
       } else {
       $fy=($Lab[0]+16)/116;
       $fx=$fy+$Lab[1]/500;
       $fz=$fy-$Lab[2]/200;
       $fx=pow($fx,3);
       $fy=pow($fy,3);
       $fz=pow($fz,3);
       }*/
    if ($Lab['L*']>903.3*0.008856) {
        $y=pow(($Lab['L*']+16)/116,3);
    } else {
        $y=$Lab['L*']/903.3;
    }
    if ($y>0.008856) {
        $fy=($Lab['L*']+16)/116;
    } else {
        $fy=(903.3*$y+16)/116;
    }
    $fx=$Lab['a*']/500+$fy;
    $fz=$fy-$Lab['b*']/500;
    $x=pow($fx,3);
    $z=pow($fz,3);
    if ($x<=0.008856) {
        $x=(116*$fx-16)/903.3;
    }
    if ($z<=0.008856) {
        $z=(116*$fz-16)/903.3;
    }
    $xyz=array('X'=>$x,'Y'=>$y,'Z'=>$z);
    // D65光源補正
    $xyz['X']*=0.95045;
    $xyz['Z']*=1.08892;
    /*     foreach ($xyz as $key => $val) {
       $xyz[$key]=floor($val*10000)/10000;
       }*/
 
    return $xyz;
}  
function XYZ2RGB ($xyz) {
    $x=$xyz['X'];
    $y=$xyz['Y'];
    $z=$xyz['Z'];

    if ($x>1) {$x=1;}
    if ($x<0) {$x=0;}
    if ($y>1) {$y=1;}
    if ($y<0) {$y=0;}
    if ($z>1) {$z=1;}
    if ($z<0) {$z=0;}
 
    if ($y>=1) {$r=$g=$b=1;}
    else {
        $r= 3.240479*$x -1.53715 *$y -0.498535*$z;
        $g=-0.969256*$x +1.875991*$y +0.041556*$z;
        $b= 0.055648*$x -0.204043*$y +1.057311*$z;
    }
 
    $rgb=array();
    $linearRGB=array('R'=>$r,'G'=>$g,'B'=>$b);
    //     for ($k=0;$k<count($linearRGB);$k++) {
    foreach ($linearRGB as $k => $v) {
        //         $v=$linearRGB[$i];
        if ($v<=0.0031308) {$v*=12.92;}
        else {$v=pow($v,1/2.4)*(1+0.055)-0.055;}
        $v*=255;
 
        $rgb[$k]=floor($v);
    }
    $rgb{'color'}=RGB2ColorCode($rgb['R'],$rgb['G'],$rgb['B']);
 
    return $rgb;
}
function Lab2LCh ($Lab) {
    $a=$Lab['a*'];
    $b=$Lab['b*'];
    if ($L>100) {$L=100;}
    if ($L<0) {$L=0;}
    if ($a>100) {$a=100;}
    if ($a<-100) {$a=-100;}
    if ($b>100) {$b=100;}
    if ($b<-100) {$b=-100;}
 
    $C=sqrt(pow($a,2)+pow($b,2));
    $h=rad2deg(atan2($b,$a));
    if ($h<0) {$h+=360;};
    return array('L*'=>$Lab['L*'],'C*'=>$C,'h'=>$h);
}
function LCh2Lab ($LCh) {  
    $C=$LCh['C*'];
 
    $h2=deg2rad($LCh['h']);
    $a=$C*cos($h2);
    $b=$C*sin($h2);
    return array('L*'=>$LCh['L*'],'a*'=>$a,'b*'=>$b);
}
 
?>
