<?php

/**
 * Class MG_UPC_Cache
 *
 *
 * simple caching values, used for not repeat sql queries
 */
class MG_UPC_Cache {

	protected $cache = array();

	public function __construct() { }

	public function get( $group, $id ) {
		if ( isset( $this->cache[ $group ] ) && isset( $this->cache[ $group ][ $id ] ) ) {
			return $this->cache[ $group ][ $id ];
		}
		return null;
	}

	public function add( $group, $id, $val ) {
		if ( ! isset( $this->cache[ $group ] ) ) {
			if ( count( $this->cache ) > 10 ) {
				array_shift( $this->cache );
			}
			$this->cache[ $group ] = array();
		}
		if ( count( $this->cache[ $group ] ) > 10 ) {
			array_shift( $this->cache[ $group ] );
		}
		$this->cache[ $group ][ $id ] = $val;
	}

	public function remove( $group = null, $id = null ) {
		if ( null === $group ) {
			$this->cache = array();
		} elseif ( isset( $this->cache[ $group ] ) ) {
			if ( null === $id ) {
				$this->cache[ $group ] = array();
			} else {
				if ( isset( $this->cache[ $group ][ $id ] ) ) {
					unset( $this->cache[ $group ][ $id ] );
				}
			}
		}
	}
}
