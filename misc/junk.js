slideToObject: function(_a4e, _a4f, _a50, _a51) {
    if (_a4e === null) {
        console.error("slideToObject: mobile obj is null");
    }
    if (_a4f === null) {
        console.error("slideToObject: target obj is null");
    }
    if (typeof _a4e == "string") {
        var _a52 = $(_a4e);
    } else {
        var _a52 = _a4e;
    }
    var _a53 = this.disable3dIfNeeded();
    var tgt = dojo.position(_a4f);
    var src = dojo.position(_a4e);
    if (typeof _a50 == "undefined") {
        _a50 = 500;
    }
    if (typeof _a51 == "undefined") {
        _a51 = 0;
    }
    if (this.instantaneousMode) {
        _a51 = Math.min(1, _a51);
        _a50 = Math.min(1, _a50);
    }
    var left = dojo.style(_a4e, "left");
    var top = dojo.style(_a4e, "top");
    var _a54 = {
        x: tgt.x - src.x + (tgt.w - src.w) / 2,
        y: tgt.y - src.y + (tgt.h - src.h) / 2
    };
    var _a55 = this.getAbsRotationAngle(_a52.parentNode);
    var _a56 = this.vector_rotate(_a54, _a55);
    left = left + _a56.x;
    top = top + _a56.y;
    this.enable3dIfNeeded(_a53);
    var anim = dojo.fx.slideTo({
        node: _a4e,
        top: top,
        left: left,
        delay: _a51,
        duration: _a50,
        unit: "px"
    });
    if (_a53 !== null) {
        anim = this.transformSlideAnimTo3d(anim, _a52, _a50, _a51, _a56.x, _a56.y);
    }
    return anim;
},