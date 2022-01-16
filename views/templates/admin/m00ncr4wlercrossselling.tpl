<div id="product-crossselling" class="panel product-tab">
    <input type="hidden" name="submitted_tabs[]" value="CrossSelling"/>
    <input type="hidden" name="inputCrossSellingProducts" id="inputCrossSellingProducts" value="" />
    <input type="hidden" name="inputCrossSellingGroups" id="inputCrossSellingGroups" value="" />

    <div class="panel-heading tab">
        {l s='Grouped Cross Selling' mod='m00ncr4wlercrossselling'}
    </div>
    <div class="row">
        <div class="form-group">
            <label class="control-label col-lg-3" for="cross_selling_autocomplete_input">
                {l s='Cross-Selling Product' mod='m00ncr4wlercrossselling'}
            </label>
            <div class="col-lg-9">
                <div id="ajax_choose_product">
                    <div class="input-group">
                        <input type="text" id="cross_selling_autocomplete_input" name="cross_selling_autocomplete_input" />
                        <span class="input-group-addon"><i class="icon-search"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Cross-Selling Group' mod='m00ncr4wlercrossselling'}
            </label>
            <div class="col-lg-9">
                <input type="text" id="inputTagifyGroups" name="inputTagifyGroups"  value="" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3"></label>
            <div class="col-lg-9">
                <div id="divCrossSelling" class="well">
                    <div class="alert alert-info">
                        {l s='Don\'t forget to save!' mod='m00ncr4wlercrossselling'}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminProducts')}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
        <button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save'}</button>
        <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save and stay'}</button>
    </div>
    <script type="text/javascript">
        {literal}
        hideOtherLanguage(default_language);

        $(function () {
            function CrossSelling() {
                var _;
                var public;
                var private;

                _ = {
                    inputAutocomplete: '#cross_selling_autocomplete_input',
                    divCrossSelling: '#divCrossSelling',
                    inputCrossSellingProducts: '#inputCrossSellingProducts',
                    inputCrossSellingGroups: '#inputCrossSellingGroups',
                    inputTagifyGroups: '#inputTagifyGroups'
                };

                public = {
                    'setOption': function (id, value) {
                        if (typeof id === 'string') {
                            _[id] = value;
                        }
                    },
                    'setOptions': function (options) {
                        if (typeof options === 'object') {
                            _ = $.extend(_, options);
                        }
                    },
                    'getOption': function (id) {
                        return _[id];
                    },
                    init: function () {
                        private._initTagify();
                        private._autocompleteProducts('ajax_products_list.php');
                        private._autocompleteGroups('{/literal}{$link->getAdminLink('AdminCrossSelling')}{literal}&ajax=1&action=getGroups&id_lang={/literal}{$id_lang}{literal}');
                        private._addEventHandler();
                    }
                };

                private = {
                    _initTagify: function () {
                        $(_.inputTagifyGroups).tagify({
                            delimiters: [],
                            addTagPrompt: '{/literal}{l s='Add Group' mod='m00ncr4wlercrossselling' js=1}{literal}'
                        });
                    },
                    _autocompleteProducts: function (url) {
                        var options = {
                            max: 20,
                            autoFill: false,
                            minChars: 1,
                            matchContains: true,
                            mustMatch: true,
                            scroll: false,
                            cacheLength: 0,
                            extraParams: {
                                format: 'json'
                            },
                            dataType: 'json',
                            parse: function (data) {
                                var parsed = [];
                                if (data == null)
                                    return [];

                                for (var i = 0; i < data.length; i++) {
                                    dataRow = {
                                        id: data[i].id,
                                        image: data[i].image,
                                        name: data[i].name,
                                        ref: data[i].ref
                                    };
                                    parsed[i] = {
                                        data: dataRow,
                                        value: data[i].id,
                                        result: data[i].name
                                    };
                                }
                                return parsed;
                            },
                            formatItem: function (item) {
                                return private._leadingZeros(item.id) + " - " + item.ref + " " + item.name;
                            },
                            formatMatch: function (item) {
                                return private._leadingZeros(item.id) + " - " + item.ref + " " + item.name;
                            },
                            formatResult: function (item) {
                                return item.name;
                            }
                        };

                        $(_.inputAutocomplete).autocomplete(url, options).result(private._addCrossSelling);
                    },
                    _autocompleteGroups: function (url) {
                        var options = {
                            max: 20,
                            autoFill: false,
                            minChars: 1,
                            matchContains: true,
                            mustMatch: true,
                            scroll: false,
                            cacheLength: 0,
                            position: {of: $(_.inputTagifyGroups).tagify('containerDiv')},
                            extraParams: {
                                format: 'json'
                            },
                            dataType: 'json',
                            parse: function (data) {
                                var parsed = [];
                                if (data == null)
                                    return [];

                                for (var i = 0; i < data.length; i++) {
                                    dataRow = {
                                        id: data[i].id,
                                        name: data[i].name,
                                        position: data[i].position
                                    };
                                    parsed[i] = {
                                        data: dataRow,
                                        value: data[i].id,
                                        result: data[i].name
                                    };
                                }
                                return parsed;
                            },
                            formatItem: function (item) {
                                return private._leadingZeros(item.id) + " - " + item.name;
                            },
                            formatMatch: function (item) {
                                return private._leadingZeros(item.id) + " - " + item.name;
                            },
                            formatResult: function (item) {
                                return item.name;
                            }
                        };

                        $(_.inputTagifyGroups).tagify('inputField').autocomplete(url, options).result(private._addCrossSellingGroup);
                    },
                    _addEventHandler: function () {
                        $(_.divCrossSelling).on('click', '.delCrossSelling', function () {
                            private._delCrossSelling(this);
                        });
                    },
                    _leadingZeros: function (number) {
                        if (number < 10)
                            return "00" + number;
                        if (number < 100)
                            return "0" + number;
                        return number;
                    },
                    _addCrossSelling: function (event, data, formatted) {
                        $(_.inputAutocomplete).val('');

                        if (data == null)
                            return false;

                        var _html = '<div class="form-control-static">' +
                                '<button type="button" class="delCrossSelling btn btn-default" data-id="' + data.id + '"><i class="icon-remove text-danger"></i></button>' +
                                '&nbsp;' + data.ref + ' - ' + data.name +
                                '</div>';

                        $(_.divCrossSelling).append(_html);
                        private._add(_.inputCrossSellingProducts, data.id);
                    },
                    _delCrossSelling: function (self) {
                        private._del(_.inputCrossSellingProducts, $(self).data('id'));
                        $(self).parent().empty().remove();
                    },
                    _addCrossSellingGroup: function (event, data, formatted) {
                        if (data == null)
                            return false;
                        $(_.inputTagifyGroups).tagify('add');
                        $(_.inputTagifyGroups).tagify('containerDiv').find('span:last>a').attr('data-id', data.id).addClass('delCrossSellingGroup').click(private._delCrossSellingGroup);
                        private._add(_.inputCrossSellingGroups, data.id);
                    },
                    _delCrossSellingGroup: function (event) {
                        private._del(_.inputCrossSellingGroups, $(event.currentTarget).data('id'));
                    },
                    _del: function (key, value) {
                        $(key).val($(key).val().replace(value + '-', ''));
                    },
                    _add: function (key, value) {
                        $(key).val($(key).val() + value + '-');
                    }
                };

                return public;
            }

            var cs = CrossSelling();
            cs.init();
        });
        {/literal}
    </script>
</div>