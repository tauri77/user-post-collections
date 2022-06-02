<?php


class MG_UPC_Invalid_Field_Exception extends Exception {

	public $field = '';

	public function __construct( $message, $code = 0, $previous = null, $field = '' ) {
		$this->field = $field;
		parent::__construct( $message, $code, $previous );
	}
}
