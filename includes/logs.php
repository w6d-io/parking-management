<?php

namespace ParkingManagement;

use ParkingManagement\database\database;
use PDO;
use PKMGMT;

class Logger
{
	private const logDirectory = WP_CONTENT_DIR . DS . 'logs' . DS . 'parking-management';
	private static bool|PDO $conn = false;
	private static bool $useDatabase;
	private static bool $useFile;
	private static int $retention = 30;

	private static array $info;

	public static function log(string $type, string $action, mixed $message): void
	{
		$date = date('Y-m-d H:i:s');
		$ip = self::getClientIP();
		$serializedMessage = self::serializeMessage($message);
		$logEntry = "[$date] [$ip] [$type] [$action] - $serializedMessage";

		self::logToFile($logEntry);
		self::config();
		if (self::$useDatabase)
			self::logToDatabase($date, $ip, $type, $action, $serializedMessage);
//		if (self::$useFile)
//		self::cleanOldLogs();
	}

	public static function error(string $action, mixed $message): void
	{
		self::log('error', $action, $message);
	}

	public static function info(string $action, mixed $message): void
	{
		self::log('info', $action, $message);
	}

	public static function warming(string $action, mixed $message): void
	{
		self::log('warming', $action, $message);
	}

	private static function config(): void
	{
		PKMGMT::load_modules();
		if (empty(self::$info)) {
			$pm = getParkingManagementInstance();
			self::$info = $pm->prop('info');
		}
		if (empty(self::$useDatabase))
			self::$useDatabase = self::$info['logs']['database'] == '1';
		if (empty(self::$useFile))
			self::$useFile = self::$info['logs']['file'] == '1';
		if (empty(self::$retention))
			self::$retention = (int)self::$info['logs']['retention'] ?? 30;
		if (!self::$conn)
			self::$conn = database::connect();
		if (self::$useFile && !is_dir(self::logDirectory)) {
			mkdir(self::logDirectory, 0755, true);
			// Add index.php file to prevent directory listing
			$indexContent = "<?php\n// Silence is golden.";
			file_put_contents(self::logDirectory . DS . 'index.php', $indexContent);
		}
	}

	private static function getClientIP()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	private static function serializeMessage($message): string
	{
		if (is_string($message)) {
			return $message;
		} else {
			return print_r($message, true);
		}
	}

	private static function logToFile($logEntry): void
	{
		if (!is_dir(self::logDirectory))
			mkdir(self::logDirectory, 0755, true);

		$filename = self::logDirectory . DS . date('Y-m-d') . '.log';
		file_put_contents($filename, $logEntry . PHP_EOL, FILE_APPEND);
	}

	private static function logToDatabase($date, $ip, $type, $action, $message): void
	{
		$query = "INSERT INTO tbl_logs (date, ip, type, action, message) VALUES (?, ?, ?, ?, ?)";
		$stmt = self::$conn->prepare($query);
		$stmt->execute([$date, $ip, $type, $action, $message]);
	}

	private static function cleanOldLogs(): void
	{
		if (self::$useDatabase)
			self::cleanOldDatabaseLogs();
		if (self::$useFile)
			self::cleanOldFileLogs();
	}

	private static function cleanOldFileLogs(): void
	{
		$files = glob(self::logDirectory . '/*.log');
		$now = time();

		foreach ($files as $file) {
			if (is_file($file)) {
				if ($now - filemtime($file) >= 60 * 60 * 24 * self::$retention) {
					unlink($file);
				}
			}
		}
	}

	private static function cleanOldDatabaseLogs(): void
	{
		$date = date('Y-m-d', strtotime("-" . self::$retention . " days"));
		$query = "DELETE FROM `tbl_logs` WHERE `date` < ?";
		$stmt = self::$conn->prepare($query);
		$stmt->execute([$date]);
	}
}
