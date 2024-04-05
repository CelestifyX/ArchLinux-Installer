from functions.functions import *
from colors.colors       import Colors

def warning():
    clear_screen()

    print_message(f'{Colors.orange}WARNING{Colors.reset}: By proceeding, you acknowledge that the author is not responsible for any incorrect actions.')
    print_message(f'Do you want to continue? (Y/n) [n]')
    answer = get_input(f"> ").lower()

    if answer in ['y', 'yes', '1']:
        clear_screen()
        return True
    else:
        return False

def setup_warning(user_data, selected_data, package_data):
    clear_screen()

    # Information
    print_message(f'{Colors.cyan}Installation Confirmation{Colors.reset}:')

    print_message(f'\nDISK: /dev/{user_data.disk} [{selected_data.file_system}]')
    print_message(f'  BOOT: {selected_data.boot}')
    print_message(f'  SWAP: {selected_data.swap}')
    print_message(f'  ROOT: {selected_data.root}')

    print_message(f'\nTIMEZONE: {user_data.timezone}')
    print_message(f'HOSTNAME: {user_data.hostname}')

    print_message(f'\nUSER & PASSWORD:')
    print_message(f'  {user_data.username}: {user_data.userpassword}')
    print_message(f'  root: {user_data.password}')

    print_message(f'\nKERNEL: {selected_data.kernel}')
    print_message(f'VIDEO DRIVER: {selected_data.driver}')
    print_message(f'SOUND DRIVER: {selected_data.sound}')
    print_message(f'DESKTOP: {selected_data.desktop}')

    if package_data.additionals is not None:
        print_message(f'\nADDITIONAL PACKAGES: {package_data.additionals}')

    # Warning
    print_message(f'\n\n{Colors.orange}WARNING{Colors.reset}: By proceeding, you acknowledge that the author is not responsible for any incorrect actions.')
    print_message(f'{Colors.orange}WARNING{Colors.reset}: Check whether the entered data is correct or not')
    print_message(f'{Colors.orange}WARNING{Colors.reset}: Save your account name and passwords')

    print_message(f'\nDo you want to continue? (Y/n) [n]')
    answer = get_input(f"> ").lower()

    if answer in ['y', 'yes', '1']:
        clear_screen()
        return True
    else:
        return False
