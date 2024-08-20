<?php

namespace utils;

use function fclose;
use function fopen;
use function getenv;
use function is_array;
use function stream_isatty;

class Terminal {
	private static string $FORMAT_BOLD          = "";
	private static string $FORMAT_OBFUSCATED    = "";
	private static string $FORMAT_ITALIC        = "";
	private static string $FORMAT_UNDERLINE     = "";
	private static string $FORMAT_STRIKETHROUGH = "";
	private static string $FORMAT_RESET 		= "";
	private static string $COLOR_BLACK 		 	= "";
	private static string $COLOR_DARK_BLUE 	 	= "";
	private static string $COLOR_DARK_GREEN 	= "";
	private static string $COLOR_DARK_AQUA	    = "";
	private static string $COLOR_DARK_RED 	    = "";
	private static string $COLOR_PURPLE 		= "";
	private static string $COLOR_GOLD 			= "";
	private static string $COLOR_GRAY		    = "";
	private static string $COLOR_DARK_GRAY	    = "";
	private static string $COLOR_BLUE 			= "";
	private static string $COLOR_GREEN 		 	= "";
	private static string $COLOR_AQUA 			= "";
	private static string $COLOR_RED 			= "";
	private static string $COLOR_LIGHT_PURPLE 	= "";
	private static string $COLOR_YELLOW 		= "";
	private static string $COLOR_WHITE 		 	= "";

	private static ?bool $formattingCodes       = null;

	static function hasFormattingCodes(): bool {
		if (self::$formattingCodes === null) throw new \InvalidStateException("Formation codes were not initialized");
		return self::$formattingCodes;
	}

	static function init(?bool $enableFormatting = null): void {
		self::$formattingCodes = ($enableFormatting ?? self::detectFormattingCodesSupport());
		if (!self::$formattingCodes) return;

		switch (Utils::getOS()) {
			case Utils::OS_LINUX:
				self::getEscapeCodes();
				break;
		}
	}

	static function isInit(): bool {
		return (self::$formattingCodes !== null);
	}

	static function toANSI(string $string): string {
		if (!is_array($string)) $string = TextFormat::tokenize($string);
		$newString = "";

		foreach ($string as $token) {
			switch ($token) {
				case TextFormat::BOLD:
					$newString .= self::$FORMAT_BOLD;
					break;
				case TextFormat::OBFUSCATED:
					$newString .= self::$FORMAT_OBFUSCATED;
					break;
				case TextFormat::ITALIC:
					$newString .= self::$FORMAT_ITALIC;
					break;
				case TextFormat::UNDERLINE:
					$newString .= self::$FORMAT_UNDERLINE;
					break;
				case TextFormat::STRIKETHROUGH:
					$newString .= self::$FORMAT_STRIKETHROUGH;
					break;
				case TextFormat::RESET:
					$newString .= self::$FORMAT_RESET;
					break;
				case TextFormat::BLACK:
					$newString .= self::$COLOR_BLACK;
					break;
				case TextFormat::DARK_BLUE:
					$newString .= self::$COLOR_DARK_BLUE;
					break;
				case TextFormat::DARK_GREEN:
					$newString .= self::$COLOR_DARK_GREEN;
					break;
				case TextFormat::DARK_AQUA:
					$newString .= self::$COLOR_DARK_AQUA;
					break;
				case TextFormat::DARK_RED:
					$newString .= self::$COLOR_DARK_RED;
					break;
				case TextFormat::DARK_PURPLE:
					$newString .= self::$COLOR_PURPLE;
					break;
				case TextFormat::GOLD:
					$newString .= self::$COLOR_GOLD;
					break;
				case TextFormat::GRAY:
					$newString .= self::$COLOR_GRAY;
					break;
				case TextFormat::DARK_GRAY:
					$newString .= self::$COLOR_DARK_GRAY;
					break;
				case TextFormat::BLUE:
					$newString .= self::$COLOR_BLUE;
					break;
				case TextFormat::GREEN:
					$newString .= self::$COLOR_GREEN;
					break;
				case TextFormat::AQUA:
					$newString .= self::$COLOR_AQUA;
					break;
				case TextFormat::RED:
					$newString .= self::$COLOR_RED;
					break;
				case TextFormat::LIGHT_PURPLE:
					$newString .= self::$COLOR_LIGHT_PURPLE;
					break;
				case TextFormat::YELLOW:
					$newString .= self::$COLOR_YELLOW;
					break;
				case TextFormat::WHITE:
					$newString .= self::$COLOR_WHITE;
					break;
				default:
					$newString .= $token;
					break;
			}
		}

		return $newString;
	}

    static function write(string $line): void {
		echo self::toANSI($line);
	}

    static function writeLine(string $line): void {
		echo self::toANSI($line) . self::$FORMAT_RESET . PHP_EOL;
	}

	private static function detectFormattingCodesSupport(): bool {
		$stdout = fopen("php://stdout", "w");
		if (!$stdout) throw new AssumptionFailedError("Opening php://stdout should never fail");

		$result = (
			stream_isatty($stdout) and
			(getenv('TERM') !== false)
		);

		fclose($stdout);
		return $result;
	}

