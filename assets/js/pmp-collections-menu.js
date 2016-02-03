var PMP = PMP || {};

(function() {
    var $ = jQuery;

    // Models & Collections
    PMP.MultiCollection = PMP.DocCollection.extend({
        search: function(query) {
            query = _.defaults(query || {}, {
                writeable: 'true',
                profile: PMP.profile,
                limit: 100
            });
            PMP.DocCollection.prototype.search.apply(this, [query, ]);
        }
    });

    // Views
    PMP.CollectionList = PMP.BaseView.extend({

        modals: {},

        events: {
            'click .pmp-collection-modify': 'modify_collection',
            'click .pmp-collection-permissions': 'modify_permissions',
            'click .pmp-collection-default': 'set_default',
        },

        initialize: function(options) {
            options = options || {};
            this.collection = options.collection || new PMP.MultiCollection();
            this.collection.on('reset', this.render.bind(this));
            this.collection.on('error', this.renderError.bind(this));

            this.showSpinner();
            if (!options.collection)
                this.collection.search();

            PMP.BaseView.prototype.initialize.apply(this, arguments);
        },

        renderError: function(response) {
            this.hideSpinner();
            this.$el.find('#pmp-collection-list').html(response.responseJSON.message);
        },

        render: function() {
            var self = this,
                template = _.template($('#pmp-collection-items-tmpl').html());

            this.$el.find('#pmp-collection-list').html('');
            this.$el.find('#pmp-collection-list').append(template({
                collection: this.collection
            }));
            this.hideSpinner();
            return this;
        },

        modify_permissions: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                collection = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

          if (typeof this.modals[collection.get('attributes').guid] == 'undefined') {
            this.modals[collection.get('attributes').guid] = new PMP.ManageCollectionPermissionsModal({
              collectionList: this,
              collection: collection
            });
          }

          this.modals[collection.get('attributes').guid].render();
        },

        modify_collection: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                collection = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.collection_modify_modal) {
                this.collection_modify_modal = new PMP.ModifyCollectionModal({
                    collection: collection
                });
            } else
                this.collection_modify_modal.collection = collection;

            this.collection_modify_modal.render();
        },

        set_default: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                collection = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.collection_default_modal)
                this.collection_default_modal = new PMP.DefaultCollectionModal({ collection: collection });
            else
                this.collection_default_modal.collection = collection;

            this.collection_default_modal.render();
        }
    });

    PMP.CreateCollectionModal = PMP.BaseCollectionModal.extend({
        content: _.template($('#pmp-create-new-collection-form-tmpl').html(), {}),

        action: 'pmp_create_collection',

        actions: {
            'Create': 'saveCollection',
            'Cancel': 'close'
        }
    });

    PMP.ModifyCollectionModal = PMP.BaseCollectionModal.extend({
        action: 'pmp_modify_collection',

        actions: {
            'Save': 'saveCollection',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.collection = options.collection;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-modify-collection-form-tmpl').html());
            this.content = template({ collection: this.collection });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    PMP.DefaultCollectionModal = PMP.BaseCollectionModal.extend({
        action: 'pmp_default_collection',

        actions: {
            'Yes': 'saveCollection',
            'Cancel': 'close'
        },

        saveCollection: function() {
            PMP.default_collection = this.collection.get('attributes').guid;
            PMP.BaseCollectionModal.prototype.saveCollection.apply(this, arguments);
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

    PMP.ManageCollectionPermissionsModal = PMP.ManageItemsModal.extend({
        className: 'pmp-collection-permissions-modal',

        allItems: new Backbone.Collection(PMP.users.items.concat(PMP.groups.items)),

        action: 'pmp_save_collection_permissions',

        unsavedChanges: false,

        profile: 'series',

        itemType: 'users',

        getModelsForPermissionLinks: function() {
          var result = [],
              self = this;

          this.items.each(function(item, idx) {
            if (typeof item.get('links').permission !== 'undefined' && item.get('links').permission.length > 0) {
              _.each(item.get('links').permission, function(obj) {
                self.allItems.each(function(item) {
                  if (obj.href.indexOf(item.get('attributes').guid) >= 0) {
                    item.set(obj);
                    result.push(item);
                  }
                });
              })
            }
          });

          return new Backbone.Collection(result);
        },

        render: function() {
            var self = this,
                template = _.template($('#pmp-manage-items-tmpl').html());

            if (!this.items) {
                this.items = new PMP.WriteableCollection([], { profile: this.profile });
                this.items.on('reset', function() {
                    self.content = template({
                        collection: self.collection,
                        items: self.getModelsForPermissionLinks(self.items),
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
        }
    });

    PMP.CollectionPermissionsModal = PMP.BaseCollectionModal.extend({
        action: 'pmp_collection_permissions',

        actions: {
          'Save': 'savePermissions',
          'Cancel': 'close'
        }
    });

    $(document).ready(function() {
        PMP.instances = {};

        PMP.instances.collection_list = new PMP.CollectionList({
            el: $('#pmp-collection-container'),
            collection: new PMP.MultiCollection((PMP.pmp_collection)? PMP.pmp_collection.items:[])
        });

        PMP.instances.collection_list.render();

        $('#pmp-create-collection').click(function() {
            if (!PMP.instances.collection_create_modal)
                PMP.instances.collection_create_modal = new PMP.CreateCollectionModal();
            PMP.instances.collection_create_modal.render();
        });
    });
})();
