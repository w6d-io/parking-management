<?php

namespace ParkingManagement\database;

use PDO;
use PDOException;

class database {
	private static array $database;
	private static PDO $_conn;
	private static string $charset = "utf8";

	private static array $error;

	public static function connect(): bool|PDO
	{
		$pm = getParkingManagementInstance();
		if (!$pm) {
			self::$error = array(
				'code' => '0001',
				'message' => 'ParkingManagement\database requires PDO object'
			);
			return false;
		}
		if (isset(self::$_conn)) {
			return self::$_conn;
		}
		self::$database = $pm->prop('database');
		if (empty(self::$database) || !is_array(self::$database)
			|| (!array_key_exists('host', self::$database)) || empty(self::$database['host'])
			|| (!array_key_exists('port', self::$database)) || empty(self::$database['port'])
			|| (!array_key_exists('name', self::$database)) || empty(self::$database['name'])
			|| (!array_key_exists('user', self::$database)) || empty(self::$database['user'])
			|| (!array_key_exists('password', self::$database)) || empty(self::$database['password'])
		)
		{
			self::$error = array(
				'code' => '0002',
				'message' => 'Database configuration error',
			);
			return false;
		}
		try {
			$dsn = 'mysql:host=' . self::$database['host'] . ';port=' . self::$database['port'] . ';dbname=' . self::$database['name']. ';charset='.self::$charset;
			self::$_conn = new PDO($dsn, self::$database['user'], self::$database['password']);
			self::$_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		} catch (PDOException $e) {
			self::$error = array(
				'code' => '0003',
				'message' => $e->getMessage(),
			);
			return false;
		}
		return self::$_conn;
	}
	public static function getError(): array
	{
		return self::$error;
	}


}
