<?php

namespace Drupal\skilld_migrate\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Archiver\ArchiveTar;

/**
 * File form.
 */
class FilesForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the FilesForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'skilld_migrate_files_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['archive'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Files archive'),
      '#description' => $this->t('Upload files archive for import - .tar or .tar.gz, archive should not have subdirectories.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['tar gz'],
      ],
      '#upload_location' => 'public://csv/files',
      '#required' => TRUE,
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
    $file_id = reset($form_state->getValue('archive'));
    $file = $this->entityTypeManager->getStorage('file')->load($file_id);

    try {
      $archive = new ArchiveTar($this->fileSystem->realpath($file->getFileUri()));
      $archive->extract($this->fileSystem->realpath('public://csv/files'));
      $this->messenger->addMessage($this->t('Archive content has been successfully extracted.'));
    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('There was an error while extracting archive content.'));
    }

    $file->delete();
  }

}
