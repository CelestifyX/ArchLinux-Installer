<?php

namespace {
    use utils\ {
        Logger,
        LogLevel,
        Terminal,
        Utils
    };

    use wizard\ {
        InstallationWizard,
        SystemInstaller
    };

    const NAME                = "ArchLinux-Installer";
    const MINIMAL_PHP_VERSION = "8.0";

    define('PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

    require_once(PATH . "src/spl/ClassLoader.php");
	require_once(PATH . "src/spl/BaseClassLoader.php");

    $autoloader = new \BaseClassLoader();
	$autoloader->addPath(PATH . "src");
	$autoloader->register(true);

    if (!file_exists(PATH)) @mkdir(PATH, 0777, true);

    (new Terminal())->init();
    (new Logger(PATH . "installer.log"));

    if (Utils::getOS() !== Utils::OS_LINUX) {
        Logger::send("This script is only supported on Linux.", LogLevel::ERROR);
        exit(1);
    }

    if (version_compare(MINIMAL_PHP_VERSION, PHP_VERSION) > 0) {
        Logger::send(NAME . " requires PHP " . MINIMAL_PHP_VERSION . ", but you have PHP " . PHP_VERSION . ".", LogLevel::CRITICAL);
        exit(1);
    }

    if (PHP_INT_SIZE < 8) {
        Logger::send("Running " . NAME . " on 32-bit systems/PHP is no longer supported.",                          LogLevel::ERROR);
        Logger::send("Please upgrade to a 64-bit system or use a 64-but PHP binary if you are on a 64-bit system.", LogLevel::ERROR);

		exit(1);
	}

    if (Utils::execute("if [ ! -f /run/archiso/airootfs/version ]; then exit 0; else exit 1; fi") !== 0) {
        Logger::send("This script must be run in an Arch Linux LiveCD environment.", LogLevel::ERROR);
        exit(1);
    }

    if (!InstallationWizard::init()) Utils::terminate();
    if (!SystemInstaller::init())    Utils::terminate();

    exit(0);
}
