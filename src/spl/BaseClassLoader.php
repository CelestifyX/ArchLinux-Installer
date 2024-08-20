<?php

class BaseClassLoader extends \Threaded implements ClassLoader {
	private ?ClassLoader $parent  = null;
	private ?\Threaded   $lookup  = null;
	private ?\Threaded   $classes = null;

	function __construct(?ClassLoader $parent = null) {
		$this->parent  = $parent;
		
		$this->lookup  = new \Threaded;
		$this->classes = new \Threaded;
	}

	function addPath(string $path, bool $prepend = false): void {
        foreach ($this->lookup as $p) {
            if ($p === $path) return;
        }

        if ($prepend) {
            $this->lookup->synchronized(
				function (string $path) {
					$entries        = $this->getAndRemoveLookupEntries();
					$this->lookup[] = $path;

					foreach ($entries as $entry) $this->lookup[] = $entry;
            	}, $path
			);
        } else {
            $this->lookup[] = $path;
        }
    }

    protected function getAndRemoveLookupEntries(): array {
        $entries = [];
        while ($this->lookup->count() > 0) $entries[] = $this->lookup->shift();

        return $entries;
    }

    function removePath(string $path): void {
        foreach ($this->lookup as $i => $p) {
            if ($p === $path) unset($this->lookup[$i]);
        }
    }

    function getClasses(): array {
        $classes = [];
        foreach ($this->classes as $class) $classes[] = $class;

        return $classes;
    }

    function getParent(): ?ClassLoader {
        return $this->parent;
    }

    function register(bool $prepend = false): bool {
        return spl_autoload_register(
			function (string $name): void {
            	$this->loadClass($name);
        	}, true, $prepend
		);
    }

    function loadClass(string $name): bool {
        $path = $this->findClass($name);

        if ($path !== null) {
            include $path;

            if (
                !class_exists($name,     false) and
                !interface_exists($name, false) and
                !trait_exists($name,     false)
            ) return false;

            try {
                if (
                    method_exists($name, "onClassLoaded") and
                    (new ReflectionClass($name))->getMethod("onClassLoaded")->isStatic()
                ) $name::onClassLoaded();
            } catch (ReflectionException $e) {
                // NOOP
            }

            $this->classes[] = $name;
            return true;
        }

        return false;
    }

    function findClass(string $name): ?string {
        $baseName = str_replace("\\", DIRECTORY_SEPARATOR, $name);

        foreach ($this->lookup as $path) {
            $filename = $path . DIRECTORY_SEPARATOR . $baseName . ".php";

            if (file_exists($filename)) {
                return $filename;
            }
        }

        return null;
    }
}