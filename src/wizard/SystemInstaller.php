<?php

namespace wizard;

use utils\ {
    Logger,
    LogLevel,
    Config,
    Utils
};

class SystemInstaller {
    private static ?array $config = null;

    static function init(): bool {
        foreach ([
            'initializeConfig',
            'formatDiskPartitions',
            'mountPartitions',
            'installKernelAndFstab',
            'setTimeZone',
            'configureLocaleAndKeyboard',
            'setHostnames',
            'configureUserAccounts',
            'installGrubBootloader',
            'customizePacmanConfig',
            'installAdditionalPackages',
            'finalizeConfiguration',
            'completeInstallation'
        ] as $method) {
            if (!method_exists(self::class, $method)) {
                Logger::send("Method " . $method . " in " . self::class . " class not found!", LogLevel::EMERGENCY);
                return false;
            }

            if (!self::$method()) return false;
        }

        return true;
    }

    private static function initializeConfig(): bool {
        if (!file_exists(\PATH . "settings.json")) {
            Logger::send("The settings.json file is missing.", LogLevel::ERROR);
            return false;
        }

        self::$config = (new Config(\PATH . "settings.json"))->getAll();
        return true;
    }

    private static function formatDiskPartitions(): bool {
        Utils::execute("clear");
        if (!Utils::runCommandWithProgress("mkfs.vfat -F32 /dev/" . self::$config["DiskData"]["boot"] . " >/dev/null 2>&1", "Formatting the EFI partition to FAT32")) return false;
        
        $file_system = self::$config["SelectedData"]["file_system"];

        $commands    = [
            "F2FS"  => "mkfs.f2fs -f /dev/"  . self::$config["DiskData"]["system"],
            "EXT4"  => "mkfs.ext4 -f /dev/"  . self::$config["DiskData"]["system"],
            "BTRFS" => "mkfs.btrfs -f /dev/" . self::$config["DiskData"]["system"],
            "XFS"   => "mkfs.xfs -f /dev/"   . self::$config["DiskData"]["system"]
        ];

        if (!isset($commands[$file_system]))                                                                                                    return false;
        if (!Utils::runCommandWithProgress($commands[$file_system] . " >/dev/null 2>&1", "Formatting the SYSTEM partition to " . $file_system)) return false;

        return true;
    }

    private static function mountPartitions(): bool {
        foreach ([
            ["mkdir -p /mnt >/dev/null 2>&1", "Creating the /mnt folder"],
            ["mount /dev/" . self::$config["DiskData"]["system"] . " /mnt >/dev/null 2>&1", "Mounting the / partition to the /mnt directory"],
            ["mkdir -p /mnt/boot/efi >/dev/null 2>&1", "Creating the /mnt/boot/efi folder"],
            ["mount /dev/" . self::$config["DiskData"]["boot"] . " /mnt/boot/efi >/dev/null 2>&1", "Mounting the EFI partition to the /mnt/boot/efi directory"]
        ] as [$command, $description]) {
            if (!Utils::runCommandWithProgress($command, $description)) return false;
        }

        return true;
    }

    private static function installKernelAndFstab(): bool {
        foreach ([
            [self::$config["PackageData"]["kernel"] . " >/dev/null 2>&1", "Installing the kernel"],
            ["genfstab -U /mnt >> /mnt/etc/fstab >/dev/null 2>&1", "Generating the fstab file"]
        ] as [$command, $description]) {
            if (!Utils::runCommandWithProgress($command, $description)) return false;
        }

        return true;
    }

    private static function setTimeZone(): bool {
        foreach ([
            ["arch-chroot /mnt /bin/bash -c 'ln -sf /usr/share/zoneinfo/" . self::$config["UserData"]["timezone"] . " /etc/localtime' >/dev/null 2>&1", "Setting the timezone"],
            ["arch-chroot /mnt /bin/bash -c 'hwclock --systohc' >/dev/null 2>&1", "Synchronizing the time"]
        ] as [$command, $description]) {
            if (!Utils::runCommandWithProgress($command, $description)) return false;
        }
        
        return true;
    }

