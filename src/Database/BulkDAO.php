<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\DuplicateBulkIdentifierException;

final class BulkDAO extends DAO {

	public function addBulk(String $identifier, String $type, String $json) {

		$statement = $this->getPDO()->prepare(
			'
INSERT INTO BulkTable (`BulkIdentifier`, `User`, `Type`, `JSON`) 
VALUES (:id, @taralloauditusername, :typ, :json)'
		);
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_STR);
			$statement->bindValue(':typ', $type, \PDO::PARAM_STR);
			$statement->bindValue(':json', $json, \PDO::PARAM_STR);
			$result = $statement->execute();
			assert($result === true, 'Add bulk');
		} catch(\PDOException $e) {
			if($e->getCode() === '23000' && $statement->errorInfo()[1] === 1062) {
				throw new DuplicateBulkIdentifierException((string) $identifier, 'Bulk already exists: ' . (string) $identifier);
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}
	}

	/**Get all imports from BulkTable*/
	public function getBulkImports(): array {
		$statement = $this->getPDO()->query('SELECT Identifier, BulkIdentifier, Time, User, Type, JSON FROM BulkTable');
		$imports = $statement->fetchAll();
		return $imports;
	}
  
  	/**Delete a bulk import via identifier*/
	public function deleteBulkImport(string $identifier) {
		$statement = $this->getPDO()->prepare('DELETE FROM BulkTable WHERE Identifier = :id');
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_INT);
			$statement->execute();
		} finally {
			$statement->closeCursor();
		}
	}

	/**Get an import's JSON from BulkTable and decodes it*/
	public function getDecodedJSON(int $Identifier): array {
		$statement = $this->getPDO()->prepare('SELECT JSON FROM BulkTable WHERE Identifier = :id');
		$importElement = null;
		try {
			$statement->bindValue(':id', $Identifier, \PDO::PARAM_INT);
			$statement->execute();
			$importElement = $statement->fetch();
			$importElement = json_decode($importElement["JSON"],JSON_OBJECT_AS_ARRAY);
		} finally {
			$statement->closeCursor();
		}

		return $importElement;
	}

	/**Check if there are entries with the same identifier*/
	public function checkDuplicatedIdentifier(String $identifier): bool {
		$statement = $this->getPDO()->prepare(
			'
		SELECT Identifier FROM BulkTable WHERE BulkIdentifier = :id FOR UPDATE
		'
		);
		try {
			$statement->bindValue(':id', $identifier, \PDO::PARAM_STR);
			$statement->execute();
			if($statement->rowCount() === 0) {
				return false;
			}
			return true;
		} finally {
			$statement->closeCursor();
		}
	}
}