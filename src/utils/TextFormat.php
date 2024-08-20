<?php

namespace utils;

use function mb_scrub;
use function preg_last_error;
use function preg_replace;
use function str_replace;

use const PREG_BACKTRACK_LIMIT_ERROR;
use const PREG_BAD_UTF8_ERROR;
use const PREG_BAD_UTF8_OFFSET_ERROR;
use const PREG_INTERNAL_ERROR;
use const PREG_JIT_STACKLIMIT_ERROR;
use const PREG_RECURSION_LIMIT_ERROR;

class TextFormat {
	const ESCAPE 		= "&";
	const EOL 			= "\n";

	const BLACK         = self::ESCAPE . "0";
	const DARK_BLUE     = self::ESCAPE . "1";
	const DARK_GREEN    = self::ESCAPE . "2";
	const DARK_AQUA     = self::ESCAPE . "3";
	const DARK_RED      = self::ESCAPE . "4";
	const DARK_PURPLE   = self::ESCAPE . "5";
	const GOLD          = self::ESCAPE . "6";
	const GRAY          = self::ESCAPE . "7";
	const DARK_GRAY     = self::ESCAPE . "8";
	const BLUE          = self::ESCAPE . "9";
	const GREEN         = self::ESCAPE . "a";
	const AQUA          = self::ESCAPE . "b";
	const RED           = self::ESCAPE . "c";
	const LIGHT_PURPLE  = self::ESCAPE . "d";
	const YELLOW        = self::ESCAPE . "e";
	const WHITE         = self::ESCAPE . "f";
	const OBFUSCATED    = self::ESCAPE . "k";
	const BOLD          = self::ESCAPE . "l";
	const STRIKETHROUGH = self::ESCAPE . "m";
	const UNDERLINE 	= self::ESCAPE . "n";
	const ITALIC        = self::ESCAPE . "o";
	const RESET         = self::ESCAPE . "r";

	static function tokenize(string $string) {
		$result = preg_split("/(" . self::ESCAPE . "[0-9a-fk-or])/u", $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		if (!$result) throw self::makePcreError();

		return $result;
	}

	static function clean(string $string, bool $removeFormat = true): string {
		$string = mb_scrub($string, 'UTF-8');
		$string = self::preg_replace("/[\x{E000}-\x{F8FF}]/u", "", $string);

		if ($removeFormat) $string = str_replace(self::ESCAPE, "", self::preg_replace("/" . self::ESCAPE . "[0-9a-fk-or]/u", "", $string));
		return str_replace("\x1b", "", self::preg_replace("/\x1b[\\(\\][[0-9;\\[\\(]+[Bm]/u", "", $string));
	}

	private static function makePcreError(): \InvalidArgumentException {
		$errorCode = preg_last_error();

		$message   = [
			PREG_INTERNAL_ERROR        => "Internal error",
			PREG_BACKTRACK_LIMIT_ERROR => "Backtrack limit reached",
			PREG_RECURSION_LIMIT_ERROR => "Recursion limit reached",
			PREG_BAD_UTF8_ERROR        => "Malformed UTF-8",
			PREG_BAD_UTF8_OFFSET_ERROR => "Bad UTF-8 offset",
			PREG_JIT_STACKLIMIT_ERROR  => "PCRE JIT stack limit reached"
		][$errorCode] ?? "Unknown (code " . $errorCode . ")";

		throw new \InvalidArgumentException("PCRE error: " . $message);
	}

	private static function preg_replace(string $pattern, string $replacement, string $string): string {
		$result = preg_replace($pattern, $replacement, $string);
		if ($result === null) throw self::makePcreError();
		
		return $result;
	}
}