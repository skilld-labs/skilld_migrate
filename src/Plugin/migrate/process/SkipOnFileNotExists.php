<?php

namespace Drupal\skilld_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Skips processing the current row when the input file uri is not exist.
 *
 * Available configuration keys:
 * - method: (optional) What to do if the input file uri does not exist.
 *
 * Possible values:
 *   - row: Skips the entire row.
 *   - process: Prevents further processing of the input property.
 * - message: (optional) A message to be logged in the {migrate_message_*} table
 *   for this row. Messages are only logged for the 'row' method. If not set,
 *   nothing is logged in the message table.
 *
 * Examples:
 *
 * @code
 * process:
 *   file:
 *     plugin: skip_on_file_not_exists
 *     method: row
 *     source: fileuri
 *     message: 'File field_name does not exist'
 * @endcode
 * If file 'fileuri' does not exist, the entire row is skipped
 * and the message is logged in the message table.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_on_file_not_exists"
 * )
 */
class SkipOnFileNotExists extends ProcessPluginBase {

  /**
   * Skips the current row when input file uri does not exist.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return mixed
   *   The input value, $value, if it is not empty.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   Thrown if the source property is not set and the row should be skipped,
   *   records with STATUS_IGNORED status in the map.
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!file_exists($value)) {
      $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      throw new MigrateSkipRowException($message);
    }
    return $value;
  }

  /**
   * Stops processing the current property when input file uri does not exist.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return mixed
   *   The input value, $value, if it is not empty.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   *   Thrown if the source property is not set and rest of the process should
   *   be skipped.
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!file_exists($value)) {
      throw new MigrateSkipProcessException();
    }
    return $value;
  }

}
