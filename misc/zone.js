// for reference only

"ebg/zone": function() {
    define(["dojo", "dojo/_base/declare"], function(dojo, _11a5) {
        return _11a5("ebg.zone", null, {
            constructor: function() {
                this.page = null;
                this.container_div = null;
                this.item_height = null;
                this.item_width = null;
                this.items = [];
                this.control_name = null;
                this.item_margin = 5;
                this.autowidth = false;
                this.autoheight = true;
                this.item_pattern = "grid";
            },
            create: function(page, _11a6, _11a7, _11a8) {
                if (_11a6 === null) {
                    console.error("Null container in zone::create");
                }
                this.page = page;
                this.container_div = _11a6;
                this.item_width = _11a7;
                this.item_height = _11a8;
                this.control_name = _11a6.id;
                if (dojo.style(this.container_div, "position") != "absolute") {
                    dojo.style(this.container_div, "position", "relative");
                }
            },
            setFluidWidth: function() {
                dojo.connect(window, "onresize", this, "updateDisplay");
            },
            setPattern: function(_11a9) {
                switch (_11a9) {
                case "grid":
                case "diagonal":
                    this.autoheight = true;
                    this.item_pattern = _11a9;
                    break;
                case "verticalfit":
                case "horizontalfit":
                case "ellipticalfit":
                    this.autoheight = false;
                    this.item_pattern = _11a9;
                    break;
                default:
                    console.error("zone::setPattern: unknow pattern: " + _11a9);
                    break;
                }
            },
            isInZone: function(id) {
                for (var i in this.items) {
                    var item = this.items[i];
                    if (item.id == id) {
                        return true;
                    }
                }
                return false;
            },
            placeInZone: function(id, _11aa) {
                if (typeof _11aa == "undefined") {
                    _11aa = 0;
                }
                if (this.isInZone(id)) {
                    return;
                }
                this.items.push({
                    id: id,
                    weight: _11aa
                });
                this.page.attachToNewParent($(id), this.container_div);
                var _11ab = function(a, b) {
                    if (a.weight > b.weight) {
                        return 1;
                    } else {
                        if (a.weight < b.weight) {
                            return -1;
                        } else {
                            return 0;
                        }
                    }
                };
                this.items.sort(_11ab);
                this.updateDisplay();
            },
            removeFromZone: function(id, _11ac, to) {
                var _11ad = function(node) {
                    dojo.destroy(node);
                };
                for (var i in this.items) {
                    var item = this.items[i];
                    if (item.id == id) {
                        var anim = null;
                        if (to) {
                            anim = this.page.slideToObject($(item.id), to).play();
                            if (_11ac === true) {
                                dojo.connect(anim, "onEnd", _11ad);
                            }
                            anim.play();
                        } else {
                            if (_11ac === true) {
                                duration = 500;
                                if (this.page.instantaneousMode) {
                                    duration = 1;
                                }
                                anim = dojo.fadeOut({
                                    node: $(item.id),
                                    duration: duration,
                                    onEnd: _11ad
                                });
                                anim.play();
                            }
                        }
                        this.items.splice(i, 1);
                        this.updateDisplay();
                        return;
                    }
                }
            },
            removeAll: function() {
                var _11ae = function(node) {
                    dojo.destroy(node);
                };
                for (var i in this.items) {
                    var item = this.items[i];
                    anim = dojo.fadeOut({
                        node: $(item.id),
                        onEnd: _11ae
                    });
                    anim.play();
                }
                this.items = [];
                this.updateDisplay();
            },
            updateDisplay: function() {
                var _11af = dojo.position(this.container_div);
                var _11b0 = _11af.w;
                if (this.autowidth) {
                    var _11b1 = dojo.position($("page-content"));
                    _11b0 = _11b1.w;
                }
                var _11b2 = 0;
                var _11b3 = 0;
                var _11b4 = 0;
                for (var i in this.items) {
                    var item = this.items[i];
                    var _11b5 = item.id;
                    var _11b6 = $(_11b5);
                    if (_11b6) {
                        var _11b7 = this.itemIdToCoords(_11b4, _11b0, _11af.h, this.items.length);
                        _11b4++;
                        _11b2 = Math.max(_11b2, _11b7.x + _11b7.w);
                        _11b3 = Math.max(_11b3, _11b7.y + _11b7.h);
                        var anim = dojo.fx.slideTo({
                            node: _11b6,
                            top: _11b7.y,
                            left: _11b7.x,
                            duration: 1000,
                            unit: "px"
                        });
                        anim = this.page.transformSlideAnimTo3d(anim, _11b6, 1000, null);
                        anim.play();
                    }
                }
                if (this.autoheight) {
                    dojo.style(this.container_div, "height", _11b3 + "px");
                }
                if (this.autowidth) {
                    dojo.style(this.container_div, "width", _11b2 + "px");
                }
            },
            itemIdToCoords: function(i, _11b8, _11b9, _11ba) {
                switch (this.item_pattern) {
                case "grid":
                    return this.itemIdToCoordsGrid(i, _11b8, _11b9, _11ba);
                case "diagonal":
                    return this.itemIdToCoordsDiagonal(i, _11b8, _11b9, _11ba);
                case "verticalfit":
                    return this.itemIdToCoordsVerticalFit(i, _11b8, _11b9, _11ba);
                case "horizontalfit":
                    return this.itemIdToCoordsHorizontalFit(i, _11b8, _11b9, _11ba);
                case "ellipticalfit":
                    return this.itemIdToCoordsEllipticalFit(i, _11b8, _11b9, _11ba);
                }
            },
            itemIdToCoordsGrid: function(i, _11bb) {
                var _11bc = Math.max(1, Math.floor(_11bb / (this.item_width + this.item_margin)));
                var _11bd = Math.floor(i / _11bc);
                var res = {};
                res.y = _11bd * (this.item_height + this.item_margin);
                res.x = (i - _11bd * _11bc) * (this.item_width + this.item_margin);
                res.w = this.item_width;
                res.h = this.item_height;
                return res;
            },
            itemIdToCoordsDiagonal: function(i, _11be) {
                var res = {};
                res.y = i * this.item_margin;
                res.x = i * this.item_margin;
                res.w = this.item_width;
                res.h = this.item_height;
                return res;
            },
            itemIdToCoordsVerticalFit: function(i, _11bf, _11c0, _11c1) {
                var res = {};
                res.w = this.item_width;
                res.h = this.item_height;
                var _11c2 = _11c1 * this.item_height;
                if (_11c2 <= _11c0) {
                    var _11c3 = this.item_height;
                    var _11c4 = (_11c0 - _11c2) / 2;
                } else {
                    var _11c3 = (_11c0 - this.item_height) / (_11c1 - 1);
                    var _11c4 = 0;
                }
                res.y = Math.round(i * _11c3 + _11c4);
                res.x = 0;
                return res;
            },
            itemIdToCoordsHorizontalFit: function(i, _11c5, _11c6, _11c7) {
                var res = {};
                res.w = this.item_width;
                res.h = this.item_height;
                var _11c8 = _11c7 * this.item_width;
                if (_11c8 <= _11c5) {
                    var _11c9 = this.item_width;
                    var _11ca = (_11c5 - _11c8) / 2;
                } else {
                    var _11c9 = (_11c5 - this.item_width) / (_11c7 - 1);
                    var _11ca = 0;
                }
                res.x = Math.round(i * _11c9 + _11ca);
                res.y = 0;
                return res;
            },
            itemIdToCoordsEllipticalFit: function(i, _11cb, _11cc, _11cd) {
                var _11ce = _11cb / 2;
                var _11cf = _11cc / 2;
                var pi = 3.1415927;
                var res = {};
                res.w = this.item_width;
                res.h = this.item_height;
                var j = _11cd - (i + 1);
                if (j <= 4) {
                    var _11d0 = res.w;
                    var _11d1 = res.h * _11cf / _11ce;
                    var angle = pi + j * (2 * pi / 5);
                    res.x = _11ce + _11d0 * Math.cos(angle) - res.w / 2;
                    res.y = _11cf + _11d1 * Math.sin(angle) - res.h / 2;
                } else {
                    if (j > 4) {
                        var _11d0 = res.w * 2;
                        var _11d1 = res.h * 2 * _11cf / _11ce;
                        var angle = pi - pi / 2 + (j - 4) * (2 * pi / Math.max(10, _11cd - 5));
                        res.x = _11ce + _11d0 * Math.cos(angle) - res.w / 2;
                        res.y = _11cf + _11d1 * Math.sin(angle) - res.h / 2;
                    }
                }
                return res;
            },
            getItemNumber: function() {
                return this.items.length;
            },
            getAllItems: function() {
                var _11d2 = [];
                for (var i in this.items) {
                    _11d2.push(this.items[i].id);
                }
                return _11d2;
            }
        });
    });
}