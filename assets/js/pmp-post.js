/**
 * PMP Post editor Javascript
 *
 * Based on code from WordPress core file `wp-admin/js/post.js`, near lines 418-453.
 *
 * @since 0.3
 */
(function() {
    var $ = jQuery,
        pmpsubmit = $('#pmp_document_meta');

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
