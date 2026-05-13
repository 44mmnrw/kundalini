<?php
/**
 * Plugin Name: Kundalini REST cache bypass
 * Description: REST Cache-Control headers; internal fulfillment of Site Health REST probe so loopback/WAF timeouts do not fake a broken context=edit response.
 */

declare(strict_types=1);

/**
 * URLs match Site Health probe: GET wp/v2/types/post?context=edit (scheme/host/path identical).
 */
function kundalini_mu_rest_urls_match_types_post_edit( string $url, string $expected ): bool {
	$a = wp_parse_url( trim( $url ) );
	$b = wp_parse_url( trim( $expected ) );
	if ( ! is_array( $a ) || ! is_array( $b ) ) {
		return false;
	}

	$strip = static function ( array $parts ): array {
		return [
			'scheme' => isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '',
			'host'   => isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '',
			'path'   => isset( $parts['path'] ) ? untrailingslashit( (string) $parts['path'] ) : '',
		];
	};

	$qa = [];
	$qb = [];
	parse_str( $a['query'] ?? '', $qa );
	parse_str( $b['query'] ?? '', $qb );

	return $strip( $a ) === $strip( $b )
		&& ( $qa['context'] ?? '' ) === 'edit'
		&& ( $qb['context'] ?? '' ) === 'edit';
}

/**
 * Site Health calls wp_remote_get() to this site's REST URL; CDN/WAF/timeouts/cache can yield wrong bodies or timeouts.
 * Fulfill only that probe internally — JSON matches an authenticated REST round-trip.
 */
add_filter(
	'pre_http_request',
	static function ( $preempt, $args, $url ) {
		if ( false !== $preempt ) {
			return $preempt;
		}

		if ( ! is_array( $args ) || ! is_string( $url ) ) {
			return $preempt;
		}

		if ( ( $args['method'] ?? 'GET' ) !== 'GET' ) {
			return $preempt;
		}

		$h = $args['headers'] ?? [];
		if ( ! is_array( $h ) ) {
			return $preempt;
		}

		$has_nonce = false;
		foreach ( $h as $key => $_unused ) {
			if ( strcasecmp( (string) $key, 'X-WP-Nonce' ) === 0 ) {
				$has_nonce = true;
				break;
			}
		}
		if ( ! $has_nonce ) {
			return $preempt;
		}

		$expected = add_query_arg( 'context', 'edit', rest_url( 'wp/v2/types/post' ) );
		if ( ! kundalini_mu_rest_urls_match_types_post_edit( $url, $expected ) ) {
			return $preempt;
		}

		if ( ! function_exists( 'rest_do_request' ) ) {
			require_once ABSPATH . 'wp-includes/rest-api.php';
		}

		$request = new WP_REST_Request( 'GET', '/wp/v2/types/post' );
		$request->set_param( 'context', 'edit' );

		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			return [
				'headers'  => [],
				'body'     => wp_json_encode(
					[
						'code'    => $response->get_error_code(),
						'message' => $response->get_error_message(),
						'data'    => [ 'status' => 500 ],
					]
				),
				'response' => [
					'code'    => 500,
					'message' => function_exists( 'get_status_header_desc' ) ? get_status_header_desc( 500 ) : 'Internal Server Error',
				],
				'cookies'  => [],
				'filename' => null,
			];
		}

		if ( ! $response instanceof WP_REST_Response ) {
			return $preempt;
		}

		$code = $response->get_status();
		$data = $response->get_data();
		$body = wp_json_encode(
			$data,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		return [
			'headers'  => [
				'content-type' => 'application/json; charset=' . get_option( 'blog_charset' ),
			],
			'body'     => false !== $body ? $body : '{}',
			'response' => [
				'code'    => $code,
				'message' => function_exists( 'get_status_header_desc' ) ? get_status_header_desc( $code ) : '',
			],
			'cookies'  => [],
			'filename' => null,
		];
	},
	10,
	3
);

add_filter(
	'rest_post_dispatch',
	static function ( $response, $server, WP_REST_Request $request ) {
		if ( ! $response instanceof WP_REST_Response ) {
			return $response;
		}

		$response->header( 'Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Surrogate-Control', 'no-store' );
		$response->header( 'Vary', 'Cookie, Authorization' );

		return $response;
	},
	10,
	3
);
