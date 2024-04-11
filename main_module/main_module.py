from warning.warning             import *
from database.database           import *

from functions.functions         import terminate_installation
from configuration.configuration import configuration
from install.install             import install
from finish.finish               import finish
from colors.colors               import Colors
from disk.disk                   import disk

class ArchInstaller:
    def __init__(self):
        self.functions =    [
            (warning,       []),
            (disk,          ["disk_data", "selected_data", "int_data"]),
            (configuration, ["user_data", "int_data", "selected_data", "package_data", "service_data"]),
            (setup_warning, ["user_data", "selected_data", "package_data", "disk_data"]),
            (install,       ["user_data", "int_data", "package_data", "service_data", "disk_data"]),
            (finish,        [])
        ]

    def run(self):
        data = {
            "user_data":     UserData(),
            "int_data":      IntData(),
            "selected_data": SelectedData(),
            "package_data":  PackageData(),
            "service_data":  ServiceData(),
            "disk_data":     DiskData()
        }

        for func, args in self.functions:
            try:
                if not func(*[data[arg] for arg in args]):
                    terminate_installation()
            except KeyboardInterrupt:
                terminate_installation()
