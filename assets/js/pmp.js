(function() {
    var $ = jQuery,
        search_form = $('#pmp-search-form');

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

    var render_results_list = function(data) {
        var results_container = $('#pmp-search-results'),
            template = _.template($('#pmp-search-result-tmpl').html());

        _.each(data.items, function(item, idx) {
            results_container.append(template(item.attributes));
        });
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
