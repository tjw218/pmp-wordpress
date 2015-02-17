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

                if (typeof profile[0] !== 'undefined')
                    return profile[0];
                else
                    return null;
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
                    if (enc.meta.crop == crop)
                        return enc;
                });
            }
            return ret;
        }
    });

    var DocCollectionAttributes = Backbone.Model.extend({
        initialize: function() {
            Backbone.Model.prototype.initialize.apply(this, arguments);
            this.sync = this.not_implemented;
            this.save = this.not_implemented;
            this.fetch = this.not_implemented;
            this.destroy = this.not_implemented;
        },

        not_implemented: function() {
            throw 'Not implemented';
        }
    });

    var DocCollection = Backbone.Collection.extend({
        model: Doc,

        initialize: function() {
            Backbone.Collection.prototype.initialize.apply(this, arguments);
            this.attributes = new DocCollectionAttributes();
        },

        search: function(query) {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var self = this;

            query = _.extend({ action: 'pmp_search' }, query);

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: query,
                method: 'post',
                success: function(result) {
                    if (result.success) {
                        self.reset(result.data.items);

                        var _attrs = {
                            query: query
                        };
                        _.each(result.data, function(v, k) {
                            if (k !== 'items')
                                _attrs[k] = v;
                        });

                        self.attributes.clear();
                        self.attributes.set(_attrs);
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
    var SearchForm = Backbone.View.extend({
        el: '#pmp-search-form',

        events: {
            "submit": "submit",
            "click #pmp-show-advanced a": "advanced"
        },

        initialize: function() {
            this.docs = new DocCollection();
            this.results = new ResultsList({ collection: this.docs });
        },

        submit: function() {
            var serialized = this.$el.serializeArray();

            var query = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    query[val.name] = val.value;
            });

            this.docs.search(query);

            return false;
        },

        advanced: function(e) {
            var target = $(e.currentTarget);
            target.remove();
            this.$el.find('#pmp-advanced-search').show();
            return false;
        }
    });

    var ResultsList = Backbone.View.extend({
        el: '#pmp-search-results',

        initialize: function(options) {
            this.collection = (typeof options.collection != 'undefined')? options.collection : new DocCollection();
            this.collection.attributes.on('change', this.render.bind(this));
            this.collection.on('error', this.renderError.bind(this));
        },

        renderError: function(response) {
            this.$el.html('');
            this.$el.append('<p class="error">' + response.responseJSON.message + '</p>');
        },

        render: function() {
            var self = this;

            this.$el.html('');

            template = _.template($('#pmp-search-result-tmpl').html());

            this.collection.each(function(model, idx) {
                var image = (model.getImageCrop('square'))? model.getImageCrop('square').href : null,
                    tmpl_vars = _.extend(model.toJSON(), {
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

            this.pagination = new ResultsPagination({
                collection: this.collection
            });
            this.$el.append(this.pagination.$el);
            this.pagination.render();

            return this;
        }
    });

    var ResultsPagination = Backbone.View.extend({
        initialize: function(options) {
            this.collection = (typeof options.collection != 'undefined')? options.collection : null;
            this.collection.on('reset', this.render.bind(this));
        },

        render: function() {
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
            query.offset = this.collection.attributes.get('offset') + 1;
            this.collection.search(query);
            return false;
        },

        prev: function(e) {
            var target = $(e.currentTarget);

            if (target.hasClass('disabled'))
                return false;

            var query = this.collection.attributes.get('query');
            query.offset = this.collection.attributes.get('offset') - 1;
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
            console.log('draft');
            //this.model.draft();
            return false;
        },

        publish: function() {
            console.log('publish');
            //this.model.publish();
            return false;
        }
    });

    $(document).ready(function() {
        window.sf = new SearchForm();
    });
})();
