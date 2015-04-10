var PMP = PMP || {};

(function() {
    var $ = jQuery;

    // Models & Collections
    PMP.GroupCollection = PMP.DocCollection.extend({
        search: function(query) {
            query = _.defaults(query || {}, {
                writeable: 'true',
                profile: 'group',
                limit: 100
            });
            PMP.DocCollection.prototype.search.apply(this, [query, ]);
        }
    });

    // Views
    PMP.GroupList = PMP.BaseView.extend({

        events: {
            'click .pmp-group-modify': 'modify_group',
            'click .pmp-group-default': 'set_default',
            'click .pmp-manage-users': 'manage_users'
        },

        initialize: function(options) {
            options = options || {};
            this.collection = options.collection || new PMP.GroupCollection();
            this.collection.attributes.on('change', this.render.bind(this));

            this.showSpinner();
            this.collection.search();

            PMP.BaseView.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var self = this,
                template = _.template($('#pmp-groups-items-tmpl').html());

            this.$el.append(template({ groups: this.collection }));
            this.hideSpinner();
            return this;
        },

        modify_group: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                group = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.group_modify_modal)
                this.group_modify_modal = new PMP.ModifyGroupModal({ group: group });
            else
                this.group_modify_modal.group = group;

            this.group_modify_modal.render();
        },

        set_default: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                group = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.group_default_modal)
                this.group_default_modal = new PMP.DefaultGroupModal({ group: group });
            else
                this.group_default_modal.group = group;

            this.group_default_modal.render();
        },

        manage_users: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                group = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.manage_users_modal)
                this.manage_users_modal = new PMP.ManageUsersModal({ group: group });
            else
                this.manage_users_modal.group = group;

            this.manage_users_modal.render();
        }
    });

    PMP.BaseGroupModal = PMP.Modal.extend({
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

    PMP.CreateGroupModal = PMP.BaseGroupModal.extend({
        content: _.template($('#pmp-create-new-group-form-tmpl').html(), {}),

        action: 'pmp_create_group',

        actions: {
            'Create': 'saveGroup',
            'Cancel': 'close'
        }
    });

    PMP.ModifyGroupModal = PMP.BaseGroupModal.extend({
        action: 'pmp_modify_group',

        actions: {
            'Save': 'saveGroup',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.group = options.group;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-modify-group-form-tmpl').html());
            this.content = template({ group: this.group });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    PMP.DefaultGroupModal = PMP.BaseGroupModal.extend({
        action: 'pmp_default_group',

        actions: {
            'Yes': 'saveGroup',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.group = options.group;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-default-group-form-tmpl').html());
            this.content = template({ group: this.group });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    PMP.ManageUsersModal = PMP.Modal.extend({
        className: 'pmp-group-modal',

        initialize: function(options) {
            this.group = options.group;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var self = this,
                template = _.template($('#pmp-manage-users-tmpl').html()),
                group_data = _.filter(GROUPS, function(x) {
                    return x.attributes.title == self.group.title;
                })[0];

            console.log(group_data);

            this.content = template({
                group: this.group,
                users: group_data.links
            });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    $(document).ready(function() {

        PMP.instances = {};

        PMP.instances.group_list = new PMP.GroupList({ el: $('#pmp-groups-container') });

        $('#pmp-create-group').click(function() {
            if (!PMP.instances.group_create_modal)
                PMP.instances.group_create_modal = new PMP.CreateGroupModal();
            PMP.instances.group_create_modal.render();
        });
    });
})();
