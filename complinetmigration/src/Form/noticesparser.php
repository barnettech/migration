<?php

/**
 * @file
 * Contains \Drupal\complinetmigration\Form\xmlparser.
 */

namespace Drupal\complinetmigration\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class noticesparser extends FormBase {

  /**
   *  {@inheritdoc}
   */
  public function getFormId() {
    return 'notices_form';
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
      "#markup" => "Clicking here will parse through 5 levels of complinet xml,
       this will create a hierarchical taxonomy of notices &nbsp;&nbsp;"
    );
    $active = array(0 => t('Process data'), 1 => t('Rollback data'));
    $form['submissiontype'] = array(
      '#type' => 'radios',
      '#title' => t('Rollback or Process data'),
      '#default_value' => isset($node->active) ? $node->active : 0,
      '#options' => $active
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Process Complinet XML Into Notices taxonomy hierarchy'),
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the form values.
    $validators = array('file_validate_extensions' => array('csv'));
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
       // Do something useful.
       drupal_set_message('thanks for submitting the form!');
       drupal_set_message('thanks for submitting the form! Your data has been rolled back.');
       if($form_state->getValue('submissiontype') == 1) {
         $tids = \Drupal::entityQuery('taxonomy_term')
           ->condition('vid', 'complinet_notices')
           ->execute();
         $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
         $entities = $controller->loadMultiple($tids);
         $controller->delete($entities);

         $vocabularies = [
           'complinet_notices'
         ];
         foreach ($vocabularies as $vocabulary) {
           $vocab = \Drupal\taxonomy\Entity\Vocabulary::load($vocabulary);
           if ($vocab) {
             $vocab->delete();
           }
         }
       }
       else {
         if (file_exists(drupal_get_path('module', 'complinetmigration') . '/FINRAManual24-04-2019.xml')) {
           $vid = "complinet_notices";
           $name = "Complinet Notices";
           $vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
           if (!isset($vocabularies[$vid])) {
             $vocabulary = \Drupal\taxonomy\Entity\Vocabulary::create(array(
                 'vid' => $vid,
                 //'machine_name' => $vid,
                 'description' => '',
                 'name' => $name,
             ));
             $vocabulary->save();
           }
           $xml = simplexml_load_file(drupal_get_path('module', 'complinetmigration') . '/FINRAManual24-04-2019.xml');
           $xml2count = count((string) $xml->section->version) - 1;
           $xml2 = (string) $xml->section->version[$xml2count]->title;
           drupal_set_message('<pre>' . print_r($xml2, TRUE) . '</pre>');
           $categories_vocabulary2 = 'complinet_notices'; // Vocabulary machine name
           foreach ($xml->section->section as $a_section) {
             $count = count($a_section->version) - 1;
             $a_title = (string) $a_section->version[$count]->title;
             if($a_title != "Notices") {
               continue;
             }
             $a_rulenumber = (string) $a_section->version[$count]->number;
             if($a_title != NULL) {
               if($a_rulenumber != NULL) {
                 drupal_set_message('GOT HERE');
                 $TermNumber = Term::create(array(
                   'parent' => array(),
                   'name' => strip_tags($a_rulenumber),
                   'vid' => $categories_vocabulary2,
                 ));
                 $TermResultNumber = $TermNumber->save();
                 $tnid = $TermNumber>id();
               }
             }
             drupal_set_message($a_title);
             drupal_set_message($a_rulenumber);
             foreach ($a_section->section as $a_section2) {
               $count = count($a_section2->version) - 1;
               $a_title2 = (string) $a_section2->version[$count]->title;
               $number = (string) $a_section2->version[$count]->number;
               $ToProcess = array("2009", "2010");
               if(in_array( $number, $ToProcess )) {
                 $a_title2 = $number;
               }

               if(strlen($a_title2) > 0) {
                 $TermNumber2 = Term::create(array(
                   'parent' => array(),
                   'name' => 'Notices - ' . $a_title2,
                   'vid' => $categories_vocabulary2,
                 ));
                 $TermResultNumber2 = $TermNumber2->save();
                 $tnid2 = $TermNumber2->id();
               } else {
                 drupal_set_message('number: ' . $number);
                 drupal_set_message('length: ' . strlen($a_title2));
                 $ToProcess = array("2009", "2010");
                 if(!in_array( $number, $ToProcess )) {
                   drupal_set_message('rejected: ' . $a_title2);
                   drupal_set_message('rejected: ' . $a_title2);
                   continue;
                 }
               }
               drupal_set_message(strlen((string) $a_section2->version[$count]->number));
               drupal_set_message(strlen($a_title2));
               drupal_set_message("two: " . $a_title2);
               drupal_set_message("two, rule number: " . $a_rulenumber2);
               foreach ($a_section2 as $a_section3) {
                 $count = count($a_section3->version) - 1;
                 $a_title3 = (string) $a_section3->version[$count]->title;
                 $a_rulenumber3 = $a_title . " - " . (string) $a_section3->version[$count]->number;

                 if($tnid2 != NULL && $a_rulenumber3 != NULL && strlen((string) $a_section3->version[$count]->number) > 0) {
                   $TermNumber3 = Term::create(array(
                     'parent' => $tnid2,
                     'name' => strip_tags($a_rulenumber3),
                     'vid' => $categories_vocabulary2,
                   ));
                   $TermResultNumber3 = $TermNumber3->save();
                   $tnid3 = $TermNumber3->id();
                 }

                 drupal_set_message("three: " . $a_title3);
                 drupal_set_message("three, rule number: " . $a_rulenumber3);

             }
             # code...
           }
         }
     }
     }
   }
}
