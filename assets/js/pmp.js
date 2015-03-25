(function() {
    var $ = jQuery;

    // Models and Collections
    var Doc = Backbone.Model.extend({
        initialize: function(attributes, options) {
            Backbone.Model.prototype.initialize.apply(this, arguments);
            this.set('items', new DocCollection(this.get('items')));
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

        creatorAliases: CREATORS,

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

                return (this.creatorAliases[last])? this.creatorAliases[last] : null;
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
                    security: AJAX_NONCE,
                    post_data: this.toJSON()
                };

            data.post_data.attachment = (this.getImage())? this.getImage().toJSON() : null;

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(result) {
                    if (result.success)
                        window.location = result.data.edit_url;
                    return false;
                },
                error: function(response) {
                    alert('There was an error processing your request. Message: "' + response.responseJSON.message + '"');
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

    var DocCollection = Backbone.Collection.extend({
        model: Doc,

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
                    security: AJAX_NONCE,
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

    // Views
    var BaseView = Backbone.View.extend({
        showSpinner: function() {
            this.$el.find('.spinner').css('display', 'inline-block');
        },

        hideSpinner: function() {
            this.$el.find('.spinner').css('display', 'none');
        }
    });

    var SearchForm = BaseView.extend({
        el: '#pmp-search-form',

        events: {
            "submit": "submit",
            "click #pmp-show-advanced a": "advanced",
            "change input": "change",
            "change select": "change"
        },

        initialize: function() {
            this.docs = new DocCollection();
            this.results = new ResultsList({ collection: this.docs });
            this.docs.on('reset', this.hideSpinner.bind(this));
            this.docs.on('error', this.hideSpinner.bind(this));
        },

        submit: function() {
            var serialized = this.$el.serializeArray();

            var query = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    query[val.name] = val.value;
            });

            this.showSpinner();
            this.docs.search(query);

            return false;
        },

        advanced: function(e) {
            var target = $(e.currentTarget);
            target.remove();
            this.$el.find('#pmp-advanced-search').show();
            return false;
        },

        change: function(e) {
            var target = $(e.currentTarget);

            if (target.attr('name') == 'profile') {
                if (target.val() !== '' && target.val() !== 'story') {
                    this.$el.find('#pmp-content-has-search').hide();
                    this.$el.find('#pmp-content-has-search select option').prop('selected', false);
                } else {
                    this.$el.find('#pmp-content-has-search').show();
                }
            }

            return false;
        }
    });

    var ResultsList = Backbone.View.extend({
        el: '#pmp-search-results',

        initialize: function(options) {
            this.collection = (typeof options.collection != 'undefined')? options.collection : new DocCollection();

            this.collection.attributes.on('change', this.renderPagingation.bind(this));
            this.collection.on('reset', this.render.bind(this));

            this.collection.on('error', this.renderError.bind(this));
        },

        renderError: function(response) {
            if (this.pagination) {
                this.pagination.remove();
                delete(this.pagination);
            }
            this.$el.html('');
            this.$el.append('<p class="error">' + response.responseJSON.message + '</p>');
        },

        render: function() {
            var self = this;

            this.$el.find('p.error').remove();
            this.$el.find('.pmp-search-result').remove();

            template = _.template($('#pmp-search-result-tmpl').html());

            this.collection.each(function(model, idx) {
                var image = (model.getBestThumbnail())? model.getBestThumbnail().href : null;

                if (!image)
                    image = (model.getFirstEnclosure())? model.getFirstEnclosure().href : null;

                // HACK: get a MUCH smaller thumbnail for NPR images
                if (model.getCreatorAlias() == 'NPR') {
                    if (image && image.match(/media\.npr\.org/)) {
                        image = image.replace(/\.jpg$/, '-s200-c85.jpg');
                    }
                }

                var tmpl_vars = _.extend(model.toJSON(), {
                        image: image,
                        creator: model.getCreatorAlias()
                    }),
                    res = $(template(tmpl_vars));

                new ResultActions({
                    el: res.find('.pmp-result-actions'),
                    model: model
                });

                self.$el.append(res);
            });

            return this;
        },

        renderPagingation: function() {
            if (!this.pagination) {
                this.pagination = new ResultsPagination({
                    collection: this.collection
                });
                this.$el.after(this.pagination.$el);
            }
            this.pagination.render();
        }
    });

    var ResultsPagination = BaseView.extend({
        initialize: function(options) {
            this.collection = (typeof options.collection != 'undefined')? options.collection : null;
            this.collection.on('reset', this.render.bind(this));
        },

        render: function() {
            this.hideSpinner();

            var attrs = this.collection.attributes;

            this.$el.html('');
            this.$el.append(
                _.template($('#pmp-search-results-pagination-tmpl').html(), {})
            );

            if (typeof attrs.get('total') == 'undefined')
                return this;

            if (attrs.get('page') <= 1)
                this.$el.find('.prev').addClass('disabled');
            else
                this.$el.find('.prev').removeClass('disabled');

            if (attrs.get('total_pages') > 1)
                this.$el.find('.next').removeClass('disabled');

            if (attrs.get('page') >= attrs.get('total_pages'))
                this.$el.find('.next').addClass('disabled');

            this.updateCount();

            return this;
        },

        events: {
            "click a.next": "next",
            "click a.prev": "prev"
        },

        next: function(e) {
            var target = $(e.currentTarget);

            if (target.hasClass('disabled'))
                return false;

            var query = this.collection.attributes.get('query');

            query.offset = this.collection.attributes.get('offset') + this.collection.attributes.get('count');

            this.showSpinner();
            this.collection.search(query);
            return false;
        },

        prev: function(e) {
            var target = $(e.currentTarget);

            if (target.hasClass('disabled'))
                return false;

            var query = this.collection.attributes.get('query');

            query.offset = this.collection.attributes.get('offset') - this.collection.attributes.get('count');

            this.showSpinner();
            this.collection.search(query);
            return false;
        },

        updateCount: function() {
            var attrs = this.collection.attributes;

            this.$el.find('.pmp-page').html(attrs.get('page'));
            this.$el.find('.pmp-total-pages').html(attrs.get('total_pages'));
        }
    });

    var ResultActions = Backbone.View.extend({
        events: {
            "click a.pmp-draft-action": "draft",
            "click a.pmp-publish-action": "publish"
        },

        draft: function() {
            var self = this,
                args = {
                message: 'Are you sure you want to create a draft of this story?',
                actions: {
                    'Yes': function() {
                        self.modal.showSpinner();
                        self.model.draft();
                        return false;
                    },
                    'Cancel': 'close'
                }
            };

            this.renderModal(args);

            return false;
        },

        publish: function() {
            var self = this,
                args = {
                message: 'Are you sure you want to publish this story?',
                actions: {
                    'Yes': function() {
                        self.modal.showSpinner();
                        self.model.publish();
                        return false;
                    },
                    'Cancel': 'close'
                }
            };

            this.renderModal(args);

            return false;
        },

        renderModal: function(args) {
            if (!this.modal) {
                this.modal = new Modal({
                    actions: args.actions,
                    message: args.message
                });
            } else {
                this.modal.actions = args.actions;
                this.modal.message = args.message;
            }

            this.modal.render();
        }
    });

    var Modal = BaseView.extend({
        id: 'pmp-modal',

        actions: {},

        message: null,

        events: {
            "click .close": "close"
        },

        initialize: function(options) {
            var self = this;

            Backbone.View.prototype.initialize.apply(this, arguments);
            this.template = _.template($('#pmp-modal-tmpl').html());

            this.message = (typeof options.message !== 'undefined')? options.message : '';
            this.actions = (typeof options.actions !== 'undefined')? options.actions : {};

            this.setEvents();

            $('body').append(this.$el);
            $('body').append('<div id="pmp-modal-overlay" />');
        },

        render: function() {
            this.$el.html(this.template({
                message: this.message,
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
            return false;
        },

        close: function() {
            $('body').removeClass('pmp-modal-open');
            return false;
        }
    });

    $(document).ready(function() {
        window.sf = new SearchForm();
    });
})();
