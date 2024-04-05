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
        self.warning_func       = warning
        self.disk_func          = disk
        self.configuration_func = configuration
        self.setup_warning_func = setup_warning
        self.install_func       = install
        self.finish_func        = finish

    def run(self):
        user_data     = UserData()
        package_data  = PackageData()
        service_data  = ServiceData()
        selected_data = SelectedData()

        try:
            if not self.warning_func():
                terminate_installation()
            if not self.disk_func(user_data, selected_data):
                terminate_installation()
            if not self.configuration_func(user_data, package_data, service_data, selected_data):
                terminate_installation()
            if not self.setup_warning_func(user_data, selected_data, package_data):
                terminate_installation()
            if not self.install_func(user_data, package_data, service_data, selected_data):
                terminate_installation()
            if not self.finish_func():
                terminate_installation()
        except KeyboardInterrupt:
            terminate_installation()
