# ArchLinux-Installer

An Arch-Linux installer written in PHP.

## Installation

1. Install Git and PHP:
    ```bash
    pacman -Sy git php
    ```

2. Clone the repository:
    ```bash
    git clone https://github.com/CelestifyX/ArchLinux-Installer
    ```

3. Navigate to the project directory:
    ```bash
    cd ArchLinux-Installer
    ```

4. Run the installer:
    ```bash
    ./start
    ```

## Usage

- [Partition your disk: **Partition 1 - EFI** (512MB or more), **Partition 2 - SYSTEM** (use the remaining space).](GUIDE.md)
- Run `./start` to start the installation process.
- Follow the prompts to configure your Arch Linux installation.