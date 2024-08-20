<?php

namespace utils;

class Config {
	const DETECT      = -1;
	const PROPERTIES  = 0;
	const CNF         = self::PROPERTIES;
	const JSON        = 1;
	const YAML        = 2;
	const SERIALIZED  = 4;
	const ENUM 	      = 5;
	const ENUMERATION = self::ENUM;

	private array $config      = [];
	private array $nestedCache = [];

	private ?string $file      = null;

	private bool $correct      = false;
	private bool $changed 	   = false;

	private int $type          = self::DETECT;
	private int $jsonOptions   = JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING;

	private static array $formats = [
		"properties" 			  => self::PROPERTIES,
		"cnf"        			  => self::CNF,
		"conf"       			  => self::CNF,
		"config"    			  => self::CNF,
		"json"       			  => self::JSON,
		"js"         			  => self::JSON,
		"yml"        			  => self::YAML,
		"yaml"       			  => self::YAML,
		"sl"         			  => self::SERIALIZED,
		"serialize"  			  => self::SERIALIZED,
		"txt"        			  => self::ENUM,
		"list"      			  => self::ENUM,
		"enum"      			  => self::ENUM,
	];

	function __construct(string $file, int $type = self::DETECT, array $default = [], ?bool &$correct = null) {
		$this->load($file, $type, $default);
		$correct = $this->correct;
	}

	function reload(): void {
		$this->config      = [];
		$this->nestedCache = [];

		$this->correct     = false;

		$this->load($this->file, $this->type);
	}

	function hasChanged(): bool {
		return $this->changed;
	}

	function setChanged(bool $changed = true): void {
		$this->changed = $changed;
	}

	static function fixYAMLIndexes(string $str) {
		return preg_replace("#^([ ]*)([a-zA-Z_]{1}[ ]*)\\:$#m", "$1\"$2\":", $str);
	}

	function load(string $file, int $type = self::DETECT, array $default = []): bool {
		$this->correct = true;

		$this->file    = $file;
		$this->type    = $type;

		if ($this->type === self::DETECT) {
			$extension = explode(".", basename($this->file));
			$extension = strtolower(trim(array_pop($extension)));

			if (isset(self::$formats[$extension])) {
				$this->type    = self::$formats[$extension];
			} else {
				$this->correct = false;
			}
		}

		if (!file_exists($file)) {
			$this->config = $default;
			$this->save();
		} else {
			if ($this->correct) {
				$content = file_get_contents($this->file);

				switch ($this->type) {
					case self::PROPERTIES:
						case self::CNF:
							$this->parseProperties($content);
							break;
					case self::JSON:
						$this->config = json_decode($content, true);
						break;
					case self::YAML:
						$content = self::fixYAMLIndexes($content);
						$this->config = yaml_parse($content);
						break;
					case self::SERIALIZED:
						$this->config = unserialize($content);
						break;
					case self::ENUM:
						$this->parseList($content);
						break;
					default:
						$this->correct = false;
						return false;
						break;
				}

				if (!is_array($this->config)) 	 					  $this->config = $default;
				if ($this->fillDefaults($default, $this->config) > 0) $this->save();
			} else {
				return false;
			}
		}

		return true;
	}

	function check(): bool {
		return ($this->correct === true);
	}

	function save(): bool {
		if ($this->correct) {
			$content = null;

			switch ($this->type) {
				case self::PROPERTIES:
					$content = $this->writeProperties();
					break;
				case self::JSON:
					$content = json_encode($this->config, $this->jsonOptions);
					break;
				case self::YAML:
					$content = yaml_emit($this->config, YAML_UTF8_ENCODING);
					break;
				case self::SERIALIZED:
					$content = serialize($this->config);
					break;
				case self::ENUM:
					$content = implode("\r\n", array_keys($this->config));
					break;
				default:
					throw new \InvalidStateException("Configuration type unknown, not installed, or not detected");
					break;
			}

			file_put_contents($this->file, $content);
			$this->changed = false;

			return true;
		} else {
			return false;
		}
	}

	function getPath(): string {
		return $this->file;
	}

	function setJsonOptions(int $options): self {
		if ($this->type !== self::JSON) throw new \RuntimeException("Attempting to set JSON options for a non-JSON configuration.");

		$this->jsonOptions = $options;
		return $this;
	}

	function enableJsonOption(int $option): self {
		if ($this->type !== self::JSON) throw new \RuntimeException("Attempting to enable the JSON option for a non-JSON configuration.");

		$this->jsonOptions |= $option;
		return $this;
	}

	function disableJsonOption(int $option): self {
		if ($this->type !== self::JSON) throw new \RuntimeException("Attempting to disable the JSON option for a non-JSON configuration.");

		$this->jsonOptions &= ~$option;
		return $this;
	}

	function getJsonOptions(): int {
		if ($this->type !== self::JSON) throw new \RuntimeException("Trying to get JSON parameters for non-JSON configuration.");
		return $this->jsonOptions;
	}

