<?php
namespace LibWeb\api;

use LibWeb\APIException;

class UploadException extends APIException {

	public function __construct( $errors ) {
		parent::__construct( $errors, "upload", "Error uploading files" );
    }
}
