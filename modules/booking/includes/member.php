<?php

namespace Booking;

use Exception;
use ParkingManagement\database\database;
use PDO;
use PDOException;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "database" . DS . "database.php";

class Member {

	public const ADMIN = 1;
	public const SECRETARY = 2;
	public const EMPLOYEE = 3;
	public const AGENCY_PARTNER = 4;
	public const AGENCY = 5;
	public const CUSTOMER = 6;

	private PDO $conn;

	public function __construct()
	{
		if (!$conn = database::connect()) {
			database::getError();
			return;
		}
		$this->conn = $conn;
	}

	public function isMemberExists($email): bool|string {

		try{
			$sql = "SELECT `id_membre` FROM `tbl_membre` WHERE `email` = ?";
			$req = $this->conn->prepare($sql);
			$req->execute(array($email)) or die($this->conn->errorInfo());
			if ($req->rowCount() == 0)
				return false;
			return '';
		} catch (PDOException $e) {
			$post = array_merge($_GET, $_POST);
			if ($post && array_key_exists('DEBUG', $post) && $post['DEBUG'] === 1) {
				print_log($e->getMessage(), false);
			}
			return false;
		}
	}

	/**
	 * @throws Exception|PDOException
	 */
	public function create(): string
	{
		$post = array_merge($_GET, $_POST);
		$password = generatePassword(8);
		$sql = "INSERT INTO `tbl_membre` ( `id_membre`, `status`, `date`, `email`, `password`, `reseau_id`, `nom`, `prenom`, `code_postal`, `ville`, `pays`, `tel_fixe`, `tel_port`, `tva`, `url`, `afficher` ) VALUES (NULL, :status, :date, :email, :password, :reseau, :nom, :prenom, :code_postal, :ville, :pays, :tel_fixe, :tel_port, :tva, :url, :afficher)";
			$req = $this->conn->prepare($sql);
			if(!$req->execute(array(
				'status' => self::CUSTOMER,
				'date' => date('Y-m-d'),
				'email' => strtolower($post['email']),
				'password' => strrev(md5($password)),
				'reseau' => 0, // Reseau
				'nom' => ucwords($post['nom']),
				'prenom' => ucwords($post['prenom']),
				'code_postal' => $post['code_postal'],
				'ville' => ucwords($post['ville']),
				'pays' => $post['pays'],
				'tel_fixe' => NULL,
				'tel_port' => $post['tel_port'],
				'tva' => '',
				'url' => slug($post['prenom'] . ' ' . $post['nom'] . ' ' . $post['ville']),
				'afficher' => 1
			)))
			{
				if (array_key_exists('DEBUG', $post) && $post['DEBUG'] === 1) {
					print_log($this->conn->errorInfo(), false);
				}
				throw new Exception("failed to create member");
			}
			return $this->conn->lastInsertId();
	}

}
