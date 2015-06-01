var PMP = PMP || {};

(function() {
    var $ = jQuery;

    var EditSearchModal = PMP.Modal.extend({
        id: 'pmp-save-query-modal',

        action: 'pmp_save_query',

        actions: {
            'Save': 'saveQuery',
            'Cancel': 'close'
        },

        initialize: function(options)  {
            this.searchForm = options.searchForm;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        content: _.template($('#pmp-save-query-tmpl').html(), {}),

        validate: function() {
            var inputs = this.$el.find('form input'),
                valid = true;

            _.each(inputs, function(v, i) {
                if (!v.validity.valid)
                    valid = false;
            });

            return valid;
        },

        saveQuery: function() {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var valid = this.validate();
            if (!valid) {
                alert('Please specify a query title before saving.');
                return false;
            }

            var serialized = this.$el.find('form').serializeArray();

            var formData = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    formData[val.name] = val.value;
            });

            var self = this,
                data = {
                    action: this.action,
                    security: PMP.ajax_nonce,
                    data: JSON.stringify({
                        options: formData,
                        query: this.searchForm.last_query
                    })
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(data) {
                    self.hideSpinner();
                    self.close();
                },
                error: function() {
                    self.hideSpinner();
                    alert('Something went wrong. Please try again.');
                }
            };

            this.showSpinner();
            this.ongoing = $.ajax(opts);
            return this.ongoing;
        }
    });

    $(document).ready(function() {
    });
})();
