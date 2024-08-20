<?php

namespace utils;

class Config {
    private const JSON_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING;

    function __construct(private string $file) {
        // NOOP
    }

    function setNested(string $key, mixed $value): bool {
        $config = $this->getConfig();

        $keys   = explode('.', $key);
        $base   = &$config[array_shift($keys)];

        foreach ($keys as $nestedKey) {
            if (
                !isset($base[$nestedKey]) ||
                !is_array($base[$nestedKey])
            ) $base[$nestedKey] = [];

            $base = &$base[$nestedKey];
        }

        $base = $value;
        return $this->setConfig($config);
    }

	function getNested(string $key, mixed $default = null): mixed {
        $config = $this->getConfig();

        $keys   = explode('.', $key);
        $base   = ($config[array_shift($keys)] ?? $default);

        foreach ($keys as $nestedKey) {
            if (
                is_array($base) and
                isset($base[$nestedKey])
            ) {
                $base = $base[$nestedKey];
            } else {
                return $default;
            }
        }

        return $base;
    }

    function getAll(): array {
        return $this->getConfig();
    }

    private function getConfig(): array {
        return (json_decode(file_get_contents($this->file), true) ?? []);
    }

    private function setConfig(array $array): bool {
        return (file_put_contents($this->file, json_encode($array, self::JSON_OPTIONS)) !== false);
    }
}