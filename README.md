# PMP WordPress

[![Build Status](https://travis-ci.org/publicmediaplatform/pmp-wordpress.svg?branch=master)](https://travis-ci.org/publicmediaplatform/pmp-wordpress)
[![Latest Release](https://img.shields.io/github/release/publicmediaplatform/pmp-wordpress.svg)](https://github.com/publicmediaplatform/pmp-wordpress/releases/latest)

Integrate [Public Media Platform](http://publicmediaplatform.org/) with WordPress.

Also see this project in the [official Wordpress plugin directory](https://wordpress.org/plugins/public-media-platform/).

Built by the [INN Nerds](http://nerds.inn.org/).

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
- [Saved search queries](#saved-search-queries)
    - [Automatically importing posts based on a saved search query](#automatically-importing-posts-based-on-a-saved-search-query)
    - [Saving a search query](#saving-a-search-query)
        - [Auto-assign categories to imported posts](#auto-assign-categories-to-imported-posts)
    - [Viewing and editing a search query](#viewing-and-editing-a-search-query)
    - [View all saved search queries](#view-all-saved-search-queries)

## Installation

Follow the standard procedure for [automatic plugin installation](https://codex.wordpress.org/Managing_Plugins#Automatic_Plugin_Installation), and search for "PMP" or "Public Media Platform".  Using the [official plugin](https://wordpress.org/plugins/search.php?type=term&q=PMP) from the Wordpress plugin directory allows you to automatically get updates.

If you'd prefer the bleeding edge `master` version of the plugin, you'll have to install it manually, following the standard procedure for [manual plugin installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).  You can get the [latest code zip here](https://github.com/publicmediaplatform/phpsdk/archive/master.zip).

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

If you would like to unsubscribe from updates for a specific post, you can do so via the "PMP: Document information" meta box on the post edit page:

![Subscribe](http://assets.apps.investigativenewsnetwork.org/pmp/subscribe.png)

To unsubscribe, simply uncheck "Subscribe to updates for this post" and click "Publish", "Update" or "Save Draft" to save.

## Pushing content to PMP

To push a new story to the PMP, navigate to Posts > Add New in the WordPress dashboard.

![Add new](http://assets.apps.investigativenewsnetwork.org/pmp/add-new.png)

Create and edit your new post as you would any other. When you are ready to publish your post, click the "Publish" button as you normally would.

![Before publishing](http://assets.apps.investigativenewsnetwork.org/pmp/before-publish.png)

If you would like to push said post to the PMP, click the "Push to PMP" button in the "PMP: Document information" meta box after your post is published. 

![After publishing](http://assets.apps.investigativenewsnetwork.org/pmp/after-publish.png)

Once your post is published, if you make changes and would like to push your changes to PMP, click the "Push to PMP" button located in the "PMP: Document information" meta box.

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

## Saved search queries

### Automatically importing posts based on a saved search query

You can save search queries to make it easier to view results and import new content in the future.

Optionally, when saving or editing a search query, you can specify that new search results be automatically imported. For search results that are automatically imported, the PMP plugin can either:

- Create draft posts from results for the saved query
- Publish posts from results for the saved query

Note: the default behavior for saved search queries is to do nothing with search results.

### Saving a search query

Start by visiting the Public Media Platform > Search link in the WordPress dashboard as you would to [search for content](#search-for-content).

Configure the search form to your liking. For example:

![Saved search query start](http://assets.apps.investigativenewsnetwork.org/pmp/saved_search_query.png)

Once your have your query configured and are ready to save, click the "Save query" button.

A modal window will appear, asking you to specify a title for the saved search query.

You will also be presented the opportunity to specify what action to take with new results for the saved search query.

At this point, you might also like to [auto-assign categories to imported posts](#auto-assign-categories-to-imported-posts).

Once you have specified a title and an action, click the "Save" button.

See:

![Saved search query modal](http://assets.apps.investigativenewsnetwork.org/pmp/saved_search_query_modal.png)

#### Auto-assign categories to imported posts

When saving a search query, you also have the opportunity to set categories to be applied to all posts automatically imported for said query.

For example, if you want to construct a search query for "Chicago marketplace" and funnel all posts found for that query to your site's "News" category, you would:

1. Ensure you have a "News" category on your site by navigating to Posts > Categories in the WordPress dashboard.
2. Navigate to Public Media Platform > Search and perform the steps to save a search query [outlined above](#saving-s-search-query).
3. Before clicking the "Save" button to save your query, select the "News" category from the category list in the "Save the current query" dialog.

See:

![Saved search query select categories](http://assets.apps.investigativenewsnetwork.org/pmp/saved_search_select_categories.png)

### Viewing and editing a search query

After clicking the "Save" button to save a search query, the page will automatically reload and present you with your newly saved query:

![Saved search query edit](http://assets.apps.investigativenewsnetwork.org/pmp/saved_search_query_edit.png)

Note the notice above the search form that reads:

    "Viewing saved query: "Chicago (PRI)"

Also note in the location where there was previously a "Save query" button, you are now presented with an "Edit query" button.

Clicking the "Edit query" button allows you to change your preferences for the saved query.

For instance, if you wanted to change the action taken for new results for the saved query to automatically publish, you would:

- Click "Edit query"
- In the modal window that appears, select "Publish posts from results for this query"
- Click the "Save" button

See:

![Saved search query edit modal](http://assets.apps.investigativenewsnetwork.org/pmp/saved_search_query_edit_modal.png)

### View all saved search queries

To see all saved search queries, visit Public Media Platform > Manage saved searches link in the WordPress dashboard.

![Manage saved searches](http://assets.apps.investigativenewsnetwork.org/pmp/manage_saved_searches.png)

From there, you see the complete list of saved search queries:

![Manage saved searches list](http://assets.apps.investigativenewsnetwork.org/pmp/manage_saved_searches_list.png)

Actions you can take for each saved search query include:

- "View and edit this search query"
- "Delete"

Clicking "View and edit this search query" will land you on the page described in the [Viewing and editing a search query](#viewing-and-editing-a-search-query) section of the documentation.

Clicking "Delete" will present you will a confirmation modal:

![Delete a saved search query](http://assets.apps.investigativenewsnetwork.org/pmp/saved_search_delete_confirm.png)
