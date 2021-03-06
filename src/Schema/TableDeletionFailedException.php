<?php

namespace Wikibase\Database\Schema;

/**
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TableDeletionFailedException extends SchemaModificationException {

	protected $tableName;

	public function __construct( $tableName, $message = '', \Exception $previous = null ) {
		parent::__construct( $message, 0, $previous );

		$this->tableName = $tableName;
	}

	public function getTableName() {
		return $this->tableName;
	}

}