from functions.functions import *
from colors.colors       import Colors

def finish():
    input(f'\n\n\n{Colors.green}SUCCESS{Colors.reset}: Installation completed. Press enter to reboot')
    execute_command('reboot')

    return True
