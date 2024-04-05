packages = {
    "kernel": {
        "1": {
            "packages": "pacstrap -i /mnt base base-devel coreutils util-linux linux linux-headers linux-firmware sudo intel-ucode iucode-tool nano dhcpcd dhclient networkmanager --noconfirm",
            "type":     "LINUX (INTEL)"
        },

        "2": {
            "packages": "pacstrap -i /mnt base base-devel coreutils util-linux linux-zen linux-zen-headers linux-firmware sudo intel-ucode iucode-tool nano dhcpcd dhclient networkmanager --noconfirm",
            "type":     "LINUX-ZEN (INTEL)"
        },

        "3": {
            "packages": "pacstrap -i /mnt base base-devel coreutils util-linux linux-lts linux-lts-headers linux-firmware sudo intel-ucode iucode-tool nano dhcpcd dhclient networkmanager --noconfirm",
            "type":     "LINUX-LTS (INTEL)"
        },

        "4": {
            "packages": "pacstrap -i /mnt base base-devel linux linux-headers linux-firmware amd-ucode archlinux-keyring nano dhcpcd dhclient networkmanager --noconfirm",
            "type":     "LINUX (AMD)"
        },

        "5": {
            "packages": "pacstrap -i /mnt base base-devel linux-zen linux-zen-headers linux-firmware amd-ucode archlinux-keyring nano dhcpcd dhclient networkmanager --noconfirm",
            "type":     "LINUX-ZEN (AMD)"
        },

        "6": {
            "packages": "pacstrap -i /mnt base base-devel linux-lts linux-lts-headers linux-firmware amd-ucode archlinux-keyring nano dhcpcd dhclient networkmanager --noconfirm",
            "type":     "LINUX-LTS (AMD)"
        }
    },

    "driver": {
        "1": {
            "packages": "pacstrap -i /mnt mesa mesa-demos xf86-video-intel lib32-mesa vulkan-intel lib32-vulkan-intel vulkan-icd-loader lib32-vulkan-icd-loader network-manager-applet libva-intel-driver lib32-libva-intel-driver --noconfirm",
            "type":     "INTEL (BUILT-IN)"
        },

        "2": {
            "packages": "pacstrap -i /mnt vulkan-icd-loader lib32-vulkan-icd-loader nvidia-dkms nvidia-utils lib32-nvidia-utils vulkan-icd-loader lib32-vulkan-icd-loader lib32-opencl-nvidia opencl-nvidia network-manager-applet nvidia-settings --noconfirm",
            "type":     "NVIDIA (PROPRIETARY)"
        },

        "3": {
            "packages": "pacstrap -i /mnt xf86-video-intel vulkan-intel lib32-vulkan-intel vulkan-icd-loader lib32-vulkan-icd-loader network-manager-applet libva-intel-driver lib32-libva-intel-driver nvidia-dkms nvidia-settings nvidia-prime --noconfirm",
            "type":     "INTEL (BUILT-IN) + NVIDIA (PROPRIETARY) [HYBRID]"
        },

        "4": {
            "packages": "pacstrap -i /mnt xf86-video-ati xf86-video-amdgpu mesa mesa-demos lib32-mesa vulkan-radeon lib32-vulkan-radeon vulkan-icd-loader lib32-vulkan-icd-loader amdvlk lib32-amdvlk network-manager-applet --noconfirm",
            "type":     "AMD (DISCRETE)"
        },

        "5": {
            "packages": "command > /dev/null",
            "type":     "NOTHING"
        }
    },

    "sound": {
        "1": {
            "packages": "pacstrap -i /mnt pipewire pipewire-media-session pipewire-audio pipewire-alsa pipewire-jack pipewire-pulse --noconfirm",
            "service":  "command > /dev/null",
            "type":     "PIPEWIRE"
        },

        "2": {
            "packages": "pacstrap -i /mnt pulseaudio pulseaudio-alsa pulseaudio-bluetooth pulseaudio-jack pulseaudio-zeroconf --noconfirm",
            "service":  "systemctl enable pulseaudio --force",
            "type":     "PULSEAUDIO"
        },

        "3": {
            "packages": "command > /dev/null",
            "service":  "command > /dev/null",
            "type":     "NOTHING"
        }
    },

    "desktop": {
        "1": {
            "packages": "pacstrap -i /mnt xorg xorg-server bluez bluez-utils xorg-xwayland plasma sddm kate dolphin konsole discover elisa ark gwenview sddm-kcm spectacle fwupd noto-fonts-emoji power-profiles-daemon bluez bluez-utils qt6-quick3d kdeplasma-addons --noconfirm",
            "service":  "systemctl enable sddm bluetooth --force",
            "type":     "KDE PLASMA"
        },

        "2": {
            "packages": "pacstrap -i /mnt xorg xorg-server gnome nautilus gnome-terminal gnome-tweaks gnome-shell-extensions rhythmbox gdm --noconfirm",
            "service":  "systemctl enable gdm --force",
            "type":     "GNOME"
        },

        "3": {
            "packages": "command > /dev/null",
            "service":  "command > /dev/null",
            "type":     "NOTHING"
        }
    }
}
