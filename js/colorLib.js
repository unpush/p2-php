(function(){

var RGB2ColorCode = function(r, g, b) {
    f = function(v) { if (v > 255) v = 255; if (v < 0) v = 0; return v; };
    return "#" +
      ("0" + f(r).toString(16)).slice(-2) +
      ("0" + f(g).toString(16)).slice(-2) +
      ("0" + f(b).toString(16)).slice(-2);
};

var HLS2RGB = function (hls) {
    var h = hls[0], l = hls[1], s = hls[2];
    var f = function(v) { if (v > 1) v = 1; if (v < 0) v = 0; return v; };
    l = f(l); s = f(s);
    h %= 360;
    var max = (l <= 0.5) ? l * (1 + s) : l * (1 - s) + s;
    var min = 2 * l - max;
    var rgb;
    if (s == 0) {
      var l2 = Math.floor(l * 255);
      rgb = {r : l2, g : l2, b : l2};
    } else {
      f = function(h1) {
        if (h1 >= 360) h1 -= 360;
        if (h1 < 0) h1 += 360;
        return (Math.floor((function() {
            if (h1 < 60) return min + (max - min) * h1 / 60
            else if (h1 < 180) return max
            else if (h1 < 240) return min + (max - min) * (240 - h1) / 60
            else return min;
          })() * 255));
      };
      rgb = {r : f(h + 120), g : f(h), b : f(h - 120)};
    }
    rgb.HLS = [h, l, s]; rgb.type = 'HLS';
    rgb.color = RGB2ColorCode(rgb.r, rgb.g, rgb.b);
    return rgb;
};

var HSV2RGB = function (hsv) {
    var h = hsv[0], s = hsv[1], v = hsv[2];
    var f = function(v) { if (v > 1) v = 1; if (v < 0) v = 0; return v; };
    s = f(s); v = f(v);
    h %= 360;
 
    var hi = Math.floor(h / 60) % 6;
    var f = h / 60 - hi;
    var p = v * (1 - s);
    var q = v * (1 - f * s);
    var t = v * (1 - (1 - f) * s);

    var R, G, B;
    switch (hi) {
      case 0: R = v; G = t; B = p; break;
      case 1: R = q; G = v; B = p; break;
      case 2: R = p; G = v; B = t; break;
      case 3: R = p; G = q; B = v; break;
      case 4: R = t; G = p; B = v; break;
      case 5: R = v; G = p; B = q; break;
    }
    var rgb = {r : Math.floor(R * 255), g : Math.floor(G * 255), b: Math.floor(B * 255),HSV : [h, s, v], type : 'HSV'};
    rgb.color = RGB2ColorCode(rgb.r, rgb.g, rgb.b);
    return rgb;
};

var Lab2RGB = function (Lab) {
    var xyz = Lab2XYZ(Lab);
    var rgb = XYZ2RGB(xyz);
    rgb.Lab = Lab; rgb.type = 'L*a*b*';
    return rgb;
};

var LCh2RGB = function (LCh) {
    var L = LCh[0], C = LCh[1], h = LCh[2];
    if (L > 100) L = 100;
    if (L < 0) L = 0;
    //     if (C>100) {C=100;}
    if (C < 0) C = 0;
    h %= 360;

    var Lab = LCh2Lab([L, C, h]);
    var rgb = Lab2RGB(Lab);
    rgb.LCh = [L, C, h]; rgb.type = 'L*C*h';
    return rgb;
};

var RGB2Lab = function (rgb) {
    var xyz = RGB2XYZ(rgb);
    var Lab = XYZ2Lab(xyz);
    return Lab;
};

var RGB2LCh = function (rgb) {
    var Lab = RGB2Lab(rgb);
    var LCh = Lab2LCh(Lab);
    return LCh;
};

var RGB2XYZ = function (rgb) {
    var f = function(c) {
      c = c / 255;
      if (c > 1) c = 1;
      if (c < 0) c = 0;
      if (c <= 0.04045) c /= 12.92
      else c = Math.pow((c + 0.055) / (1 + 0.055), 2.4);
      return c;
    };
    var r = f(rgb[0]), g = f(rgb[1]), b = f(rgb[2]);

    var x = 0.412453 * r + 0.35758  * g + 0.180423 * b;
    var y = 0.212671 * r + 0.71516  * g + 0.072169 * b;
    var z = 0.019334 * r + 0.119193 * g + 0.950227 * b;
    return [x, y, z];
};

var XYZ2Lab = function (xyz) {
    // D65ŒõŒ¹•â³
    xyz[0] /= 0.95045;
    xyz[2] /= 1.08892;

    var f = [];
    for (i = 0; i < 3; i++) {
        var c = xyz[i];
        if (c > 1) c = 1;
        if (c < 0) c = 0;
        f[i] = (c > 0.008856) ? Math.pow(c, 1/3) : (903.3 * c + 16) / 116;
    }
    var L = 116 * f[1] - 16;
    var a = 500 * ((f[0] / 0.95045) - f[1]);
    var b = 200 * (f[1] - (f[2] / 1.08892));

    return [L, a, b];     // L:[0..100],a:[-134..220],b:[-140..122]
};

var Lab2XYZ = function (Lab) {
    //  if (Lab[0]>=100) {fy=1;}
    var fx, fy, fz;
    if (Lab[0]<7.9996) {
        fy = Lab[0] / 903.3;
        fx = fy + Lab[1] / 3893.5;
        fz = fy - Lab[2] / 1557.4;
    } else {
        fy = (Lab[0] + 16) / 116;
        fx = fy + Lab[1] / 500;
        fz = fy - Lab[2] / 200;
        fx = Math.pow(fx ,3);
        fy = Math.pow(fy ,3);
        fz = Math.pow(fz ,3);
    }
    var xyz = [fx, fy, fz];
    // D65ŒõŒ¹•â³
    xyz[0] *= 0.95045;
    xyz[2] *= 1.08892;
    for (var i = 0; i < 3; i++) {
        xyz[i] = Math.floor(xyz[i] * 10000) / 10000;
    }

    return xyz;
};

var XYZ2RGB = function (xyz) {
    var f = function(v) { if (v > 1) v = 1; if (v < 0) v = 0; return v; };
    var x = f(xyz[0]), y = f(xyz[1]), z = f(xyz[2]);

    var r, g, b;
    if (y >= 1) r = g = b = 1
    else {
      r =  3.240479 * x - 1.53715  * y - 0.498535 * z;
      g = -0.969256 * x + 1.875991 * y + 0.041556 * z;
      b =  0.055648 * x - 0.204043 * y + 1.057311 * z;
    }

    f = function(c) {
      if (c <= 0.0031308) c *= 12.92
      else c = Math.pow(c, 1 / 2.4) * (1 + 0.055) - 0.055;
      c *= 255;
      return Math.floor(c);
    };
    var rgb = {r : f(r), g : f(g), b : f(b)};
    rgb.color = RGB2ColorCode(rgb.r, rgb.g, rgb.b);
    rgb.xyz = [x, y, z]; rgb.type = 'XYZ';
    return rgb;
};

var Lab2LCh = function (Lab) {
    var L = Lab[0], a = Lab[1], b = Lab[2];
    if (L > 100) L = 100;
    if (L < 0) L = 0;
    if (a > 100) a = 100;
    if (a < -100) a = -100;
    if (b > 100) b = 100;
    if (b < -100) b = -100;

    var C = Math.sqrt(Math.pow(a, 2) + Math.pow(b, 2));
    var h = Math.atan2(b, a) * 180 / Math.PI;
    if (h < 0) h += 360;
    return [L, C, h];
};

var LCh2Lab = function (LCh) {
    var L = LCh[0], C = LCh[1], h = LCh[2];

    var h2 = h * Math.PI / 180;
    var a = C * Math.cos(h2);
    var b = C * Math.sin(h2);
    return [L, a, b];
};

if (!this['ColorLib']) ColorLib = {
    HSV2RGB : HSV2RGB,
    HLS2RGB : HLS2RGB,
    LCh2RGB : LCh2RGB
};

})();
