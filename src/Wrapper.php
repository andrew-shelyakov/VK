<?php
namespace VK;

class Wrapper {
	private $apiInstance;
	private $method;
	
	public function __construct ($apiInstance) {
		$this->apiInstance = $apiInstance;
	}

	public function __get (string $method) {
		$this->method = $method;
		return $this;
	}

	public function __call (string $method, array $parameters = []) {
		$method = $this->method . '.' . $method;
		$parameters = $parameters[0] ?? [];
		$response = $this->apiInstance->call ($method, $parameters);
		$this->method = NULL;
		return $response;
	}
}
