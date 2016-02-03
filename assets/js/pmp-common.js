var PMP = PMP || {};

(function() {
    var $ = jQuery;

    // Models and Collections
    PMP.Doc = Backbone.Model.extend({
        initialize: function(attributes, options) {
            Backbone.Model.prototype.initialize.apply(this, arguments);
            this.set('items', new PMP.DocCollection(this.get('items')));
        },

        profileAliases: {
            'ef7f170b-4900-4a20-8b77-3142d4ac07ce': 'audio',
            '8bf6f5ae-84b1-4e52-a744-8e1ac63f283e': 'contributor',
            '42448532-7a6f-47fb-a547-f124d5d9053e': 'episode',
            '5f4fe868-5065-4aa2-86e6-2387d2c7f1b6': 'image',
            '88506918-b124-43a8-9f00-064e732cbe00': 'property',
            'c07bd70c-8644-4c5d-933a-40d5d7032036': 'series',
            'b9ce545e-01a2-44d0-9a15-a73da4ed304b': 'story',
            '3ffa207f-cfbe-4bcd-987c-0bd8e29fdcb6': 'topic',
            '85115aa1-df35-4324-9acd-2bb261f8a541': 'video',
        },

        getProfile: function() {
            var links = this.get('links');

            if (links) {
                var profile = links.profile;

                if (typeof profile !== 'undefined') {
                    if (typeof profile[0] !== 'undefined')
                        return profile[0];
                    else
                        return null;
                }
            }
            return null;
        },

        getProfileAlias: function() {
            var link = this.getProfile();
            if (link && link.href) {
                var guidOrAlias = link.href.split('/');
                guidOrAlias = _.last(guidOrAlias);
                return (this.profileAliases[guidOrAlias])? this.profileAliases[guidOrAlias] : guidOrAlias;
            } else
                return null;
        },

        getCreator: function() {
            var links = this.get('links');

            if (links) {
                var creator = links.creator;

                if (typeof creator[0] !== 'undefined')
                    return creator[0];
                else
                    return null;
            }
            return null;
        },

        getCreatorAlias: function() {
            var creator = this.getCreator();

            if (creator && creator.href) {
                var parts = creator.href.split('/'),
                    last = _.last(parts);

                return (PMP.creators[last])? PMP.creators[last] : null;
            } else
                return null;
        },

        getImage: function() {
            if (this.getProfileAlias() == 'image')
                return this;

            return this.get('items').find(function(item) {
                if (item.getProfileAlias() == 'image')
                    return item;
            });
        },

        getImageCrop: function(crop) {
            var image = this.getImage(),
                ret;

            if (image) {
                ret = _.find(image.get('links').enclosure, function(enc) {
                    if (enc.meta && enc.meta.crop == crop)
                        return enc;
                });
            }
            return ret;
        },

        getBestThumbnail: function() {
            var thumbnail = null,
                sizes = ['small', 'thumb', 'standard', 'primary'];

            for (var idx in sizes) {
                thumbnail = this.getImageCrop(sizes[idx]);
                if (thumbnail) { break; }
            }

            if (!thumbnail && this.getImage()) {
                var fallback = this.getImage();
                thumbnail = fallback.get('links').enclosure[0];
            }

            return thumbnail;
        },

        getFirstEnclosure: function() {
            return (this.get('links').enclosure)? this.get('links').enclosure[0] : null;
        },

        draft: function() {
            this.createPost(true);
            return false;
        },

        publish: function() {
            this.createPost(false);
            return false;
        },

        createPost: function(draft) {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var self = this,
                action = (draft)? 'pmp_draft_post' : 'pmp_publish_post',
                data = {
                    action: action,
                    security: PMP.ajax_nonce,
                    pmp_guid: this.attributes.attributes.guid
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(result) {
                    self.trigger(action + '_success');
                    return false;
                },
                error: function(response) {
                    alert('There was an error processing your request. Message: "' + response.responseJSON.message + '"');
                    self.trigger(action + '_error');
                    window.location.reload(true);
                }
            };

            this.ongoing = $.ajax(opts);
            return this.ongoing;
        },

        toJSON: function() {
            var attrs = _.clone(this.attributes);
            attrs.items = attrs.items.toJSON();
            return attrs;
        }
    });

    PMP.DocCollection = Backbone.Collection.extend({
        model: PMP.Doc,

        initialize: function() {
            this.attributes = new Backbone.Model();
            Backbone.Collection.prototype.initialize.apply(this, arguments);
        },

        search: function(query) {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var self = this,
                data = {
                    action: 'pmp_search',
                    security: PMP.ajax_nonce,
                    query: JSON.stringify(query)
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(result) {
                    if (result.success) {
                        self.reset(result.data.items);

                        var attrs = _.extend({
                            query: query
                        }, result.data);

                        self.attributes.set(attrs);
                    }
                },
                error: function(response) {
                    self.trigger('error', response);
                }
            };

            this.ongoing = $.ajax(opts);

            return this.ongoing;
        }
    });

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
    PMP.BaseView = Backbone.View.extend({
        showSpinner: function() {
            this.$el.find('.spinner').css({display: 'inline-block', visibility: 'visible'});
        },

        hideSpinner: function() {
            this.$el.find('.spinner').css({display: 'none', visibility: 'hidden'});
        }
    });

    PMP.Modal = PMP.BaseView.extend({
        actions: null,

        content: null,

        events: {
            "click .close": "close"
        },

        initialize: function(options) {
            var self = this;

            this.$el.addClass('pmp-modal');

            Backbone.View.prototype.initialize.apply(this, arguments);
            this.template = _.template($('#pmp-modal-tmpl').html());

            if (!this.content)
                this.content = (typeof options.content !== 'undefined')? options.content : '';

            if (!this.actions)
                this.actions = (typeof options.actions !== 'undefined')? options.actions : {};

            this.setEvents();

            $('body').append(this.$el);
            if ($('#pmp-modal-overlay').length == 0)
                $('body').append('<div id="pmp-modal-overlay" />');
        },

        render: function() {
            this.$el.html(this.template({
                content: this.content,
                actions: this.actions
            }));
            this.setEvents();
            this.open();
        },

        setEvents: function() {
            var events = {};
            _.each(this.actions, function(v, k) { events['click .' + k] = v; });
            this.delegateEvents(_.extend(this.events, events));
        },

        open: function() {
            $('body').addClass('pmp-modal-open');
            this.$el.removeClass('hide');
            this.$el.addClass('show');
            return false;
        },

        close: function() {
            $('body').removeClass('pmp-modal-open');
            this.$el.removeClass('show');
            this.$el.addClass('hide');
            return false;
        }
    });


    PMP.BaseCollectionModal = PMP.Modal.extend({
        className: 'pmp-collection-modal',

        saveCollection: function() {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var valid = this.validate();
            if (!valid) {
                alert('Please complete all required fields before submitting.');
                return false;
            }

            var serialized = this.$el.find('form').serializeArray();

            var collection = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    collection[val.name] = val.value;
            });

            var self = this,
                data = {
                    action: this.action,
                    security: PMP.ajax_nonce,
                    collection: JSON.stringify({ attributes: collection }),
                    profile: PMP.profile
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(data) {
                    self.hideSpinner();
                    self.close();
                    PMP.instances.collection_list.showSpinner();
                    PMP.instances.collection_list.collection.search();
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
            this.collectionList = (typeof options.collectionList !== 'undefined' ) ? options.collectionList : null;
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
                name: 'pmp-item-guids',
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
                                  '<input type="hidden" name="pmp-item-guids" value="<%= obj.value %>" />' +
                                  '<span class="remove">&#10005;</span></div>');

            this.$el.find('.error').remove();
            if (list.find('input[value="' + obj.value + '"]').length > 0) {
                list.after('<p class="error">"' + obj.title + '" already exists.</p>');
                return false;
            }

            this.$el.find('#pmp-items-form').prepend(tmpl({ obj: obj }));
            this.searchForm.typeahead('val', null);
            this.trigger('itemsChange');
        },

        saveItems: function(e) {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var target = $(e.currentTarget);
            if (target.hasClass('disabled'))
                return false;

            var collection_guid = this.collection.get('attributes').guid,
                serialized = this.$el.find('form#pmp-items-form :input').serializeArray(),
                names = _.uniq(_.map(serialized, function(item) { return item.name; } )),
                values = {};

            // Build out object with keys/values for each of the input names
            // in the form#pmp-items-form element. All of these values wil;
            // be sent over the wire.
            _.each(serialized, function(item, idx) {
              if (!values[item.name]) {
                values[item.name] = item.value;
              } else if (values[item.name]) {
                if (values[item.name].constructor !== Array) {
                  var temp = values[item.name];
                  values[item.name] = [];
                  values[item.name].push(temp);
                }
                values[item.name].push(item.value);
              }
            });

            var self = this,
                data = {
                    action: this.action,
                    security: PMP.ajax_nonce,
                    data: JSON.stringify({
                        collection_guid: collection_guid,
                        values: values
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
                    if (self.collectionList) {
                      delete(self.collectionList.modals[collection_guid]);
                    }
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

})();
