<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemFeatures;
use WEEEOpen\Tarallo\Server\ItemIncomplete;


final class FeatureDAO extends DAO {

	/**
	 * Add features to ALL TEH ITEMS
	 *
	 * @param ItemFeatures[] $items
	 *
	 * @return ItemFeatures[]|Item[] same array
	 */
	public function getFeaturesAll(array $items) {
		foreach($items as $item) {
			$this->getFeatures($item);
		}

		return $items;
	}

	/**
	 * Get all items that have a certain value (exact match) for a feature.
	 * For anything more complicated use SearchDAO facilities.
	 *
	 * @param Feature $feature Feature and value to search
	 * @param int $limit Maximum number of results
	 * @param null|ItemIncomplete $location
	 *
	 * @return ItemIncomplete[] Items that have that feature (or empty array if none)
	 */
	public function getItemsByFeatures(Feature $feature, ?ItemIncomplete $location = null, int $limit = 100): array {
		$pdo = $this->getPDO();
		$column = Feature::getColumn($feature->type);

		if($location !== null) {
			// TODO: move this stuff to a function, it's used everywhere...
			$also = 'AND `Code` IN (SELECT Descendant FROM Tree WHERE Ancestor = :loc)';
		} else {
			$also = '';
		}

		/** @noinspection SqlResolve */
		$query = "SELECT `Code`
FROM ItemFeature
WHERE Feature = :feat
AND `$column` = :val
$also
LIMIT :lim";
		$statement = $pdo->prepare($query);

		$statement->bindValue(':feat', $feature->name, \PDO::PARAM_STR);
		$statement->bindValue(':val', $feature->value, Feature::getPDOType($feature->type));
		$statement->bindValue(':lim', $limit, \PDO::PARAM_INT);
		if($location !== null) {
			$statement->bindValue(':loc', $location->getCode(), \PDO::PARAM_STR);
		}

		$result = [];

		try {
			$statement->execute();
			if($statement->rowCount() > 0) {
				foreach($statement as $row) {
					$result[] = new ItemIncomplete($row['Code']);
				}

				return $result;
			}
		} finally {
			$statement->closeCursor();
		}

		return $result;
	}

