<?php

namespace wizard;

use utils\ {
    Logger,
    LogLevel,
    Config,
    Utils
};

class InstallationWizard {
    private static ?Config $config   = null;
    private static ?array  $packages = null;

    static function init(): bool {
        foreach ([
            'showPartitionWarning',
            'showActionsWarning',
            'initializeConfiguration',
            'configureDisk',
            'configureUsers',
            'configureBaseSettings',
            'configurePackages',
            'showInformation'
        ] as $method) {
            if (!method_exists(self::class, $method)) {
                Logger::send("Method " . $method . " in " . self::class . " class not found!", LogLevel::EMERGENCY);
                return false;
            }

            if (!self::$method()) return false;
        }

        return true;
    }

    private static function showPartitionWarning(): bool {
        Utils::execute("clear");

        Logger::send("Before starting the Arch Linux installer, you need to partition your disk.", LogLevel::INFO);
        Logger::send("Have you partitioned the disk? [Y/n]",                                       LogLevel::NOTICE);

        $answer = strtolower(Utils::getInput("y"));
        if (!in_array($answer, ['y', 'yes', '1'])) return false;

        return true;
    }

    private static function showActionsWarning(): bool {
        Utils::execute("clear");

        Logger::send("By proceeding, you acknowledge that the author is not responsible for any incorrect actions.", LogLevel::INFO);
        Logger::send("Do you want to continue? [Y/n]",                                                               LogLevel::NOTICE);

        $answer = strtolower(Utils::getInput("y"));
        if (!in_array($answer, ['y', 'yes', '1'])) return false;

        return true;
    }

    private static function initializeConfiguration(): bool {
        if (!file_exists(\PATH . "settings.json")) {
            if (!copy(\PATH . "src/resources/settings.json", \PATH . "settings.json")) {
                Logger::send("Failed to copy settings.json", LogLevel::ERROR);
                return false;
            }
        }

        self::$config   = new Config(\PATH  . "settings.json");
        self::$packages = (new Config(\PATH . "src/resources/packages.json"))->getAll();

        return true;
    }

    private static function configureDisk(): bool {
        Utils::execute("clear");

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Enter the drive name (EXAMPLE: sda, sdc, nvme0n1)", LogLevel::INFO);

        $answer = Utils::validateDevice(Utils::getInput(null), "Disk %device not found.", "You didn`t enter any disk name.");
        if ($answer === false) return false;

        self::$config->setNested("device.disk", $answer);

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Enter BOOT partition (EXAMPLE: sda1, sdc1, nvme0n1p1)", LogLevel::INFO);

        $answer = Utils::validateDevice(Utils::getInput(null), "Partition %device not found.", "You didn`t enter any disk name.");
        if ($answer === false) return false;

        self::$config->setNested("device.boot", $answer);

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Enter SYSTEM partition (EXAMPLE: sda2, sdc2, nvme0n1p2)", LogLevel::INFO);

        $answer = Utils::validateDevice(Utils::getInput(null), "Partition %device not found.", "You didn`t enter any disk name.");
        if ($answer === false) return false;

        self::$config->setNested("device.system", $answer);

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Select file system type for system (0 - F2FS, 1 - EXT4, 2 - BTRFS, 3 - XFS) [0]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3'], true);
        if ($answer === false) return false;

        self::$config->setNested("device.file_system", [
            "0" => "F2FS",
            "1" => "EXT4",
            "2" => "BTRFS",
            "3" => "XFS"
        ][$answer]);

        return true;
    }

    private static function configureUsers(): bool {
        Utils::execute("clear");

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter a new username [user]", LogLevel::INFO);

        $answer = Utils::validateInput(strtolower(Utils::getInput("user")), ['root', 'localhost'], "Username \"%valid\" is not allowed. Please choose another username.", "user");
        if ($answer === false) return false;

        self::$config->setNested("accounts.user.login", $answer);

        // ---------------------------------------------------------------------------------------------------------
        $random = Utils::generatePassword();

        Logger::send("Enter a new password (for " . $answer . ") [" . $random . "]", LogLevel::INFO);
        $answer = Utils::validatePassword(Utils::getInput($random), $random);

        self::$config->setNested("accounts.user.password", $answer);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter a new password (for root) [" . $random . "]", LogLevel::INFO);
        $answer = Utils::validatePassword(Utils::getInput($random), $random);

        self::$config->setNested("accounts.root.password", $answer);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Please confirm if you want to enable autologin for the user [y/N]:", LogLevel::INFO);

        $answer = strtolower(Utils::getInput(null));
        $answer = (in_array($answer, ['y', 'yes', '1']) ? "enable" : null);

        self::$config->setNested("accounts.user.autologin", $answer);
        return true;
    }

