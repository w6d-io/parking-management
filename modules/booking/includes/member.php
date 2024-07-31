<?php

namespace Booking;

use Exception;
use ParkingManagement\database\database;
use ParkingManagement\Logger;
use PDO;
use PDOException;

enum MemberStatus: int
{
	case ADMIN = 1;
	case SECRETARY = 2;
	case EMPLOYEE = 3;
	case AGENCY_PARTNER = 4;
	case AGENCY = 5;
	case CUSTOMER = 6;
}

class Member
{
	private PDO $conn;

	public function __construct()
	{
		if (!$conn = database::connect()) {
			Logger::error("member.database.connect", database::getError());
			return;
		}
		$this->conn = $conn;
	}

	public function isMemberExists($email): bool|string
	{

		try {
			$sql = "SELECT `id_membre` FROM `tbl_membre` WHERE `email` = ?";
			$req = $this->conn->prepare($sql);
			$req->execute(array($email)) or die($this->conn->errorInfo());
			if ($req->rowCount() == 0)
				return false;
			return $req->fetch(PDO::FETCH_ASSOC)["id_membre"];
		} catch (PDOException $e) {
			$post = array_merge($_GET, $_POST);
			Logger::error("member.isMemberExists", ['data'=>$post,'error'=>$e->getMessage()]);
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
		if (!$req->execute(array(
			'status' => MemberStatus::CUSTOMER,
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
		))) {
			Logger::error("member.create", ['errorInfo' => $this->conn->errorInfo()]);
			throw new Exception("failed to create member");
		}
		return $this->conn->lastInsertId();
	}

	/**
	 * @throws Exception
	 */
	public function read(int $member_id): array
	{
		$query = 'SELECT `id_membre`
     , `status`, `date`, `email`
     , `nom`, `prenom`
     , `code_postal`, `ville`, `pays`
     , `tel_fixe`, `tel_port`
		FROM `tbl_membre` WHERE id_membre = :id';
		$req = $this->conn->prepare($query);
		if (!$req->execute(array('id' => $member_id)))
			throw new Exception("failed to read member");
		return $req->fetch(PDO::FETCH_ASSOC);
	}

}
