<?php

namespace Drupal\skilld_migrate\Form;

use Drupal\Core\Link;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\skilld_migrate\ImportCsvBatch;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Import form.
 */
class ImportCsvForm extends FormBase {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the ImportCsvForm.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MigrationPluginManagerInterface $plugin_manager, MessengerInterface $messenger) {
    $this->pluginManager = $plugin_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'skilld_migrate_import_csv_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $migration_groups = MigrationGroup::loadMultiple();

    $form['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Please verify migration group settings by editing it at @link.', [
        '@link' => Link::createFromRoute(
          $this->t('Migration groups'),
          'entity.migration_group.list',
          [],
          ['attributes' => ['target' => '_blank']])->toString(),
      ]) . '<br>'
      . $this->t('If your migration requires file assets please upload them at @link.', [
        '@link' => Link::createFromRoute(
          $this->t('File assets'),
          'skilld_migrate.files')->toString(),
      ]),
    ];

    $options = [];
    /** @var \Drupal\migrate_plus\Entity\MigrationGroup $migration_group */
    foreach ($migration_groups as $migration_group) {
      $options[$migration_group->id()] = $migration_group->label();
    }
    $form['migration_group'] = [
      '#type' => 'select',
      '#title' => $this->t('Select migration group to run'),
      '#options' => $options,
    ];
    $form['count'] = [
      '#type' => 'select',
      '#title' => $this->t('Rows limit per batch step'),
      '#options' => array_combine([10, 20, 50, 100], [10, 20, 50, 100]),
      '#default_value' => 10,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: load migration group and verify the file is available?
    $migration_group_id = $form_state->getValue('migration_group');

    $migrations = $this->pluginManager->createInstances([]);
    // Do not return any migrations which fail to meet requirements.
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($migrations as $id => $migration) {
      if ($migration->getSourcePlugin() instanceof RequirementsInterface) {
        try {
          $migration->getSourcePlugin()->checkRequirements();
        }
        catch (RequirementsException $e) {
          unset($migrations[$id]);
        }
      }
    }

    // Filter by migration group.
    foreach ($migrations as $id => $migration) {
      if ($migration->get('migration_group') != $migration_group_id) {
        unset($migrations[$id]);
      }
    }

    // TODO: work on migration dependencies.
    $operations = [];
    $count_rows = TRUE;
    foreach ($migrations as $id => $migration) {
      $source = $migration->getSourceConfiguration();
      if ($source['plugin'] != 'csv_limit') {
        $operations[] = [
          [ImportCsvBatch::class, 'run'],
          [
            ['migration' => $migration],
          ],
        ];

        continue;
      }

      $source_plugin = $migration->getSourcePlugin();
      $source_plugin_clone = clone $source_plugin;
      $total_count = $source_plugin_clone->initializeIterator()->count();
      $offset = 0;
      $count = (int) $form_state->getValue('count');

      while ($total_count > 0) {
        $source['offset'] = $offset;
        $source['count'] = $count;
        $migration_limit = clone $migration;
        $migration_limit->set('source', $source);

        $operations[] = [
          [ImportCsvBatch::class, 'run'],
          [
            [
              'migration' => $migration_limit,
              'count_rows' => $count_rows,
            ],
          ],
        ];

        $total_count = $total_count - $count;
        $offset += $count;
      }

      // Count rows only for first migration.
      $count_rows = FALSE;
    }

    $batch = [
      'title' => $this->t('Running import'),
      'progress_message' => $this->t('Running import'),
      'operations' => $operations,
      'finished' => [
        ImportCsvBatch::class,
        'finished',
      ],
    ];
    batch_set($batch);
  }

}
