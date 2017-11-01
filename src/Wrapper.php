<?php
namespace VK;

class Wrapper {
	private $apiInstance;
	private $class;
	
	public function __construct ($apiInstance) {
		$this->apiInstance = $apiInstance;
	}

	public function __get (string $class) {
		$this->class = $class;
		return $this;
	}

	public function __call (string $method, array $parameters = []) {
		$method = $this->class . '.' . $method;
		$this->class = NULL;
		$parameters = $parameters[0] ?? [];
		return $this->apiInstance->call ($method, $parameters);
	}
}
