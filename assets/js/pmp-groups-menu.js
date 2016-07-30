var PMP = PMP || {};

(function() {
    var $ = jQuery;

    // Models & Collections
    PMP.WriteableCollection = PMP.DocCollection.extend({

        initialize: function(models, options) {
            this.profile = options.profile;
            PMP.DocCollection.prototype.initialize.apply(this, arguments);
        },

        search: function(query) {
            query = _.defaults(query || {}, {
                writeable: 'true',
                profile: this.profile,
                limit: 9999
            });
            PMP.DocCollection.prototype.search.apply(this, [query, ]);
        }
    });

    // Views
    PMP.GroupList = PMP.BaseView.extend({

        modals: {},

        events: {
            'click .pmp-group-modify': 'modifyGroup',
            'click .pmp-group-default': 'setDefault',
            'click .pmp-manage-users': 'manageUsers'
        },

        initialize: function(options) {
            options = options || {};
            this.collection = options.collection || new PMP.WriteableCollection([], { profile: 'group' });
            this.collection.on('reset', this.render.bind(this));

            this.showSpinner();
            if (!options.collection)
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

            this.group_modify_modal = new PMP.ModifyGroupModal({ group: group });
            this.group_modify_modal.render();
        },

        setDefault: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                group = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            this.group_default_modal = new PMP.DefaultGroupModal({ group: group });
            this.group_default_modal.render();
        },

        manageUsers: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                group = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (typeof this.modals[group.get('attributes').guid] == 'undefined')
                this.modals[group.get('attributes').guid] = new PMP.ManageUsersModal({
                    collection: group,
                    collectionList: this
                });

            this.modals[group.get('attributes').guid].render();
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
                    security: PMP.ajax_nonce,
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
        content: _.template($('#pmp-create-new-group-form-tmpl').html())(),

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

        saveGroup: function() {
            PMP.default_group = this.group.get('attributes').guid;
            PMP.BaseGroupModal.prototype.saveGroup.apply(this, arguments);
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

    PMP.ManageItemsModal = PMP.Modal.extend({
        className: 'pmp-manage-items-modal',

        /**
         * Should be a Backbone Collection of all the items that can be managed
         * (i.e., added or removed from some other Document collection or
         * permissions array)
         */
        allItems: new Backbone.Collection(),

        /**
         * A string -- the ajax action name to call for the set of items in question.
         */
        action: 'pmp_not_implemented',

        events: {
          'typeahead:selected': 'addItem',
          'click .close': 'close',
          'click .remove': 'removeItem'
        },

        content: '<h2>Loading...</h2>',

        actions: {
            'Save': 'saveItems',
            'Cancel': 'close'
        },

        unsavedChanges: false,

        type: null,

        initialize: function(options) {
            var self = this;
            this.collection = options.collection;
            this.collectionList = options.collectionList;
            this.on('itemsChange', function() { self.unsavedChanges = true; });
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        close: function() {
            if (this.unsavedChanges) {
                var ret = confirm("You have unsaved changes. Are you sure you want to cancel?");
                if (ret)
                    return PMP.Modal.prototype.close.apply(this, arguments);
                else
                    return false;
            } else
                return PMP.Modal.prototype.close.apply(this, arguments);
        },

        removeItem: function(e) {
            var target = $(e.currentTarget);
                target.parent().remove();
            this.trigger('itemsChange');
        },

        itemSearch: function(query, cb) {
            var regex = new RegExp(query, 'gi');
                map = this.allItems.map(function(item) {
                    if (regex.test(item.get('attributes').title)) {
                        return {
                            title: item.get('attributes').title,
                            value: item.get('attributes').guid
                        };
                    }
                    return null;
                }),
                results = _.filter(map, function(obj) { return obj !== null; });

            return cb(results);
        },

        setupTypeahead: function() {
            this.searchForm = this.$el.find('#pmp-item-search').typeahead({
                minLength: 3, highlight: true
            }, {
                name: 'pmp-items',
                source: this.itemSearch.bind(this),
                displayKey: 'title'
            });
        },

        render: function() {
            var self = this,
                template = _.template($('#pmp-manage-items-tmpl').html());

            if (!this.items) {
                this.items = new PMP.WriteableCollection([], { profile: this.profile });
                this.items.on('reset', function() {
                    self.content = template({
                        collection: self.collection,
                        items: self.items,
                        profile: self.profile,
                        itemType: self.itemType
                    });
                    PMP.Modal.prototype.render.apply(self);

                    self.$el.find('a.Save').addClass('disabled');
                    self.on('itemsChange', self.itemsChange.bind(self));

                    self.setupTypeahead.apply(self);
                    self.hideSpinner();
                });
                PMP.Modal.prototype.render.apply(self);
                this.showSpinner();
                this.items.search({ guid: this.collection.get('attributes').guid });
            } else {
                PMP.Modal.prototype.render.apply(self);
                this.setupTypeahead();
            }
        },

        addItem: function(event, obj, selector) {
            var list = this.$el.find('#pmp-items-list'),
                tmpl = _.template('<div class="pmp-item"><%= obj.title %>' +
                                  '<input type="hidden" name="pmp-items" value="<%= obj.value %>" />' +
                                  '<span class="remove">&#10005;</span></div>');

            this.$el.find('.error').remove();
            if (list.find('input[value="' + obj.value + '"]').length > 0) {
                list.after('<p class="error">"' + obj.title + '" already exists.</p>');
                return false;
            }

            this.$el.find('#pmp-items-form').append(tmpl({ obj: obj }));
            this.searchForm.typeahead('val', null);
            this.trigger('itemsChange');
        },

        saveItems: function(e) {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var target = $(e.currentTarget);
            if (target.hasClass('disabled'))
                return false;

            var serialized = this.$el.find('form#pmp-items-form').serializeArray(),
                items_guids = _.map(serialized, function(item) { return item.value; }),
                collection_guid = this.collection.get('attributes').guid;

            var self = this,
                data = {
                    action: this.action,
                    security: PMP.ajax_nonce,
                    data: JSON.stringify({
                        collection_guid: collection_guid,
                        items_guids: items_guids
                    })
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(data) {
                    self.hideSpinner();
                    self.unsavedChanges = false;
                    self.close();
                    delete(self.collectionList.modals[collection_guid]);
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

        itemsChange: function(e) {
            this.$el.find('a.Save').removeClass('disabled');
            return false;
        }

    });

    PMP.ManageUsersModal = PMP.ManageItemsModal.extend({
        className: 'pmp-group-modal',

        allItems: new Backbone.Collection(PMP.users.items),

        action: 'pmp_save_users',

        unsavedChanges: false,

        profile: 'group',

        itemType: 'users'
    });

    $(document).ready(function() {
        PMP.instances = {};

        PMP.instances.group_list = new PMP.GroupList({
            el: $('#pmp-groups-container'),
            collection: new PMP.WriteableCollection((PMP.groups)? PMP.groups.items:[], { profile: 'group' })
        });

        PMP.instances.group_list.render();

        $('#pmp-create-group').click(function() {
            if (!PMP.instances.group_create_modal)
                PMP.instances.group_create_modal = new PMP.CreateGroupModal();
            PMP.instances.group_create_modal.render();
        });
    });
})();
