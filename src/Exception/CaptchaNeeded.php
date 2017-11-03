<?php
namespace VK\Exception;
use VK\Exception\Response;
use VK\Captcha;

class CaptchaNeeded extends Response {
	private $captcha;

	public function __construct (string $message = '', int $code = 0, Throwable $previous = NULL, Captcha $captcha = NULL) {
		parent::__construct ($message, $code, $previous);
		$this->captcha = $captcha;
	}

	public function getCaptcha () {
		return $this->captcha;
	}
}
