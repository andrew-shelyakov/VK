<?php
namespace VK;
use VK\Exception;
use VK\Wrapper;
use VK\Captcha;

class Api {
	public $baseUrl = 'https://api.vk.com';
	public $oauthUrl = 'https://oauth.vk.com';
	public $callbackUrl;

	public $version = '5.68';
	public $language = 'en';
	public $https = TRUE;
	public $testMode;

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

	public function authorize (string $username, string $password, Captcha $captcha = NULL) {
		$parameters = array_merge (
			$this->getCommonParameters (),
			[
				'grant_type' => 'password',
				'client_id' => $this->clientId,
				'client_secret' => $this->clientSecret,
				'username' => $username,
				'password' => $password,
			]
		);
		if ($captcha !== NULL) {
			$captcha->addTo ($parameters);
		}
		$url = $this->oauthUrl . '/token?';
		$url .= $this->buildQuery($parameters);
		$response = $this->request ($url);
		$this->accessToken = $response['access_token'];
	}

	public function call (string $method, array $parameters = [], Captcha $captcha = NULL) {
		$url = $this->baseUrl . '/method/' . $method;
		$parameters = array_filter (
			array_merge (
				$this->getCommonParameters (),
				[
					'access_token' => $this->accessToken,
				],
				$parameters
			),
			function ($value) {
				return !($value === NULL || $value === '');
			}
		);
		if ($captcha !== NULL) {
			$captcha->addTo ($parameters);
		}
		if ($this->accessToken !== NULL && $this->tokenSecret !== NULL) {
			$parameters['sig'] = $this->generateSignature ($method, $parameters);
		}
		return $this->request (
			$url,
			'POST',
			[],
			$parameters
		);
	}

	private function getCommonParameters () {
		return [
			'v' => $this->version,
			'lang' => $this->language,
			'https' => $this->https,
			'test_mode' => $this->testMode,
		];
	}

	private function generateSignature (string $method, array $parameters = []) {
		$queryString = $this->buildQuery ($parameters, FALSE);
		return md5 ($method . '?' . $queryString . $this->tokenSecret);
	}

	private function buildQuery (array $parameters = [], bool $encode = TRUE) {
		$queryArray = [];
		foreach ($parameters as $parameter => $value) {
			if ($encode === TRUE) {
				$value = urlencode ($value);
			}
			$queryArray[] = $parameter . '=' . $value;
		}
		return implode ('&', $queryArray);
	}

	private function request (string $url, string $method = 'GET', array $headers = [], array $fields = []) {
		$exception = NULL;
		$options = [
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_USERAGENT => $this->userAgent,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POSTFIELDS => $fields,
		];
		$curl = curl_init ($url);
		curl_setopt_array (
			$curl,
			$options
		);
		$response = curl_exec ($curl);
		if ($response === FALSE) {
			$exception = new Exception\Request (
				curl_error ($curl),
				curl_errno ($curl)
			);
		} else {
			$response = json_decode ($response, TRUE);
			if (isset($response['captcha_sid'], $response['captcha_img']) === TRUE) {
				$exception = new Exception\CaptchaNeeded (
					'Captcha needed',
					14,
					NULL,
					new Captcha (
						[
							'sid' => $response['captcha_sid'],
							'img' => $response['captcha_img'],
						]
					)
				);
			} else if (isset ($response['error'], $response['error']['error_msg']) === TRUE) {
				$exception = new Exception\Response (
					$response['error']['error_msg'],
					$response['error']['error_code']
				);
			}
		}
		curl_close ($curl);
		if ($exception !== NULL) {
			throw $exception;			
		}
		return $response['response'] ?? $response;
	}
}
