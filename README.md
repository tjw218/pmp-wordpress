# PMP WordPress

[![Latest Release](https://img.shields.io/github/release/publicmediaplatform/pmp-wordpress.svg)](https://github.com/publicmediaplatform/pmp-wordpress/releases/latest)

Integrate [Public Media Platform](http://publicmediaplatform.org/) with WordPress.

## Using the plugin

### Installation

Follow the standard procedure for [installing WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

You can download the most recent version of the plugin by [clicking here](https://github.com/publicmediaplatform/pmp-wordpress/releases/latest).

Once the plugin files are installed, activate the plugin via the WordPress dashboard.

### Settings

To use the PMP WordPress plugin, you'll need to specify a **PMP API URL**, **Client ID** and **Client Secret** via the Public Media Platform > Settings page in the WordPress dashboard.

![Settings](http://assets.apps.investigativenewsnetwork.org/pmp/settings.png)

### Search for content

#### Keyword search

Search for content by visiting the Public Media Platform > Search link in the WordPress dashboard.

Example of search results:

![Search results](http://assets.apps.investigativenewsnetwork.org/pmp/search_results.png)


### Import content

From the search results list, you can choose to create a draft post or choose to immediately publish a post based on a search result.

![Search result](http://assets.apps.investigativenewsnetwork.org/pmp/search_result.png)

After clicking one of the "Create draft" or "Publish" links, you'll see a confirmation dialog:

![Create draft](http://assets.apps.investigativenewsnetwork.org/pmp/draft_story.png)

Upon confirming, a new post will be created and you will be redirected to the post's edit page:

![Edit draft](http://assets.apps.investigativenewsnetwork.org/pmp/draft_created.png)


### (Un)Subscribe from content updates

By default, the plugin periodically checks for updates to all content that you import from PMP.

If you would like to unsubscribe from updates for a specific post, you can do so via the "PMP: Subscribe to updates" meta box on the post edit page:

![Subscribe](http://assets.apps.investigativenewsnetwork.org/pmp/subscribe.png)

To unsubscribe, simply uncheck "Subscribe to updates for this post" and click "Publish", "Update" or "Save Draft" to save.
