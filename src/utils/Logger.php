<?php

namespace utils;

class Logger {
    private static ?string $logFile                    = null;
    private static ?bool $mainThreadHasFormattingCodes = null;
    
    private const FORMAT = "&7[&e%time&7] &7[&a%prefix&7/&f%color%level&7]: &f%message";

    function __construct(string $logFile) {
        touch($logFile);
        
        self::$logFile                      = $logFile;
        self::$mainThreadHasFormattingCodes = Terminal::hasFormattingCodes();
    }

    static function send(string $message, string $level): void {
		switch ($level) {
            case LogLevel::INFO:
                self::writeInTerminal($message, $level, TextFormat::WHITE);
                break;
			case LogLevel::EMERGENCY:
				self::writeInTerminal($message, $level, TextFormat::RED);
				break;
			case LogLevel::ALERT:
				self::writeInTerminal($message, $level, TextFormat::RED);
				break;
			case LogLevel::CRITICAL:
				self::writeInTerminal($message, $level, TextFormat::RED);
				break;
			case LogLevel::ERROR:
				self::writeInTerminal($message, $level, TextFormat::DARK_RED);
				break;
			case LogLevel::WARNING:
				self::writeInTerminal($message, $level, TextFormat::GOLD);
				break;
			case LogLevel::NOTICE:
				self::writeInTerminal($message, $level, TextFormat::YELLOW);
				break;
			case LogLevel::DEBUG:
				self::writeInTerminal($message, $level, TextFormat::GRAY);
				break;
		}
	}

    private static function writeInTerminal(string $message, string $level, string $color): void {
        $message = str_replace(
            ["%time", "%prefix", "%color", "%level", "%message"],
            [date("H:i:s"), \NAME, $color, $level, $message],

            self::FORMAT
        );

        if (!Terminal::isInit()) Terminal::init(self::$mainThreadHasFormattingCodes);

        Terminal::writeLine($message);
        self::writeLog($message);
    }

    private static function writeLog(string $message): void {
        $handle = fopen(self::$logFile, "ab");
        if (!$handle) throw new \RuntimeException("Failed to open file for writing: " . self::$logFile);

        fwrite($handle, TextFormat::clean($message) . PHP_EOL);
        fclose($handle);
    }
}