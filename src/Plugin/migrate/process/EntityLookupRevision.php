<?php

namespace Drupal\skilld_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;

/**
 * This plugin looks for existing entities.
 *
 * Returns entity id and entity revision id.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_lookup_revision",
 *   handle_multiples = TRUE
 * )
 *
 * @see EntityLookup
 */
class EntityLookupRevision extends EntityLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $entity_id = parent::transform($value, $migrateExecutable, $row, $destinationProperty);

    if (empty($entity_id)) {
      return NULL;
    }

    $multiple = is_array($entity_id);
    $ids = $multiple ? $entity_id : [$entity_id];

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityManager->getStorage($this->lookupEntityType);
    $entities = $storage->loadMultiple($ids);

    if (empty($entities)) {
      return NULL;
    }

    $results = [];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($entities as $entity) {
      $results[] = [$entity->id(), $entity->getRevisionId()];
    }

    return $results;
  }

}
