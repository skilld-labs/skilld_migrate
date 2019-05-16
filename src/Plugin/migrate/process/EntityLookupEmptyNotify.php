<?php

namespace Drupal\skilld_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;

/**
 * This plugin skip row if entities does not exist.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_lookup_empty_notify",
 *   handle_multiples = TRUE
 * )
 *
 * @see EntityLookup
 */
class EntityLookupEmptyNotify extends EntityLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $entity_id = parent::transform($value, $migrateExecutable, $row, $destinationProperty);

    if (empty($entity_id)) {
      $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      $this->migration->getIdMap()->saveMessage($row->getSourceIdValues(), $message, MigrationInterface::MESSAGE_INFORMATIONAL);
    }

    return $entity_id;
  }

}
