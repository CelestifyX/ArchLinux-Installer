from functions.functions import *
from colors.colors       import Colors

def disk(
    disk_data,
    selected_data,
    int_data
):
    execute_command('fdisk -l')

    print('\n\n\nEnter the drive name (EXAMPLE: sda, sdc, nvme0n1)')
    disk = validate_device("> ", f'{Colors.red}ERROR{Colors.reset}: Disk \'%device%\' not found.', f'{Colors.red}ERROR{Colors.reset}: You didn`t enter any disk name.')

    if disk:
        disk_data.disk = disk
    else:
        return False

    input(f'\nPress Enter to begin partitioning the /dev/{disk_data.disk} drive (1 - EFI, 2 - SYSTEM)')
    execute_command(f'cfdisk --zero /dev/{disk_data.disk}')
    clear_screen()

    print('\nEnter BOOT partition (EXAMPLE: sda1, sdc1, nvme0n1p1)')
    boot = validate_device("> ", f'{Colors.red}ERROR{Colors.reset}: Partition \'%device%\' not found.', f'{Colors.red}ERROR{Colors.reset}: You didn`t enter any partition name.')

    if boot:
        disk_data.boot = boot
    else:
        return False

    print('\nEnter SYSTEM partition (EXAMPLE: sda2, sdc2, nvme0n1p2)')
    system = validate_device("> ", f'{Colors.red}ERROR{Colors.reset}: Partition \'%device%\' not found.', f'{Colors.red}ERROR{Colors.reset}: You didn`t enter any partition name.')

    if system:
        disk_data.system = system
    else:
        return False

    print('\nSelect file system type for system (1 - F2FS, 2 - EXT4, 3 - BTRFS) [1]')
    int_data.file_system = validate_choice("> ", ['1', '2', '3'], True)

    file_system = {
        "1": "F2FS",
        "2": "EXT4",
        "3": "BTRFS"
    }.get(int_data.file_system)

    if file_system is not None:
        selected_data.file_system = file_system
    else:
        return False

    return True
