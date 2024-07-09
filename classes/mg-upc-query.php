<?php

class MG_UPC_Query {

	public $result;

	/**
	 * Query vars set by the user
	 *
	 * @var array|mixed
	 */
	private $query = null;

	/**
	 * Query vars, after parsing
	 *
	 * @var array|mixed
	 */
	public $query_vars = array();

	/**
	 * Array of list IDs.
	 *
	 * @var MG_UPC_List[]|int[]
	 */
	public $lists;

	/**
	 * The number of lists for the current query.
	 *
	 * @var int
	 */
	public $list_count = 0;

	/**
	 * Index of the current item in the loop.
	 *
	 * @var int
	 */
	public $current_list = - 1;

	/**
	 * Whether the loop has started and the caller is in the loop.
	 *
	 * @var bool
	 */
	public $in_the_loop = false;

	/**
	 * The current list.
	 *
	 * This property does not get populated when the `fields` argument is set to
	 * `ids` or `id=>parent`.
	 *
	 * @var Array|null
	 */
	public $list;

	/**
	 * The number of found lists for the current query.
	 *
	 * If limit clause was not used, equals $list_count.
	 *
	 * @var int
	 */
	public $found_lists = 0;

	/**
	 * The number of pages.
	 *
	 * @var int
	 */
	public $max_num_pages = 0;

	/**
	 * Signifies whether the current query is for a single list.
	 *
	 * @var bool
	 */
	public $is_single = false;

	/**
	 * Signifies whether the current query is for a paged result and not for the first page.
	 *
	 * @var bool
	 */
	public $is_paged = false;

	/**
	 * Signifies whether the current query is for a date archive.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_date = false;

	/**
	 * Signifies whether the current query is for a year archive.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_year = false;

	/**
	 * Signifies whether the current query is for a month archive.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_month = false;

	/**
	 * Signifies whether the current query is for a day archive.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $is_day = false;

	/**
	 * Signifies whether the current query is for a specific time.
	 *
	 * @var bool
	 */
	public $is_time = false;

	/**
	 * Signifies whether the current query is for an author archive.
	 *
	 * @var bool
	 */
	public $is_author = false;

	/**
	 * Signifies whether the current query is for a list type archive.
	 *
	 * @var bool
	 */
	public $is_type = false;

	/**
	 * Signifies whether the current query is for a search.
	 *
	 * @var bool
	 */
	public $is_search = false;

	/**
	 * Signifies whether the current query couldn't find anything.
	 *
	 * @var bool
	 */
	public $is_404 = false;

	/*
	 * The numbers of items on the list
	 *
	 * @var int
	 */
	public $items_limit = 0;

	public function __construct( $query ) {
		$this->init();
		$this->parse_query( $query );

		return $this->get_lists();
	}

	private function init_query_flags() {
		$this->is_single = false;
		$this->is_author = false;
		$this->is_type   = false;
		$this->is_search = false;
		$this->is_paged  = false;

		$this->is_date  = false;
		$this->is_year  = false;
		$this->is_month = false;
		$this->is_day   = false;
		$this->is_time  = false;
	}

	/**
	 * Initiates object properties and sets default values.
	 */
	public function init() {
		unset( $this->lists );
		unset( $this->query );
		$this->query_vars = array();
		unset( $this->queried_object );
		unset( $this->queried_object_id );
		$this->list_count   = 0;
		$this->current_list = - 1;
		$this->in_the_loop  = false;
		unset( $this->list );
		$this->found_lists   = 0;
		$this->max_num_pages = 0;

		$this->items_limit = get_option( 'mg_upc_item_per_page', 16 );

		$this->init_query_flags();
	}

	/**
	 * Reparse the query vars.
	 */
	public function parse_query_vars() {
		$this->parse_query();
	}

