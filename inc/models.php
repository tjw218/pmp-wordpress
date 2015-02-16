<?php

/**
 * A wrapper for PMP SDK with conveniences for the plugin
 *
 * @since 0.1
 */
class SDKWrapper {

	protected $sdk;

	public function __construct() {
		$settings = get_option('pmp_settings');

		$this->sdk = new \Pmp\Sdk(
			$settings['pmp_api_url'],
			$settings['pmp_client_id'],
			$settings['pmp_client_secret']
		);
	}

	public function __call($name, $args) {
		return call_user_method_array($name, $this->sdk, $args);
	}

	/**
	 * Convenience method cleans up query results data for use with Backbone.js models and collections.
	 *
	 * @param $method (string) The query method to call (i.e., queryDocs or queryGroups, etc.)
	 * @param $arguments (array) The options to be pased to the query method.
	 *
	 * @since 0.1
	 */
	public function query2json($method, $args) {
		$result = $this->queryDocs($args);

		if (empty($result))
			return $result;
		else {
			$items = $result->items();
			$data = array(
				"total" => $result->items()->totalItems(),
				"count" => $result->items()->count(),
				"page" => $result->items()->pageNum(),
				"offset" => $result->items()->pageNum() - 1,
				"total_pages" => $result->items()->totalPages()
			);

			if ($items) {
				foreach ($items as $item) {
					$links = (array) $item->links;

					unset($links['auth']);
					unset($links['query']);

					$data['items'][] = array_merge((array) $item->attributes, array(
						'links' => $links,
						'items' => (array) $item->items
					));
				}
			}
		}

		return $data;
	}

}