    private static function configureBaseSettings(): bool {
        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter your hostname [usr]", LogLevel::INFO);

        $answer = Utils::validateInput(strtolower(Utils::getInput("usr")), ['localhost'], "The hostname '%valid' is not suitable. Please choose another hostname.", "usr");
        if ($answer === false) return false;

        self::$config->setNested("hostname", $answer);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter your timezone (Example: America/Chicago) [UTC]", LogLevel::INFO);

        $answer = Utils::validateTimezone(Utils::getInput("UTC"), "UTC");
        if ($answer === false) return false;

        self::$config->setNested("timezone", $answer);
        return true;
    }

    private static function configurePackages(): bool {
        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a kernel (0 - LINUX (INTEL), 1 - LINUX-ZEN (INTEL), 2 - LINUX LTS (INTEL), 3 - LINUX (AMD), 4 - LINUX-ZEN (AMD), 5 - LINUX LTS (AMD))", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3', '4', '5']);
        if ($answer === false) return false;

        self::$config->setNested("kernel.package", Utils::arrayToString(self::$packages["kernel"]["common_packages"]) . " " . Utils::arrayToString(self::$packages["kernel"]["types"][$answer]["packages"]));
        self::$config->setNested("kernel.type",    self::$packages["kernel"]["types"][$answer]["type"]);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a video driver (0 - INTEL (BUILT-IN), 1 - NVIDIA (PROPRIETARY), 2 - INTEL (BUILT-IN) + NVIDIA (PROPRIETARY), 3 - AMD (DISCRETE), 4 - NOTHING)", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3', '4']);
        if ($answer === false) return false;

        self::$config->setNested("drivers.video.package", (!empty(self::$packages["video"]["types"][$answer]["packages"] ?? []) ? Utils::arrayToString(self::$packages["video"]["common_packages"]) . " " . Utils::arrayToString(self::$packages["video"]["types"][$answer]["packages"]) : null));
        self::$config->setNested("drivers.video.service", (!empty(self::$packages["video"]["types"][$answer]["service"]           ?? []) ? Utils::arrayToString(self::$packages["video"][$answer]["service"])                                                                            : null));
        self::$config->setNested("drivers.video.type",    self::$packages["video"]["types"][$answer]["type"]);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a audio driver (0 - PIPEWIRE, 1 - PULSEAUDIO, 2 - ALSA, 3 - NOTHING) [0]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3'], true);
        if ($answer === false) return false;

        self::$config->setNested("drivers.audio.package", (!empty(self::$packages["audio"][$answer]["packages"] ?? []) ? Utils::arrayToString(self::$packages["audio"][$answer]["packages"]) : null));
        self::$config->setNested("drivers.audio.service", (!empty(self::$packages["audio"][$answer]["service"]  ?? []) ? Utils::arrayToString(self::$packages["audio"][$answer]["service"])  : null));
        self::$config->setNested("drivers.audio.type",    self::$packages["audio"][$answer]["type"]);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select the desktop environment (0 - KDE, 1 - GNOME, 2 - XFCE4, 3 - CINNAMON, 4 - BUDGIE, 5 - MATE, 6 - LXQT, 7 - DEEPIN. 8 - COSMIC, 9 - ENLIGHTENMENT, 10 - CUTEFISH, 11 - HYPRLAND, 12 - BSPWM, 13 - AWESOME, 14 - I3, 15 - QTILE, 16 - SWAY, 17 - NOTHING) [0]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17'], true);
        if ($answer === false) return false;

        self::$config->setNested("desktop.package",         (!empty(self::$packages["desktop"][$answer]["packages"] ?? []) ? Utils::arrayToString(self::$packages["desktop"][$answer]["packages"]) : null));
        self::$config->setNested("desktop.service",         (!empty(self::$packages["desktop"][$answer]["service"]  ?? []) ? Utils::arrayToString(self::$packages["desktop"][$answer]["service"])  : null));
        self::$config->setNested("desktop.type",            self::$packages["desktop"][$answer]["type"]);

        self::$config->setNested("desktop.greeter.package", (!empty(self::$packages["greeter"][self::$packages["desktop"][$answer]["greeter"]]["packages"] ?? []) ? Utils::arrayToString(self::$packages["greeter"][self::$packages["desktop"][$answer]["greeter"]]["packages"]) : null));
        self::$config->setNested("desktop.greeter.service", (!empty(self::$packages["greeter"][self::$packages["desktop"][$answer]["greeter"]]["service"]  ?? []) ? Utils::arrayToString(self::$packages["greeter"][self::$packages["desktop"][$answer]["greeter"]]["service"])  : null));
        self::$config->setNested("desktop.greeter.type",    self::$packages["greeter"][self::$packages["desktop"][$answer]["greeter"]]["type"]);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a font (0 - NOTO-FONTS, 1 - NOTHING) [0]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1'], true);
        if ($answer === false) return false;

        self::$config->setNested("font.package", (!empty(self::$packages["font"][$answer]["packages"] ?? []) ? Utils::arrayToString(self::$packages["font"][$answer]["packages"]) : null));
        self::$config->setNested("font.type",    self::$packages["font"][$answer]["type"]);
        return true;
    }

