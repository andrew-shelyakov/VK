<?php
namespace VK;

class Captcha {
	public $sid;
	public $img;
	public $key;

	public function __construct (array $options = []) {
		$allowableOptions = get_object_vars ($this);
		foreach ($options as $option => $value) {
			if (array_key_exists ($option, $allowableOptions) === TRUE) {
				$this->{$option} = $value;
			}
		}
	}

	public function addTo (array &$parameters) {
		$parameters = array_merge (
			$parameters,
			[
				'captcha_sid' => $this->sid,
				'captcha_key' => $this->key,
			]
		);
	}
}
