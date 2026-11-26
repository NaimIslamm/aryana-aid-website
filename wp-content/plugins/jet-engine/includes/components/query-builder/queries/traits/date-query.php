<?php
namespace Jet_Engine\Query_Builder\Queries\Traits;

trait Date_Query_Trait {

	public function normalize_date_args() {
		$args = $this->final_query;

		if ( empty( $args['date_query'] ?? false ) ) {
			return;
		}

		if ( ! is_array( $args['date_query'] ) ) {
			return;
		}

		if ( isset( $args['date_query']['after'] ) || isset( $args['date_query']['before'] ) ) {
			$raw    = $args['date_query'];
			$to_add = array();

			if ( isset( $raw['after'] ) ) {
				$to_add['after'] = $raw['after'];
			}

			if ( isset( $raw['before'] ) ) {
				$to_add['before'] = $raw['before'];
			}

			if ( isset( $raw['column'] ) ) {
				$to_add['column'] = $raw['column'];
			}

			$raw[] = $to_add;
			unset( $raw['after'] );
			unset( $raw['before'] );
			unset( $raw['column'] );

			$this->final_query['date_query'] = $raw;

			$args = $this->final_query;
		}

		if ( isset( $args['date_query']['year'] ) || isset( $args['date_query']['month'] ) || isset( $args['date_query']['day'] ) ) {
			$raw    = $args['date_query'];
			$to_add = array();

			if ( isset( $raw['year'] ) ) {
				$to_add['year'] = $raw['year'];
			}

			if ( isset( $raw['month'] ) ) {
				$to_add['month'] = $raw['month'];
			}

			if ( isset( $raw['day'] ) ) {
				$to_add['day'] = $raw['day'];
			}

			if ( isset( $raw['column'] ) ) {
				$to_add['column'] = $raw['column'];
			}

			$raw[] = $to_add;
			unset( $raw['year'] );
			unset( $raw['month'] );
			unset( $raw['day'] );
			unset( $raw['column'] );

			$this->final_query['date_query'] = $raw;
		}
	}

	/**
	 * Prepare Date Query arguments by initial arguments list
	 *
	 * @param   array $args
	 * @return array
	 */
	public function prepare_date_query_args( $args = array() ) {

		$raw        = $args['date_query'];
		$date_query = array();

		// if ( isset( $raw[0] ) && ( isset( $raw['after'] ) || isset( $raw['before'] ) ) ) {
		// 	$to_add = array();

		// 	if ( isset( $raw['after'] ) ) {
		// 		$to_add['after'] = $raw['after'];
		// 	}

		// 	if ( isset( $raw['before'] ) ) {
		// 		$to_add['before'] = $raw['before'];
		// 	}

		// 	if ( isset( $raw['column'] ) ) {
		// 		$to_add['column'] = $raw['column'];
		// 	}

		// 	$raw[] = $to_add;
		// 	unset( $raw['after'] );
		// 	unset( $raw['before'] );
		// 	unset( $raw['column'] );
		// }

		foreach ( $raw as $key => $query_row ) {

			if ( ! is_array( $query_row ) ) {
				// To prevent fatal error if `year` argument is empty.
				if ( 'year' === $key && empty( $query_row ) ) {
					continue;
				}

				$date_query[ $key ] = $query_row;
				continue;
			}

			// To prevent fatal error if `year` argument is empty.
			if ( empty( $query_row['year'] ) ) {
				unset( $query_row['year'] );
			}

			// To prevent database error in the Comments Query if all key fields are empty.
			if ( $this->is_not_empty_key_fields( $query_row ) ) {
				$date_query[ $key ] = $query_row;
			}
		}

		return $date_query;
	}

	public function is_not_empty_key_fields( $query_row ) {
		$check_keys = array(
			'year',
			'month',
			'day',
			'after',
			'before',

			'dayofyear',
			'dayofweek',
			'dayofweek_iso',
			'week',
			'hour',
			'minute',
			'second',
		);

		foreach ( $check_keys as $key ) {
			if ( ! \Jet_Engine_Tools::is_empty( $query_row, $key ) ) {
				return true;
			}
		}

		return false;
	}

}
