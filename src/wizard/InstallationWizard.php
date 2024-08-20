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
        Utils::clear_screen();

        Logger::send("Before starting the Arch Linux installer, you need to partition your disk.", LogLevel::INFO);
        Logger::send("Have you partitioned the disk? [Y/n]",                                       LogLevel::NOTICE);

        $answer = Utils::getInput("y");
        if (!in_array($answer, ['y', 'yes', '1'])) return false;

        return true;
    }

    private static function showActionsWarning(): bool {
        Utils::clear_screen();

        Logger::send("By proceeding, you acknowledge that the author is not responsible for any incorrect actions.", LogLevel::INFO);
        Logger::send("Do you want to continue? [Y/n]",                                                               LogLevel::NOTICE);

        $answer = Utils::getInput("y");
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

        if (!file_exists(\PATH . "additional_packages.json")) {
            if (!copy(\PATH . "src/resources/additional_packages.json", \PATH . "additional_packages.json")) {
                Logger::send("Failed to copy additional_packages.json", LogLevel::ERROR);
                return false;
            }
        }

        self::$config   = new Config(\PATH  . "settings.json",               Config::JSON);
        self::$packages = (new Config(\PATH . "src/resources/packages.json", Config::JSON))->getAll();

        return true;
    }

    private static function configureDisk(): bool {
        Utils::clear_screen();

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Enter the drive name (EXAMPLE: sda, sdc, nvme0n1)", LogLevel::INFO);

        $answer = Utils::validateDevice(Utils::getInput(null), "Disk %device not found.", "You didn`t enter any disk name.");
        if (!$answer) return false;

        self::$config->setNested("DiskData.disk", $answer);
        self::$config->save();

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Enter BOOT partition (EXAMPLE: sda1, sdc1, nvme0n1p1)", LogLevel::INFO);

        $answer = Utils::validateDevice(Utils::getInput(null), "Partition %device not found.", "You didn`t enter any disk name.");
        if (!$answer) return false;

        self::$config->setNested("DiskData.boot", $answer);
        self::$config->save();

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Enter SYSTEM partition (EXAMPLE: sda2, sdc2, nvme0n1p2)", LogLevel::INFO);

        $answer = Utils::validateDevice(Utils::getInput(null), "Partition %device not found.", "You didn`t enter any disk name.");
        if (!$answer) return false;

        self::$config->setNested("DiskData.system", $answer);
        self::$config->save();

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Select file system type for system (1 - F2FS, 2 - EXT4, 3 - BTRFS) [1]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['1', '2', '3'], true);
        if (!$answer) return false;

        self::$config->setNested("SelectedData.file_system", [
            "1" => "F2FS",
            "2" => "EXT4",
            "3" => "BTRFS"
        ][$answer]);

        self::$config->save();
        return true;
    }

    private static function configureUsers(): bool {
        Utils::clear_screen();

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter a new username [user]", LogLevel::INFO);

        $answer = Utils::validateInput(Utils::getInput("user"), ['root', 'localhost'], "Username \"%valid\" is not allowed. Please choose another username.", "user");
        if (!$answer) return false;

        self::$config->setNested("UserData.user", $answer);
        self::$config->save();

        // ---------------------------------------------------------------------------------------------------------
        $random = Utils::generatePassword();

        Logger::send("Enter a new password (for " . $answer . " [" . $random . "]", LogLevel::INFO);
        $answer = Utils::getInput($random);

        self::$config->setNested("UserData.userpassword", $answer);
        self::$config->save();

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter a new password (for root [" . $random . "]", LogLevel::INFO);
        $answer = Utils::getInput($random);

        self::$config->setNested("UserData.password", $answer);
        self::$config->save();

        return true;
    }

    private static function configureBaseSettings(): bool {
        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter your hostname [usr]", LogLevel::INFO);

        $answer = Utils::validateInput(Utils::getInput("usr"), ['localhost'], "The hostname '%valid' is not suitable. Please choose another hostname.", "usr");
        if (!$answer) return false;

        self::$config->setNested("UserData.hostname", $answer);
        self::$config->save();

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter your timezone (Example: America/Chicago) [UTC]", LogLevel::INFO);

        $answer = Utils::validateTimezone(Utils::getInput("UTC"), "UTC");
        if (!$answer) return false;

        self::$config->setNested("UserData.timezone", $answer);
        self::$config->save();
        
        return true;
    }

    private static function configurePackages(): bool {
        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a kernel (1 - LINUX (INTEL), 2 - LINUX-ZEN (INTEL), 3 - LINUX LTS (INTEL), 4 - LINUX (AMD), 5 - LINUX-ZEN (AMD), 6 - LINUX LTS (AMD))", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['1', '2', '3', '4', '5', '6']);
        if (!$answer) return false;

        self::$config->setNested("PackageData.kernel",  self::$packages["kernel"][$answer]["packages"]);
        self::$config->setNested("SelectedData.kernel", self::$packages["kernel"][$answer]["type"]);
        self::$config->save();

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a video driver (1 - INTEL (BUILT-IN), 2 - NVIDIA (PROPRIETARY), 3 - INTEL (BUILT-IN) + NVIDIA (PROPRIETARY), 4 - AMD (DISCRETE), 5 - NOTHING)", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['1', '2', '3', '4', '5']);
        if (!$answer) return false;

        self::$config->setNested("PackageData.video",  (self::$packages["video"][$answer]["packages"] ?? null));
        self::$config->setNested("ServiceData.video",  (self::$packages["video"][$answer]["video"]    ?? null));
        self::$config->setNested("SelectedData.video", self::$packages["video"][$answer]["type"]);
        self::$config->save();

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a sound driver (1 - PIPEWIRE, 2 - PULSEAUDIO, 3 - NOTHING) [1]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['1', '2', '3'], true);
        if (!$answer) return false;

        self::$config->setNested("PackageData.sound",  (self::$packages["sound"][$answer]["packages"] ?? null));
        self::$config->setNested("ServiceData.sound",  (self::$packages["sound"][$answer]["service"]  ?? null));
        self::$config->setNested("SelectedData.sound", self::$packages["sound"][$answer]["type"]);
        self::$config->save();

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select the desktop environment (1 - KDE, 2 - GNOME, 3 - XFCE4, 4 - CINNAMON, 5 - BUDGIE, 6 - NOTHING) [1]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['1', '2', '3', '4', '5', '6'], true);
        if (!$answer) return false;

        self::$config->setNested("PackageData.desktop",  (self::$packages["desktop"][$answer]["packages"] ?? null));
        self::$config->setNested("ServiceData.desktop",  (self::$packages["desktop"][$answer]["service"]  ?? null));
        self::$config->setNested("SelectedData.desktop", self::$packages["desktop"][$answer]["type"]);
        self::$config->save();

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a font (1 - NOTO-FONTS, 2 - NOTHING) [1]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['1', '2'], true);
        if (!$answer) return false;

        self::$config->setNested("PackageData.font",  (self::$packages["font"][$answer]["packages"] ?? null));
        self::$config->setNested("SelectedData.font", self::$packages["font"][$answer]["type"]);
        self::$config->save();

        // ---------------------------------------------------------------------------------------------------------
        $packages = "";
        foreach (Utils::getExistingPackages() as $package) $packages .= $package . " ";

        self::$config->setNested("PackageData.additionals", rtrim($packages));
        self::$config->save();

        return true;
    }

    private static function showInformation(): bool {
        Utils::clear_screen();

        Logger::send("Installation Confirmation:", LogLevel::DEBUG);
        
        echo("\n");

        Logger::send("DISK: /dev/"     . self::$config->getNested("DiskData.disk")   . " " . Utils::getSize(self::$config->getNested("DiskData.disk"), null, false),                                                                                                     LogLevel::INFO);
        Logger::send("  BOOT: /dev/"   . self::$config->getNested("DiskData.boot")   . " " . Utils::getSize(self::$config->getNested("DiskData.disk"), self::$config->getNested("DiskData.boot"))   . " [FAT32]",                                                        LogLevel::INFO);
        Logger::send("  SYSTEM: /dev/" . self::$config->getNested("DiskData.system") . " " . Utils::getSize(self::$config->getNested("DiskData.disk"), self::$config->getNested("DiskData.system")) . " [" . self::$config->getNested("SelectedData.file_system") . "]", LogLevel::INFO);
        
        echo("\n");

        Logger::send("ACCOUNTS:",                                                                                                 LogLevel::INFO);
        Logger::send("  " . self::$config->getNested("UserData.user") . ": " . self::$config->getNested("UserData.userpassword"), LogLevel::INFO);
        Logger::send("  root: "                                              . self::$config->getNested("UserData.password"),     LogLevel::INFO);

        echo("\n");

        Logger::send("TIMEZONE: " . self::$config->getNested("UserData.timezone"), LogLevel::INFO);
        Logger::send("HOSTNAME: " . self::$config->getNested("UserData.hostname"), LogLevel::INFO);

        echo("\n");

        Logger::send("CONFIGURATION:",                                                      LogLevel::INFO);
        Logger::send("  KERNEL: "       . self::$config->getNested("SelectedData.kernel"),  LogLevel::INFO);
        Logger::send("  VIDEO DRIVER: " . self::$config->getNested("SelectedData.video"),   LogLevel::INFO);
        Logger::send("  SOUND DRIVER: " . self::$config->getNested("SelectedData.sound"),   LogLevel::INFO);
        Logger::send("  DESKTOP: "      . self::$config->getNested("SelectedData.desktop"), LogLevel::INFO);
        Logger::send("  FONT: "         . self::$config->getNested("SelectedData.font"),    LogLevel::INFO);

        if (!empty(self::$config->getNested("PackageData.additionals"))) {
            echo("\n");
            Logger::send("ADDITIONAL PACKAGES: " . implode(", ", array_filter(explode(" ", self::$config->getNested("PackageData.additionals")))), LogLevel::INFO);
        }

        echo("\n\n");

        Logger::send("By proceeding, you acknowledge that the author is not responsible for any incorrect actions.", LogLevel::WARNING);
        Logger::send("Check whether the entered data is correct or not",                                             LogLevel::WARNING);
        Logger::send("Save your account name and passwords",                                                         LogLevel::WARNING);
        Logger::send("Do you want to continue? [Y/n]",                                                               LogLevel::NOTICE);

        $answer = Utils::getInput("y");
        if (!in_array($answer, ['y', 'yes', '1'])) return false;

        return true;
    }
}