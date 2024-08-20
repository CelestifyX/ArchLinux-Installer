<?php

namespace utils;

class Utils {
    const OS_WINDOWS = "win";
	const OS_IOS     = "ios";
	const OS_MACOS   = "mac";
	const OS_ANDROID = "android";
	const OS_LINUX   = "linux";
	const OS_BSD     = "bsd";
	const OS_UNKNOWN = "other";

    private static ?string $os = null;

    static function getOS($recalculate = false): string {
        if (
            (self::$os === null) ||
            $recalculate
        ) {
            $uname = php_uname("s");

            if (stripos($uname, "Darwin") !== false) {
                self::$os = ((strpos(php_uname("m"), "iP") === 0) ? self::OS_IOS : self::OS_MACOS);
            } elseif (
                (stripos($uname, "Win") !== false) ||
                ($uname === "Msys")
            ) {
                self::$os = self::OS_WINDOWS;
            } elseif (stripos($uname, "Linux") !== false) {
                self::$os = (@file_exists("/system/build.prop") ? self::OS_ANDROID : self::OS_LINUX);
            } elseif (
                (stripos($uname, "BSD") !== false) ||
                ($uname === "DragonFly")
            ) {
                self::$os = self::OS_BSD;
            } else {
                self::OS_UNKNOWN;
            }
        }

        return self::$os;
    }

    static function terminate(): void {
        Logger::send("Installation aborted.", LogLevel::CRITICAL);
        exit(1);
    }

    static function getInput(mixed $default = ""): ?string {
        fwrite(STDOUT, "> ");
		$input = trim(fgets(STDIN));

		return (($input === "") ? $default : $input);
	}

    static function execute(string $command, bool $shell = false): int|string|null {
        if ($shell) {
            return shell_exec($command);
        } else {
            $result_code = null;

            system($command, $result_code);
            return $result_code;
        }
    }

    static function clear_screen(): void {
        self::execute("clear");
    }

    static function validateDevice(?string $choice, string $messageNotFound, string $messageEmpty): string|false {
        while (true) {
            if (empty($choice)) {
                Logger::send($messageEmpty, LogLevel::INFO);
                $choice = self::getInput(null);
                continue;
            }
    
            try {
                $devices = array_filter(explode("\n", self::execute("lsblk -o NAME -l /dev/" . $choice, true)));
                $devices = array_map('trim', $devices);
    
                if (in_array($choice, $devices)) {
                    return $choice;
                } else {
                    Logger::send(str_replace("%device", $choice, $messageNotFound), LogLevel::WARNING);
                    $choice = self::getInput(null);
                }
            } catch (\Exception $e) {
                Logger::send("An error has occurred: " . $e->getMessage(), LogLevel::ERROR);
                return false;
            }
        }
    }

    static function validateChoice(?string $choice, array $validChoices, bool $defaultToOne = false): string|false {
        while (true) {
            if (
                empty($choice) and
                $defaultToOne
            ) {
                return "1";
                break;
            }

            try {
                if (in_array($choice, $validChoices)) {
                    return $choice;
                } else {
                    Logger::send("Invalid choice. Please try again.", LogLevel::WARNING);
                    $choice = self::getInput(null);
                }
            } catch (\Exception $e) {
                Logger::send("An error has occurred: " . $e->getMessage(), LogLevel::ERROR);
                return false;
            }
        }
    }

    static function validateInput(?string $choice, array $validChoices, string $message, ?string $default = null): string|false {
        $choice = strtolower($choice);

        while (true) {
            try {
                if (!in_array($choice, $validChoices)) {
                    return $choice;
                } else {
                    Logger::send(str_replace("%valid", $choice, $message), LogLevel::WARNING);
                    $choice = self::getInput($default);
                }
            } catch (\Exception $e) {
                Logger::send("An error has occurred: " . $e->getMessage(), LogLevel::ERROR);
                return false;
            }
        }
    }

    static function validateTimezone(?string $choice, ?string $default = null): string|false {
        while (true) {
            try {
                $result = self::execute("timedatectl list-timezones", true);
                if ($result === null) throw new \Exception("Failed to execute command");

                $timezones = explode("\n", trim($result));
                
                if (in_array($choice, $timezones, true)) {
                    return $choice;
                } else {
                    Logger::send("Timezone '" . $choice . "' not found. Please enter a valid timezone.", LogLevel::WARNING);
                    $choice = self::getInput($default);
                }
            } catch (\Exception $e) {
                Logger::send("An error has occurred: " . $e->getMessage(), LogLevel::ERROR);
                return false;
            }
        }
    }

    static function getSize(string $device, ?string $partition = null, bool $isPartition = true): ?string {
        $command = ($isPartition ?
            "fdisk -l /dev/" . $device . " | grep /dev/" . $partition :
            "fdisk -l /dev/" . $device . " | grep 'Disk /dev/" . $device . "'"
        );
    
        $output = self::execute($command, true);
        if ($output === null) return null;
    
        $lines = explode("\n", trim($output));
    
        if ($isPartition) {
            foreach ($lines as $line) {
                if (strpos($line, $device) !== false) return (preg_split('/\s+/', $line)[4] ?? null);
            }
        } else {
            $parts = explode(':', ($lines[0] ?? ''));
            return (isset($parts[1]) ? trim(explode(',', $parts[1])[0]) : null);
        }
    
        return null;
    }
    
    static function getExistingPackages(): array {
        return array_filter(
            self::readAdditionalPackages(), function (string $package): bool {
                return (self::execute("pacman -Si " . $package . " >/dev/null 2>&1") === 0);
            }
        );
    }

    static function generatePassword(int $length = 8): string {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password   = '';
    
        for ($i = 0; $i < $length; $i++) $password .= $characters[random_int(0, (strlen($characters) - 1))];
        return $password;
    }
    
    static function runCommandWithProgress(string $command, string $description): bool {
        $start_time  = microtime(true);
        Logger::send("&7[&l&6WAIT&r&7] &f" . $description, LogLevel::INFO);
    
        $return_code = Utils::execute($command);
        $end_time    = microtime(true);
    
        $isSuccess   = ($return_code === 0);
        $message     = ($isSuccess ? "&7[&l&aSUCCESS&r&7] &f" . $description : "&7[&l&cERROR&r&7] &f" . $description);
    
        Logger::send($message . " &7[&b" . number_format(($end_time - $start_time), 2) . "s&7]", ($isSuccess ? LogLevel::INFO : LogLevel::ERROR));
        return $isSuccess;
    }  
    
    static function sendLogFileToServer(): void {
        // TODO
    }

    private static function readAdditionalPackages(): array {
        $filePath = \PATH . 'additional_packages.json';
        if (!file_exists($filePath)) return [];
    
        $jsonContent = file_get_contents($filePath);
        $packages    = json_decode($jsonContent, true);
    
        return (is_array($packages) ? array_filter($packages) : []);
    }
}