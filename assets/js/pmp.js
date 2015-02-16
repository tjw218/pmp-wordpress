(function() {
    var $ = jQuery,
        search_form = $('#pmp-search-form');

    // Models and Collections
    var Doc = Backbone.Model.extend();

    var DocCollection = Backbone.Collection.extend();

    // Views
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

    // Utils
    var search = function(query, callback) {
        query = _.extend({ action: 'pmp_search' }, query);

        var opts = {
            url: ajaxurl,
            dataType: 'json',
            data: query,
            method: 'post'
        };

        if (typeof callback == 'function')
            opts.success = callback;

        var ret = $.ajax(opts);

        return ret;
    };

    var render_results_list = function(result) {
        $('#pmp-search-results').html('');

        if (result.success) {
            var collection = new Backbone.Collection(_.values(result.data.items) || []),
                results_container = $('#pmp-search-results'),
                template = _.template($('#pmp-search-result-tmpl').html());

            collection.each(function(model, idx) {
                console.log(model);

                var res = $(template(model.toJSON()));

                new ResultActions({
                    el: res.find('.pmp-result-actions'),
                    model: model
                });

                results_container.append(res);
            });
        } else {
            // TODO: Show error message
        }
    };

    search_form.on('submit', function() {
        var serialized = $(this).serializeArray();

        var data = {};
        _.each(serialized, function(val, idx) {
            data[val.name] = val.value;
        });

        search(data, render_results_list);
        return false;
    });
})();
