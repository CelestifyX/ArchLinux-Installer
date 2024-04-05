from colors.colors import Colors

class Status:
    PROGRESS = '[.]'
    OK       = f'[{Colors.light_green}\u2714{Colors.reset}]'
    ERROR    = f'[{Colors.light_red}\u2718{Colors.reset}]'
