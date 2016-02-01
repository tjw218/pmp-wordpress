# Managing Groups & Permissions

By default all content in the PMP is public, unless access is restricted by the publisher. Access can be limited by creating a Group, and then adding Users to the Group. See the [PMP Docs on permissions](https://support.pmp.io/guides#pmp-terminology-permissions) for more on how this works in the PMP.

The PMP WordPress plugin allows you to create a Group which will get pushed to the PMP.  After you create a new Group you can add users to it. But note that you can only add existing PMP users, that is, people or organizations who have registered for a PMP account. You can add and delete users in a group and change the name of the group, and changes will propagate to the PMP. But you can’t delete a group from the PMP plugin once you create it. 

Tip: You can see [all existing PMP users in the PMP Search portal](https://support.pmp.io/search?advanced=1&searchsort=date&profile=user).

## Creating and Managing Groups

To manage your own PMP Groups & Permissions, navigate to **Public Media Platform** > **Groups & Permissions** in the WordPress dashboard.

To create a new group, click the "Create new group" button at the top of the Groups & Permissions page. You'll be met with a "Create a group" prompt where you can specify your new group's title and tags.

![creating a new PMP group](/assets/img/create-a-group-pmp-plugin.png)

The title field is required.

The tags field should be a comma separated list. For example:

    my_first_tag, another tag, yet-another-tag

The tags will add to the data about the group in the PMP.

## Modify an Existing Group

To modify the title or tags for an existing group, click the "Modify" link below the name of the group you wish to modify. You can change the name of the group, and add users and tags, but once created you can't delete a group.

## Setting the Default Group for New Content

To set the default group to which all new content pushed to PMP will be added, click the "Set as default" link below the name of the group of your choice.

You will asked to confirm your choice:

![Confirm default group](/assets/img/set-default-group-for-pmp-push.png)

After clicking "Yes" to confirm, the confirmation prompt will close and the list of groups will update. The group you set as the default will appear with "(default)" near its name:

![Default group set](/assets/img/pmp-default-group.png)

## Managing Users

To manage the users for a group, click the "Manage users" link below the group of your choice.

You'll see a user management prompt appear:

![Manage users](/assets/img/pmp-user-dialogue.png)

### Adding Users

To add a new user, click on the text field towards the bottom of the prompt and start typing a user's name:

![Search users](/assets/img/pmp-add-user.png)

As you type a user's name, suggestions will appear below the text field. Add a user by clicking one of the suggestions that appears. The user's name will be added to the list above the search field:

![User added](/assets/img/pmp-user-added.png)

Tip: If you have any problem finding registered PMP users, you can see the [complete list of users on the PMP search portal](https://support.pmp.io/search?advanced=1&searchsort=date&profile=user).

### Removing Users

To remove a user, click the "x" to the right of their name.

### Saving Changes

Once you've added or removed users from a group, you must click the "Save" button for your changes to take effect.