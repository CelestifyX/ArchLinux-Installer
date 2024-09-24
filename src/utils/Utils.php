<?php

namespace utils;

class Utils {
	const OS_LINUX   = "linux";
	const OS_UNKNOWN = "other";

    private static ?string $os = null;

    static function isLinux(): bool {
        if (stripos(php_uname("s"), "Linux") !== false) return true;
        return false;
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

    static function execute(string $command, bool $shell = false): mixed {
        if ($shell) {
            return shell_exec($command);
        } else {
            $result_code = null;

            system($command, $result_code);
            return $result_code;
        }
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

    static function validateChoice(?string $choice, array $validChoices, bool $defaultZero = false): string|false {
        if (
            empty($choice) and
            $defaultZero
        ) return "0";
        
        while (true) {
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

    static function validatePassword(string|int $choice, string $default = null): string|false {
        while (true) {
            try {
                $minLength = 4;
                $maxLength = 20;

                if (
                    (strlen($choice) < $minLength) ||
                    (strlen($choice) > $maxLength)
                ) {
                    Logger::send("Password must be between " . $minLength . " and " . $maxLength . " characters long.", LogLevel::WARNING);
                    $choice = self::getInput($default);
                } elseif (preg_match('/[^a-zA-Z0-9]/', $choice)) {
                    Logger::send("Password contains forbidden characters.", LogLevel::WARNING);
                    $choice = self::getInput($default);
                } else {
                    return $choice;
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
    
        $return_code = self::execute($command);
        $end_time    = microtime(true);
    
        $isSuccess   = ($return_code === 0);
        $message     = ($isSuccess ? "&7[&l&aSUCCESS&r&7] &f" . $description : "&7[&l&cERROR&r&7] &f" . $description);
    
        Logger::send($message . " &7[&b" . number_format(($end_time - $start_time), 2) . "s&7]", ($isSuccess ? LogLevel::INFO : LogLevel::ERROR));
        return $isSuccess;
    }

    static function arrayToString(array $list): string {
        return implode(' ', $list);
    }

    private static function readAdditionalPackages(): array {
        $filePath = \PATH . 'settings.json';
        if (!file_exists($filePath)) return [];
    
        $jsonContent = file_get_contents($filePath);
        $packages    = json_decode($jsonContent, true)['additionals'];

        return (
            (
                !empty($packages) and
                is_array($packages)
            ) ? array_filter($packages) : []
        );
    }
}