    private static function configureLocaleAndKeyboard(): bool {
        foreach ([
            ["arch-chroot /mnt /bin/bash -c 'sed -i s/#en_US.UTF-8/en_US.UTF-8/g /etc/locale.gen' >/dev/null 2>&1", "Uncommenting English language settings"],
            ["arch-chroot /mnt /bin/bash -c 'locale-gen' >/dev/null 2>&1", "Generating locales"],
            ["arch-chroot /mnt /bin/bash -c 'echo LANG=en_US.UTF-8 > /etc/locale.conf' >/dev/null 2>&1", "Setting up the locale"],
            ["arch-chroot /mnt /bin/bash -c 'echo KEYMAP=en > /etc/vconsole.conf' >/dev/null 2>&1", "Setting up the keyboard layout"],
            ["arch-chroot /mnt /bin/bash -c 'echo FONT=cyr-sun16 >> /etc/vconsole.conf' >/dev/null 2>&1", "Adding a Cyrillic font"]
        ] as [$command, $description]) {
            if (!Utils::runCommandWithProgress($command, $description)) return false;
        }

        return true;
    }

    private static function setHostnames(): bool {
        foreach ([
            ["arch-chroot /mnt /bin/bash -c 'echo " . self::$config["UserData"]["hostname"] . " > /etc/hostname' >/dev/null 2>&1", "Setting the hostname"],
            ["arch-chroot /mnt /bin/bash -c 'echo 127.0.0.1 localhost > /etc/hosts' >/dev/null 2>&1", "Setting up localhost"],
            ["arch-chroot /mnt /bin/bash -c 'echo ::1 localhost >> /etc/hosts' >/dev/null 2>&1", "Adding localhost for IPv6"],
            ["arch-chroot /mnt /bin/bash -c 'echo 127.0.0.1 " . self::$config["UserData"]["hostname"] . ".localdomain " . self::$config["UserData"]["hostname"] . " >> /etc/hosts' >/dev/null 2>&1", "Adding a custom host to hosts file"]
        ] as [$command, $description]) {
            if (!Utils::runCommandWithProgress($command, $description)) return false;
        }

        return true;
    }

    private static function configureUserAccounts(): bool {
        foreach ([
            ["arch-chroot /mnt /bin/bash -c \"echo '%wheel ALL=(ALL) ALL' | EDITOR='tee -a' visudo\" >/dev/null 2>&1", "Allowing the wheel group to use sudo"],
            ["arch-chroot /mnt /bin/bash -c 'echo Defaults pwfeedback >> /etc/sudoers' >/dev/null 2>&1", "Added pwfeedback to /etc/sudoers"],
            ["arch-chroot /mnt /bin/bash -c 'useradd -m -G wheel -s /bin/bash " . self::$config["UserData"]["accounts"]["user"]["username"] . "' >/dev/null 2>&1", "Adding a user to the wheel group"],
            ["echo root:" . self::$config["UserData"]["accounts"]["root"]["password"] . " | arch-chroot /mnt chpasswd >/dev/null 2>&1", "Changing the password for root"],
            ["echo " . self::$config["UserData"]["accounts"]["user"]["username"] . ":" . self::$config["UserData"]["accounts"]["user"]["password"] . " | arch-chroot /mnt chpasswd >/dev/null 2>&1", "Changing the password for " . self::$config["UserData"]["accounts"]["user"]["username"]]
        ] as [$command, $description]) {
            if (!Utils::runCommandWithProgress($command, $description)) return false;
        }

        if (self::$config["UserData"]["accounts"]["user"]["autologin"] === "enable") {
            $config = "/etc/systemd/system/getty@tty1.service.d/override.conf";

            foreach ([
                ["mkdir -p " . dirname($config),   "Creating directory for autologin config"],
                ["echo '[Service]' > "  . $config, "Initializing autologin config file"],
                ["echo 'ExecStart=' >> " . $config, "Writing ExecStart line"],
                ["echo 'ExecStart=-/usr/bin/agetty --autologin " . self::$config["UserData"]["accounts"]["user"]["username"] . " --noclear %I \$TERM' >> " . $config, "Writing autologin command with user"]
            ] as [$command, $description]) {
                if (!Utils::runCommandWithProgress($command, $description)) return false;
            }
        }

        return true;
    }

