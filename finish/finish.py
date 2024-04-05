from functions.functions import *

from colors.colors       import Colors
from report.report       import send_report

def finish():
    send_report('success')

    get_input(f'\n\n\n{Colors.green}SUCCESS{Colors.reset}: Installation completed. Press enter to reboot')
    execute_command(f'reboot')

    return True
