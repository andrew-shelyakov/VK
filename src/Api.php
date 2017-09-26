<?php
namespace VK;
use VK\Exception;
use VK\Wrapper;

class Api {
	public $baseUrl = 'https://api.vk.com';
	public $oauthUrl = 'https://oauth.vk.com/';
	public $callbackUrl;
	public $version = '5.68';
	public $language = 'en';

	public $userAgent;

	public $clientId;
	public $clientSecret;
	public $scope = [];

	public $accessToken;
	public $tokenSecret;

	public function __construct (array $options = []) {
		$allowableOptions = get_object_vars ($this);
		foreach ($options as $option => $value) {
			if (array_key_exists ($option, $allowableOptions) === TRUE) {
				$this->{$option} = $value;
			}
		}
	}

	public function getWrapper () {
		return new Wrapper ($this);
	}

	public function call (string $method, array $parameters = []) {
		$method = '/method/' . $method;
		$parameters = array_filter (
			array_merge (
				[
					'https' => TRUE,
					'v' => $this->version,
					'lang' => $this->language,
					'access_token' => $this->accessToken
				],
				$parameters
			),
			function ($value) {
				return ($value !== NULL || $value !== '');
			}
		);
		if ($this->accessToken !== NULL && $this->tokenSecret !== NULL) {
			$parameters['sig'] = $this->generateSignature ($method, $parameters);
		}
		$queryString = $this->buildQuery ($parameters);
		return $this->request (
			$this->baseUrl . $method,
			$queryString
		);
	}

	private function generateSignature (string $method, array $parameters = []) {
		$queryString = $this->buildQuery ($parameters, FALSE);
		return md5 ($method . '?' . $queryString . $this->tokenSecret);
	}

	private function buildQuery (array $parameters = [], $encodeUrl = TRUE) {
		$queryArray = [];
		foreach ($parameters as $parameter => $value) {
			if ($encodeUrl === TRUE) {
				$value = urlencode ($value);
			}
			$queryArray[] = $parameter . '=' . $value;
		}
		return implode ('&', $queryArray);
	}

	private function request ($url, $data, $headers = []) {
		$response = json_decode (
			file_get_contents (
				$url,
				FALSE,
				stream_context_create (
					[
						'http' => [
							'method'  => 'POST',
							'header'  => [
								'User-Agent: ' . $this->userAgent,
								'Content-Type: application/x-www-form-urlencoded'
							],
							'content' => $data
						]
					]
				)
			),
			TRUE
		);
		if (isset ($response['error'], $response['error']['error_msg'], $response['error']['error_code']) === TRUE) {
			throw new Exception ($response['error']['error_msg'], $response['error']['error_code']);
		}
		return $response['response'] ?? $response;
	}
}
