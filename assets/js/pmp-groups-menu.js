(function() {
    var $ = jQuery,
        Modal = PMP.Modal,
        Doc = PMP.Doc;

    var CreateGroupModal = Modal.extend({
        className: 'pmp-create-group-modal',

        content: _.template($('#pmp-create-new-group-form-tmpl').html(), {}),

        actions: {
            'Create': 'createGroup',
            'Cancel': 'close'
        },

        createGroup: function() {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var valid = this.validate();
            if (!valid) {
                alert('Please complete all required fields before submitting.');
                return false;
            }

            var serialized = this.$el.find('form').serializeArray();

            var group = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    group[val.name] = val.value;
            });

            var self = this,
                data = {
                    action: 'pmp_create_group',
                    security: AJAX_NONCE,
                    group: JSON.stringify({ attributes: group })
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(data) {
                    window.location.reload(true);
                },
                error: function() {
                    alert('Something went wrong. Please try again.');
                }
            };

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

    $(document).ready(function() {
        $('#pmp-create-group').click(function() {
            var modal = new CreateGroupModal();
            modal.render();
        })
    });
})();
