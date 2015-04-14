var PMP = PMP || {};

(function() {
    var $ = jQuery;

    // Models & Collections
    PMP.SeriesCollection = PMP.DocCollection.extend({
        search: function(query) {
            query = _.defaults(query || {}, {
                writeable: 'true',
                profile: 'series',
                limit: 100
            });
            PMP.DocCollection.prototype.search.apply(this, [query, ]);
        }
    });

    // Views
    PMP.SeriesList = PMP.BaseView.extend({

        events: {
            'click .pmp-series-modify': 'modify_series',
            'click .pmp-series-default': 'set_default',
        },

        initialize: function(options) {
            options = options || {};
            this.collection = options.collection || new PMP.SeriesCollection();
            this.collection.on('reset', this.render.bind(this));
            this.collection.on('error', this.renderError.bind(this));

            this.showSpinner();
            if (!options.collection)
                this.collection.search();

            PMP.BaseView.prototype.initialize.apply(this, arguments);
        },

        renderError: function(response) {
            this.hideSpinner();
            this.$el.find('#pmp-series-list').html(response.responseJSON.message);
        },

        render: function() {
            var self = this,
                template = _.template($('#pmp-series-items-tmpl').html());

            this.$el.find('#pmp-series-list').html('');
            this.$el.find('#pmp-series-list').append(template({
                collection: this.collection
            }));
            this.hideSpinner();
            return this;
        },

        modify_series: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                series = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.series_modify_modal) {
                this.series_modify_modal = new PMP.ModifySeriesModal({
                    series: series
                });
            } else
                this.series_modify_modal.series = series;

            this.series_modify_modal.render();
        },

        set_default: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                series = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.collection_default_modal)
                this.collection_default_modal = new PMP.DefaultSeriesModal({ series: series });
            else
                this.collection_default_modal.series = series;

            this.collection_default_modal.render();
        }
    });

    PMP.BaseSeriesModal = PMP.Modal.extend({
        className: 'pmp-series-modal',

        saveSeries: function() {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var valid = this.validate();
            if (!valid) {
                alert('Please complete all required fields before submitting.');
                return false;
            }

            var serialized = this.$el.find('form').serializeArray();

            var series = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    series[val.name] = val.value;
            });

            var self = this,
                data = {
                    action: this.action,
                    security: AJAX_NONCE,
                    series: JSON.stringify({ attributes: series })
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(data) {
                    self.hideSpinner();
                    self.close();
                    PMP.instances.series_list.showSpinner();
                    PMP.instances.series_list.collection.search();
                },
                error: function() {
                    self.hideSpinner();
                    alert('Something went wrong. Please try again.');
                }
            };

            this.showSpinner();
            this.ongoing = $.ajax(opts);
            return this.ongoing;
        },

        validate: function() {
            var inputs = this.$el.find('form input'),
                valid = true;

            _.each(inputs, function(v, i) {
                if (!v.validity.valid)
                    valid = false;
            });

            return valid;
        }
    });

    PMP.CreateSeriesModal = PMP.BaseSeriesModal.extend({
        content: _.template($('#pmp-create-new-series-form-tmpl').html(), {}),

        action: 'pmp_create_series',

        actions: {
            'Create': 'saveSeries',
            'Cancel': 'close'
        }
    });

    PMP.ModifySeriesModal = PMP.BaseSeriesModal.extend({
        action: 'pmp_modify_series',

        actions: {
            'Save': 'saveSeries',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.series = options.series;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-modify-series-form-tmpl').html());
            this.content = template({ series: this.series });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    PMP.DefaultSeriesModal = PMP.BaseSeriesModal.extend({
        action: 'pmp_default_series',

        actions: {
            'Yes': 'saveSeries',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.series = options.series;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-default-series-form-tmpl').html());
            this.content = template({ series: this.series });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    $(document).ready(function() {
        PMP.instances = {};

        PMP.instances.series_list = new PMP.SeriesList({
            el: $('#pmp-series-container'),
            collection: new PMP.SeriesCollection(PMP_SERIES.items)
        });

        PMP.instances.series_list.render();

        $('#pmp-create-series').click(function() {
            if (!PMP.instances.series_create_modal)
                PMP.instances.series_create_modal = new PMP.CreateSeriesModal();
            PMP.instances.series_create_modal.render();
        });
    });
})();
