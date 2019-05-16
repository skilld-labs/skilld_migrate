<?php

namespace Drupal\skilld_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate;

/**
 * This plugin generates entities within the process plugin.
 *
 * Uses the values from row source.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_generate_from_source"
 * )
 *
 * @see EntityGenerate
 */
class EntityGenerateFromSource extends EntityGenerate {

  /**
   * The source values for entity creation.
   *
   * @var array
   */
  protected $source;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $this->source = $row->getSource();
    return parent::transform($value, $migrateExecutable, $row, $destinationProperty);
  }

  /**
   * {@inheritdoc}
   */
  protected function entity($value) {
    $entity_values = parent::entity($value);

    $entity_values = array_merge($entity_values, $this->source);

    return $entity_values;
  }

}
