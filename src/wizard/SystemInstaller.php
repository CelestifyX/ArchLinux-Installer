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

        self::$config = (new Config(\PATH . "settings.json", Config::JSON))->getAll();
        return true;
    }

    private static function formatDiskPartitions(): bool {
        Utils::clear_screen();
        if (!Utils::runCommandWithProgress("mkfs.vfat -F32 /dev/" . self::$config["DiskData"]["boot"] . " >/dev/null 2>&1", "Formatting the EFI partition to FAT32")) return false;
        
        $file_system = self::$config["SelectedData"]["file_system"];

        if ($file_system === "F2FS") {
            if (!Utils::runCommandWithProgress("mkfs.f2fs -f /dev/"  . self::$config["DiskData"]["system"] . " >/dev/null 2>&1", "Formatting the SYSTEM partition to " . $file_system)) return false;
        } elseif ($file_system === "EXT4") {
            if (!Utils::runCommandWithProgress("mkfs.ext4 -f /dev/"  . self::$config["DiskData"]["system"] . " >/dev/null 2>&1", "Formatting the SYSTEM partition to " . $file_system)) return false;
        } elseif ($file_system === "BTRFS") {
            if (!Utils::runCommandWithProgress("mkfs.btrfs -f /dev/" . self::$config["DiskData"]["system"] . " >/dev/null 2>&1", "Formatting the SYSTEM partition to " . $file_system)) return false;
        } else {
            return false;
        }

        return true;
    }

    private static function mountPartitions(): bool {
        if (!Utils::runCommandWithProgress("mkdir -p /mnt >/dev/null 2>&1", "Creating the /mnt folder"))                                                                                       return false;
        if (!Utils::runCommandWithProgress("mount /dev/" . self::$config["DiskData"]["system"] . " /mnt >/dev/null 2>&1", "Mounting the / partition to the /mnt directory"))                   return false;
        if (!Utils::runCommandWithProgress("mkdir -p /mnt/boot/efi >/dev/null 2>&1", "Creating the /mnt/boot/efi folder"))                                                                     return false;
        if (!Utils::runCommandWithProgress("mount /dev/" . self::$config["DiskData"]["boot"] . " /mnt/boot/efi >/dev/null 2>&1", "Mounting the EFI partition to the /mnt/boot/efi directory")) return false;

        return true;
    }

    private static function installKernelAndFstab(): bool {
        if (!Utils::runCommandWithProgress(self::$config["PackageData"]["kernel"] . " >/dev/null 2>&1", "Installing the kernel"))   return false;
        if (!Utils::runCommandWithProgress("genfstab -U /mnt >> /mnt/etc/fstab >/dev/null 2>&1", "Generating the fstab file")) return false;

        return true;
    }

    private static function setTimeZone(): bool {
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'ln -sf /usr/share/zoneinfo/" . self::$config["UserData"]["timezone"] . "' >/dev/null 2>&1", "Setting the timezone")) return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'hwclock --systohc' >/dev/null 2>&1", "Synchronizing the time"))                                                      return false;

        return true;
    }

    private static function configureLocaleAndKeyboard(): bool {
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'sed -i s/#en_US.UTF-8/en_US.UTF-8/g /etc/locale.gen' >/dev/null 2>&1", "Uncommenting English language settings")) return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'locale-gen' >/dev/null 2>&1", "Generating locales"))                                                              return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'echo LANG=en_US.UTF-8 > /etc/locale.conf' >/dev/null 2>&1", "Setting up the locale"))                             return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'echo KEYMAP=en > /etc/vconsole.conf' >/dev/null 2>&1", "Setting up the keyboard layout"))                         return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'echo FONT=cyr-sun16 >> /etc/vconsole.conf' >/dev/null 2>&1", "Adding a Cyrillic font"))                           return false;

        return true;
    }

    private static function setHostnames(): bool {
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'echo " . self::$config["UserData"]["hostname"] . " > /etc/hostname' >/dev/null 2>&1", "Setting the hostname"))                                                                                 return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'echo 127.0.0.1 localhost > /etc/hosts' >/dev/null 2>&1", "Setting up localhost"))                                                                                                              return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'echo ::1 localhost >> /etc/hosts' >/dev/null 2>&1", "Adding localhost for IPv6"))                                                                                                              return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'echo 127.0.0.1 " . self::$config["UserData"]["hostname"] . ".localdomain " . self::$config["UserData"]["hostname"] . " >> /etc/hosts' >/dev/null 2>&1", "Adding a custom host to hosts file")) return false;

        return true;
    }

    private static function configureUserAccounts(): bool {
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c \"echo '%wheel ALL=(ALL) ALL' | EDITOR='tee -a' visudo\" >/dev/null 2>&1", "Allowing the wheel group to use sudo"))                                                                    return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'useradd -m -G wheel -s /bin/bash " . self::$config["UserData"]["user"] . "' >/dev/null 2>&1", "Adding a user to the wheel group"))                                                  return false;
        if (!Utils::runCommandWithProgress("echo root:" . self::$config["UserData"]["password"] . " | arch-chroot /mnt chpasswd >/dev/null 2>&1", "Changing the password for root"))                                                                           return false;
        if (!Utils::runCommandWithProgress("echo " . self::$config["UserData"]["user"] . ":" . self::$config["UserData"]["userpassword"] . " | arch-chroot /mnt chpasswd >/dev/null 2>&1", "Changing the password for " . self::$config["UserData"]["user"])) return false;

        return true;
    }

    private static function installGrubBootloader(): bool {
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'pacman -Syy grub efibootmgr --noconfirm' >/dev/null 2>&1", "Installing the necessary utilities for EFI booting"))                                                                                          return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'grub-install --target=x86_64-efi --efi-directory=/boot/efi --bootloader-id=Arch --no-nvram --removable /dev/" . self::$config["DiskData"]["disk"] . "' >/dev/null 2>&1", "Installing GRUB on the system")) return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'grub-mkconfig -o /boot/grub/grub.cfg' >/dev/null 2>&1", "Creating a GRUB configuration file"))                                                                                                             return false;
        
        // NOTICE: Initially there will be warnings that some modules are missing, so we will not complete the installation of the system because of these warnings.
        Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'mkinitcpio -P' >/dev/null 2>&1", "Updating the initramfs image");

        return true;
    }

    private static function customizePacmanConfig(): bool {
        if (!Utils::runCommandWithProgress("sed -i '/\\[multilib\\]/,/Include/s/^#//' /etc/pacman.conf >/dev/null 2>&1", "Activating multilib repositories (for installing the system)"))                                return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'sed -i s/#ParallelDownloads\ =\ 5/ParallelDownloads\ =\ 10/g /etc/pacman.conf' >/dev/null 2>&1", "Activating ParallelDownload (for system)")) return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'sed -i s/#VerbosePkgLists/VerbosePkgLists/g /etc/pacman.conf' >/dev/null 2>&1", "Activating detailed package list (for system)"))             return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'sed -i s/#Color/Color/g /etc/pacman.conf' >/dev/null 2>&1", "Activating colors for pacman"))                                                  return false;
        if (!Utils::runCommandWithProgress("sed -i '/\\[multilib\\]/,/Include/s/^#//' /mnt/etc/pacman.conf >/dev/null 2>&1", "Activating multilib repositories (for system)"))                                           return false;

        return true;
    }

    private static function installAdditionalPackages(): bool {
        if (self::$config["PackageData"]["video"] !== null) {
            if (!Utils::runCommandWithProgress(self::$config["PackageData"]["video"] . " >/dev/null 2>&1", "Installing video drivers")) return false;
        }

        if (self::$config["PackageData"]["sound"] !== null) {
            if (!Utils::runCommandWithProgress(self::$config["PackageData"]["sound"] . " >/dev/null 2>&1", "Installing sound drivers")) return false;

            if (self::$config["ServiceData"]["sound"] !== null) {
                if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c '" . self::$config["ServiceData"]["sound"] . "' >/dev/null 2>&1", "Enabling audio services")) return false;
            }
        }

        if (self::$config["PackageData"]["desktop"] !== null) {
            if (!Utils::runCommandWithProgress(self::$config["PackageData"]["desktop"] . " >/dev/null 2>&1", "Installing the desktop environment")) return false;

            if (self::$config["ServiceData"]["desktop"] !== null) {
                if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c '" . self::$config["ServiceData"]["desktop"] . "' >/dev/null 2>&1", "Enabling graphical display services")) return false;
            }
        }

        if (self::$config["PackageData"]["font"] !== null) {
            if (!Utils::runCommandWithProgress(self::$config["PackageData"]["font"] . " >/dev/null 2>&1", "Installation a font")) return false;
        }

        if (!empty(self::$config["PackageData"]["additionals"])) {
            $formatted_packages = implode(", ", array_filter(explode(" ", self::$config["PackageData"]["additionals"])));
            if (!Utils::runCommandWithProgress("pacstrap -i /mnt " . self::$config["PackageData"]["additionals"] . " --noconfirm >/dev/null 2>&1", "Installing additional packages " . $formatted_packages)) return false;
        }

        return true;
    }

    private static function finalizeConfiguration(): bool {
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'sed -i s/#greeter-session=lightdm-slick-greeter/greeter-session=lightdm-slick-greeter/g /etc/lightdm/lightdm.conf' >/dev/null 2>&1; RETVAL=$?", "Activating the greeter-session")) return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'systemctl enable NetworkManager dhcpcd' >/dev/null 2>&1", "Enabling NetworkManager and dhcpcd services"))                                                                          return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'echo Defaults pwfeedback >> /etc/sudoers' >/dev/null 2>&1", "Added pwfeedback to /etc/sudoers"))                                                                                   return false;
        if (!Utils::runCommandWithProgress("arch-chroot /mnt /bin/bash -c 'exit' >/dev/null 2>&1", "Leaving the arch-chroot environment"))                                                                                                                    return false;
        if (!Utils::runCommandWithProgress("umount -R /mnt >/dev/null 2>&1", "Unmounts all partitions mounted on /mnt."))                                                                                                                                     return false;

        return true;
    }

    private static function completeInstallation(): bool {
        Utils::sendLogFileToServer();
        echo("\n\n\n");

        Logger::send("Installation completed.",  LogLevel::INFO);
        Logger::send("Reboot system now? [Y/n]", LogLevel::NOTICE);

        $answer = Utils::getInput("y");
        if (!in_array($answer, ['y', 'yes', '1'])) exit(0);

        Utils::execute("reboot");
        return true;
    }
}