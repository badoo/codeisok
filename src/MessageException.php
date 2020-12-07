<?php

namespace GitPHP;

class MessageException extends \Exception
{

	public $Error;

	public $StatusCode;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $message message string
	 * @param boolean $error true if this is an error rather than informational
	 * @param integer $statusCode HTTP status code to return
	 * @param integer $code exception code
	 * @param \Exception $previous previous exception
	 * @return \Exception message exception object
	 */
	public function __construct($message, $error = false, $statusCode = 200, $code = 0) {
		$this->Error = $error;
		$this->StatusCode = $statusCode;
		parent::__construct($message, $code);
	}
}
