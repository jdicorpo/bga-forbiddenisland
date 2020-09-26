// "ebg/stock_r": function() {
    define(["dojo", "dojo/_base/declare"], function(dojo, _113e) {
        return _113e("ebg.stock_r", null, {
            constructor: function() {
                this.page = null;
                this.container_div = null;
                this.item_height = null;
                this.item_width = null;
                this.backgroundSize = null;
                this.item_type = {};
                this.items = [];
                this.item_selected = {};
                this.next_item_id = 1;
                this.control_name = null;
                this.selectable = 2;
                this.selectionApparance = "border";
                this.apparenceBorderWidth = "1px";
                this.selectionClass = "stockitem_selected";
                this.extraClasses = "";
                this.centerItems = false;
                this.item_margin = 5;
                this.autowidth = false;
                this.order_items = true;
                this.horizontal_overlap = 0;
                this.vertical_overlap = 0;
                this.use_vertical_overlap_as_offset = false;
                this.onItemCreate = null;
                this.onItemDelete = null;
                this.jstpl_stock_item = "<div id=\"${id}\" class=\"stockitem ${extra_classes}\" style=\"top:${top}px;left:${left}px;width:${width}px;height:${height}px;${position};background-image:url('${image}');${additional_style}\"></div>";
                this.image_items_per_row = null;
                this.image_in_vertical_row = false;
                this.hResize = null;
            },
            create: function(page, _113f, _1140, _1141) {
                if (typeof _113f == "string") {
                    console.error("stock::create: second argument must be a HTML object and not a string");
                }
                if (typeof _1140 == "undefined") {
                    console.error("stock::create: item_width is undefined");
                }
                if (typeof _1141 == "undefined") {
                    console.error("stock::create: item_height is undefined");
                }
                this.page = page;
                this.container_div = _113f;
                this.item_width = _1140;
                this.item_height = _1141;
                this.control_name = _113f.id;
                if (dojo.style(this.container_div, "position") != "absolute") {
                    dojo.style(this.container_div, "position", "relative");
                }
                this.hResize = dojo.connect(window, "onresize", this, dojo.hitch(this, function(evt) {
                    this.updateDisplay();
                }));
                page.registerEbgControl(this);
            },
            destroy: function() {
                if (this.hResize !== null) {
                    dojo.disconnect(this.hResize);
                }
                this.items = {};
                this.page = null;
                this.container_div = null;
                this.control_name = null;
            },
            count: function() {
                return this.items.length;
            },
            addItemType: function(type, _1142, image, _1143) {
                if (!_1143) {
                    _1143 = 0;
                }
                this.item_type[type] = {
                    weight: toint(_1142),
                    image: image,
                    image_position: _1143
                };
            },
            addToStock: function(type, from) {
                var id = this.next_item_id;
                this.next_item_id++;
                this.addToStockWithId(type, id, from);
            },
            addToStockWithId: function(type, id, from, _1144) {
                var _1145 = {
                    id: id,
                    type: type
                };
                var _1146 = true;
                if (typeof _1144 != "undefined") {
                    if (_1144 == ":first") {
                        _1146 = false;
                    } else {
                        _1145.loc = _1144;
                    }
                }
                if ($(this.getItemDivId(id))) {
                    for (var i in this.items) {
                        var item = this.items[i];
                        if (item.id == id) {
                            this._removeFromStockItemInPosition(i);
                        }
                    }
                    dojo.destroy(this.getItemDivId(id));
                }
                if (_1146) {
                    this.items.push(_1145);
                } else {
                    this.items.unshift(_1145);
                }
                if (this.order_items) {
                    var _1147 = function(a, b) {
                        if (a.type > b.type) {
                            return 1;
                        } else {
                            if (a.type < b.type) {
                                return -1;
                            } else {
                                return 0;
                            }
                        }
                    };
                    this.sortItems();
                }
                this.updateDisplay(from);
            },
            removeFromStock: function(type, to, _1148) {
                for (var i in this.items) {
                    var item = this.items[i];
                    if (item.type == type) {
                        this._removeFromStockItemInPosition(i, to, _1148);
                        return true;
                    }
                }
                return false;
            },
            removeFromStockById: function(id, to, _1149) {
                for (var i in this.items) {
                    var item = this.items[i];
                    if (item.id == id) {
                        this._removeFromStockItemInPosition(i, to, _1149);
                        return true;
                    }
                }
                return false;
            },
            _removeFromStockItemInPosition: function(i, to, _114a) {
                var _114b = function(node) {
                    dojo.destroy(node);
                };
                var item = this.items[i];
                if (this.onItemDelete) {
                    this.onItemDelete(this.getItemDivId(item.id), item.type, item.id);
                }
                this.items.splice(i, 1);
                var _114c = this.getItemDivId(item.id);
                this.unselectItem(item.id);
                item_div = $(_114c);
                if (typeof to != "undefined") {
                    var anim = dojo.fx.chain([this.page.slideToObject(item_div, to), dojo.fadeOut({
                        node: item_div,
                        onEnd: _114b
                    })]).play();
                } else {
                    dojo.fadeOut({
                        node: item_div,
                        onEnd: _114b
                    }).play();
                }
                dojo.addClass(item_div, "to_be_destroyed");
                if (_114a !== true) {
                    this.updateDisplay();
                }
            },
            removeAll: function() {
                for (var i in this.items) {
                    var item = this.items[i];
                    if (this.onItemDelete) {
                        this.onItemDelete(this.getItemDivId(item.id), item.type, item.id);
                    }
                }
                this.items = [];
                this.item_selected = {};
                this.next_item_id = 1;
                dojo.empty(this.control_name);
            },
            removeAllTo: function(to) {
                var ids = [];
                for (var i in this.items) {
                    ids.push(this.items[i].id);
                }
                for (var i in ids) {
                    this.removeFromStockById(ids[i], to, true);
                }
                this.updateDisplay();
            },
            getPresentTypeList: function() {
                var _114d = {};
                for (var i in this.items) {
                    var item = this.items[i];
                    _114d[item.type] = 1;
                }
                return _114d;
            },
            getItemDivId: function(id) {
                return this.control_name + "_item_" + id;
            },
            updateDisplay: function(from) {
                if (!$(this.control_name)) {
                    return;
                }
                var _114e = dojo.marginBox(this.control_name);
                var _114f = this.item_width;
                var _1150 = 0;
                var _1151 = "auto";
                if (this.horizontal_overlap != 0) {
                    _114f = Math.round(this.item_width * this.horizontal_overlap / 100);
                    _1150 = this.item_width - _114f;
                    _1151 = 1;
                }
                var _1152 = 0;
                if (this.vertical_overlap != 0) {
                    _1152 = Math.round(this.item_height * this.vertical_overlap / 100) * (this.use_vertical_overlap_as_offset ? 1 : -1);
                }
                var _1153 = _114e.w;
                if (this.autowidth) {
                    var _1154 = dojo.marginBox($("page-content"));
                    _1153 = _1154.w;
                }
                var _1155 = 0;
                var _1156 = 0;
                var _1157 = 0;
                var _1158 = Math.max(1, Math.floor((_1153 - _1150) / (_114f + this.item_margin)));
                var _1159 = 0;
                var _115a = 0;
                var n = 0;
                for (var i in this.items) {
                    var item = this.items[i];
                    var _115b = this.getItemDivId(item.id);
                    if (_1151 != "auto") {
                        _1151++;
                    }
                    if (typeof item.loc == "undefined") {
                        var _115c = Math.floor(n / _1158);
                        _1159 = Math.max(_1159, _115c);
                        _1155 = _1159 * (this.item_height + _1152 + this.item_margin);
                        _1156 = (n - _1159 * _1158) * (_114f + this.item_margin);
                        _115a = Math.max(_115a, _1156 + _114f);
                        if (this.vertical_overlap != 0 && n % 2 == 0 && this.use_vertical_overlap_as_offset) {
                            _1155 += _1152;
                        }
                        if (this.centerItems) {
                            var _115d = (_115c == Math.floor(this.count() / _1158) ? this.count() % _1158 : _1158);
                            _1156 += (_1153 - _115d * (_114f + this.item_margin)) / 2;
                        }
                        n++;
                    } else {}
                    var _115e = $(_115b);
                    if (_115e) {
                        if (typeof item.loc == "undefined") {
                            dojo.fx.slideTo({
                                node: _115e,
                                top: _1155,
                                left: _1156,
                                duration: 1000,
                                unit: "px"
                            }).play();
                        } else {
                            this.page.slideToObject(_115e, item.loc, 1000).play();
                        }
                        if (_1151 != "auto") {
                            dojo.style(_115e, "zIndex", _1151);
                        }
                    } else {
                        var type = this.item_type[item.type];
                        if (!type) {
                            console.error("Stock control: Unknow type: " + type);
                        }
                        if (typeof _115b == "undefined") {
                            console.error("Stock control: Undefined item id");
                        } else {
                            if (typeof _115b == "object") {
                                console.error("Stock control: Item id with 'object' type");
                                console.error(_115b);
                            }
                        }
                        additional_style = "";
                        if (this.backgroundSize !== null) {
                            additional_style += "background-size:" + this.backgroundSize;
                        }
                        var _115f = dojo.trim(dojo.string.substitute(this.jstpl_stock_item, {
                            id: _115b,
                            width: this.item_width,
                            height: this.item_height,
                            top: _1155,
                            left: _1156,
                            image: type.image,
                            position: (_1151 == "auto") ? "" : ("z-index:" + _1151),
                            extra_classes: this.extraClasses,
                            additional_style: additional_style
                        }));
                        dojo.place(_115f, this.control_name);
                        _115e = $(_115b);
                        if (typeof item.loc != "undefined") {
                            this.page.placeOnObject(_115e, item.loc);
                        }
                        if (this.selectable == 0) {
                            dojo.addClass(_115e, "stockitem_unselectable");
                        }
                        dojo.connect(_115e, "onclick", this, "onClickOnItem");
                        if (toint(type.image_position) !== 0) {
                            var _1160 = 0;
                            var _1161 = 0;
                            if (this.image_items_per_row) {
                                var row = Math.floor(type.image_position / this.image_items_per_row);
                                if (!this.image_in_vertical_row) {
                                    _1160 = (type.image_position - (row * this.image_items_per_row)) * 100;
                                    _1161 = row * 100;
                                } else {
                                    _1161 = (type.image_position - (row * this.image_items_per_row)) * 100;
                                    _1160 = row * 100;
                                }
                                dojo.style(_115e, "backgroundPosition", "-" + _1160 + "% -" + _1161 + "%");
                            } else {
                                _1160 = type.image_position * 100;
                                dojo.style(_115e, "backgroundPosition", "-" + _1160 + "% 0%");
                            }
                        }
                        if (this.onItemCreate) {
                            this.onItemCreate(_115e, item.type, _115b);
                        }
                        if (typeof from != "undefined") {
                            this.page.placeOnObject(_115e, from);
                            if (typeof item.loc == "undefined") {
                                var anim = dojo.fx.slideTo({
                                    node: _115e,
                                    top: _1155,
                                    left: _1156,
                                    duration: 1000,
                                    unit: "px"
                                });
                                anim = this.page.transformSlideAnimTo3d(anim, _115e, 1000, null);
                                anim.play();
                            } else {
                                this.page.slideToObject(_115e, item.loc, 1000).play();
                            }
                        } else {
                            dojo.style(_115e, "opacity", 0);
                            dojo.fadeIn({
                                node: _115e
                            }).play();
                        }
                    }
                }
                var _1162 = (_1159 + 1) * (this.item_height + _1152 + this.item_margin);
                dojo.style(this.control_name, "height", _1162 + "px");
                if (this.autowidth) {
                    if (_115a > 0) {
                        _115a += (this.item_width - _114f);
                    }
                    dojo.style(this.control_name, "width", _115a + "px");
                }
            },
            resetItemsPosition: function() {
                this.updateDisplay();
            },
            changeItemsWeight: function(_1163) {
                for (var type in _1163) {
                    var _1164 = _1163[type];
                    if (this.item_type[type]) {
                        this.item_type[type].weight = _1164;
                    } else {
                        console.error("unknow item type" + type);
                    }
                }
                this.sortItems();
                this.updateDisplay();
            },
            sortItems: function() {
                var _1165 = dojo.hitch(this, function(a, b) {
                    if (this.item_type[a.type].weight > this.item_type[b.type].weight) {
                        return 1;
                    } else {
                        if (this.item_type[a.type].weight < this.item_type[b.type].weight) {
                            return -1;
                        } else {
                            return 0;
                        }
                    }
                });
                this.items.sort(_1165);
            },
            setSelectionMode: function(mode) {
                if (mode != this.selectable) {
                    this.unselectAll();
                    this.selectable = mode;
                    if (mode == 0) {
                        dojo.query("#" + this.control_name + " .stockitem").addClass("stockitem_unselectable");
                    } else {
                        dojo.query("#" + this.control_name + " .stockitem_unselectable").removeClass("stockitem_unselectable");
                    }
                }
            },
            setSelectionAppearance: function(mode) {
                this.unselectAll();
                this.selectionApparance = mode;
            },
            isSelected: function(id) {
                if (this.item_selected[id]) {
                    if (this.item_selected[id] == 1) {
                        return true;
                    }
                }
                return false;
            },
            selectItem: function(id) {
                var _1166 = $(this.getItemDivId(id));
                if (this.selectionApparance == "border") {
                    dojo.style(_1166, "borderWidth", this.apparenceBorderWidth);
                } else {
                    if (this.selectionApparance == "disappear") {
                        dojo.fadeOut({
                            node: _1166
                        }).play();
                    } else {
                        if (this.selectionApparance == "class") {
                            dojo.addClass(_1166, this.selectionClass);
                        }
                    }
                }
                this.item_selected[id] = 1;
            },
            unselectItem: function(id) {
                var _1167 = $(this.getItemDivId(id));
                if (this.selectionApparance == "border") {
                    dojo.style(_1167, "borderWidth", "0px");
                } else {
                    if (this.selectionApparance == "disappear") {
                        dojo.fadeIn({
                            node: _1167
                        }).play();
                    } else {
                        if (this.selectionApparance == "class") {
                            dojo.removeClass(_1167, this.selectionClass);
                        }
                    }
                }
                this.item_selected[id] = 0;
            },
            selectAll: function() {
                var _1168 = false;
                for (var i in this.items) {
                    if (!this.isSelected(this.items[i].id)) {
                        this.selectItem(this.items[i].id);
                        _1168 = true;
                    }
                }
                if (_1168) {
                    this.onChangeSelection(this.control_name);
                }
            },
            unselectAll: function() {
                var _1169 = false;
                for (var i in this.items) {
                    if (this.isSelected(this.items[i].id)) {
                        this.unselectItem(this.items[i].id);
                        _1169 = true;
                    }
                }
                if (_1169) {
                    this.onChangeSelection(this.control_name);
                }
            },
            onClickOnItem: function(evt) {
                evt.stopPropagation();
                if (this.selectable !== 0) {
                    var _116a = (this.control_name + "_item_").length;
                    var _116b = evt.currentTarget.id.substr(_116a);
                    if (this.isSelected(_116b)) {
                        this.unselectItem(_116b);
                    } else {
                        if (this.selectable === 1) {
                            this.unselectAll();
                        }
                        this.selectItem(_116b);
                    }
                    this.onChangeSelection(this.control_name, _116b);
                }
            },
            onChangeSelection: function(_116c, _116d) {},
            getSelectedItems: function() {
                var _116e = [];
                for (var i in this.items) {
                    var item = this.items[i];
                    if (this.isSelected(item.id)) {
                        _116e.push(item);
                    }
                }
                return _116e;
            },
            getUnselectedItems: function() {
                var _116f = [];
                for (var i in this.items) {
                    var item = this.items[i];
                    if (!this.isSelected(item.id)) {
                        _116f.push(item);
                    }
                }
                return _116f;
            },
            getItemNumber: function() {
                return this.items.length;
            },
            getAllItems: function() {
                var _1170 = [];
                for (var i in this.items) {
                    _1170.push(this.items[i]);
                }
                return _1170;
            },
            getItemsByType: function(type) {
                var _1171 = [];
                for (var i in this.items) {
                    if (this.items[i].type === type) {
                        _1171.push(this.items[i]);
                    }
                }
                return _1171;
            },
            getFirstItemOfType: function(type) {
                for (var i in this.items) {
                    if (this.items[i].type === type) {
                        return this.items[i];
                    }
                }
                return null;
            },
            getItemsByWeight: function(_1172) {
                var _1173 = [];
                for (var i in this.items) {
                    if (this.item_type[this.items[i].type].weight === _1172) {
                        _1173.push(this.items[i]);
                    }
                }
                return _1173;
            },
            getFirstItemWithWeight: function(_1174) {
                for (var i in this.items) {
                    if (this.item_type[this.items[i].type].weight === _1174) {
                        return this.items[i];
                    }
                }
                return null;
            },
            getItemById: function(_1175) {
                for (var i in this.items) {
                    if (this.items[i].id == _1175) {
                        return this.items[i];
                    }
                }
                return null;
            },
            getItemTypeById: function(_1176) {
                for (var i in this.items) {
                    if (this.items[i].id == _1176) {
                        return this.items[i].type;
                    }
                }
                return null;
            },
            getItemWeightById: function(_1177) {
                for (var i in this.items) {
                    if (this.items[i].id == _1177) {
                        return this.item_type[this.items[i].type].weight;
                    }
                }
                return null;
            },
            selectItemsByType: function(type) {
                for (var i in this.items) {
                    if (this.items[i].type == type && !this.isSelected(this.items[i].id)) {
                        this.selectItem(this.items[i].id);
                    }
                }
            },
            unselectItemsByType: function(type) {
                for (var i in this.items) {
                    if (this.items[i].type == type && this.isSelected(this.items[i].id)) {
                        this.unselectItem(this.items[i].id);
                    }
                }
            },
            setOverlap: function(_1178, _1179) {
                this.horizontal_overlap = _1178;
                if (typeof _1179 == "undefined") {
                    this.vertical_overlap = 0;
                } else {
                    this.vertical_overlap = _1179;
                }
                this.updateDisplay();
            },
            resizeItems: function(width, _117a, _117b, _117c) {
                this.item_height = _117a;
                this.item_width = width;
                dojo.query("#" + this.control_name + " .stockitem").style("width", width + "px");
                dojo.query("#" + this.control_name + " .stockitem").style("height", _117a + "px");
                if (typeof _117b != "undefined" && typeof _117c != "undefined") {
                    dojo.query("#" + this.control_name + " .stockitem").style("backgroundSize", _117b + "px " + _117c + "px");
                    this.backgroundSize = _117b + "px " + _117c + "px";
                }
                this.updateDisplay();
            },
        });
    });
// }