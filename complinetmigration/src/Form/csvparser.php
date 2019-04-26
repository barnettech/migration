<?php

/**
 * @file
 * Contains \Drupal\complinetmigration\Form\csvparser.
 */

namespace Drupal\complinetmigration\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class csvparser extends FormBase {

  /**
   *  {@inheritdoc}
   */
  public function getFormId() {
    return 'csvparser_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Use the Form API to define form elements.
    $form['htmlforyou'] = array(
      "#markup" => "Clicking here will parse through a csv file full of recordids to not process into nodes or taxonomy"
    );

    $active = array(0 => t('Process data'), 1 => t('Rollback data'));
    $form['submissiontype'] = array(
      '#type' => 'radios',
      '#title' => t('Rollback or Process data'),
      '#default_value' => isset($node->active) ? $node->active : 0,
      '#options' => $active
    );

    $form['my_file'] = array(
      '#type' => 'managed_file',
      '#name' => 'my_file',
      '#title' => t('Upload a CSV File to batch add users to the system'),
      '#size' => 20,
      '#upload_validators' => $validators,
      '#upload_location' => 'public://my_files/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('csv', 'xlsx'),
        'file_validate_size' => array(MAX_FILE_SIZE*1024*1024),
       ),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Process CSV file'),
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the form values.
    $validators = array('file_validate_extensions' => array('csv', 'xlsx'));
  // Check for a new uploaded file.
  $file = file_save_upload('csv_upload', $validators);
  //$file = $form_state['values']['csv_upload'];
  if (isset($file)) {
    // File upload was attempted.
    if ($file) {
      // Put the temporary file in form_values so we can save it on submit.
      $form_state['values']['csv_upload_file'] = $file;
    }
    else {
      // File upload failed.
      form_set_error('csv_upload', t('The file could not be uploaded.'));
    }
  }
     }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Got in here');
    if($form_state->getValue('submissiontype') == 1) {
      drupal_set_message('thanks for submitting the form! Your data has been rolled back.');
    }
    else {
      drupal_set_message('thanks for submitting the form!');
      $fid = $form_state->getValue('my_file');
      //dpm($fid);
      // dpm(File::load($fid[0]));
      // https://drupal.stackexchange.com/questions/19894/how-can-i-import-the-contents-of-an-uploaded-csv-file-into-a-drupal-managed-tabl
      if (!empty($fid)) {
        $file = File::load($fid[0]);
        $file->setPermanent();
        $file->save();
      }
      $data = file_get_contents($file->getFileUri());
      $lines = explode(PHP_EOL, $data);
      $array = array();
      foreach ($lines as $line) {
       $array[] = str_getcsv($line);
      }
      // dpm($array);
      drupal_set_message("About to loop through the array");
      $recordids = array();
      foreach($array as $csvdata) {
       /*drupal_set_message('zero: ' . $csvdata[0]);
       drupal_set_message('one: ' . $csvdata[1]);
       drupal_set_message('two: ' . $csvdata[2]);*/
       if($csvdata[2] == 'TRUE') {
         $recordids[] = $csvdata[0];
                    // ---- end section ----
        }
        # code...
      }
      dpm($recordids);
}
}
}