	/**
	 * Fills in the query variables, which do not exist within the parameter.
	 *
	 * @param array $query_vars Defined query variables.
	 *
	 * @return array Complete query variables with undefined ones filled in empty.
	 */
	public function fill_query_vars( $query_vars ) {
		$keys = array(
			'error',
			'second',
			'minute',
			'hour',
			'day',
			'monthnum',
			'm',
			'year',
			'w',
			'list_type',
			'author',
			'author_name',
			'paged',
			'meta_key',
			'meta_value',
			's',
			'title',
			'fields',
			'orderby',
			'order',
		);

		foreach ( $keys as $key ) {
			if ( ! isset( $query_vars[ $key ] ) ) {
				$query_vars[ $key ] = '';
			}
		}

		$array_keys = array(
			'type__in',
			'type__not_in',
			'type__and',
			'list__in',
			'list__not_in',
			'list_name__in',
			'author__in',
			'author__not_in',
		);

		foreach ( $array_keys as $key ) {
			if ( ! isset( $query_vars[ $key ] ) ) {
				$query_vars[ $key ] = array();
			}
		}

		return $query_vars;
	}

	/**
	 * Parse a query string and set query type booleans.
	 *
	 * @param string|array $query {
	 *     Optional. Array or string of Query parameters.
	 *
	 * @type int|string $author Author ID, or comma-separated list of IDs.
	 * @type string $author_name User 'user_nicename'.
	 * @type int[] $author__in An array of author IDs to query from.
	 * @type int[] $author__not_in An array of author IDs not to query from.
	 * @type int $day Day of the month. Default empty. Accepts numbers 1-31.
	 * @type string $fields List fields to query for. Accepts:
	 *                                                    - '' Returns an array of complete list row
	 *                                                    - 'ids' Returns an array of list IDs (`int[]`).
	 *                                                    Default ''.
	 * @type int $hour Hour of the day. Default empty. Accepts numbers 0-23.
	 * @type int $ID List ID.
	 * @type int|bool $ignore_sticky_lists Whether to ignore sticky lists or not.
	 *                                                    Default false.
	 * @type int[] $list__in An array of list IDs to retrieve, sticky lists will be included.
	 * @type int[] $list__not_in An array of list IDs not to retrieve. Note: a string of comma-separated IDs will NOT work.
	 * @type string[] $list_name__in An array of list slugs that results must match.
	 * @type string|string[] $list_type A list type slug (string) or array of list type slugs. Default 'any'.
	 * @type string|string[] $list_status A list status (string) or array of list statuses. Default 'Publish'.
	 * @type int $lists_per_page The number of lists to query for. Use -1 to request all lists.
	 * @type int $m Combination YearMonth. Accepts any four-digit year and month
	 *                                                    numbers 01-12. Default empty.
	 * @type int $minute Minute of the hour. Default empty. Accepts numbers 0-59.
	 * @type int $monthnum The two-digit month. Default empty. Accepts numbers 1-12.
	 * @type string $name List slug.
	 * @type bool $nopaging Show all lists (true) or paginate (false). Default false.
	 * @type int $offset The number of lists to offset before retrieval.
	 * @type string $order Designates ascending or descending order of lists. Default 'DESC'.
	 *                                                    Accepts 'ASC', 'DESC'.
	 * @type string|array $orderby Sort retrieved list by parameter. One or more options may be passed.
	 *                                                    Accepts:
	 *                                                    - 'none'
	 *                                                    - 'ID'
	 *                                                    - 'views'
	 *                                                    - 'vote_counter'
	 *                                                    - 'count'
	 *                                                    - 'created'
	 *                                                    - 'modified'
	 *                                                    - 'title'
	 *                                                    Default is 'none', except when a search is being performed, when
	 *                                                    the default is 'relevance'.
	 * @type int $page Show the number of lists that would show up on page X of a static front page.
	 * @type int $paged The number of the current page.
	 * @type string $s Search keyword(s).
	 * @type int $second Second of the minute. Default empty. Accepts numbers 0-59.
	 * @type string $title List title.
	 * @type int $w The week number of the year. Default empty. Accepts numbers 0-53.
	 * @type int $year The four-digit year. Default empty. Accepts any four-digit year.
	 */
	public function parse_query( $query = '' ) {
		if ( ! empty( $query ) ) {
			$this->init();
			$this->query      = wp_parse_args( $query );
			$this->query_vars = $this->query;
		} elseif ( ! isset( $this->query ) ) {
			$this->query = $this->query_vars;
		}

		$this->query_vars = $this->fill_query_vars( $this->query_vars );
		$qv               = &$this->query_vars;

		if ( isset( $qv['ID'] ) ) {
			$this->is_single = true;
			if ( ! is_scalar( $qv['ID'] ) || (int) $qv['ID'] < 0 ) {
				$qv['ID']    = 0;
				$qv['error'] = '404';
			} else {
				$qv['ID'] = (int) $qv['ID'];
			}
		}

		if ( isset( $qv['name'] ) ) {
			$this->is_single = true;
		}

		$qv['year']     = is_scalar( $qv['year'] ) ? absint( $qv['year'] ) : 0;
		$qv['monthnum'] = is_scalar( $qv['monthnum'] ) ? absint( $qv['monthnum'] ) : 0;
		$qv['day']      = is_scalar( $qv['day'] ) ? absint( $qv['day'] ) : 0;
		$qv['w']        = is_scalar( $qv['w'] ) ? absint( $qv['w'] ) : 0;
		$qv['m']        = is_scalar( $qv['m'] ) ? preg_replace( '|[^0-9]|', '', $qv['m'] ) : '';
		$qv['paged']    = is_scalar( $qv['paged'] ) ? absint( $qv['paged'] ) : 0;
		$qv['author']   = is_scalar( $qv['author'] ) ? preg_replace( '|[^0-9,-]|', '', $qv['author'] ) : '';
		$qv['title']    = is_scalar( $qv['title'] ) ? trim( $qv['title'] ) : '';
		$qv['orderby']  = is_scalar( $qv['orderby'] ) ? trim( $qv['orderby'] ) : '';
		$qv['order']    = is_scalar( $qv['order'] ) ? trim( $qv['order'] ) : '';

		if ( empty( $qv['lists_per_page'] ) ) {
			//TODO add setting
			$qv['lists_per_page'] = get_option( 'mg_upc_list_per_page', 10 );
		}

		if ( is_scalar( $qv['hour'] ) && '' !== $qv['hour'] ) {
			$qv['hour'] = absint( $qv['hour'] );
		} else {
			$qv['hour'] = '';
		}

		if ( is_scalar( $qv['minute'] ) && '' !== $qv['minute'] ) {
			$qv['minute'] = absint( $qv['minute'] );
		} else {
			$qv['minute'] = '';
		}

		// Fairly large, potentially too large, upper bound for search string lengths.
		if ( ! is_scalar( $qv['s'] ) || ( ! empty( $qv['s'] ) && strlen( $qv['s'] ) > 1600 ) ) {
			$qv['s'] = '';
		}

		// Look for archive queries. Dates, categories, authors, search, list type archives.
		if ( isset( $this->query['s'] ) ) {
			$this->is_search = true;
		}

		if ( '' !== $qv['second'] ) {
			$this->is_time = true;
			$this->is_date = true;
		}

		if ( '' !== $qv['minute'] ) {
			$this->is_time = true;
			$this->is_date = true;
		}

		if ( '' !== $qv['hour'] ) {
			$this->is_time = true;
			$this->is_date = true;
		}

		if ( $qv['day'] ) {
			if ( ! $this->is_date ) {
				$date = sprintf( '%04d-%02d-%02d', $qv['year'], $qv['monthnum'], $qv['day'] );
				if ( $qv['monthnum'] && $qv['year'] && ! wp_checkdate( $qv['monthnum'], $qv['day'], $qv['year'], $date ) ) {
					$qv['error'] = 404;
				} else {
					$this->is_day  = true;
					$this->is_date = true;
				}
			}
		}

		if ( $qv['monthnum'] ) {
			if ( ! $this->is_date ) {
				if ( 12 < $qv['monthnum'] ) {
					$qv['error'] = 404;
				} else {
					$this->is_month = true;
					$this->is_date  = true;
				}
			}
		}

		if ( $qv['year'] ) {
			if ( ! $this->is_date ) {
				$this->is_year = true;
				$this->is_date = true;
			}
		}

		if ( $qv['m'] ) {
			$this->is_date = true;
			if ( strlen( $qv['m'] ) > 9 ) {
				$this->is_time = true;
			} elseif ( strlen( $qv['m'] ) > 7 ) {
				$this->is_day = true;
			} elseif ( strlen( $qv['m'] ) > 5 ) {
				$this->is_month = true;
			} else {
				$this->is_year = true;
			}
		}

		if ( $qv['w'] ) {
			$this->is_date = true;
		}

		if ( $qv['list_type'] ) {
			$this->is_type = true;
		}

		if ( empty( $qv['author'] ) && empty( $qv['author__in'] ) ) {
			$this->is_author = false;
		} else {
			$this->is_author = true;
		}

		if ( '' !== $qv['author_name'] ) {
			$this->is_author   = true;
			$qv['author_name'] = mg_upc_sanitize_username( $qv['author_name'] );
			$author            = get_user_by( 'slug', $qv['author_name'] );
			if ( ! $author ) {
				$qv['error'] = 404;
			}
		}

		if ( ! empty( $qv['paged'] ) && ( (int) $qv['paged'] > 1 ) ) {
			$this->is_paged = true;
		}

		if ( ! empty( $qv['list_type'] ) ) {
			if ( ! is_array( $qv['list_type'] ) ) {
				$qv['list_type'] = explode( ',', $qv['list_type'] );
			}
			if ( is_array( $qv['list_type'] ) ) {
				$qv['list_type'] = array_map( 'sanitize_key', $qv['list_type'] );
				global $mg_upc_list_types;
				if ( empty( array_intersect( array_keys( $mg_upc_list_types ), $qv['list_type'] ) ) ) {
					$qv['error'] = 404;
				}
			} else {
				$qv['error'] = 404;
			}
		}

		if ( ! empty( $qv['list_status'] ) ) {
			if ( ! is_array( $qv['list_status'] ) ) {
				$qv['list_status'] = explode( ',', $qv['list_status'] );
			}
			if ( is_array( $qv['list_status'] ) ) {
				$qv['list_status'] = array_map( 'sanitize_key', $qv['list_status'] );
			} else {
				$qv['error'] = 404;
			}
		}

		if ( 404 === (int) $qv['error'] ) {
			$this->set_404();
		}

		/**
		 * Fires after the main query vars have been parsed.
		 *
		 * @param MG_UPC_Query $query The MG_UPC_Query instance (passed by reference).
		 */
		do_action_ref_array( 'mg_upc_parse_query', array( &$this ) );
	}

