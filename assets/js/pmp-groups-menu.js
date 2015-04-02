(function() {
    var $ = jQuery,
        Modal = PMP.Modal,
        Doc = PMP.Doc;

    var BaseGroupModal = Modal.extend({
        className: 'pmp-group-modal',

        saveGroup: function() {
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
                    action: this.action,
                    security: AJAX_NONCE,
                    group: JSON.stringify({ attributes: group })
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(data) {
                    self.hideSpinner();
                    window.location.reload(true);
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

    var CreateGroupModal = BaseGroupModal.extend({
        content: _.template($('#pmp-create-new-group-form-tmpl').html(), {}),

        action: 'pmp_create_group',

        actions: {
            'Create': 'saveGroup',
            'Cancel': 'close'
        }
    });

    var ModifyGroupModal = BaseGroupModal.extend({
        action: 'pmp_modify_group',

        actions: {
            'Save': 'saveGroup',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.group = options.group;
            Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-modify-group-form-tmpl').html());
            this.content = template({ group: this.group });
            Modal.prototype.render.apply(this, arguments);
        }
    });

    $(document).ready(function() {

        PMP.instances = {};

        $('#pmp-create-group').click(function() {
            if (!PMP.instances.group_create_modal)
                PMP.instances.group_create_modal = new CreateGroupModal();
            PMP.instances.group_create_modal.render();
        });

        $('.pmp-group-modify').click(function() {
            var group = {
                    guid: $(this).data('guid'),
                    title: $(this).data('title'),
                    tags: $(this).data('tags')
                };

            if (!PMP.instances.group_modify_modal)
                PMP.instances.group_modify_modal = new ModifyGroupModal({ group: group });
            else
                PMP.instances.group_modify_modal.group = group;

            PMP.instances.group_modify_modal.render();
        });
    });
})();
