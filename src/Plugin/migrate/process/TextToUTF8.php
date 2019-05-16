<?php

namespace Drupal\skilld_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use ForceUTF8\Encoding;

/**
 * This plugin ensures that special characters will be saved correctly.
 *
 * @MigrateProcessPlugin(
 *   id = "text_to_utf8"
 * )
 */
class TextToUTF8 extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      return html_entity_decode(Encoding::toUTF8($value));
    }

    $return = [];
    foreach ($value as $key => $subvalue) {
      if (!is_string($subvalue)) {
        $return[$key] = $subvalue;
      }
      else {
        $return[$key] = html_entity_decode(Encoding::toUTF8($subvalue));
      }
    }

    return $return;
  }

}
