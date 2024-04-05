class UserData:
    def __init__(self):
        self.disk         = None
        self.file_system  = None
        self.timezone     = None
        self.hostname     = None
        self.password     = None
        self.username     = None
        self.userpassword = None
        self.kernel       = None
        self.driver       = None
        self.sound        = None
        self.desktop      = None

class PackageData:
    def __init__(self):
        self.kernel       = None
        self.driver       = None
        self.sound        = None
        self.desktop      = None
        self.additionals  = None

class ServiceData:
    def __init__(self):
        self.desktop      = None
        self.sound        = None

class SelectedData:
    def __init__(self):
        self.boot         = None
        self.swap         = None
        self.root         = None
        self.kernel       = None
        self.driver       = None
        self.sound        = None
        self.desktop      = None
        self.file_system  = None
