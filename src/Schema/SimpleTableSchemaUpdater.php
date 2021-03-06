<?php

namespace Wikibase\Database\Schema;

use Wikibase\Database\Schema\Definitions\FieldDefinition;
use Wikibase\Database\Schema\Definitions\TableDefinition;

/**
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SimpleTableSchemaUpdater implements TableSchemaUpdater {

	protected $schemaModifier;

	public function __construct( SchemaModifier $schemaModifier ) {
		$this->schemaModifier = $schemaModifier;
	}

	/**
	 * @see TableSchemaUpdater::updateTable
	 *
	 * @param TableDefinition $currentTable
	 * @param TableDefinition $newTable
	 *
	 * @throws TableSchemaUpdateException
	 */
	public function updateTable( TableDefinition $currentTable, TableDefinition $newTable ) {
		if ( $currentTable->getName() !== $newTable->getName() ) {
			throw new TableSchemaUpdateException(
				$currentTable,
				$newTable,
				'The tables need to have the same name'
			);
		}

		$updater = new PrivateTableUpdate( $this->schemaModifier, $currentTable, $newTable );

		try {
			$updater->updateTable();
		}
		catch ( SchemaModificationException $ex ) {
			throw new TableSchemaUpdateException(
				$currentTable,
				$newTable,
				$ex->getMessage(),
				$ex
			);
		}
	}

}

class PrivateTableUpdate {

	protected $schemaModifier;
	protected $currentTable;
	protected $newTable;

	public function __construct( SchemaModifier $schemaModifier,
		TableDefinition $currentTable, TableDefinition $newTable ) {

		$this->schemaModifier = $schemaModifier;
		$this->currentTable = $currentTable;
		$this->newTable = $newTable;
	}

	public function updateTable() {
		$this->removeFields(
			array_diff_key(
				$this->currentTable->getFields(),
				$this->newTable->getFields()
			)
		);

		$this->addFields(
			array_diff_key(
				$this->newTable->getFields(),
				$this->currentTable->getFields()
			)
		);
	}

	/**
	 * @param FieldDefinition[] $fields
	 */
	protected function removeFields( array $fields ) {
		foreach ( $fields as $field ) {
			$this->schemaModifier->removeField( $this->currentTable->getName(), $field->getName() );
		}
	}

	/**
	 * @param FieldDefinition[] $fields
	 */
	protected function addFields( array $fields ) {
		foreach ( $fields as $field ) {
			$this->schemaModifier->addField( $this->currentTable->getName(), $field );
		}
	}

}
