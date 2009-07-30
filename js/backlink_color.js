var BacklinkColor = function(prefix) {
    this.prefix = prefix;
};
BacklinkColor.prototype = {
    colors : [],
    highlightStyle : {},
    backlinks : {},

    useColors : [],
    getColor : function() {
        var color;
        if (this.colors.length == 0) {
            this.colors = this.useColors;
            this.useColors = [];
        }
        color = this.colors.shift();
        this.useColors.push(color);
        return color;
    },
    releaseColor : function(color) {
        var tmp = [];
        for (var i=0; i < this.useColors.length; i++) {
            if (this.useColors[i] == color) {
                this.colors.push(color);
            } else {
                tmp.push(this.useColors[i]);
            }
        }
        this.useColors = tmp;
    },
    click : function(res, evt) {
        if (evt.type == 'dblclick') {
            // 't1qm100' -> '100' -> 't1m100'
            var target = document.getElementById(
                    this.prefix + 'm' + this._getResnumFromIdstr(res.id));
            var _this = this;
            res.backlinktimer = setTimeout(function() {
                _this.mark(target);
                res.backlinktimer = null;
            }, 250);
            if (res.addEventListener) {
                res.addEventListener('click', function(event) {
                            _this.click(res, event);
                            res.removeEventListener('click', arguments.callee, false);
                        }, false);
            } else {
                res.attachEvent('onclick', function(event) {
                            _this.click(res, event);
                            res.detachEvent('onclick', arguments.callee, false);
                        });
            }
        } else
        if (evt.type == 'click') {
            if (res.backlinktimer) {
                clearTimeout(res.backlinktimer);
                res.backlinktimer = null;
                // 't1qm100' -> '100' -> 't1m100'
                var target = document.getElementById(
                        this.prefix + 'm' + this._getResnumFromIdstr(res.id));
                this.unmark(target, target.colorBl);
            }
        }
    },

    mark : function(res, style) {
        if (!res) return false;
        if (!res.styleBefore) {
            res.styleBefore = {color : res.style.color, fontStyle : res.style['fontStyle'], fontWeight : res.style['fontWeight'], fontFamily : res.style['fontFamily']};
        }
        if (style == null) {
            style = {color : this.getColor()};
            if (res.colorBl == style.color) {
                style.color = this.getColor();
            }
            this.releaseColor(res.colorBl);
            this.setStyle(res, (function(p) {
                        var F = new Function();
                        F.prototype = p;
                        var ret = new F();
                        ret.color = style.color;
                        return ret;
                    })(this.highlightStyle));
        } else {
            this.setStyle(res, res.styleBefore);
            this.setStyle(res, style);
        }
        var resnum = this._getResnumFromIdstr(res.id);
        if (this.backlinks[resnum] == null) return true;
        for (var i in this.backlinks[resnum]) {
            if (this.backlinks[resnum][i] == resnum) continue;
            this.mark(document.getElementById(
                        this.prefix + 'm' + this.backlinks[resnum][i]), style);
        }
    },
    unmark : function(res, color) {
        if (!res) return false;
        if (color) this.releaseColor(color);
        this.setStyle(res, res.styleBefore);
        res.styleBefore = null;
        var resnum = this._getResnumFromIdstr(res.id);
        if (this.backlinks[resnum] == null) return true;
        for (var i in this.backlinks[resnum]) {
            if (this.backlinks[resnum][i] == resnum) continue;
            this.unmark(document.getElementById(
                        this.prefix + 'm' + this.backlinks[resnum][i]));
        }
    },
    setStyle : function(res, style) {
        for (var i in style) {
            res.style[i] = style[i];
        }
        if (style.color) {
            res.colorBl = style.color;
        }
    },
    setUp : function() {
        var _this = this;
        for (var i in this.backlinks) {
            if (this.backlinks[i].length < 1) continue;
            if (this.backlinks[i].length == 1 && this.backlinks[i][0] == i) continue;
            var m = document.getElementById(this.prefix + 'm' + i);
            if (m) this.observe(m, 'dblclick', function(e) { _this.click(this, e); });
            var qm = document.getElementById(this.prefix + 'qm' + i);
            if (qm) this.observe(qm, 'dblclick', function(e) { _this.click(this, e); });
        }
    },
    observe : function(target, type, listener) {
        if (target.addEventListener) {
            target.addEventListener(type, listener, false);
        } else {
            target.attachEvent('on' + type, function() {
                    listener.call(target, window.event);
                });
        }
    },
    _getResnumFromIdstr : function(idstr) {
        return (this.prefix ? idstr.substr(this.prefix.length) : idstr)
            .replace(/[a-z]|[A-Z]/g, '');   // 't1m100' -> '100'
   }

};