	/**
	 * Sets the 404 property and saves whether query is feed.
	 */
	public function set_404() {

		$this->init_query_flags();
		$this->is_404 = true;

		/**
		 * Fires after a 404 is triggered.
		 *
		 * @param MG_UPC_Query $query The MG_UPC_Query instance (passed by reference).
		 */
		do_action_ref_array( 'mg_upc_set_404', array( $this ) );
	}

	/**
	 * Retrieves the value of a query variable.
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed $default_value Optional. Value to return if the query variable is not set.
	 *                              Default empty string.
	 *
	 * @return mixed Contents of the query variable.
	 */
	public function get( $query_var, $default_value = '' ) {
		if ( isset( $this->query_vars[ $query_var ] ) ) {
			return $this->query_vars[ $query_var ];
		}

		return $default_value;
	}

	/**
	 * Sets the value of a query variable.
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed $value Query variable value.
	 */
	public function set( $query_var, $value ) {
		$this->query_vars[ $query_var ] = $value;
	}

	/**
	 * Retrieves an array of list based on query variables.
	 *
	 * @return MG_UPC_List[]|int[] Array of list objects or post IDs.
	 */
	public function get_lists() {
		/**
		 * Fires after the query variable object is created, but before the actual query is run.
		 *
		 * @param MG_UPC_Query $query The MG_UPC_Query instance (passed by reference).
		 */
		do_action_ref_array( 'pre_get_lists', array( &$this ) );

		$qv = &$this->query_vars;

		// Fill again in case 'pre_get_posts' unset some vars.
		$qv = $this->fill_query_vars( $qv );

		$model_args = array(
			'limit'   => isset( $qv['nopaging'] ) && $qv['nopaging'] ? 0 : $qv['lists_per_page'],
			'page'    => $qv['paged'],
			'status'  => array( 'publish' ),
			'orderby' => $qv['orderby'],
			'order'   => $qv['order'],
			'pined'   => false, //No sort by pined list
		);

		$no_empty_set = array(
			'list_type'       => 'type',
			's'               => 'search',
			'before'          => 'before',
			'after'           => 'after',
			'modified_after'  => 'modified_after',
			'modified_before' => 'modified_before',
			'offset'          => 'offset',
			'name'            => 'slug',
			'ID'              => 'ID',
			'second'          => 'second',
			'minute'          => 'minute',
			'hour'            => 'hour',
			'day'             => 'day',
			'monthnum'        => 'month',
			'year'            => 'year',
			'w'               => 'weekofyear',
		);

		foreach ( $no_empty_set as $qv_key => $model_arg ) {
			if ( ! empty( $this->query_vars[ $qv_key ] ) ) {
				$model_args[ $model_arg ] = $this->query_vars[ $qv_key ];
			}
		}

		if ( 'ids' === $qv['fields'] ) {
			$model_args['fields'] = 'ID';
		}

		//Datetime set (override singular sets)
		if ( ! empty( $this->query_vars['m'] ) ) {
			$m = $this->query_vars['m'];

			$model_args['year'] = substr( $m, 0, 4 );
			if ( strlen( $m ) > 5 ) {
				$model_args['month'] = substr( $m, 4, 2 );
			}
			if ( strlen( $m ) > 7 ) {
				$model_args['day'] = substr( $m, 6, 2 );
			}
			if ( strlen( $$m ) > 9 ) {
				$model_args['hour'] = substr( $m, 8, 2 );
			}
			if ( strlen( $m ) > 11 ) {
				$model_args['minute'] = substr( $m, 10, 2 );
			}
			if ( strlen( $m ) > 13 ) {
				$model_args['second'] = substr( $m, 12, 2 );
			}
		}

		//Authors
		if ( ! empty( $qv['author'] ) ) {
			$author_param = $qv['author'];
			if ( is_scalar( $qv['author'] ) ) {
				$author_param = explode( ',', $qv['author'] );
			}
			if ( is_array( $author_param ) && count( $author_param ) === 1 && (int) $author_param[0] > 0 ) {
				$model_args['author'] = (int) $author_param[0];
			} elseif ( is_array( $author_param ) ) {
				$qv['author__in']     = array_merge(
					$qv['author__in'],
					array_filter(
						$author_param,
						function( $i ) {
							return (int) $i > 0; }
					)
				);
				$qv['author__not_in'] = array_merge(
					$qv['author__not_in'],
					array_filter(
						$author_param,
						function( $i ) {
							return (int) $i < 0; }
					)
				);
			}
		}
		if ( ! empty( $qv['author__in'] ) && is_array( $qv['author__in'] ) ) {
			$model_args['author'] = array_map( 'absint', array_unique( $qv['author__in'] ) );
		}
		if ( ! empty( $qv['author__not_in'] ) && is_array( $qv['author__not_in'] ) ) {
			$model_args['not_author'] = array_map( 'absint', array_unique( $qv['author__not_in'] ) );
		}
		if ( ! empty( $qv['author_name'] ) ) {
			$qv['author_name'] = mg_upc_sanitize_username( $qv['author_name'] );
			$author            = get_user_by( 'slug', $qv['author_name'] );
			if ( $author ) {
				$qv['author'] = $author->ID; //override all others author vars
			}
		}

		// IDs
		if ( ! empty( $qv['list__in'] ) && is_array( $qv['list__in'] ) ) {
			if ( ! empty( $qv['ID'] ) ) {
				if ( is_scalar( $qv['ID'] ) ) {
					$qv['list__in'][] = $qv['ID'];
				} elseif ( is_array( $qv['ID'] ) ) {
					$qv['list__in'] = array_merge( $qv['list__in'], $qv['ID'] );
				}
			}
			$model_args['ID'] = array_map( 'absint', array_unique( $qv['list__in'] ) );
		}
		if ( ! empty( $qv['list__not_in'] ) && is_array( $qv['list__not_in'] ) ) {
			$model_args['not_ID'] = array_map( 'absint', array_unique( $qv['list__not_in'] ) );
		}

		// slugs
		if ( ! empty( $qv['list_name__in'] ) && is_array( $qv['list_name__in'] ) ) {
			if ( ! empty( $qv['name'] ) ) {
				if ( is_string( $qv['name'] ) ) {
					$qv['list_name__in'][] = $qv['name'];
				} elseif ( is_array( $qv['name'] ) ) {
					$qv['list_name__in'] = array_merge( $qv['list_name__in'], $qv['name'] );
				}
			}
			$model_args['slug'] = array_map( 'sanitize_title', array_unique( $qv['list_name__in'] ) );
		} elseif ( ! empty( $model_args['slug'] ) && is_array( $model_args['slug'] ) ) {
			$model_args['slug'] = array_map( 'sanitize_title', array_unique( $model_args['slug'] ) );
		} elseif ( ! empty( $model_args['slug'] ) && is_string( $model_args['slug'] ) ) {
			$model_args['slug'] = sanitize_title( $model_args['slug'] );
		}
		if ( ! empty( $qv['list_name__not_in'] ) && is_array( $qv['list_name__not_in'] ) ) {
			$model_args['not_slug'] = array_map( 'sanitize_title', array_unique( $qv['list_name__not_in'] ) );
		}

		if ( isset( $qv['list_status'] ) && ! is_array( $qv['list_status'] ) ) {
			$qv['list_status'] = array_map( 'sanitize_key', explode( ',', $qv['list_status'] ) );
		}
		if ( ! empty( $qv['list_type'] ) && ! is_array( $qv['list_type'] ) ) {
			$qv['list_type'] = array_map( 'sanitize_key', explode( ',', $qv['list_type'] ) );
		}

		if ( ! empty( $qv['list_type'] ) ) {
			$model_args['type'] = $qv['list_type'];
		}

		//limit to searchable list types and statuses
		if ( ! empty( $model_args['search'] ) ) {
			$searchable_list_type   = MG_UPC_Helper::get_instance()->get_searchable_list_types();
			$searchable_list_status = MG_UPC_Helper::get_instance()->get_searchable_list_statuses();
			if ( isset( $qv['list_status'] ) && ! in_array( 'any', $qv['list_status'], true ) ) {
				$qv['list_status'] = array_intersect( $qv['list_status'], $searchable_list_status );
			} else {
				$qv['list_status'] = $searchable_list_status;
			}
			if ( isset( $qv['list_type'] ) && ! in_array( 'any', $qv['list_type'], true ) ) {
				$model_args['type'] = array_intersect( $qv['list_type'], $searchable_list_type );
			} else {
				$model_args['type'] = $searchable_list_type;
			}
		}

		// Set list types or status to a list that the user can read
		if ( ! empty( $qv['list_status'] ) ) {
			$private_statuses = MG_UPC_Helper::get_instance()->get_private_list_statuses( true );
			if (
				count( array_intersect( $qv['list_status'], $private_statuses ) ) > 0 ||
				in_array( 'any', $qv['list_status'], true )
			) {
				if ( empty( $model_args['author'] ) || get_current_user_id() !== $model_args['author'] ) {
					$only_public_status = false;
					//valid list types with the private statuses filter
					$list_types_filter = MG_UPC_Helper::get_instance()->get_list_types_can_private_read( $model_args['type'] );
					/*
					 * This disables listing mixed list types <-> private and public read status
					 * Example:
					 *    If the current user has permissions to view public and private bookmarks,
					 *    but only has permissions to view favorites with public status.
					 *    A query with:
					 *         - status=published,private and
					 *         - list_type=bookmark,favorites
					 *    Will be processed as:
					 *         - status=published,private and
					 *         - list_type=bookmark
					 *    In the future it should be processed like:
					 *         ( type=bookmark AND status IN (published, private)) OR
					 *         (type=favorite AND status = published )
					 *
					 *    (*) If current user only can read public lists for both types, will be processed as:
					 *         - status=published
					 *         - list_type=bookmark,favorites
					 **/
					//TODO: mixed query
					if ( ! empty( $list_types_filter ) ) {
						$model_args['type'] = $list_types_filter; // Only types that user can read with private status
					} else {
						$only_public_status = true; // Don't find private statuses
					}
					if ( $only_public_status ) {
						//only public access
						$qv['list_status'] = MG_UPC_Helper::get_instance()->get_public_list_statuses();
						if ( ! empty( $model_args['search'] ) ) {
							$qv['list_status'] = array_intersect(
								$qv['list_status'],
								MG_UPC_Helper::get_instance()->get_searchable_list_statuses()
							);
						}
					}
				}
			}
			$model_args['status'] = $qv['list_status'];
		}

		if (
			! empty( $qv['ignore_sticky_lists'] ) &&
			in_array( $qv['ignore_sticky_lists'], array( '1', true, 1 ), true )
		) {
			$model_args['not_type'] = MG_UPC_Helper::get_instance()->get_stick_list_types( true );
		}

		try {
			$lists = MG_List_Model::get_instance()->find( $model_args );
		} catch ( MG_UPC_Invalid_Field_Exception $e ) {
			mg_upc_error_log( 'MG_UPC_Query: Error on query: ' . $e->getMessage() );

			$lists = array(
				'results'     => array(),
				'total'       => 0,
				'total_pages' => 0,
			);
		}

		$this->lists         = $lists['results'];
		$this->found_lists   = $lists['total'];
		$this->max_num_pages = $lists['total_pages'];
		$this->list_count    = count( $this->lists );

		if ( 'ids' === $qv['fields'] ) {
			$ids = array();
			foreach ( $this->lists as $list ) {
				$ids = $list->ID;
			}
			$this->lists = $ids;
		}

		$this->lists = apply_filters_ref_array( 'mg_upc_get_lists', array( $this->lists, &$this ) );

		return $this->lists;
	}

