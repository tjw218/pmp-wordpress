var PMP = PMP || {};

(function() {
    var $ = jQuery;

    // Models & Collections
    PMP.SeriesPropertiesCollection = PMP.DocCollection.extend({
        search: function(query) {
            query = _.defaults(query || {}, {
                writeable: 'true',
                profile: 'property',
                limit: 100
            });
            PMP.DocCollection.prototype.search.apply(this, [query, ]);
        }
    });

    // Views
    PMP.SeriesPropertiesList = PMP.BaseView.extend({

        events: {
            'click .pmp-series-property-modify': 'modify_series_property',
            'click .pmp-series-property-default': 'set_default',
        },

        initialize: function(options) {
            options = options || {};
            this.collection = options.collection || new PMP.SeriesPropertiesCollection();
            this.collection.attributes.on('change', this.render.bind(this));

            this.showSpinner();
            this.collection.search();

            PMP.BaseView.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var self = this,
                template = _.template($('#pmp-series-properties-items-tmpl').html());

            this.$el.find('#pmp-series-properties-list').html('');
            this.$el.find('#pmp-series-properties-list').append(template({
                collection: this.collection
            }));
            this.hideSpinner();
            return this;
        },

        modify_series_property: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                series_property = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.series_property_modify_modal) {
                this.series_property_modify_modal = new PMP.ModifySeriesPropertyModal({
                    series_property: series_property
                });
            } else
                this.series_property_modify_modal.group = group;

            this.series_property_modify_modal.render();
        },

        set_default: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                collection = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.collection_default_modal)
                this.collection_default_modal = new PMP.DefaultGroupModal({ collection: collection });
            else
                this.collection_default_modal.collection = collection;

            this.collection_default_modal.render();
        }
    });

    PMP.BaseSeriesPropertyModal = PMP.Modal.extend({
        className: 'pmp-series-property-modal',

        saveSeriesProperty: function() {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var valid = this.validate();
            if (!valid) {
                alert('Please complete all required fields before submitting.');
                return false;
            }

            var serialized = this.$el.find('form').serializeArray();

            var series_property = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    series_property[val.name] = val.value;
            });

            var self = this,
                data = {
                    action: this.action,
                    security: AJAX_NONCE,
                    series_property: JSON.stringify({ attributes: series_property })
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(data) {
                    self.hideSpinner();
                    self.close();
                    PMP.instances.series_properties_list.showSpinner();
                    PMP.instances.series_properties_list.collection.search();
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

    PMP.CreateSeriesPropertyModal = PMP.BaseSeriesPropertyModal.extend({
        content: _.template($('#pmp-create-new-series-property-form-tmpl').html(), {}),

        action: 'pmp_create_series_property',

        actions: {
            'Create': 'saveSeriesProperty',
            'Cancel': 'close'
        }
    });

    PMP.ModifySeriesProperty = PMP.BaseSeriesPropertyModal.extend({
        action: 'pmp_modify_series_property',

        actions: {
            'Save': 'saveSeriesProperty',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.collection = options.collection;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-modify-series-property-form-tmpl').html());
            this.content = template({ collection: this.collection });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    PMP.DefaultCollectionModal = PMP.BaseSeriesPropertyModal.extend({
        action: 'pmp_default_collection',

        actions: {
            'Yes': 'saveSeriesProperty',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.collection = options.collection;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-default-collection-form-tmpl').html());
            this.content = template({ collection: this.collection });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    $(document).ready(function() {

        PMP.instances = {};

        //PMP.instances.series_properties_list = new PMP.SeriesPropertiesList({ el: $('#pmp-series-properties-container') });

        $('#pmp-create-group').click(function() {
            if (!PMP.instances.series_property_create_modal)
                PMP.instances.series_property_create_modal = new PMP.CreateSeriesPropertyModal();
            PMP.instances.series_property_create_modal.render();
        });
    });
})();
