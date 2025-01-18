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

	private array $member;

	public function getMember(): array
	{
		return $this->member;
	}

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
			Logger::error("member.isMemberExists", ['data' => $post, 'error' => $e->getMessage()]);
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
		$this->member = [
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
		];
		if (!$req->execute($this->member)) {
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

	/**
	 * Updates specific fields of a member record
	 * @param int $member_id The ID of the member to update
	 * @param array $fields Associative array of fields to update and their new values
	 * @return bool
	 */
	public function patch(int $member_id, array $fields): bool
	{
		// Define allowable fields for updating
		$allowedFields = [
			'code_postal', 'ville', 'pays',
			'tel_fixe', 'tel_port'
		];

		// Filter out any fields that aren't in the allowed list
		$updateFields = array_intersect_key($fields, array_flip($allowedFields));

		if (empty($updateFields)) {
			Logger::error("member.patch", "No valid fields provided for update");
//			throw new Exception("No valid fields provided for update");
			return false;
		}

		try {
			// Build the SQL query dynamically based on the fields to update
			$setClauses = array_map(function ($field) {
				return "`$field` = :$field";
			}, array_keys($updateFields));

			$sql = "UPDATE `tbl_membre` SET " . implode(', ', $setClauses) . " WHERE `id_membre` = :id_membre";

			$req = $this->conn->prepare($sql);

			// Add member_id to the parameters
			$params = array_merge($updateFields, ['id_membre' => $member_id]);

			// Format specific fields if needed
			if (isset($params['email'])) {
				$params['email'] = strtolower($params['email']);
			}
			if (isset($params['nom'])) {
				$params['nom'] = ucwords($params['nom']);
			}
			if (isset($params['prenom'])) {
				$params['prenom'] = ucwords($params['prenom']);
			}
			if (isset($params['ville'])) {
				$params['ville'] = ucwords($params['ville']);
			}

			if (!$req->execute($params)) {
				Logger::error("member.patch", [
					'member_id' => $member_id,
					'fields' => $fields,
					'errorInfo' => $this->conn->errorInfo()
				]);
//				throw new Exception("Failed to update member");
				return false;
			}

			return true;
		} catch (PDOException $e) {
			Logger::error("member.patch", [
				'member_id' => $member_id,
				'fields' => $fields,
				'error' => $e->getMessage()
			]);
//			throw $e;
			return false;
		}
	}

}