	/**
	 * Set up the next list and iterate current list index.
	 *
	 * @return false|MG_UPC_List Next list.
	 */
	public function next_list() {

		$this->current_list ++;

		$this->list = MG_UPC_List::get_instance( $this->lists[ $this->current_list ] );

		return $this->list;
	}

	/**
	 * Sets up the current list.
	 *
	 * Retrieves the next list, sets up the list, sets the 'in the loop'
	 * property to true.
	 *
	 * @global MG_UPC_Query $list Global list object.
	 */
	public function the_list() {
		global $mg_upc_list;

		$this->in_the_loop = true;

		if ( - 1 === $this->current_list ) { // Loop has just started.
			/**
			 * Fires once the loop is started.
			 *
			 * @param MG_UPC_Query $query The MG_UPC_Query instance (passed by reference).
			 */
			do_action_ref_array( 'mg_upc_loop_start', array( &$this ) );
		}

		$mg_upc_list = $this->next_list();
		$this->setup_listdata( $mg_upc_list );
	}

	/**
	 * Determines whether there are more lists available in the loop.
	 *
	 * @return bool True if lists are available, false if end of the loop.
	 */
	public function have_lists() {
		if ( $this->current_list + 1 < $this->list_count ) {
			return true;
		} elseif ( $this->current_list + 1 === $this->list_count && $this->list_count > 0 ) {
			/**
			 * Fires once the loop has ended.
			 *
			 * @param MG_UPC_Query $query The MG_UPC_Query instance (passed by reference).
			 */
			do_action_ref_array( 'mg_upc_loop_end', array( &$this ) );
			// Do some cleaning up after the loop.
			$this->rewind_lists();
		} elseif ( 0 === $this->list_count ) {
			/**
			 * Fires if no results are found in a list query.
			 *
			 * @param MG_UPC_Query $query The MG_UPC_Query instance.
			 */
			do_action( 'mg_upc_loop_no_results', $this );
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Rewind the lists and reset list index.
	 */
	public function rewind_lists() {
		$this->current_list = - 1;
		if ( $this->list_count > 0 ) {
			$this->list = MG_UPC_List::get_instance( $this->lists[0] );
		}
	}

	/**
	 * Set up global list data.
	 *
	 * @param MG_UPC_List|array|object|int $collection MG_UPC_List instance or List ID/object.
	 *
	 * @return bool    True when finished.
	 * @global string $currentday
	 * @global string $currentmonth
	 * @global int $page
	 *
	 * @global int $id
	 * @global WP_User $authordata
	 */
	public function setup_listdata( $collection ) {
		global $mg_upc_id, $mg_upc_authordata, $mg_upc_currentday, $mg_upc_currentmonth;

		if ( ! ( $collection instanceof MG_UPC_List ) ) {
			$collection = mg_upc_get_list( $collection );
		}

		if ( ! $collection ) {
			return false;
		}

		$elements = $this->generate_listdata( $collection );
		if ( false === $elements ) {
			return false;
		}

		//Set globals data
		$mg_upc_id           = $elements['id'];
		$mg_upc_authordata   = $elements['authordata'];
		$mg_upc_currentday   = $elements['currentday'];
		$mg_upc_currentmonth = $elements['currentmonth'];

		/**
		 * Fires once the list data has been set up.
		 *
		 * @param MG_UPC_List $collection The List object (passed by reference).
		 * @param MG_UPC_Query $query The current Query object (passed by reference).
		 */
		do_action_ref_array( 'mg_upc_the_list', array( &$collection, &$this ) );

		return true;
	}

	/**
	 * Generate list data.
	 *
	 * @param MG_UPC_List|object|array|int $list MG_UPC_List instance or List ID/object.
	 *
	 * @return array|false Elements of list or false on failure.
	 */
	public function generate_listdata( $list ) {

		if ( ! ( $list instanceof MG_UPC_List ) ) {
			$list = mg_upc_get_list( $list );
		}

		if ( ! $list ) {
			return false;
		}

		$list->set_items_limit( $this->items_limit );

		return array(
			'id'           => (int) $list->ID,
			'authordata'   => get_userdata( $list->author ),
			'currentday'   => mysql2date( 'd.m.y', $list->created, false ),
			'currentmonth' => mysql2date( 'm', $list->created, false ),
		);
	}

	/**
	 * Determines whether the query is for an existing collection.
	 *
	 * @return bool Whether the query is for an existing single post.
	 */
	public function is_single() {
		if ( ! $this->is_single ) {
			return false;
		}
		return true;
	}

	/**
	 * Determines whether the query is for a list type.
	 *
	 * @return bool Whether the query is for a list type.
	 */
	public function is_type() {
		return $this->is_type;
	}

	/**
	 * Determines whether the query is for an author.
	 *
	 * @return bool Whether the query is for an author.
	 */
	public function is_author() {
		return $this->is_author;
	}

	/**
	 * Is the query the main query?
	 *
	 * @global MG_UPC_Query $mg_upc_query Query object.
	 *
	 * @return bool Whether the query is the main query.
	 */
	public function is_main_query() {
		global $mg_upc_the_query;
		return $mg_upc_the_query === $this;
	}

	/**
	 * Set the posts limit (per list on result query)
	 * @param $limit
	 *
	 * @return void
	 */
	public function set_items_limit( $limit ) {
		$this->items_limit = $limit;
	}

}
