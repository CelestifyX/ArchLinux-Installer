class UserData:
    def __init__(self):
        self.user         = None
        self.password     = None
        self.userpassword = None
        self.hostname     = None
        self.timezone     = None

class IntData:
    def __init__(self):
        self.file_system  = None
        self.kernel       = None
        self.driver       = None
        self.sound        = None
        self.desktop      = None
        self.font         = None

class DiskData:
    def __init__(self):
        self.disk         = None
        self.boot         = None
        self.swap         = None
        self.system       = None

class SelectedData:
    def __init__(self):
        self.file_system  = None
        self.kernel       = None
        self.driver       = None
        self.sound        = None
        self.desktop      = None
        self.font         = None

class PackageData:
    def __init__(self):
        self.additionals  = None
        self.kernel       = None
        self.driver       = None
        self.sound        = None
        self.desktop      = None
        self.font         = None

class ServiceData:
    def __init__(self):
        self.sound        = None
        self.desktop      = None
