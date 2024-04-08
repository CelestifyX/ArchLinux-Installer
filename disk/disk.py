from functions.functions import *
from colors.colors       import Colors

def disk(disk_data, selected_data, int_data):
    execute_command('fdisk -l')

    print_message(f'\n\n\nEnter the drive name (EXAMPLE: sda, sdc, nvme0n1)')
    disk = validate_device("> ", f'{Colors.red}ERROR{Colors.reset}: Disk \'%device%\' not found.', f'{Colors.red}ERROR{Colors.reset}: You didn`t enter any disk name.')

    if disk:
        disk_data.disk = disk
    else:
        return False

    get_input(f'\nPress Enter to begin partitioning the /dev/{disk_data.disk} drive (1 - EFI, 2 - SWAP, 3 - /)')
    execute_command(f'cfdisk --zero /dev/{disk_data.disk}')
    clear_screen()

    print_message(f'\nEnter BOOT partition (EXAMPLE: sda1, sdc1, nvme0n1p1)')
    boot = validate_device("> ", f'{Colors.red}ERROR{Colors.reset}: Partition \'%device%\' not found.', f'{Colors.red}ERROR{Colors.reset}: You didn`t enter any partition name.')

    if boot:
        disk_data.boot = boot
    else:
        return False

    print_message(f'\nEnter SWAP partition (EXAMPLE: sda2, sdc2, nvme0n1p2)')
    swap = validate_device("> ", f'{Colors.red}ERROR{Colors.reset}: Partition \'%device%\' not found.', f'{Colors.red}ERROR{Colors.reset}: You didn`t enter any partition name.')

    if swap:
        disk_data.swap = swap
    else:
        return False

    print_message(f'\nEnter ROOT partition (EXAMPLE: sda3, sdc3, nvme0n1p3)')
    system = validate_device("> ", f'{Colors.red}ERROR{Colors.reset}: Partition \'%device%\' not found.', f'{Colors.red}ERROR{Colors.reset}: You didn`t enter any partition name.')

    if system:
        disk_data.system = system
    else:
        return False

    print_message(f'\nSelect file system type for / (1 - F2FS, 2 - EXT4, 3 - BTRFS) [1]')
    int_data.file_system = validate_choice("> ", ['1', '2', '3'], True)

    if int_data.file_system == "1":
        selected_data.file_system = "F2FS"
    elif int_data.file_system == "2":
        selected_data.file_system = "EXT4"
    elif int_data.file_system == "3":
        selected_data.file_system = "BTRFS"
    else:
        return False

    return True
