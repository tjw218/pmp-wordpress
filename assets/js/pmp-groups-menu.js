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
            'click .pmp-group-modify': 'modifyGroup',
            'click .pmp-group-default': 'setDefault',
            'click .pmp-manage-users': 'manageUsers'
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

            this.$el.find('#pmp-groups-list').html('');
            this.$el.find('#pmp-groups-list').append(template({ groups: this.collection }));
            this.hideSpinner();
            return this;
        },

        modifyGroup: function(e) {
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

        setDefault: function(e) {
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

        manageUsers: function(e) {
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
                    self.close();
                    PMP.instances.group_list.showSpinner();
                    PMP.instances.group_list.collection.search();
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

        users: new Backbone.Collection(PMP_USERS.items),

        events: {
            'typeahead:selected': 'addUser',
            "click .close": "close",
            "click .remove": 'removeUser'
        },

        actions: {
            'Save': 'saveUsers',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.group = options.group;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var self = this,
                template = _.template($('#pmp-manage-users-tmpl').html());

            this.content = template({ group: this.group });
            PMP.Modal.prototype.render.apply(this, arguments);

            this.$el.find('a.Save').addClass('disabled');
            this.on('usersChange', this.usersChange.bind(this));

            this.searchForm = this.$el.find('#pmp-user-search').typeahead({
                minLength: 3, highlight: true
            }, {
                name: 'pmp-users',
                source: this.userSearch.bind(this),
                displayKey: 'title'
            });
        },

        userSearch: function(query, cb) {
            var regex = new RegExp(query, 'gi');
                map = this.users.map(function(user) {
                    if (regex.test(user.get('attributes').title)) {
                        return {
                            title: user.get('attributes').title,
                            value: user.get('attributes').guid
                        };
                    }
                    return null;
                }),
                results = _.filter(map, function(obj) { return obj !== null; });

            return cb(results);
        },

        addUser: function(event, obj, selector) {
            var tmpl = _.template('<div class="pmp-user"><%= obj.title %>' +
                                  '<input type="hidden" name="pmp-users[]" value="<%= obj.value %>" />' +
                                  '<span class="remove">&#10005;</span></div>');
            this.$el.find('#pmp-users-form').append(tmpl({ obj: obj }));
            this.searchForm.typeahead('val', null);
            this.$el.find('.error').remove();
            this.trigger('usersChange');
        },

        removeUser: function(e) {
            var target = $(e.currentTarget);
                target.parent().remove();
            this.trigger('usersChange');
        },

        saveUsers: function(e) {
            var target = $(e.currentTarget);

            if (target.hasClass('disabled'))
                return false;

            return false;
        },

        usersChange: function(e) {
            this.$el.find('a.Save').removeClass('disabled');
            return false;
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
