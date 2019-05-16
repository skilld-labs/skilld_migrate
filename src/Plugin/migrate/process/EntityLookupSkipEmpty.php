<?php

namespace Drupal\skilld_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;
use Drupal\migrate\MigrateSkipRowException;

/**
 * This plugin skip row if entities does not exist.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_lookup_skip_empty",
 *   handle_multiples = TRUE
 * )
 *
 * @see EntityLookup
 */
class EntityLookupSkipEmpty extends EntityLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $entity_id = parent::transform($value, $migrateExecutable, $row, $destinationProperty);

    if (empty($entity_id)) {
      $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      throw new MigrateSkipRowException($message);
    }

    return $entity_id;
  }

}