	private static function getEscapeCodes(): void {
		self::$FORMAT_BOLD 			= (trim(Utils::execute('tput bold',  true)) ?: "\x1b[1m");
		self::$FORMAT_OBFUSCATED 	= (trim(Utils::execute('tput smacs', true)) ?: "\x1b[8m");
		self::$FORMAT_ITALIC 		= (trim(Utils::execute('tput sitm',  true)) ?: "\x1b[3m");
		self::$FORMAT_UNDERLINE 	= (trim(Utils::execute('tput smul',  true)) ?: "\x1b[4m");
		self::$FORMAT_STRIKETHROUGH = "\x1b[9m";
		self::$FORMAT_RESET 		= (trim(Utils::execute('tput sgr0',  true)) ?: "\x1b[0m");
	
		$colors = (int) trim(Utils::execute('tput colors', true));
	
		if ($colors > 8) {
			self::$COLOR_BLACK        = (($colors >= 256) ? trim(Utils::execute('tput setaf 16',  true)) : trim(Utils::execute('tput setaf 0',  true)));
			self::$COLOR_DARK_BLUE    = (($colors >= 256) ? trim(Utils::execute('tput setaf 19',  true)) : trim(Utils::execute('tput setaf 4',  true)));
			self::$COLOR_DARK_GREEN   = (($colors >= 256) ? trim(Utils::execute('tput setaf 34',  true)) : trim(Utils::execute('tput setaf 2',  true)));
			self::$COLOR_DARK_AQUA    = (($colors >= 256) ? trim(Utils::execute('tput setaf 37',  true)) : trim(Utils::execute('tput setaf 6',  true)));
			self::$COLOR_DARK_RED     = (($colors >= 256) ? trim(Utils::execute('tput setaf 124', true)) : trim(Utils::execute('tput setaf 1',  true)));
			self::$COLOR_PURPLE       = (($colors >= 256) ? trim(Utils::execute('tput setaf 127', true)) : trim(Utils::execute('tput setaf 5',  true)));
			self::$COLOR_GOLD         = (($colors >= 256) ? trim(Utils::execute('tput setaf 214', true)) : trim(Utils::execute('tput setaf 3',  true)));
			self::$COLOR_GRAY         = (($colors >= 256) ? trim(Utils::execute('tput setaf 145', true)) : trim(Utils::execute('tput setaf 7',  true)));
			self::$COLOR_DARK_GRAY    = (($colors >= 256) ? trim(Utils::execute('tput setaf 59',  true)) : trim(Utils::execute('tput setaf 8',  true)));
			self::$COLOR_BLUE         = (($colors >= 256) ? trim(Utils::execute('tput setaf 63',  true)) : trim(Utils::execute('tput setaf 12', true)));
			self::$COLOR_GREEN        = (($colors >= 256) ? trim(Utils::execute('tput setaf 83',  true)) : trim(Utils::execute('tput setaf 10', true)));
			self::$COLOR_AQUA         = (($colors >= 256) ? trim(Utils::execute('tput setaf 87',  true)) : trim(Utils::execute('tput setaf 14', true)));
			self::$COLOR_RED          = (($colors >= 256) ? trim(Utils::execute('tput setaf 203', true)) : trim(Utils::execute('tput setaf 9',  true)));
			self::$COLOR_LIGHT_PURPLE = (($colors >= 256) ? trim(Utils::execute('tput setaf 207', true)) : trim(Utils::execute('tput setaf 13', true)));
			self::$COLOR_YELLOW       = (($colors >= 256) ? trim(Utils::execute('tput setaf 227', true)) : trim(Utils::execute('tput setaf 11', true)));
			self::$COLOR_WHITE        = (($colors >= 256) ? trim(Utils::execute('tput setaf 231', true)) : trim(Utils::execute('tput setaf 15', true)));
		} else {
			self::$COLOR_BLACK        = (self::$COLOR_DARK_GRAY  = trim(Utils::execute('tput setaf 0', true)));
			self::$COLOR_RED 		  = (self::$COLOR_DARK_RED   = trim(Utils::execute('tput setaf 1', true)));
			self::$COLOR_GREEN        = (self::$COLOR_DARK_GREEN = trim(Utils::execute('tput setaf 2', true)));
			self::$COLOR_YELLOW       = (self::$COLOR_GOLD       = trim(Utils::execute('tput setaf 3', true)));
			self::$COLOR_BLUE 		  = (self::$COLOR_DARK_BLUE  = trim(Utils::execute('tput setaf 4', true)));
			self::$COLOR_LIGHT_PURPLE = (self::$COLOR_PURPLE     = trim(Utils::execute('tput setaf 5', true)));
			self::$COLOR_AQUA         = (self::$COLOR_DARK_AQUA  = trim(Utils::execute('tput setaf 6', true)));
			self::$COLOR_GRAY         = (self::$COLOR_WHITE      = trim(Utils::execute('tput setaf 7', true)));
		}
	}
	
}