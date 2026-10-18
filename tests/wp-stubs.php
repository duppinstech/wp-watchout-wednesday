<?php
/**
 * Tiny stand-ins for WordPress types the unit tests touch directly.
 *
 * @package Watch_Out_Wednesday
 */

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error.
	 */
	class WP_Error {
		/**
		 * @var string
		 */
		public $code;

		/**
		 * @var string
		 */
		public $message;

		/**
		 * @param string $code Code.
		 * @param string $message Message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * @param mixed $thing Value.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	/**
	 * Records the query args and returns posts from $GLOBALS['wow_query_posts'].
	 */
	class WP_Query {
		/**
		 * @var array
		 */
		public $query_vars = array();

		/**
		 * @var array
		 */
		public $posts = array();

		/**
		 * @param array $args Query.
		 */
		public function __construct( $args = array() ) {
			$this->query_vars         = $args;
			$GLOBALS['wow_last_query'] = $args;
			$this->posts              = isset( $GLOBALS['wow_query_posts'] ) ? $GLOBALS['wow_query_posts'] : array();
		}
	}
}
