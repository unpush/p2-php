<?php
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
    if ($b>255) {$b=255;}
    if ($b<0) {$b=0;}

    return "#".
        substr("0".dechex($r),-2,2) .
        substr("0".dechex($g),-2,2) .
        substr("0".dechex($b),-2,2);
}
function HLS2RGB ($hls) {
    // HLS→RGB変換
    list($h,$l,$s)=$hls;

    if ($s>1) {$s=1;}
    if ($s<0) {$s=0;}
    if ($l>1) {$l=1;}
    if ($l<0) {$l=0;}
    $h%=360;

    $max=($l<=0.5) ? $l*(1+$s) : $l*(1-$s)+$s;
    $min=2*$l-$max;
    if ($s==0) {
        $l2=floor($l*255);
        return array($l2,$l2,$l2,$h,$l,$s,"type"=>"HLS",'color'=>RGB2ColorCode($l2,$l2,$l2));
    }   else {
        $h_ary=array($h+120,$h,$h-120);
        $rgb=array();
        for ($h0=0;$h0<3;$h0++) {
            $h1=$h_ary[$h0];
            if ($h1>=360) {$h1-=360;}
            if ($h1<0)    {$h1+=360;}

            if ($h1<60)         {$R=$min+($max-$min)*$h1/60;}
            else if ($h1<180)   {$R=$max;}
            else if ($h1<240)   {$R=$min+($max-$min)*(240-$h1)/60;}
            else                {$R=$min;}

            array_push($rgb,floor($R*255));
        }
        array_push($rgb,$h,$l,$s);
        $rgb['type']='HLS';
        $rgb{'color'}=RGB2ColorCode($rgb[0],$rgb[1],$rgb[2]);
        return $rgb;

    }
}

function HSV2RGB ($hsv) {
    // HSV→RGB変換
    list($h,$s,$v)=$hsv;
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
    $rgb=array(floor($R*255),floor($G*255),floor($B*255),$h,$s,$v,'type'=>'HSV');
    $rgb{'color'}=RGB2ColorCode($rgb[0],$rgb[1],$rgb[2]);
    return $rgb;
}
 
function Lab2RGB ($Lab) {
    $xyz=Lab2XYZ($Lab);
    $rgb=XYZ2RGB($xyz);
    $rgb=array_merge($rgb,$xyz,$Lab);
    $rgb['type']='L*a*b*';
    return $rgb;
}
function LCh2RGB ($LCh) {
    list($L,$C,$h)=$LCh;
    if ($L>100) {$L=100;}
    if ($L<0) {$L=0;}
    //     if ($C>100) {$C=100;}
    if ($C<0) {$C=0;}
    $LCh[0]=$L;
    $LCh[1]=$C;
    $LCh[2]=$h%=360;

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
    for ($i=0;$i<3;$i++) {
        $c=$rgb[$i]/255;
        if ($c>1) {$c=1;}
        if ($c<0) {$c=0;} 
        if ($c<=0.04045) {$c/=12.92;}
        else {$c=pow(($c+0.055)/(1+0.055),2.4);}
        $linearRGB[$i]=$c;
    }
    list($r,$g,$b)=$linearRGB;

    $x=0.412453*$r+0.35758*$g+0.180423*$b;
    $y=0.212671*$r+0.71516*$g+0.072169*$b;
    $z=0.019334*$r+0.119193*$g+0.950227*$b;
    return array($x,$y,$z);
}
 
function XYZ2Lab ($xyz) {
    // D65光源補正
    $xyz[0]/=0.95045;
    $xyz[2]/=1.08892;

    $f=array();
    for ($i=0;$i<3;$i++) {
        $c=$xyz[$i];
        if ($c>1) {$c=1;}
        if ($c<0) {$c=0;} 
        $f[$i]=($c>0.008856) ? pow($c,1/3) : (903.3*$c+16)/116;
    }
    $L=116*$f[1]-16;
    $a=500*(($f[0]/0.95045)-$f[1]);
    $b=200*($f[1]-($f[2]/1.08892));

    return array($L,$a,$b);     // L:[0..100],a:[-134..220],b:[-140..122]
}

function Lab2XYZ ($Lab) {
    //  if ($Lab[0]>=100) {$fy=1;}
    if ($Lab[0]<7.9996) {
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
    }
    $xyz=array($fx,$fy,$fz);
    // D65光源補正
    $xyz[0]*=0.95045;
    $xyz[2]*=1.08892;
    for ($i=0;$i<3;$i++) {
        $xyz[$i]=floor($xyz[$i]*10000)/10000;
    }

    return $xyz;
}  
function XYZ2RGB ($xyz) {
    list($x,$y,$z)=$xyz;
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
    $linearRGB=array($r,$g,$b);
    for ($i=0;$i<count($linearRGB);$i++) {
        $c=$linearRGB[$i];
        if ($c<=0.0031308) {$c*=12.92;}
        else {$c=pow($c,1/2.4)*(1+0.055)-0.055;}
        $c*=255;

        $rgb[$i]=floor($c);
    }
    $rgb{'color'}=RGB2ColorCode($rgb[0],$rgb[1],$rgb[2]);

    return $rgb;
}
function Lab2LCh ($Lab) {
    list($L,$a,$b)=$Lab;
    if ($L>100) {$L=100;}
    if ($L<0) {$L=0;}
    if ($a>100) {$a=100;}
    if ($a<-100) {$a=-100;}
    if ($b>100) {$b=100;}
    if ($b<-100) {$b=-100;}

    $C=sqrt(pow($a,2)+pow($b,2));
    $h=rad2deg(atan2($b,$a));
    if ($h<0) {$h+=360;};
    return array($L,$C,$h);
}
function LCh2Lab ($LCh) {
    list($L,$C,$h)=$LCh;

    $h2=deg2rad($h);
    $a=$C*cos($h2);
    $b=$C*sin($h2);
    return array($L,$a,$b);
}

?>
