<?php

/**
 * A wrapper for PMP SDK with conveniences for the plugin
 *
 * @since 0.1
 */
class SDKWrapper {

	public $sdk;

	public function __construct() {
		$settings = get_option('pmp_settings');

		$this->sdk = new \Pmp\Sdk(
			$settings['pmp_api_url'],
			$settings['pmp_client_id'],
			$settings['pmp_client_secret']
		);
	}

	public function __call($name, $args) {
		return call_user_func_array(array($this->sdk, $name), $args);
	}

	/**
	 * Convenience method cleans up query results data and returns serializable version for use with
	 * Backbone.js models and collections.
	 *
	 * @param $method (string) The query method to call (i.e., queryDocs or queryGroups, etc.)
	 * @param $arguments (array) The options to be pased to the query method.
	 *
	 * @since 0.1
	 */
	public function query2json() {
		$args = func_get_args();
		$method = $args[0];
		$args_array = array_slice($args, 1);
		$result = call_user_func_array(array($this, $method), $args_array);

		if (empty($result)) {
			return $result;
		} else if (preg_match('/^fetch.*$/', $method)) {
			$data = $this->prepFetchData($result);
		} else {
			$data = $this->prepQueryData($result);
		}

		if (isset($args_array[0]) && isset($args_array[0]['limit']))
			$limit = $args_array[0]['limit'];

		$data['items'] = $this->populateEditLinks($data['items']);

		return $data;
	}

	/**
	 * For each $item in the $items array, check for an existing WP post with the same PMP GUID and
	 * add a `_wp_edit_link` attribute to the $item if one exists.
	 *
	 * @since 0.3
	 */
	public function populateEditLinks($items) {
		$post_ids_and_guids = $this->getPmpPostIdsAndGuids();

		$populated = $items;
		foreach ($items as $idx => $item) {
			foreach ($post_ids_and_guids as $result) {
				if ($item['attributes']['guid'] == $result['meta_value']) {
					$populated[$idx]['attributes']['_wp_edit_link'] = get_edit_post_link($result['post_id']);
					break;
				}
			}
		}

		return $populated;
	}

	/**
	 * Get the `post_id` and `pmp_guid` for all existing posts that originate from the PMP
	 *
	 * @since 0.3
	 */
	public function getPmpPostIdsAndGuids() {
		global $wpdb;
		return $wpdb->get_results(
			"select post_id, meta_value from {$wpdb->postmeta} where meta_key = 'pmp_guid'", ARRAY_A);
	}

	/**
	 * Prep results from calls to SDK 'fetch*' methods.
	 *
	 * @since 0.2
	 */
	public static function prepFetchData($result) {
		// There should only be 1 result when using `fetch*` methods
		$data = array(
			"total" => 1,
			"count" => 1,
			"page" => 1,
			"offset" => 0,
			"total_pages" => 1
		);

		$links = (array) $result->links;
		unset($links['auth']);
		unset($links['query']);

		$item = array(
			'attributes' => (array) $result->attributes,
			'links' => $links
		);

		$items = $result->items();
		if ($items) {
			foreach ($items as $related_item) {
				$related_links = (array) $related_item->links;
				unset($related_links['auth']);
				unset($related_links['query']);

				$item['items'][] = array(
					'links' => $related_links,
					'items' => (array) $related_item->items,
					'attributes' => (array) $related_item->attributes
				);
			}
		}

		$data['items'][] = $item;
		return $data;
	}

	/**
	 * Prep results from calls to SDK 'query*' methods.
	 *
	 * @since 0.2
	 */
	public static function prepQueryData($result) {
		$items = $result->items();
		$data = array(
			"total" => $result->items()->totalItems(),
			"count" => $result->items()->count(),
			"page" => $result->items()->pageNum(),
			"offset" => ($result->items()->pageNum() - 1) * $result->items()->count(),
			"total_pages" => $result->items()->totalPages()
		);

		if ($items) {
			foreach ($items as $item) {
				$links = (array) $item->links;

				unset($links['auth']);
				unset($links['query']);

				$data['items'][] = array(
					'links' => $links,
					'items' => (array) $item->items,
					'attributes' => (array) $item->attributes
				);
			}
		}
		return $data;
	}

	/**
	 * Convenience method that takes a guid and returns a full href for said guid
	 *
	 * @since 0.2
	 */
	public function href4guid($guid) {
		$link = $this->sdk->home->link(\Pmp\Sdk::FETCH_DOC);
		return $link->expand(array('guid' => $guid));
	}

