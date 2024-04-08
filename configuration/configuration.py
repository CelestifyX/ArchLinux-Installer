from functions.functions import *

from packages.packages   import packages
from colors.colors       import Colors

def configuration(user_data, int_data, selected_data, package_data, service_data):
    print_message(f'\nEnter a new username [user]')
    username = validate_input(f"> ", ['root', 'localhost'], f'{Colors.red}ERROR{Colors.reset}: Username \'%valid%\' is not allowed. Please choose another username.').lower()

    if not username:
        user_data.username = "user"
    else:
        user_data.username = username

    generate_password_user = generate_password()
    print_message(f'\nEnter new password (for {user_data.username}) [{generate_password_user}]')
    userpassword = get_input(f"> ").strip()

    if not userpassword:
        user_data.userpassword = generate_password_user
    else:
        user_data.userpassword = userpassword

    generate_password_root = generate_password()
    print_message(f'\nEnter new password (for root) [{generate_password_root}]')
    password = get_input(f"> ").strip()

    if not password:
        user_data.password = generate_password_root
    else:
        user_data.password = password

    print_message(f'\nEnter your timezone (Example: America/New_York) [UTC]')
    timezone = validate_timezone(f"> ", f'{Colors.red}ERROR{Colors.reset}: Timezone \'%timezone%\' not found. Please enter a valid timezone.')

    if timezone:
        user_data.timezone = timezone
    else:
        return False

    print_message(f'\nEnter your hostname [usr]')
    hostname = validate_input(f"> ", ['root', 'localhost'], f'{Colors.red}ERROR{Colors.reset}: The hostname \'%valid%\' is not suitable. Please choose another hostname.')

    if not hostname:
        user_data.hostname = "usr"
    else:
        user_data.hostname = hostname

    print_message(f'\nEnter the additional packages you need (Example: zip,unzip,git) [Enter]')
    additionals_packages = get_input("> ").strip()

    additionals_packages_status = set(pkg.strip() for pkg in additionals_packages.split(',') if pkg.strip() and check_package_exists(pkg.strip()))

    if additionals_packages_status:
        package_data.additionals = ' '.join(additionals_packages_status)

    print_message(f'\nSelect kernel (1 - Linux (INTEL), 2 - Linux ZEN (INTEL), 3 - Linux LTS (INTEL), 4 - Linux (AMD), 5 - Linux ZEN (AMD), 6 - Linux LTS (AMD))')
    int_data.kernel = validate_choice("> ", ['1', '2', '3', '4', '5', '6'])

    kernel = packages.get("kernel", {}).get(int_data.kernel)

    if kernel:
        package_data.kernel  = kernel["packages"]
        selected_data.kernel = kernel["type"]
    else:
        return False

    print_message(f'\nSelect video driver (1 - INTEL (BUILT-IN), 2 - NVIDIA (PROPRIETARY), 3 - INTEL (BUILT-IN) + NVIDIA (PROPRIETARY), 4 - AMD (DISCRETE), 5 - NOTHING)')
    int_data.driver = validate_choice("> ", ['1', '2', '3', '4', '5'])

    driver = packages.get("driver", {}).get(int_data.driver)

    if driver:
        package_data.driver  = driver["packages"]
        selected_data.driver = driver["type"]
    else:
        return False

    print_message(f'\nSelect sound driver (1 - PIPEWIRE, 2 - PULSEAUDIO, 3 - NOTHING) [1]')
    int_data.sound = validate_choice("> ", ['1', '2', '3'], True)

    sound = packages.get("sound", {}).get(int_data.sound)

    if sound:
        package_data.sound  = sound["packages"]
        service_data.sound  = sound["service"]
        selected_data.sound = sound["type"]
    else:
        return False

    print_message(f'\nSelect your work environment (1 - KDE, 2 - GNOME, 3 - NOTHING) [1]')
    int_data.desktop = validate_choice("> ", ['1', '2', '3'], True)

    desktop = packages.get("desktop", {}).get(int_data.desktop)

    if desktop:
        package_data.desktop  = desktop["packages"]
        service_data.desktop  = desktop["service"]
        selected_data.desktop = desktop["type"]
    else:
        return False

    return True