    private static function showInformation(): bool {
        Utils::execute("clear");
        Logger::send("Installation Confirmation:", LogLevel::DEBUG);
        
        echo("\n");

        Logger::send("DISK: /dev/"     . self::$config->getNested("device.disk")   . " " . Utils::getSize(self::$config->getNested("device.disk"), null, false),                                                                                             LogLevel::INFO);
        Logger::send("  BOOT: /dev/"   . self::$config->getNested("device.boot")   . " " . Utils::getSize(self::$config->getNested("device.disk"), self::$config->getNested("device.boot"))   . " [FAT32]",                                                  LogLevel::INFO);
        Logger::send("  SYSTEM: /dev/" . self::$config->getNested("device.system") . " " . Utils::getSize(self::$config->getNested("device.disk"), self::$config->getNested("device.system")) . " [" . self::$config->getNested("device.file_system") . "]", LogLevel::INFO);
        
        echo("\n");

        Logger::send("ACCOUNTS:",                                                                                                                                                                                                                LogLevel::INFO);
        Logger::send("  root: " . self::$config->getNested("accounts.root.password"),                                                                                                                                                            LogLevel::INFO);
        Logger::send("  " . self::$config->getNested("accounts.user.login") . ": " . self::$config->getNested("accounts.user.password") . ", AUTOLOGIN: " . ((self::$config->getNested("accounts.user.autologin") === "enable") ? "ENABLE" : "DISABLE"), LogLevel::INFO);

        echo("\n");

        Logger::send("TIMEZONE: " . self::$config->getNested("timezone"), LogLevel::INFO);
        Logger::send("HOSTNAME: " . self::$config->getNested("hostname"), LogLevel::INFO);

        echo("\n");

        Logger::send("CONFIGURATION:",                                                                                                                 LogLevel::INFO);
        Logger::send("  KERNEL: "       . self::$config->getNested("kernel.type"),                                                                     LogLevel::INFO);
        Logger::send("  VIDEO DRIVER: " . self::$config->getNested("drivers.video.type"),                                                              LogLevel::INFO);
        Logger::send("  AUDIO DRIVER: " . self::$config->getNested("drivers.audio.type"),                                                              LogLevel::INFO);
        Logger::send("  DESKTOP: "      . self::$config->getNested("desktop.type") . ", GREETER: " . self::$config->getNested("desktop.greeter.type"), LogLevel::INFO);
        Logger::send("  FONT: "         . self::$config->getNested("font.type"),                                                                       LogLevel::INFO);

        $additionals = Utils::getExistingPackages();

        if (!empty($additionals)) {
            echo("\n");
            Logger::send("ADDITIONAL PACKAGES: " . implode(", ", $additionals), LogLevel::INFO);
        }

        echo("\n\n");

        Logger::send("By proceeding, you acknowledge that the author is not responsible for any incorrect actions.", LogLevel::WARNING);
        Logger::send("Check whether the entered data is correct or not",                                             LogLevel::WARNING);
        Logger::send("Save your account name and passwords",                                                         LogLevel::WARNING);
        Logger::send("Do you want to continue? [Y/n]",                                                               LogLevel::NOTICE);

        if (!in_array(strtolower(Utils::getInput("y")), ['y', 'yes', '1'])) return false;
        return true;
    }
}