	/**
	 * Get the guid from a PMP href
	 *
	 * @since 0.2
	 */
	public static function guid4href($href) {
		$test = preg_match('/\/([\d\w-]+)$/', $href, $matches);
		return $matches[1];
	}

	/**
	 * Convert a comma-delimited list into an array suitable for use an an attribute of a CollectionDocJson
	 *
	 * @since 0.2
	 */
	public static function commas2array($string) {
		return array_map(
			function($tag) { return trim($tag); },
			explode(',', $string)
		);
	}

	/**
	 * Get any image items for a Doc
	 *
	 * @since 0.2
	 */
	public static function getImages($doc) {
		$images = $doc->items('image');
		if ($doc->getProfileAlias() == 'image'){
			$images[] = $doc;
		}
		return $images;
	}

	/**
	 * Get any audio items for a Doc
	 *
	 * @since 0.2
	 */
	public static function getAudios($doc) {
		$audios = $doc->items('audio');
		if ($doc->getProfileAlias() == 'audio'){
			$audios[] = $doc;
		}
		return $audios;
	}

	/**
	 * Get the first valid audio-enclosure-url from an audio doc
	 *
	 * @since 0.2
	 */
	public static function getPlayableUrl($audio_doc) {
		$guid      = $audio_doc->attributes->guid;
		$enclosure = $audio_doc->links('enclosure')->first();
		if (!$enclosure) {
			pmp_debug("  -- NO ENCLOSURES for audio[$guid]");
			return null;
		}

		// supplementary data
		$href      = $enclosure->href;
		$type      = isset($enclosure->type) ? $enclosure->type : null;
		$uri_parts = parse_url($href);
		$extension = pathinfo($uri_parts['path'], PATHINFO_EXTENSION);
		if (!in_array($uri_parts['scheme'], array('http', 'https'))) {
			pmp_debug("  -- INVALID ENCLOSURE HREF ($href) for audio[$guid]");
			return null;
		}

		// dereference playlists (m3u)
		if ($type == 'audio/m3u' || $extension == 'm3u') {
			pmp_debug("  -- dereferencing playlist for audio[$guid]");
			$response = wp_remote_get($href);
			$lines = explode("\n", $response['body']);
			$href = $lines[0];
			$uri_parts = parse_url($href);
			$extension = pathinfo($uri_parts['path'], PATHINFO_EXTENSION);
			$type = null; // we don't know this anymore
		}

		// check for "known" types
		if ($type && in_array($type, array_values(get_allowed_mime_types()))) {
			pmp_debug("  -- known mime type for audio[$guid]");
			return $href;
		}
		if (in_array($extension, wp_get_audio_extensions())) {
			pmp_debug("  -- known extension for audio[$guid]");
			return $href;
		}

		// not sure what this is
		pmp_debug("  -- UNABLE TO PLAY enclosure ($href) for audio[$guid]");
		return null;
	}

	/**
	 * Get the "best" enclosure metadata from an image doc
	 *
	 * @since 0.2
	 */
	public static function getViewableImage($image_doc) {
		$data = array(
			'post_meta' => pmp_get_post_meta_from_pmp_doc($image_doc),
			'alt'       => $image_doc->attributes->title,
			'caption'   => isset($image_doc->attributes->description) ? $image_doc->attributes->description : '',
			'credit'    => isset($image_doc->attributes->byline) ? $image_doc->attributes->byline : '',
			'url'       => null,
		);

		// also set the post/image alt
		$data['post_meta']['_wp_attachment_image_alt'] = $data['alt'];

		// look for the best crop
		$best_enclosure = $image_doc->links('enclosure')->first();
		foreach ($image_doc->links('enclosure') as $enc) {
			if (isset($enc->meta) && isset($enc->meta->crop)) {
				if ($enc->meta->crop == 'primary') {
					$best_enclosure = $enc;
					break;
				}
				else if ($enc->meta->crop == 'standard') {
					$best_enclosure = $enc;
					break;
				}
			}
		}

		// only return the struct if we've got a url
		if ($best_enclosure) {
			pmp_debug("  -- got enclosure for image[{$image_doc->attributes->guid}]");
			$data['url'] = $best_enclosure->href;
			$data['post_meta']['pmp_image_url'] = $best_enclosure->href;
			return $data;
		}
		else {
			return null;
		}
	}

}
