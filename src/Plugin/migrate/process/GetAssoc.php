<?php

namespace Drupal\skilld_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\process\Get;

/**
 * Gets the source value maintaining the keys in case of the array of values.
 *
 * Available configuration keys:
 * - process: key of the source array, which should be processed as array.
 * - source: Source property.
 *
 * @MigrateProcessPlugin(
 *   id = "get_assoc"
 * )
 */
class GetAssoc extends Get {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source = $this->configuration['source'];
    $parent = parent::transform($value, $migrate_executable, $row, $destination_property);

    if (!is_array($source)) {
      return $parent;
    }

    $keys = array_keys($source);
    $processed_values = array_combine($keys, $parent);
    if (!empty($this->configuration['process']) && isset($processed_values[$this->configuration['process']]) && is_array($processed_values[$this->configuration['process']])) {
      $return_values = [];
      foreach ($processed_values[$this->configuration['process']] as $processed_value) {
        $return_value = $processed_values;
        $return_value[$this->configuration['process']] = $processed_value;
        $return_values[] = $return_value;
      }
      return $return_values;
    }
    return $processed_values;
  }

}
