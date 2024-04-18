from functions.functions import *

from packages.packages   import packages
from colors.colors       import Colors

def configuration(
    user_data,
    int_data,
    selected_data,
    package_data,
    service_data
):
    print('\nEnter a new username [user]')
    username = validate_input(f"> ", ['root', 'localhost'], f'{Colors.red}ERROR{Colors.reset}: Username \'%valid%\' is not allowed. Please choose another username.').lower()

    if not username:
        user_data.username = "user"
    else:
        user_data.username = username

    generate_password_user = generate_password()
    print(f'\nEnter new password (for {user_data.username}) [{generate_password_user}]')
    userpassword = input(f"> ").strip()

    if not userpassword:
        user_data.userpassword = generate_password_user
    else:
        user_data.userpassword = userpassword

    generate_password_root = generate_password()
    print(f'\nEnter new password (for root) [{generate_password_root}]')
    password = input(f"> ").strip()

    if not password:
        user_data.password = generate_password_root
    else:
        user_data.password = password

    print('\nEnter your timezone (Example: America/New_York) [UTC]')
    timezone = validate_timezone(f"> ", f'{Colors.red}ERROR{Colors.reset}: Timezone \'%timezone%\' not found. Please enter a valid timezone.')

    if timezone:
        user_data.timezone = timezone
    else:
        return False

    print('\nEnter your hostname [usr]')
    hostname = validate_input(f"> ", ['root', 'localhost'], f'{Colors.red}ERROR{Colors.reset}: The hostname \'%valid%\' is not suitable. Please choose another hostname.')

    if not hostname:
        user_data.hostname = "usr"
    else:
        user_data.hostname = hostname

    print('\nSelect a font (1 - NOTO-FONTS, 2 - NOTHING) [1]')
    int_data.font = validate_choice("> ", ['1', '2'], True)

    font = packages.get("font", {}).get(int_data.font)

    if font:
        package_data.font  = font["packages"]
        selected_data.font = font["type"]
    else:
        return False

    print('\nSelect kernel (1 - LINUX (INTEL), 2 - LINUX ZEN (INTEL), 3 - LINUX LTS (INTEL), 4 - LINUX (AMD), 5 - LINUX ZEN (AMD), 6 - LINUX LTS (AMD))')
    int_data.kernel = validate_choice("> ", ['1', '2', '3', '4', '5', '6'])

    kernel = packages.get("kernel", {}).get(int_data.kernel)

    if kernel:
        package_data.kernel  = kernel["packages"]
        selected_data.kernel = kernel["type"]
    else:
        return False

    print('\nSelect video driver (1 - INTEL (BUILT-IN), 2 - NVIDIA (PROPRIETARY), 3 - INTEL (BUILT-IN) + NVIDIA (PROPRIETARY), 4 - AMD (DISCRETE), 5 - NOTHING)')
    int_data.driver = validate_choice("> ", ['1', '2', '3', '4', '5'])

    driver = packages.get("driver", {}).get(int_data.driver)

    if driver:
        package_data.driver  = driver["packages"]
        selected_data.driver = driver["type"]
    else:
        return False

    print('\nSelect sound driver (1 - PIPEWIRE, 2 - PULSEAUDIO, 3 - NOTHING) [1]')
    int_data.sound = validate_choice("> ", ['1', '2', '3'], True)

    sound = packages.get("sound", {}).get(int_data.sound)

    if sound:
        package_data.sound  = sound["packages"]
        service_data.sound  = sound["service"]
        selected_data.sound = sound["type"]
    else:
        return False

    print('\nSelect your work environment (1 - KDE, 2 - GNOME, 3 - NOTHING) [1]')
    int_data.desktop = validate_choice("> ", ['1', '2', '3'], True)

    desktop = packages.get("desktop", {}).get(int_data.desktop)

    if desktop:
        package_data.desktop  = desktop["packages"]
        service_data.desktop  = desktop["service"]
        selected_data.desktop = desktop["type"]
    else:
        return False

    print('\nEnter the additional packages you need (Example: zip,unzip,git) [Enter]')
    additionals_packages = input("> ").strip()

    additionals_packages_status = set(pkg.strip() for pkg in additionals_packages.split(',') if pkg.strip() and check_package_exists(pkg.strip()))

    if additionals_packages_status:
        package_data.additionals = ' '.join(additionals_packages_status)

    return True
