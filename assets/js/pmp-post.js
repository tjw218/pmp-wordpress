/**
 * PMP Post editor Javascript
 *
 * Based on code from WordPress core file `wp-admin/js/post.js`, near lines 418-453.
 *
 * @since 0.3
 */
var PMP = PMP || {};

(function() {
    var $ = jQuery,
        pmpsubmit = $('#pmp_document_meta');

    PMP.AsyncMenu = PMP.BaseView.extend({

        action: 'pmp_get_select_options',

        initialize: function(options) {
            PMP.BaseView.prototype.initialize.apply(this, arguments);
            this.type = options.type;
            this.template = _.template($(options.template).html());
            this.multiSelect = (typeof options.multiSelect !== 'undefined')? options.multiSelect : false;
            this.getOptions();
            return this;
        },

        getOptions: function() {
            this.showSpinner();

            var self = this,
            action = this.action,
            data = {
                action: action,
                security: PMP.ajax_nonce,
                data: JSON.stringify({
                    post_id: PMP.post_id,
                    type: this.type
                })
            };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(result) {
                    self.hideSpinner();
                    self.optionData = result;
                    self.render.apply(self);
                },
                error: function(response) {
                    alert('There was an error processing your request. Message: "' + response.responseJSON.message + '"');
                    window.location.reload(true);
                }
            };

            this.ongoing = $.ajax(opts);
            return this.ongoing;
        },

        render: function() {
            var markup = $('<div />');

            var renderedTmpl = this.template(_.extend(
              this.optionData, { multiSelect: this.multiSelect }
            ));

            markup
                .append(renderedTmpl)
                .hide()
                .appendTo(this.$el)
                .fadeIn(500);

            this.$el.find('select').chosen({disable_search_threshold: 10});

            return this;
        }
    });

    $(document).ready(function() {
        var menus = $('[data-pmp-override-type]');

        if ($('#pmp-override-defaults').length > 0) {
            menus.each(function(idx, el) {
                var type = $(el).data('pmp-override-type');

                var menu = new PMP.AsyncMenu({
                    type: type,
                    el: $(el),
                    template: '#pmp-async-select-tmpl',
                    multiSelect: true
                });
            });
        }
    });

    pmpsubmit.find(':button, :submit').on('click.edit-post', function(event) {
        var $button = $(this);

        if ($button.hasClass('disabled')) {
            event.preventDefault();
            return;
        }

        if ($button.hasClass('submitdelete') || $button.is('#post-preview'))
            return;

        $('form#post').off('submit.edit-post').on('submit.edit-post', function(event) {
            if (event.isDefaultPrevented())
                return;

            // Stop autosave
            if (wp.autosave)
                wp.autosave.server.suspend();

            $(window).off('beforeunload.edit-post');
            $button.addClass('disabled');
            pmpsubmit.find('#pmp-publish-actions .spinner').show();
        });
    });
})();
