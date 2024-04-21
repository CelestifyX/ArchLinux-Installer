from functions.functions import *
from colors.colors       import Colors

def warning():
    clear_screen()

    print(f'{Colors.orange}WARNING{Colors.reset}: By proceeding, you acknowledge that the author is not responsible for any incorrect actions.')
    print('Do you want to continue? (Y/n) [n]')
    answer = input("> ").lower()

    if answer in ['y', 'yes', '1']:
        clear_screen()
        return True
    else:
        return False

def log_file_found_warning():
    clear_screen()

    if os.path.exists("installer.log"):
        print(f'{Colors.bold}{Colors.red}CRITICAL{Colors.reset}: The file `installer.log` exists. That`s right, the installation is complete.')
        print(f'{Colors.bold}{Colors.red}CRITICAL{Colors.reset}: It is recommended to reboot the system.')

        return False
    else:
        return True

def setup_warning(
    user_data,
    selected_data,
    package_data,
    disk_data
):
    clear_screen()

    # Information
    print(f'{Colors.cyan}Installation Confirmation{Colors.reset}:')

    disk_size_gb   = get_size(disk_data.disk, None, False)
    boot_size_gb   = get_size(disk_data.disk, disk_data.boot)
    swap_size_gb   = get_size(disk_data.disk, disk_data.swap)
    system_size_gb = get_size(disk_data.disk, disk_data.system)

    print(f'\nDISK: /dev/{disk_data.disk} ({disk_size_gb})')
    print(f'  BOOT: /dev/{disk_data.boot} ({boot_size_gb}) [FAT32]')
    print(f'  SWAP: /dev/{disk_data.swap} ({swap_size_gb})')
    print(f'  SYSTEM: /dev/{disk_data.system} ({system_size_gb}) [{selected_data.file_system}]')

    print(f'\nTIMEZONE: {user_data.timezone}')
    print(f'HOSTNAME: {user_data.hostname}')

    print(f'\nACCOUNTS:')
    print(f'  {user_data.username}: {user_data.userpassword}')
    print(f'  root: {user_data.password}')

    print(f'\nKERNEL: {selected_data.kernel}')
    print(f'VIDEO DRIVER: {selected_data.driver}')
    print(f'SOUND DRIVER: {selected_data.sound}')
    print(f'DESKTOP: {selected_data.desktop}')
    print(f'FONT: {selected_data.font}')

    if package_data.additionals is not None:
        formatted_packages = ", ".join(package_data.additionals.split())
        print(f'\nADDITIONAL PACKAGES: {formatted_packages}')

    # Warning
    print(f'\n\n{Colors.orange}WARNING{Colors.reset}: By proceeding, you acknowledge that the author is not responsible for any incorrect actions.')
    print(f'{Colors.orange}WARNING{Colors.reset}: Check whether the entered data is correct or not')
    print(f'{Colors.orange}WARNING{Colors.reset}: Save your account name and passwords')

    print('\nDo you want to continue? (Y/n) [n]')
    answer = input("> ").lower()

    if answer in ['y', 'yes', '1']:
        clear_screen()
        return True
    else:
        return False
