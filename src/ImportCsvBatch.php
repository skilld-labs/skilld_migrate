<?php

namespace Drupal\skilld_migrate;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateMapSaveEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use ForceUTF8\Encoding;

/**
 * Runs import from CSV batch.
 */
class ImportCsvBatch {

  /**
   * MigrateMessage instance to capture messages during the migration process.
   *
   * @var \Drupal\migrate_drupal_ui\Batch\MigrateMessageCapture
   */
  protected static $messages;

  /**
   * The processed items for the batch.
   *
   * @var int
   */
  protected static $numProcessed = 0;

  /**
   * The imported items for the batch.
   *
   * @var int
   */
  protected static $numImported = 0;

  /**
   * Returns the logger using the skilld_migrate channel.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger instance.
   */
  protected static function logger() {
    return \Drupal::logger('skilld_migrate');
  }

  /**
   * Returns the messenger.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   The messenger instance.
   */
  protected static function messenger() {
    return \Drupal::messenger();
  }

  /**
   * Counts up map save events.
   *
   * @param \Drupal\migrate\Event\MigrateMapSaveEvent $event
   *   The map save event.
   */
  public static function onMapSave(MigrateMapSaveEvent $event) {
    static::$numProcessed++;
  }

  /**
   * Reacts to item import.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post-save event.
   */
  public static function onPostRowSave(MigratePostRowSaveEvent $event) {
    static::$numImported++;
  }

  /**
   * Runs import migration batch.
   *
   * @param array $config
   *   An array of user input values from the form.
   * @param array $context
   *   The batch context.
   */
  public static function run(array $config, array &$context) {
    if (empty($context['results'])) {
      $context['results'] = [
        'result' => MigrationInterface::RESULT_FAILED,
        'processed' => 0,
        'imported' => 0,
        'skipped' => 0,
      ];
    }
    else {
      static::$numProcessed = $context['results']['processed'];
      static::$numImported = $context['results']['imported'];
    }

    // Add event listeners to count number of processed results.
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $events = [
      MigrateEvents::POST_ROW_SAVE => 'onPostRowSave',
      MigrateEvents::MAP_SAVE => 'onMapSave',
    ];
    foreach ($events as $event_name => $method) {
      $listeners = $event_dispatcher->getListeners($event_name);
      foreach ($listeners as $listener) {
        // Check if we already have our listener added.
        if (is_array($listener) && reset($listener) == static::class) {
          // Remove our listener if we don't need to count the rows.
          if (!$config['count_rows']) {
            $event_dispatcher->removeListener($event_name, $listener);
          }
          continue 2;
        }
      }
      // Add our listener if we need to count the rows.
      if ($config['count_rows']) {
        $event_dispatcher->addListener($event_name, [
          static::class,
          $method,
        ]);
      }
    }

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $config['migration'];

    $migration_result = MigrationInterface::RESULT_FAILED;
    if ($migration) {
      static::$messages = new MigrateMessage();
      $executable = new MigrateExecutable($migration, static::$messages);
      try {
        $migration_result = $executable->import();
      }
      catch (\Exception $e) {
        static::logger()->error(Encoding::toUTF8($e->getMessage()));
      }
      if ($migration_result == MigrationInterface::RESULT_FAILED) {
        $migration->setStatus(MigrationInterface::STATUS_IDLE);
      }
    }
    $context['results']['migration_group'] = $migration->migration_group;
    $context['results']['result'] = $migration_result;
    $context['results']['processed'] = static::$numProcessed;
    $context['results']['imported'] = static::$numImported;
    $context['results']['skipped'] = static::$numProcessed - static::$numImported;

    $context['message'] = new PluralTranslatableMarkup($context['results']['processed'], '1 row has been processed.', '@count rows have been processed.');
  }

  /**
   * Implements the Batch API finished method.
   */
  public static function finished($success, $results, $operations) {
    $status = ($success && $results['result'] == MigrationInterface::RESULT_COMPLETED) ? t('Import has been successfully completed.') : FALSE;
    if (!$status) {
      switch ($results['result']) {
        case MigrationInterface::RESULT_INCOMPLETE:
          $status = t('Import has incomplete status.');
          break;

        case MigrationInterface::RESULT_STOPPED:
          $status = t('Import has been stopped.');
          break;

        case MigrationInterface::RESULT_FAILED:
          $status = t('Import has failed.');
          break;

        case MigrationInterface::RESULT_SKIPPED:
        case MigrationInterface::RESULT_DISABLED:
          $status = t('Import has been skipped.');
          break;

        default:
          $status = t('Import has unknown status.');
      }
    }
    if ($results['processed'] == 0) {
      static::messenger()->addMessage(t('File has been processed. Data has been created/updated.'));
    }
    else {
      static::messenger()->addMessage(new PluralTranslatableMarkup($results['processed'],
        '@status 1 row has been processed, @imported imported, @skipped skipped.',
        '@status @count rows have been processed, @imported imported, @skipped skipped.',
        [
          '@status' => $status,
          '@imported' => $results['imported'],
          '@skipped' => $results['skipped'],
        ]
      ));
    }

    // Provide link to review results.
    if (!isset($results['migration_group'])) {
      return;
    }
    static::messenger()->addMessage(t('See the results at @link.', [
      '@link' => Link::createFromRoute(
        t('migration group'),
        'entity.migration.list',
        [
          'migration_group' => $results['migration_group'],
        ],
        ['attributes' => ['target' => '_blank']])->toString(),
    ]));
  }

}
