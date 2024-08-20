<?php

interface ClassLoader {
    function __construct(ClassLoader $parent = null);
	function addPath(string $path, bool $prepend = false): void;
	function removePath(string $path): void;
	function getClasses(): array;
	function getParent(): ?self;
	function register(bool $prepend = false): bool;
	function loadClass(string $name): bool;
	function findClass(string $name): ?string;
}