	function __get($k) {
		return $this->get($k);
	}

	function __set($k, $v) {
		$this->set($k, $v);
	}

	function __isset($k) {
		return $this->exists($k);
	}
	
	function __unset($k) {
		$this->remove($k);
	}

	function setNested($key, $value) {
		$vars = explode(".", $key);
		$base = array_shift($vars);

		if (!isset($this->config[$base])) $this->config[$base] = [];
		$base =& $this->config[$base];

		while (count($vars) > 0) {
			$baseKey = array_shift($vars);
			if (!isset($base[$baseKey])) $base[$baseKey] = [];

			$base =& $base[$baseKey];
		}

		$base = $value;

		$this->nestedCache = [];
		$this->changed     = true;
	}

	function getNested($key, $default = null) {
		if (isset($this->nestedCache[$key])) return $this->nestedCache[$key];

		$vars = explode(".", $key);
		$base = array_shift($vars);

		if (isset($this->config[$base])) {
			$base = $this->config[$base];
		} else {
			return $default;
		}

		while (count($vars) > 0) {
			$baseKey = array_shift($vars);

			if (
				is_array($base) and
				isset($base[$baseKey])
			) {
				$base = $base[$baseKey];
			} else {
				return $default;
			}
		}

		return ($this->nestedCache[$key] = $base);
	}

	function removeNested(string $key): void {
		$this->nestedCache = [];

		$vars 		 = explode(".", $key);
		$currentNode =& $this->config;

		while (count($vars) > 0) {
			$nodeName = array_shift($vars);

			if (isset($currentNode[$nodeName])) {
				if (empty($vars)) {
					unset($currentNode[$nodeName]);
				} elseif (is_array($currentNode[$nodeName])) {
					$currentNode =& $currentNode[$nodeName];
				}
			} else {
				break;
			}
		}
	}

	function get($k, $default = false) {
		return (
			(
				$this->correct and
				isset($this->config[$k])
			) ? $this->config[$k] : $default
		);
	}

	function getKey($key, $default = false) {
		return (isset($this->config[$key]) ? $this->config[$key] : $default);
	}

	function set($k, $v = true) {
		$this->config[$k] = $v;
		$this->changed 	  = true;

		foreach ($this->nestedCache as $nestedKey => $nvalue) {
			if (substr($nestedKey, 0, strlen($k) + 1) === ($k . ".")) unset($this->nestedCache[$nestedKey]);
		}
	}

	function setAll(array $v) {
		$this->config  = $v;
		$this->changed = true;
	}

	function exists($k, $lowercase = false): bool {
		return ($lowercase ? isset(array_change_key_case($this->config, CASE_LOWER)[strtolower($k)]) : isset($this->config[$k]));
	}

	function remove($k): void {
		unset($this->config[$k]);
		$this->changed = true;
	}

	function getAll(bool $keys = false): array {
		return ($keys ? array_keys($this->config) : $this->config);
	}

	function setDefaults(array $defaults): void {
		$this->fillDefaults($defaults, $this->config);
	}

	private function fillDefaults($default, &$data) {
		$changed = 0;

		foreach ($default as $k => $v) {
			if (is_array($v)) {
				if (
					!isset($data[$k]) ||
					!is_array($data[$k])
				) $data[$k] = [];

				$changed += $this->fillDefaults($v, $data[$k]);
			} elseif (!isset($data[$k])) {
				$data[$k] = $v;
				++$changed;
			}
		}

		if ($changed > 0) $this->changed = true;
		return $changed;
	}

	private function parseList($content): void {
		foreach (explode("\n", trim(str_replace("\r\n", "\n", $content))) as $v) {
			$v = trim($v);
			if ($v == "") continue;

			$this->config[$v] = true;
		}
	}

	private function writeProperties(): string {
		$content = "#Properties Config file\r\n#" . date("D M j H:i:s T Y") . "\r\n";

		foreach ($this->config as $k => $v) {
			if (is_bool($v)) {
				$v = ($v ? "on" : "off");
			} elseif (is_array($v)) {
				$v = implode(";", $v);
			}

			$content .= $k . "=" . $v . "\r\n";
		}

		return $content;
	}

	private function parseProperties($content): void {
		if (preg_match_all('/([a-zA-Z0-9\-_\.]*)=([^\r\n]*)/u', $content, $matches) > 0) {
			foreach ($matches[1] as $i => $k) {
				$v = trim($matches[2][$i]);

				switch (strtolower($v)) {
					case "on":
						case "true":
							case "yes":
								$v = true;
								break;
					case "off":
						case "false":
							case "no":
								$v = false;
								break;
				}
				
				if (isset($this->config[$k])) Logger::send("Duplicate property " . $k . " in file " . $this->file, LogLevel::DEBUG);
				$this->config[$k] = $v;
			}
		}
	}
}
