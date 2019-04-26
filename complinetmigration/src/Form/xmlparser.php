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

class xmlparser extends FormBase {

  /**
   *  {@inheritdoc}
   */
  public function getFormId() {
    return 'first_form';
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
       this will create a hierarchical taxonomy of rules and titles &nbsp;&nbsp;"
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
      '#value' => t('Process Complinet XML Into Taxonomies'),
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
    drupal_set_message('thanks for submitting the form! Your data has been rolled back.');
    if($form_state->getValue('submissiontype') == 1) {
      $tids = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'complinet_titles')
        ->execute();
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);

      $tids = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'complinet_rules')
        ->execute();
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);

      $vocabularies = [
        'complinet_rules',
        'complinet_titles'
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
      $vid = "complinet_titles";
      $name = "Complinet Titles";
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
      $vid = "complinet_rules";
      $name = "Complinet Rules";
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
      $categories_vocabulary = 'complinet_titles'; // Vocabulary machine name
      $categories_vocabulary2 = 'complinet_rules'; // Vocabulary machine name
      foreach ($xml->section->section as $a_section) {
        $count = count($a_section->version) - 1;
        $a_title = (string) $a_section->version[$count]->title;
        if($a_title == "Notices") {
          continue;
        }
        $a_rulenumber = (string) $a_section->version[$count]->number;
        if($a_title == 'NOTICES') {
          continue;
        }
        $ToProcess = array("FINRA Rules", "Capital Acquisition Broker Rules",
        "Funding Portal Rules", "NASD&#174; Rules", "Immediately Effective Rule Changes Pending Determination of Effective Date",
        "Incorporated NYSE Rules", "Incorporated NYSE Rule Interpretations",
        "Retired Rules", "Temporary Dual FINRA-NYSE Member Rule Series");
        if(!in_array( $a_title, $ToProcess )) {
          continue;
        }
        if($a_title != NULL) {
          $term = Term::create(array(
            'parent' => array(),
            'name' => strip_tags($a_title),
            'vid' => $categories_vocabulary,
          ));
          $TermResult = $term->save();
          $tid = $term->id();

          if($a_rulenumber != NULL) {
            drupal_set_message('GOT HERE');
            $TermNumber = Term::create(array(
              'parent' => array(),
              'name' => $a_rulenumber,
              'vid' => $categories_vocabulary2,
            ));
            $TermResultNumber = $TermNumber->save();
            $tnid = $TermNumber->id();
          }
        }
        drupal_set_message($a_title);
        drupal_set_message($a_rulenumber);
        foreach ($a_section->section as $a_section2) {
          $count = count($a_section2->version) - 1;
          $a_title2 = (string) $a_section2->version[$count]->title;
          $startdate = $a_section2->version[$count]['start'];
          $a_rulenumber2 = $a_title . " - " . (string) $a_section2->version[$count]->number;
          drupal_set_message("two: " . $a_title2);
          drupal_set_message("two, rule number: " . $a_rulenumber2);
          drupal_set_message('Start date is ' . $startdate);
          if($startdate == '2030-12-31') {
            drupal_set_message('Start date of 2030');
            continue;
          }
          if($tid != NULL && $a_title2 != NULL) {
            $term2 = Term::create(array(
              'parent' => $tid,
              'name' => strip_tags($a_title2),
              'vid' => $categories_vocabulary,
            ));
            $TermResult2 = $term2->save();
            $tnid = $term2->id();
          }

          if($a_rulenumber2 != NULL && strlen((string) $a_section2->version[$count]->number) > 0) {
            $TermNumber2 = Term::create(array(
              'parent' => array(),
              'name' => $a_rulenumber2,
              'vid' => $categories_vocabulary2,
            ));
            $TermResultNumber2 = $TermNumber2->save();
            $tnid2 = $TermNumber2->id();
          }
          foreach ($a_section2 as $a_section3) {
            $count = count($a_section3->version) - 1;
            $a_title3 = (string) $a_section3->version[$count]->title;
            $startdate = $a_section3->version[$count]['start'];
            drupal_set_message("three: " . $a_title3);
            drupal_set_message("three, rule number: " . $a_rulenumber3);
            drupal_set_message('Start date is ' . $startdate);
            if($startdate == '2030-12-31') {
              drupal_set_message('Start date of 2030');
              continue;
            }
            $a_rulenumber3 = $a_title . " - " . (string) $a_section3->version[$count]->number;
            if($tid2 != NULL && $a_title3 != NULL) {
              $term3 = Term::create(array(
                'parent' => $tid2,
                'name' => strip_tags($a_title3),
                'vid' => $categories_vocabulary,
              ));
              $TermResult3 = $term3->save();
              $tid3 = $term3->id();
            }

            if($tnid2 != NULL && $a_rulenumber3 != NULL && strlen((string) $a_section3->version[$count]->number) > 0) {
              $TermNumber3 = Term::create(array(
                'parent' => $tnid2,
                'name' => $a_rulenumber3,
                'vid' => $categories_vocabulary2,
              ));
              $TermResultNumber3 = $TermNumber3->save();
              $tnid3 = $TermNumber3->id();
            }
            // --begin new section --
            foreach ($a_section3 as $a_section4) {
              $count = count($a_section4->version) - 1;
              $a_title4 = (string) $a_section4->version[$count]->title;
              $startdate = $a_section4->version[$count]['start'];
              drupal_set_message('Start date is ' . $startdate);
              drupal_set_message("four: " . $a_title4);
              drupal_set_message("four, rule number: " . $a_rulenumber4);
              if($startdate == '2030-12-31') {
                drupal_set_message('Start date of 2030');
                continue;
              }
              $a_rulenumber4 = $a_title . " - " . (string) $a_section4->version[$count]->number;
              if($tid3 != NULL && $a_title4 != NULL) {
                $term4 = Term::create(array(
                  'parent' => $tid3,
                  'name' => strip_tags($a_title4),
                  'vid' => $categories_vocabulary,
                ));
                $TermResult4 = $term4->save();
                $tid4 = $term4->id();
              }

              if($tnid3 != NULL && $a_rulenumber4 != NULL && strlen((string) $a_section4->version[$count]->number) > 0) {
                $TermNumber4 = Term::create(array(
                  'parent' => $tnid3,
                  'name' => $a_rulenumber4,
                  'vid' => $categories_vocabulary2,
                ));
                $TermResultNumber4 = $TermNumber4->save();
                $tnid4 = $TermNumber4->id();
              }

              // ---- end section ----
              // --begin new section --
              foreach ($a_section4 as $a_section5) {
                $count = count($a_section5->version) - 1;
                $a_title5 = (string) $a_section5->version[$count]->title;
                $startdate = $a_section5->version[$count]['start'];
                $a_rulenumber5 = $a_title . " - " . (string) $a_section5->version[$count]->number;
                drupal_set_message("five: " . $a_title5);
                drupal_set_message("five, rule number: " . $a_rulenumber5);
                drupal_set_message('Start date is ' . $startdate);
                if($startdate == '2030-12-31') {
                  drupal_set_message('Start date of 2030');
                  continue;
                }
                if($tid4 != NULL && $a_title5 != NULL) {
                  $term5 = Term::create(array(
                    'parent' => $tid4,
                    'name' => strip_tags($a_title5),
                    'vid' => $categories_vocabulary,
                  ));
                  $TermResult5 = $term5->save();
                  $tid5 = $term5->id();
                }

                if($tnid4 != NULL && $a_rulenumber5 != NULL && strlen((string) $a_section5->version[$count]->number) > 0) {
                  $TermNumber5 = Term::create(array(
                    'parent' => $tnid4,
                    'name' => $a_rulenumber5,
                    'vid' => $categories_vocabulary2,
                  ));
                  $TermResultNumber5 = $TermNumber5->save();
                  $tnid5 = $TermNumber5->id();
                }

              // ---- end section ----
              // --begin new section --
                foreach ($a_section5 as $a_section6) {
                  $count = count($a_section6->version) - 1;
                  $a_title6 = (string) $a_section6->version[$count]->title;
                  $startdate = $a_section6->version[$count]['start'];
                  $a_rulenumber6 = $a_title . " - " . (string) $a_section6->version[$count]->number;
                  drupal_set_message("six: " . $a_title6);
                  drupal_set_message("six, rule number: " . $a_rulenumber6);
                  drupal_set_message('Start date is ' . $startdate);
                  if($startdate == '2030-12-31') {
                    drupal_set_message('Start date of 2030');
                    continue;
                  }
                  if($tid5 != NULL && $a_title6 != NULL) {
                    $term6 = Term::create(array(
                      'parent' => $tid5,
                      'name' => strip_tags($a_title6),
                      'vid' => $categories_vocabulary,
                    ));
                    $TermResult6 = $term6->save();
                    $tid6 = $term6->id();
                  }

                  if($tnid5 != NULL && $a_rulenumber6 != NULL && strlen((string) $a_section6->version[$count]->number) > 0) {
                    $TermNumber6 = Term::create(array(
                      'parent' => $tnid5,
                      'name' => $a_rulenumber6,
                      'vid' => $categories_vocabulary2,
                    ));
                    $TermResultNumber6 = $TermNumber6->save();
                    $tnid6 = $TermNumber6->id();
                  }

                // ---- end section ----
                // --begin new section --
                  foreach ($a_section6 as $a_section7) {
                    break;
                    $count = count($a_section7->version) - 1;
                    $a_title7 = (string) $a_section7->version[$count]->title;
                    $startdate = $a_section7->version[$count]['start'];
                    $a_rulenumber7 = $a_title . " - " . (string) $a_section7->version[$count]->number;
                    drupal_set_message("seven: " . $a_title7);
                    drupal_set_message("seven, rule number: " . $a_rulenumber7);
                    drupal_set_message('Start date is ' . $startdate);
                    if($startdate == '2030-12-31') {
                      drupal_set_message('Start date of 2030');
                      continue;
                    }
                    if($tid6 != NULL && $a_title7 != NULL) {
                      $term7 = Term::create(array(
                        'parent' => $tid6,
                        'name' => strip_tags($a_title7),
                        'vid' => $categories_vocabulary,
                      ));
                      $TermResult7 = $term7->save();
                      $tid7 = $term7->id();
                    }

                    if($tnid6 != NULL && $a_rulenumber7 != NULL && strlen((string) $a_section7->version[$count]->number) > 0) {
                      $TermNumber7 = Term::create(array(
                        'parent' => $tnid6,
                        'name' => $a_rulenumber7,
                        'vid' => $categories_vocabulary2,
                      ));
                      $TermResultNumber7 = $TermNumber7->save();
                      $tnid7 = $TermNumber7->id();
                    }

                  // ---- end section ----
                  // --begin new section --
                    foreach ($a_section7 as $a_section8) {
                      break;
                      $count = count($a_section8->version) - 1;
                      $a_title8 = (string) $a_section8->version[$count]->title;
                      $startdate = $a_section8->version[$count]['start'];
                      if($startdate == '2030-12-31') {
                        drupal_set_message('Start date is ' . $startdate);
                        //continue;
                      }
                      $a_rulenumber8 = $a_title . " - " . (string) $a_section8->version[$count]->number;
                      drupal_set_message("eight: " . $a_title8);
                      drupal_set_message("eight, rule number: " . $a_rulenumber8);
                      if($tid7 != NULL && $a_title8 != NULL) {
                        $term8 = Term::create(array(
                          'parent' => $tid7,
                          'name' => strip_tags($a_title8),
                          'vid' => $categories_vocabulary,
                        ));
                        $TermResult8 = $term8->save();
                        $tid8 = $term8->id();
                      }

                      if($tnid7 != NULL && $a_rulenumber8 != NULL && strlen((string) $a_section8->version[$count]->number) > 0) {
                        $TermNumber8 = Term::create(array(
                          'parent' => $tnid7,
                          'name' => $a_rulenumber8,
                          'vid' => $categories_vocabulary2,
                        ));
                        $TermResultNumber8 = $TermNumber8->save();
                        $tnid8 = $TermNumber8->id();
                      }
                    }
                    // ---- end section ----

        }
        # code...
      }
    }
}
}
}
}
}

  }

}
}


// for QA
/*
select name,count(*), tid from taxonomy_term_field_data
where vid='complinet_rules'
group by name
having count(*) >1;
*/
