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

        $answer = Utils::getInput("y");
        if (!in_array($answer, ['y', 'yes', '1'])) return false;

        return true;
    }

    private static function showActionsWarning(): bool {
        Utils::execute("clear");

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

        self::$config->setNested("DiskData.disk", $answer);

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Enter BOOT partition (EXAMPLE: sda1, sdc1, nvme0n1p1)", LogLevel::INFO);

        $answer = Utils::validateDevice(Utils::getInput(null), "Partition %device not found.", "You didn`t enter any disk name.");
        if ($answer === false) return false;

        self::$config->setNested("DiskData.boot", $answer);

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Enter SYSTEM partition (EXAMPLE: sda2, sdc2, nvme0n1p2)", LogLevel::INFO);

        $answer = Utils::validateDevice(Utils::getInput(null), "Partition %device not found.", "You didn`t enter any disk name.");
        if ($answer === false) return false;

        self::$config->setNested("DiskData.system", $answer);

        // ----------------------------------------------------------------------------------------------------------
        Logger::send("Select file system type for system (0 - F2FS, 1 - EXT4, 2 - BTRFS, 3 - XFS) [0]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3'], true);
        if ($answer === false) return false;

        self::$config->setNested("SelectedData.file_system", [
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

        $answer = Utils::validateInput(Utils::getInput("user"), ['root', 'localhost'], "Username \"%valid\" is not allowed. Please choose another username.", "user");
        if ($answer === false) return false;

        self::$config->setNested("UserData.accounts.user.username", $answer);

        // ---------------------------------------------------------------------------------------------------------
        $random = Utils::generatePassword();

        Logger::send("Enter a new password (for " . $answer . ") [" . $random . "]", LogLevel::INFO);
        $answer = Utils::getInput($random);

        self::$config->setNested("UserData.accounts.user.password", $answer);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter a new password (for root) [" . $random . "]", LogLevel::INFO);
        $answer = Utils::getInput($random);

        self::$config->setNested("UserData.accounts.root.password", $answer);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Please confirm if you want to enable autologin for the user [y/N]:", LogLevel::INFO);

        $answer = Utils::getInput(null);
        if (in_array($answer, ['y', 'yes', '1'])) $answer = "enable";

        self::$config->setNested("UserData.accounts.user.autologin", $answer);
        return true;
    }

    private static function configureBaseSettings(): bool {
        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter your hostname [usr]", LogLevel::INFO);

        $answer = Utils::validateInput(Utils::getInput("usr"), ['localhost'], "The hostname '%valid' is not suitable. Please choose another hostname.", "usr");
        if ($answer === false) return false;

        self::$config->setNested("UserData.hostname", $answer);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Enter your timezone (Example: America/Chicago) [UTC]", LogLevel::INFO);

        $answer = Utils::validateTimezone(Utils::getInput("UTC"), "UTC");
        if ($answer === false) return false;

        self::$config->setNested("UserData.timezone", $answer);
        return true;
    }

    private static function configurePackages(): bool {
        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a kernel (0 - LINUX (INTEL), 1 - LINUX-ZEN (INTEL), 2 - LINUX LTS (INTEL), 3 - LINUX (AMD), 4 - LINUX-ZEN (AMD), 5 - LINUX LTS (AMD))", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3', '4', '5']);
        if ($answer === false) return false;

        self::$config->setNested("PackageData.kernel",  "pacstrap -i /mnt " . Utils::arrayToString(self::$packages["kernel"][$answer]["packages"]) . " --noconfirm");
        self::$config->setNested("SelectedData.kernel", self::$packages["kernel"][$answer]["type"]);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a video driver (0 - INTEL (BUILT-IN), 1 - NVIDIA (PROPRIETARY), 2 - INTEL (BUILT-IN) + NVIDIA (PROPRIETARY), 3 - AMD (DISCRETE), 4 - NOTHING)", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3', '4']);
        if ($answer === false) return false;

        self::$config->setNested("PackageData.video",  (!empty(self::$packages["video"][$answer]["packages"] ?? []) ? "pacstrap -i /mnt " . Utils::arrayToString(self::$packages["video"][$answer]["packages"]) . " --noconfirm" : null));
        self::$config->setNested("ServiceData.video",  (!empty(self::$packages["video"][$answer]["service"]  ?? []) ? "systemctl enable " . Utils::arrayToString(self::$packages["video"][$answer]["service"])  . " --force"     : null));
        self::$config->setNested("SelectedData.video", self::$packages["video"][$answer]["type"]);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a audio driver (0 - PIPEWIRE, 1 - PULSEAUDIO, 2 - ALSA, 3 - NOTHING) [0]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3'], true);
        if ($answer === false) return false;

        self::$config->setNested("PackageData.audio",  (!empty(self::$packages["audio"][$answer]["packages"] ?? []) ? "pacstrap -i /mnt " . Utils::arrayToString(self::$packages["audio"][$answer]["packages"]) . " --noconfirm" : null));
        self::$config->setNested("ServiceData.audio",  (!empty(self::$packages["audio"][$answer]["service"]  ?? []) ? "systemctl enable " . Utils::arrayToString(self::$packages["audio"][$answer]["service"])  . " --force"     : null));
        self::$config->setNested("SelectedData.audio", self::$packages["audio"][$answer]["type"]);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select the desktop environment (0 - KDE, 1 - GNOME, 2 - XFCE4, 3 - CINNAMON, 4 - BUDGIE, 5 - NOTHING) [0]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1', '2', '3', '4', '5'], true);
        if ($answer === false) return false;

        self::$config->setNested("PackageData.desktop",  (!empty(self::$packages["desktop"][$answer]["packages"] ?? []) ? "pacstrap -i /mnt " . Utils::arrayToString(self::$packages["desktop"][$answer]["packages"]) . " --noconfirm" : null));
        self::$config->setNested("ServiceData.desktop",  (!empty(self::$packages["desktop"][$answer]["service"]  ?? []) ? "systemctl enable " . Utils::arrayToString(self::$packages["desktop"][$answer]["service"])  . " --force"     : null));
        self::$config->setNested("SelectedData.desktop", self::$packages["desktop"][$answer]["type"]);

        // ---------------------------------------------------------------------------------------------------------
        Logger::send("Select a font (0 - NOTO-FONTS, 1 - NOTHING) [0]", LogLevel::INFO);

        $answer = Utils::validateChoice(Utils::getInput(null), ['0', '1'], true);
        if ($answer === false) return false;

        self::$config->setNested("PackageData.font",  (!empty(self::$packages["font"][$answer]["packages"] ?? []) ? "pacstrap -i /mnt " . Utils::arrayToString(self::$packages["font"][$answer]["packages"]) . " --noconfirm" : null));
        self::$config->setNested("SelectedData.font", self::$packages["font"][$answer]["type"]);

        // ---------------------------------------------------------------------------------------------------------
        $packages = "";
        foreach (Utils::getExistingPackages() as $package) $packages .= $package . " ";

        self::$config->setNested("PackageData.additionals", "pacstrap -i /mnt " . rtrim($packages) . " --noconfirm");
        return true;
    }

    private static function showInformation(): bool {
        Utils::execute("clear");
        Logger::send("Installation Confirmation:", LogLevel::DEBUG);
        
        echo("\n");

        Logger::send("DISK: /dev/"     . self::$config->getNested("DiskData.disk")   . " " . Utils::getSize(self::$config->getNested("DiskData.disk"), null, false),                                                                                                     LogLevel::INFO);
        Logger::send("  BOOT: /dev/"   . self::$config->getNested("DiskData.boot")   . " " . Utils::getSize(self::$config->getNested("DiskData.disk"), self::$config->getNested("DiskData.boot"))   . " [FAT32]",                                                        LogLevel::INFO);
        Logger::send("  SYSTEM: /dev/" . self::$config->getNested("DiskData.system") . " " . Utils::getSize(self::$config->getNested("DiskData.disk"), self::$config->getNested("DiskData.system")) . " [" . self::$config->getNested("SelectedData.file_system") . "]", LogLevel::INFO);
        
        echo("\n");

        Logger::send("ACCOUNTS:",                                                                                                                                                                                                                                              LogLevel::INFO);
        Logger::send("  root: " . self::$config->getNested("UserData.accounts.root.password"),                                                                                                                                                                                 LogLevel::INFO);
        Logger::send("  " . self::$config->getNested("UserData.accounts.user.username") . ": " . self::$config->getNested("UserData.accounts.user.password") . ", AUTOLOGIN: " . ((self::$config->getNested("UserData.accounts.user.autologin") === "enable") ? "YES" : "NO"), LogLevel::INFO);

        echo("\n");

        Logger::send("TIMEZONE: " . self::$config->getNested("UserData.timezone"), LogLevel::INFO);
        Logger::send("HOSTNAME: " . self::$config->getNested("UserData.hostname"), LogLevel::INFO);

        echo("\n");

        Logger::send("CONFIGURATION:",                                                      LogLevel::INFO);
        Logger::send("  KERNEL: "       . self::$config->getNested("SelectedData.kernel"),  LogLevel::INFO);
        Logger::send("  VIDEO DRIVER: " . self::$config->getNested("SelectedData.video"),   LogLevel::INFO);
        Logger::send("  AUDIO DRIVER: " . self::$config->getNested("SelectedData.audio"),   LogLevel::INFO);
        Logger::send("  DESKTOP: "      . self::$config->getNested("SelectedData.desktop"), LogLevel::INFO);
        Logger::send("  FONT: "         . self::$config->getNested("SelectedData.font"),    LogLevel::INFO);

        if (!empty(self::$config->getNested("PackageData.additionals"))) {
            echo("\n");
            Logger::send("ADDITIONAL PACKAGES: " . implode(", ", Utils::getExistingPackages()), LogLevel::INFO);
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
