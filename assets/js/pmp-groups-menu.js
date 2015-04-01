(function() {
    var $ = jQuery,
        Modal = PMP.Modal,
        Doc = PMP.Doc;

    var CreateGroupModal = Modal.extend({
        className: 'pmp-create-group-modal',

        content: _.template($('#pmp-create-new-group-form-tmpl').html(), {}),

        actions: {
            'Create': 'createGroup',
            'Cancel': 'close'
        },

        createGroup: function() {
            var serialized = this.$el.find('form').serializeArray();

            var query = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    query[val.name] = val.value;
            });

            console.log(query);
            return false;
        }
    });

    $(document).ready(function() {
        $('#pmp-create-group').click(function() {
            var modal = new CreateGroupModal();
            modal.render();
        })
    });
})();
