(function() {
    var $ = jQuery,
        Modal = PMP.Modal;

    var CreateGroupModal = Modal.extend({
        content: _.template($('#pmp-create-new-group-form-tmpl').html(), {}),
        actions: {
            'Create': function() {
                return false;
            },
            'Cancel': 'close'
        }
    });

    $(document).ready(function() {
        $('#pmp-create-group').click(function() {
            var modal = new CreateGroupModal();
            modal.render();
        })
    });
})();