    private static function installGrubBootloader(): bool {
        foreach ([
            ["arch-chroot /mnt /bin/bash -c 'pacman -Syy grub efibootmgr --noconfirm' >/dev/null 2>&1", "Installing the necessary utilities for EFI booting"],
            ["arch-chroot /mnt /bin/bash -c 'grub-install --target=x86_64-efi --efi-directory=/boot/efi --bootloader-id=Arch --no-nvram --removable /dev/" . self::$config["DiskData"]["disk"] . "' >/dev/null 2>&1", "Installing GRUB on the system"],
            ["arch-chroot /mnt /bin/bash -c 'grub-mkconfig -o /boot/grub/grub.cfg' >/dev/null 2>&1", "Creating a GRUB configuration file"]
        ] as [$command, $description]) {
            if (!Utils::runCommandWithProgress($command, $description)) return false;
        }

        // NOTICE: Initially there will be warnings that some modules are missing, so we will not complete the installation of the system because of these warnings.
        Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'mkinitcpio -P' >/dev/null 2>&1", "Updating the initramfs image");
        return true;
    }

    private static function customizePacmanConfig(): bool {
        foreach ([
            ["sed -i '/\\[multilib\\]/,/Include/s/^#//' /etc/pacman.conf >/dev/null 2>&1", "Activating multilib repositories (for installing the system)"],
            ["arch-chroot /mnt /bin/bash -c 'sed -i s/#ParallelDownloads\ =\ 5/ParallelDownloads\ =\ 10/g /etc/pacman.conf' >/dev/null 2>&1", "Activating ParallelDownload (for system)"],
            ["arch-chroot /mnt /bin/bash -c 'sed -i s/#VerbosePkgLists/VerbosePkgLists/g /etc/pacman.conf' >/dev/null 2>&1", "Activating detailed package list (for system)"],
            ["arch-chroot /mnt /bin/bash -c 'sed -i s/#Color/Color/g /etc/pacman.conf' >/dev/null 2>&1", "Activating colors for pacman"],
            ["sed -i '/\\[multilib\\]/,/Include/s/^#//' /mnt/etc/pacman.conf >/dev/null 2>&1", "Activating multilib repositories (for system)"]
        ] as [$command, $description]) {
            if (!Utils::runCommandWithProgress($command, $description)) return false;
        }

        return true;
    }

    private static function installAdditionalPackages(): bool {
        foreach ([
            "video"   => "Installing video drivers",
            "audio"   => "Installing audio drivers",
            "desktop" => "Installing the desktop environment",
            "font"    => "Installation of a font"
        ] as $key => $description) {
            if (self::$config["PackageData"][$key] !== null) {
                if (!Utils::runCommandWithProgress(self::$config["PackageData"][$key] . " >/dev/null 2>&1", $description)) return false;

                if (
                    isset(self::$config["ServiceData"][$key]) and
                    (self::$config["ServiceData"][$key] !== null)
                ) {
                    if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c '" . self::$config["ServiceData"][$key] . "' >/dev/null 2>&1", "Enabling " . $key . " services")) return false;
                }
            }
        }

        if (!empty(self::$config["PackageData"]["additionals"])) {
            if (!Utils::runCommandWithProgress(self::$config["PackageData"]["additionals"] . " --noconfirm >/dev/null 2>&1", "Installing additional packages " . implode(", ", Utils::getExistingPackages()))) return false;
        }

        return true;
    }

    private static function finalizeConfiguration(): bool {
        foreach ([
            ["arch-chroot /mnt /bin/bash -c 'systemctl enable NetworkManager dhcpcd' >/dev/null 2>&1", "Enabling NetworkManager and dhcpcd services"],
            ["arch-chroot /mnt /bin/bash -c 'exit' >/dev/null 2>&1", "Leaving the arch-chroot environment"],
            ["umount -R /mnt >/dev/null 2>&1", "Unmounts all partitions mounted on /mnt."]
        ] as [$command, $description]) {
            if (!Utils::runCommandWithProgress($command, $description)) return false;
        }

        return true;
    }

    private static function completeInstallation(): bool {
        echo("\n\n\n");

        Logger::send("Installation completed.",  LogLevel::INFO);
        Logger::send("Reboot system now? [Y/n]", LogLevel::NOTICE);

        $answer = Utils::getInput("y");
        if (in_array($answer, ['y', 'yes', '1'])) Utils::execute("reboot");

        return true;
    }
}
