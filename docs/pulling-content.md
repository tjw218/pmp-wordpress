# Pulling Content from the PMP

## Keyword Search

Search for content by visiting the **Public Media Platform** > **Search** link in the WordPress dashboard. Perform a simple search by entering keywords in the search field. If you want to search by a specific phrase, use quotes around the phrase, like "Chicago fire".

You can also limit the search to story titles etc by using these prefixes:

- title:”search term”
- teaser:”search term”
- tags:”search term”
- content:”search term”
- byline:”name”

Here's an example of search results for Chicago.  Since we didn't use a prefix, this search will return stories that have "Chicago" anywhere in the story:

![Search results](/assets/img/pmp-search-in-largo-chicago.png)

## Advanced Search

You can further refine a search for PMP content by clicking "Show advanced options" and filtering by several options. Select a content creator, search by media type, tag, PMP GUID, or PMP collection GUID (the GUIDs can be found using [the PMP search portal](https://support.pmp.io/)). 

You can filter by content creator and add a tag to refine your search even further:

![Advanced Search](/assets/img/pmp-advanced-search-in-largo.png)

The search results give you a pretty good preview of available stories. Each story has a link to its preview page on the PMP:

![Story link to preview page on the PMP](/assets/img/pmp-story-in-largo-search-results-link.png)

You can click this link to see the story summary on the PMP:

![Story link to preview page on the PMP](/assets/img/search-result-on-pmp.png)

If you want to see the full story on the public media site where it was original published, click the headline on the PMP story summary:

![Story on the Hear and Now website](/assets/img/here-and-new-story.png)

Note that you can [save a search query for reuse later](#saved-search-queries). So once you set up a search that returns stories for a particular content source or topic, you can run the saved search to import more matching stories in the future. 

## Import Content

Now to the important part: Pulling PMP content into your WordPress site from the search results. From the search results list, you can choose to create a draft post or choose to immediately publish a post based on a search result.

![Search result](/assets/img/pmp-story-in-largo-search-results.png)

After clicking one of the "Create draft" or "Publish" links, you'll see a confirmation dialog:

![Search result dialogue box](/assets/img/save-draft-dialogue-pmp-largo.png)

Upon confirming, a new post will be created and you will be redirected to the post's edit page:

![Editing draft of PMP story in WordPress](/assets/img/draft-pmp-post-in-largo.png)

You can now edit the PMP story in the usual WordPress way. Note that if you subscribe to updates for this post (which is enabled by default), any future changes to the PMP story will automatically overwrite your existing post.

## (Un)Subscribe from Content Updates

By default, the plugin periodically checks for updates to all content that you import from PMP. Any updates to stories in the PMP will automatically update the corresponding posts on your site. Typically this is useful, but if you've edited the post for your site you might want to disable PMP updates. 

You can disable automatic updates to each post via the **PMP: Document information** meta box on the post edit page:

![PMP plugin settings for subscribing to post updates](/assets/img/post-pmp-subscribe-settings.png)

To unsubscribe to updates, simply uncheck "Subscribe to updates for this post" and then click "Publish", "Update" or "Save Draft" to save.