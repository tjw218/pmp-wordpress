# PMP WordPress

[![Latest Release](https://img.shields.io/github/release/publicmediaplatform/pmp-wordpress.svg)](https://github.com/publicmediaplatform/pmp-wordpress/releases/latest)

Integrate [Public Media Platform](http://publicmediaplatform.org/) with WordPress.

## Table of contents

- [Installation](#installation)
- [Settings](#settings)
- [Search for content](#search-for-content)
    - [Keyword search](#keyword-search)
- [Import Content](#import-content)
- [(Un)subscribe from content update](#unsubscribe-from-content-updates)
- [Pushing content to PMP](#pushing-content-to-pmp)
- [Groups & permissions](#groups--permissions)
    - [Create a new group](#create-a-new-group)
    - [Modify an existing group](#modify-an-existing-group)
    - [Setting the default group for new content](#setting-the-default-group-for-new-content)
    - [Managing users](#managing-users)
        - [Adding users](#adding-users)
        - [Removing users](#removing-users)
        - [Saving changes](#saving-changes)
- [Series](#series)
    - [Setting the default Series](#setting-the-default-series)
- [Properties](#properties)
    - [Setting the default Property](#setting-the-default-property)

## Installation

Follow the standard procedure for [installing WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

You can download the most recent version of the plugin by [clicking here](https://github.com/publicmediaplatform/pmp-wordpress/releases/latest).

Once the plugin files are installed, activate the plugin via the WordPress dashboard.

## Settings

To use the PMP WordPress plugin, you'll need to specify a **PMP API URL**, **Client ID** and **Client Secret** via the Public Media Platform > Settings page in the WordPress dashboard.

![Settings](http://assets.apps.investigativenewsnetwork.org/pmp/settings.png)

## Search for content

### Keyword search

Search for content by visiting the Public Media Platform > Search link in the WordPress dashboard.

Example of search results:

![Search results](http://assets.apps.investigativenewsnetwork.org/pmp/search_results.png)


## Import content

From the search results list, you can choose to create a draft post or choose to immediately publish a post based on a search result.

![Search result](http://assets.apps.investigativenewsnetwork.org/pmp/search_result.png)

After clicking one of the "Create draft" or "Publish" links, you'll see a confirmation dialog:

![Create draft](http://assets.apps.investigativenewsnetwork.org/pmp/draft_story.png)

Upon confirming, a new post will be created and you will be redirected to the post's edit page:

![Edit draft](http://assets.apps.investigativenewsnetwork.org/pmp/draft_created.png)


## (Un)Subscribe from content updates

By default, the plugin periodically checks for updates to all content that you import from PMP.

If you would like to unsubscribe from updates for a specific post, you can do so via the "PMP: Subscribe to updates" meta box on the post edit page:

![Subscribe](http://assets.apps.investigativenewsnetwork.org/pmp/subscribe.png)

To unsubscribe, simply uncheck "Subscribe to updates for this post" and click "Publish", "Update" or "Save Draft" to save.

## Pushing content to PMP

To push a new story to the PMP, navigate to Posts > Add New in the WordPress dashboard.

![Add new](http://assets.apps.investigativenewsnetwork.org/pmp/add-new.png)

Create and edit your new post as you would any other. When you are ready to publish your post, if you would like to push said post to the PMP, click the "Publish and push to PMP" button just above the default WordPress "Publish" button.

![Publish and push](http://assets.apps.investigativenewsnetwork.org/pmp/publish-and-push.png)

Once your post is published, if you make changes and would like to push your changes to PMP, click the "Update and push to PMP" button just above the default WordPress "Update" button.

![Update and push](http://assets.apps.investigativenewsnetwork.org/pmp/update-and-push.png)

## Groups & Permissions

To manage PMP Groups & Permissions, navigate to Public Media Platform > Groups & Permissions in the WordPress dashboard.

![Navigate to groups](http://assets.apps.investigativenewsnetwork.org/pmp/navigate-groups.png)

### Create a new group

To create a new group, click the "Create new group" button at the top of the Groups & Permissions page.

You'll be met with a "Create a group" prompt where you can specify your new group's title and tags.

The title field is required.

The tags field should be a comma separated list. For example:

    my_first_tag, another tag, yet-another-tag
    
### Modify an existing group

To modify the title or tags for an existing group, click the "Modify" link below the name of the group you wish to modify.

### Setting the default group for new content

To set the default group to which all new content pushed to PMP will be added, click the "Set as default" link below the name of the group of your choice.

You will asked to confirm your choice:

![Confirm default group](http://assets.apps.investigativenewsnetwork.org/pmp/confirm-default-group.png)

After clicking "Yes" to confirm, the confirmation prompt will close and the list of groups will update. The group you set as the default will appear with "(default)" near its name:

![Default group set](http://assets.apps.investigativenewsnetwork.org/pmp/default-group-set.png)

### Managing users

To manage the users for a group, click the "Manage users" link below the group of your choice.

You'll see a user management prompt appear:

![Manage users](http://assets.apps.investigativenewsnetwork.org/pmp/manage-users.png)

#### Adding users

To add a new user, click on the text field towards the bottom of the prompt and start typing a user's name:

![Search users](http://assets.apps.investigativenewsnetwork.org/pmp/search-users.png)

As you type a user's name, suggestions will appear below the text field. Add a user by clicking one of the suggestions that appears. The user's name will be added to the list above the search field:

![User added](http://assets.apps.investigativenewsnetwork.org/pmp/new-user-added.png)

#### Removing users

To remove a user, click the "x" to the right of their name.

#### Saving changes

Once you've added or removed users from a group, you must click the "Save" button for your changes to take effect.

## Series

To manage series, navigate to Public Media Platform > Series in the WordPress dashboard.

![Navigate to series](http://assets.apps.investigativenewsnetwork.org/pmp/navigate-series.png)

### Setting the default series

To set the default series to which all new content pushed to PMP will be added, click the "Set as default" link below the series of your choice.

You will asked to confirm your choice:

![Confirm default series](http://assets.apps.investigativenewsnetwork.org/pmp/confirm-default-series.png)

After clicking "Yes" to confirm, the confirmation prompt will close and the list of series will update. The series you set as the default will appear with "(default)" near its name:

![Default series set](http://assets.apps.investigativenewsnetwork.org/pmp/default-series-set.png)

## Properties

To manage properties, navigate to Public Media Platform > Properties in the WordPress dashboard.

![Navigate to series](http://assets.apps.investigativenewsnetwork.org/pmp/navigate-properties.png)

### Setting the default property

To set the default property to which all new content pushed to PMP will be added, click the "Set as default" link below the series of your choice.

You will asked to confirm your choice:

![Confirm default property](http://assets.apps.investigativenewsnetwork.org/pmp/confirm-default-property.png)

After clicking "Yes" to confirm, the confirmation prompt will close and the list of series will update. The property you set as the default will appear with "(default)" near its name:

![Default property set](http://assets.apps.investigativenewsnetwork.org/pmp/default-property-set.png)
