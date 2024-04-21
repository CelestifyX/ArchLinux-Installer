from functions.functions import *

def install(
    user_data,
    int_data,
    package_data,
    service_data,
    disk_data
):
    # Formatting an EFI partition to FAT32
    if not execute_and_process_command(f'mkfs.vfat -F32 /dev/{disk_data.boot}', 'Formatting the EFI partition to FAT32'):
        return False

    # Formatting the / partition to the selected file system
    if int_data.file_system == "1":
        if not execute_and_process_command(f'mkfs.f2fs -f /dev/{disk_data.system}', 'Formatting the SYSTEM partition to F2FS'):
            return False
    elif int_data.file_system == "2":
        if not execute_and_process_command(f'mkfs.ext4 -f /dev/{disk_data.system}', 'Formatting the SYSTEM partition to EXT4'):
            return False
    elif int_data.file_system == "3":
        if not execute_and_process_command(f'mkfs.btrfs -f /dev/{disk_data.system}', 'Formatting the SYSTEM partition to BTRFS'):
            return False
    else:
        return False

    # Creating a swap partition and activating it
    if not execute_and_process_command(f'mkswap -f /dev/{disk_data.swap}', 'Creating a swap partition'):
        return False
    if not execute_and_process_command(f'swapon -f /dev/{disk_data.swap}', 'Activating the swap partition'):
        return False

    # Mounting the EFI partition and / into the /mnt directory
    if not execute_and_process_command('mkdir -p /mnt/', 'Creating the /mnt folder'):
        return False
    if not execute_and_process_command(f'mount /dev/{disk_data.system} /mnt', 'Mounting the / partition to the /mnt directory'):
        return False
    if not execute_and_process_command('mkdir -p /mnt/boot/efi', 'Creating the /mnt/boot/efi folder'):
        return False
    if not execute_and_process_command(f'mount /dev/{disk_data.boot} /mnt/boot/efi', 'Mounting the EFI partition to the /mnt/boot/efi directory'):
        return False

    # Activating multilib repositories (To install the system)
    if not execute_and_process_command('sed -i "/\[multilib\]/,/Include/"\'s/^#//\' /etc/pacman.conf', 'Activating multilib repositories (for installing the system)'):
        return False

    # Installing the kernel and generating fstab file
    if not execute_and_process_command(f'{package_data.kernel}', 'Installing the kernel'):
        return False
    if not execute_and_process_command('genfstab -U /mnt >> /mnt/etc/fstab', 'Generating the fstab file'):
        return False

    # Setting the time zone and synchronizing time
    if not execute_and_process_command(f'arch-chroot /mnt /bin/bash -c "ln -sf /usr/share/zoneinfo/{user_data.timezone} /etc/localtime"', 'Setting the timezone'):
        return False
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "hwclock --systohc"', 'Synchronizing the time'):
        return False

    # Setting up the locale and generating local data
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "sed -i s/#en_US.UTF-8/en_US.UTF-8/g /etc/locale.gen"', 'Uncommenting English language settings'):
        return False
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "locale-gen"', 'Generating locales'):
        return False
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "echo LANG=en_US.UTF-8 > /etc/locale.conf"', 'Setting up the locale'):
        return False

    # Setting the keyboard layout
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "echo KEYMAP=en > /etc/vconsole.conf"', 'Setting up the keyboard layout'):
        return False
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "echo FONT=cyr-sun16 >> /etc/vconsole.conf"', 'Adding a Cyrillic font'):
        return False

    # Setting the hostname
    if not execute_and_process_command(f'arch-chroot /mnt /bin/bash -c "echo \'{user_data.hostname}\' > /etc/hostname"', 'Setting the hostname'):
        return False

    # Configuring the hosts file
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "echo 127.0.0.1 localhost > /etc/hosts"', 'Setting up localhost'):
        return False
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "echo ::1 localhost >> /etc/hosts"', 'Adding localhost for IPv6'):
        return False
    if not execute_and_process_command(f'arch-chroot /mnt /bin/bash -c "echo \'127.0.0.1 {user_data.hostname}.localdomain {user_data.hostname}\' >> /etc/hosts"', 'Adding a custom host to hosts file'):
        return False

    # Activating multilib repositories, detailed package list and installing multiple downloads (For system)
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "sed -i s/#ParallelDownloads\ =\ 5/ParallelDownloads\ =\ 10/g /etc/pacman.conf"', 'Activating multilib repositories (for system)'):
        return False
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "sed -i s/#VerbosePkgLists/VerbosePkgLists/g /etc/pacman.conf"', 'Activating detailed package list (for system)'):
        return False
    if not execute_and_process_command('sed -i "/\\[multilib\\]/,/Include/"\'s/^#//\' /mnt/etc/pacman.conf', 'Activating multilib repositories (for system)'):
        return False

    # Allowing the wheel group to use sudo
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "echo \'%wheel ALL=(ALL) ALL\' | EDITOR=\'tee -a\' visudo"', 'Allowing the wheel group to use sudo'):
        return False

    # Setting passwords and adding a user to the wheel group
    if not execute_and_process_command(f'arch-chroot /mnt /bin/bash -c "useradd -m -G wheel -s /bin/bash {user_data.username}"', 'Adding a user to the wheel group'):
        return False
    if not execute_and_process_command(f'echo \'root:{user_data.password}\' | arch-chroot /mnt chpasswd', 'Changing the password for root'):
        return False
    if not execute_and_process_command(f'echo \'{user_data.username}:{user_data.userpassword}\' | arch-chroot /mnt chpasswd', f'Changing the password for {user_data.username}'):
        return False

    # Updating the initramfs image
    os.system('arch-chroot /mnt /bin/bash -c "mkinitcpio -P >/dev/null 2>&1"')

    # Installing the necessary utilities for EFI and installing GRUB on the system
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "pacman -Syy grub efibootmgr --noconfirm"', 'Installing the necessary utilities for EFI booting'):
        return False

    # Installing GRUB on the system
    if not execute_and_process_command(f'arch-chroot /mnt /bin/bash -c "grub-install --target=x86_64-efi --efi-directory=/boot/efi --bootloader-id=Arch --no-nvram --removable /dev/{disk_data.disk}"', 'Installing GRUB on the system'):
        return False

    # Updating the Package Database
    if not execute_and_process_command('pacman -Syy --noconfirm', 'Updating the Package Database'):
        return False

    # Installing video drivers
    if not execute_and_process_command(f'{package_data.driver}', 'Installing video drivers'):
        return False

    # Installing sound drivers and enabling audio services
    if not execute_and_process_command(f'{package_data.sound}', 'Installing sound drivers'):
        return False
    if not execute_and_process_command(f'arch-chroot /mnt /bin/bash -c "{service_data.sound}"', 'Enabling audio services'):
        return False

    # Installation of the working environment and font
    if not execute_and_process_command(f'{package_data.desktop}', 'Installing the desktop environment'):
        return False
    if not execute_and_process_command(f'{package_data.font}', 'Installation a font'):
        return False

    # Installing additional packages
    if package_data.additionals is not None:
        formatted_packages = ", ".join(package_data.additionals.split())
        if not execute_and_process_command(f'pacstrap -i /mnt {package_data.additionals} --noconfirm', f'Installing additional packages ({formatted_packages})'):
            return False

    # Activating the greeter-sessiont
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "sed -i s/#greeter-session=lightdm-slick-greeter/greeter-session=lightdm-slick-greeter/g /etc/lightdm/lightdm.conf >/dev/null 2>&1; RETVAL=$?"', 'Activating the greeter-session'):
        return False

    # Enabling graphical display services
    if not execute_and_process_command(f'arch-chroot /mnt /bin/bash -c "{service_data.desktop}"', 'Enabling graphical display services'):
        return False

    # Enabling NetworkManager and dhcpcd services
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "systemctl enable NetworkManager dhcpcd"', 'Enabling NetworkManager and dhcpcd services'):
        return False

    # Creating a GRUB configuration file
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "grub-mkconfig -o /boot/grub/grub.cfg"', 'Creating a GRUB configuration file'):
        return False

    # Leaving a arch-chroot environment
    if not execute_and_process_command('arch-chroot /mnt /bin/bash -c "exit"', 'Leaving the arch-chroot environment'):
        return False

    # Unmounts all partitions mounted on /mnt.
    if not execute_and_process_command('umount -R /mnt', 'Unmounts all partitions mounted on /mnt.'):
        return False

    return True