	/**
	 * Add features to an item
	 *
	 * @param ItemFeatures $item
	 *
	 * @return ItemFeatures|Item same item
	 */
	public function getFeatures(ItemFeatures $item) {
		/*
		 * This seemed a good query to fetch default and non-default features, when database structure was different:
		 *
		 * SELECT Item2.ItemID, Item2.ItemFor, Feature.FeatureName, COALESCE(ItemFeature.`Value`, ItemFeature.ValueText, FeatureValue.ValueText) AS `FeatureValue`
		 * FROM (SELECT ItemID, ItemID AS ItemFor FROM Item UNION ALL SELECT `Default` AS ItemID, ItemID AS ItemFor FROM Item WHERE `Default` IS NOT NULL)  Item2
		 * JOIN ItemFeature ON  Item2.ItemID = ItemFeature.ItemID
		 * JOIN Feature ON ItemFeature.FeatureID = Feature.FeatureID
		 * LEFT JOIN FeatureValue ON ItemFeature.FeatureID = FeatureValue.FeatureID
		 * WHERE (ItemFeature.ValueEnum = FeatureValue.ValueEnum OR ItemFeature.ValueEnum IS NULL)
		 * AND Item2.ItemID IN (1, 2, 3);
		 *
		 * However, the subquery gives the correct and expected result, but the main query loses FOR UNFATHOMABLE REASONS the second half of the UNIONed data.
		 * So we're doing two queries. That UNION probably killed performance, too, so it's acceptable anyway.
		 *
		 * TODO: retry with new structure: who knows, it might work!
		 */

		// TODO: default features
		$statement = $this->getPDO()->prepare('SELECT Feature, COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) AS `Value`
            FROM ItemFeature
            WHERE `Code` = :cod;');

		$statement->bindValue(':cod', $item->getCode(), \PDO::PARAM_STR);

		try {
			$statement->execute();
			if($statement->rowCount() > 0) {
				foreach($statement as $row) {
					/** @var Item[] $items */
					$item->addFeature(Feature::ofString($row['Feature'], $row['Value']));
				}
			}
		} finally {
			$statement->closeCursor();
		}

		return $item;
	}

	/**
	 * Add a U audit entry for the specified item.
	 *
	 * @param ItemIncomplete $item
	 */
	public function addAuditEntry(ItemIncomplete $item) {
		$statementAudit = $this->getPDO()
			->prepare('INSERT INTO Audit (`Code`, `Change`, `User`) VALUES (?, \'U\', @taralloAuditUsername)');

		try {
			$success = $statementAudit->execute([$item->getCode()]);
			assert($success, 'add audit table entry for features update of ' . $item->getCode());
		} finally {
			$statementAudit->closeCursor();
		}
	}

	/**
	 * Set item features.
	 *
	 * @param ItemFeatures $item
	 *
	 * @return bool True if anything actually changed (and an U audit entry was generated), false otherwise.
	 * @TODO: it would be cool if changing a feature to the value it already has still didn't generate an entry...
	 */
	public function setFeatures(ItemFeatures $item): bool {
		$features = $item->getFeatures();

		if(empty($features)) {
			return false;
		}

		foreach($features as $feature) {
			$column = Feature::getColumn($feature->type);
			$type = Feature::getPDOType($feature->type);
			/** @noinspection SqlResolve */
			$statement = $this->getPDO()
				->prepare("INSERT INTO ItemFeature (Feature, `Code`, `$column`) VALUES (:feature, :item, :val) ON DUPLICATE KEY UPDATE `$column`=:val2");

			try {
				$statement->bindValue(':feature', $feature->name, \PDO::PARAM_STR);
				$statement->bindValue(':item', $item->getCode(), \PDO::PARAM_STR);
				$statement->bindValue(':val', $feature->value, $type);
				$statement->bindValue(':val2', $feature->value, $type);
				$result = $statement->execute();
				assert($result, 'set feature');
			} finally {
				$statement->closeCursor();
			}
		}

		$this->addAuditEntry($item);
		return true;
	}

	/**
	 * Delete a single feature from an item. This generates no audit entries, BTW.
	 *
	 * @param ItemFeatures $item
	 * @param string $feature
	 */
	public function deleteFeature(ItemFeatures $item, $feature) {
		if(!is_string($feature)) {
			throw new \InvalidArgumentException('Name of feature to be deleted should be a string');
		}

		$statement = $this->getPDO()->prepare('DELETE IGNORE FROM ItemFeature WHERE `Code` = ? AND `Feature`= ?');

		try {
			$result = $statement->execute([$item->getCode(), $feature]);
			assert($result, 'delete feature');
		} finally {
			$statement->closeCursor();
		}
	}


	/**
	 * Delete all features from an item
	 *
	 * @param ItemIncomplete $item
	 */
	public function deleteFeaturesAll(ItemIncomplete $item) {
		$statement = $this->getPDO()->prepare('DELETE IGNORE FROM ItemFeature WHERE `Code` = ?');

		try {
			$result = $statement->execute([$item->getCode()]);
			assert($result, 'delete all features');
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Count how many items have each possible value for a feature
	 *
	 * e.g. with feature name = "color":
	 * - red: 10
	 * - yellow: 6
	 * - grey: 4
	 * and so on.
	 *
	 * If some (enum) values aren't assigned to an item they're not reported, actually,
	 * so it's not really every possible value.
	 *
	 * @param string $feature Feature name
	 * @param int $limit max number of rows to retrieve
	 *
	 * @return int[] value => count, sorted by count descending
	 */
	public function groupItemsByValue(string $feature, int $limit = 100): array {
		$pdo = $this->getPDO();

		$statement = $pdo->prepare('SELECT COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) AS `Value`, COUNT(*) as Quantity FROM ItemFeature WHERE Feature = ? LIMIT ?');

		$result = [];
		try {
			$result = $statement->execute([$feature, $limit]);
			assert($result, "group items by value");
			if($statement->rowCount() > 0) {
				$result = $statement->fetchAll(\PDO::FETCH_NUM)[0];
			}
		} finally {
			$statement->closeCursor();
		}

		return $result;
	}
}
