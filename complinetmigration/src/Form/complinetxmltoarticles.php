<?php

/**
 * @file
 * Contains \Drupal\complinetmigration\Form\complinetxmltoarticles.
 */

namespace Drupal\complinetmigration\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class complinetxmltoarticles extends FormBase {

  /**
   *  {@inheritdoc}
   */
  public function getFormId() {
    return 'complinetxmltoarticles_form';
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
      "#markup" => "Clicking here will parse through Corporate Organization, and Archives complinet xml,
       this will take the xml to create Articles and will taxonomize them into rules and titles &nbsp;&nbsp;"
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
      '#value' => t('Process Complinet XML Into Articles and taxnomize them into rules, titles, etc'),
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

  /*protected function getTidByName($name = NULL, $vocabulary = NULL) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = (string) $name;
    }
    if (!empty($vocabulary)) {
      $properties['vid'] = $vocabulary;
    }
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    return !empty($term) ? $term->id() : 0;
  }*/


     /**
      * {@inheritdoc}
      */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do something useful.
    $connection = \Drupal::database();
    if($form_state->getValue('submissiontype') == 1) {
      // rollback data
      $connection = \Drupal::database();
      $query = $connection->query("SELECT nid FROM {complinetmigration}");
      $result = $query->fetchAll();
      foreach($result as $row) {
        $nid = $row->nid;
        \Drupal::logger('complinetmigration')->notice('Rolled back nid ' . $nid);
        drupal_set_message($nid);
        $node = node_load($nid);
        if($node) {
          $node->delete();
        }
      }
      $tids = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'complinet_archive_titles')
        ->execute();
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);

      $tids = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'complinet_corp_org_titles')
        ->execute();
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);

      $vocabularies = [
        'complinet_archive_titles',
        'complinet_corp_org_titles'
      ];
      foreach ($vocabularies as $vocabulary) {
        $vocab = \Drupal\taxonomy\Entity\Vocabulary::load($vocabulary);
        if ($vocab) {
          $vocab->delete();
        }
      }
      drupal_set_message("Migration has been successfully rolled back");
    }
    else {
      // process data
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
      // the recordids to not process are $recordids.
      // dpm($recordids);
      // end of getting the array of recordids to not process.
      drupal_set_message('thanks for submitting the form!');
      $xml = simplexml_load_file(drupal_get_path('module', 'complinetmigration') . '/FINRAManual24-04-2019.xml');
      //$xml = simplexml_load_file(drupal_get_path('module', 'complinetmigration') . '/FINRAManual08-08-18.xml');
      $xml2count = count((string) $xml->section->version) - 1;
      $xml2 = (string) $xml->section->version[$xml2count]->title;
      $vid = "complinet_corp_org_titles";
      $name = "Complinet Corporate Organization Titles";
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
      $vid2 = "complinet_archive_titles";
      $name2 = "Complinet Archive Titles";
      $vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
      if (!isset($vocabularies[$vid2])) {
        $vocabulary = \Drupal\taxonomy\Entity\Vocabulary::create(array(
          'vid' => $vid2,
          //'machine_name' => $vid,
          'description' => '',
          'name' => $name2,
        ));
        $vocabulary->save();
      }
      $firstcounter = 0;
      foreach ($xml->section->section as $a_section) {
        $count = count($a_section->version) - 1;
        $a_title = strip_tags((string) $a_section->version[$count]->title);
        //$a_title = str_replace(["-", "–"], '', $a_title);
        $ToProcess = array("Corporate Organization", "Archive");
        if(!in_array( $a_title, $ToProcess )) {
          continue;
        }
        if($a_title == 'Corporate Organization') {
          $TheVid = $vid;
          $TheName = $name;
          // FMR Corp Organization
          $original_content_type = '60936';
        }
        else {
          $TheVid = $vid2;
          $TheName = $name2;
          // FMR Archives
          $original_content_type = '61491';
        }
        $a_rulenumber = (string) $a_section->version[$count]->number;
        $node = Node::create([
          'type'        => 'article',
          'title'       => strip_tags($a_title),
          'langcode' => 'en',
          'uid' => '1',
          'status' => 1,
        ]);
        if($a_title) {
          $node->book = array(
            "bid" => 'new'
          );
        $node->setPublished(TRUE);
        //$node->set('moderation_state', "published");
        $node->save();
        $previous_nid1 = $node->id();
        drupal_set_message($a_title);
        drupal_set_message($a_rulenumber);
        $firstcounter = $firstcounter + 1;
        $secondcounter = 0;
        foreach ($a_section->section as $a_section2) {
          $count = count($a_section2->version) - 1;
          $a_title2 = strip_tags((string) $a_section2->version[$count]->title);
          $a_title2 = html_entity_decode(strip_tags($a_title2), ENT_QUOTES, 'UTF-8');
          $recordid = $a_section2->version[$count]['recordId'];
          if (in_array($recordid, $recordids)) {
            drupal_set_message('skipping recordid ' . $recordid);
            continue;
          }
          $elementid = $a_section2['elementId'];
          $startdate = $a_section2->version[$count]['start'];
          $enddate = $a_section2->version[$count]['end'];
          drupal_set_message('start date ' . $startdate);
          drupal_set_message('end date ' . $enddate);
          $a_rulenumber2 = $a_title . ", " . (string) $a_section2->version[$count]->number;
          $number = (string) $a_section2->version[$count]->number;
          $body = $a_section2->version[$count]->content;
          drupal_set_message("two: " . $a_title2);
          drupal_set_message("two, rule number: " . $a_rulenumber2);
          if($a_rulenumber2 != NULL) {
            $term = Term::create(array(
              'parent' => array(),
              'name' => $number . " " . strip_tags($a_title2),
              'vid' => $TheVid,
            ));
            $TermResult = $term->save();
            $cid = $term->id();
            $tid1 = $term->id();
          }
          $node = Node::create([
            'type'        => 'article',
            'title'       => html_entity_decode(strip_tags($number), ENT_QUOTES, 'UTF-8') . " " . strip_tags($a_title2),
            'langcode' => 'en',
            'uid' => '1',
            'status' => 1,
            'body' => array(
              'value' => $body,
              'format' => 'full_html',
            ),
            'field_elementid' => $elementid,
            'field_recordid' => $recordid,
            'field_complinet_record_number' => $number,
            //'field_complinet_rule' => $tid,
            'field_complinet_title' => $cid,
          ]);
          drupal_set_message('previous nid :205 ' . $previous_nid1);
          if($a_title2 && strlen($previous_nid1) > 0) {
            $node->book = array(
              "bid" => $previous_nid1,
              "pid" => (int)$previous_nid1,
              "weight" => $secondcounter
            );
          }
          else {
            $node->book = array(
              "bid" => $previous_nid1,
            );
          }
          if($a_title2) {
            $node->set('field_start_date', $startdate);
            $node->set('field_end_date', $enddate);
            $node->set('field_original_content_type', $original_content_type);
            //$node->set('moderation_state', "published");
            $node->revision = TRUE;
            $time = strtotime($startdate);
            $now = strtotime('now');
            if($now < $time) {
              $node->set('moderation_state', 'future_revision');
            }else {
              $node->set('moderation_state', 'published');
            }

            if($startdate != '2030-12-31') {
              $node->save();
              $previous_nid_level2 = $node->id();
              $result = $connection->insert('complinetmigration')
                ->fields([
                  'nid' => $node->id(),
                ])
                ->execute();
            }
          }
          $secondcounter = $secondcounter + 1;
          $thirdcounter = 0;
          // lastly grab the revisions
          for ($x = 0; $x <= $count; $x++) {
            $a_title2b = strip_tags((string) $a_section2->version[$x]->title);
            $a_title2b = html_entity_decode(strip_tags($a_title2b), ENT_QUOTES, 'UTF-8');
            $recordidb = $a_section2->version[$x]['recordId'];
            $startdateb = $a_section2->version[$x]['start'];
            $enddateb = $a_section2->version[$x]['end'];
            $a_rulenumber2b = $a_title . ", " . (string) $a_section2->version[$x]->number;
            $numberb = (string) $a_section2->version[$x]->number;
            $bodyb = $a_section2->version[$x]->content;
            $node->title = html_entity_decode(strip_tags($numberb), ENT_QUOTES, 'UTF-8') . " " . strip_tags($a_title2b);
            $node->field_recordid = $recordidb;
            $node->set('field_start_date', $startdateb);
            $node->set('field_end_date', $enddateb);
            $node->set('field_original_content_type', $original_content_type);
            $node->body = $bodyb;
            $node->field_complinet_record_number = $numberb;
            $node->revision = TRUE;
            $time = strtotime($startdateb);
            $now = strtotime('now');
            if($now < $time) {
              $node->set('moderation_state', 'future_revision');
            }else {
              $node->set('moderation_state', 'published');
            }
            if($startdateb != '2030-12-31') {
              $node->save();
            }
          }
          $nodeoriginal->save;
          foreach ($a_section2->section as $a_section3) {
            $count = count($a_section3->version) - 1;
            $a_title3 = strip_tags((string) $a_section3->version[$count]->title);
            $a_title3 = html_entity_decode(strip_tags($a_title3), ENT_QUOTES, 'UTF-8');
            $recordid = $a_section3->version[$count]['recordId'];
            if (in_array($recordid, $recordids)) {
              drupal_set_message('skipping recordid ' . $recordid);
              continue;
            }
            $elementid = $a_section3['elementId'];
            $startdate = $a_section3->version[$count]['start'];
            $enddate = $a_section3->version[$count]['end'];
            $a_rulenumber3 = $a_title . ", " . (string) $a_section3->version[$count]->number;
            $number = (string) $a_section3->version[$count]->number;
            drupal_set_message("three: " . $a_title3);
            drupal_set_message("three, rule number: " . $a_rulenumber3);

            $body = $a_section3->version[$count]->content;
            //$tid = $this->getTidByName($a_rulenumber3, 'complinet_rules');
            //$cid = $this->getTidByName($a_title3, 'complinet_titles');
            if($a_rulenumber3 != NULL) {
              $term = Term::create(array(
                'parent' => $tid1,
                'name' => $number . " " . strip_tags($a_title3),
                'vid' => $TheVid,
              ));
              $TermResult2 = $term->save();
              $cid = $term->id();
              $tid2 = $term->id();
            }
            $node = Node::create([
              'type'        => 'article',
              'title'       => html_entity_decode(strip_tags($number), ENT_QUOTES, 'UTF-8') . " " . strip_tags($a_title3),
              'langcode' => 'en',
              'uid' => '1',
              'status' => 1,
              'body' => array(
                'value' => $body,
                'format' => 'full_html',
              ),
              'field_elementid' => $elementid,
              'field_recordid' => $recordid,
              'field_complinet_record_number' => $number,
              //'field_complinet_rule' => $tid,
              'field_complinet_title' => $cid,
            ]);
            drupal_set_message('previous nid :253 ' . $previous_nid_level2);
            if($a_title3 && strlen($previous_nid_level2) > 0) {
                $node->book["bid"] = $previous_nid1;
            }
            else {
                $node->book = array(
                  "bid" => $previous_nid1
                );
            }
            if(strlen($a_title3) > 0) {
              $node->set('field_start_date', $startdate);
              $node->set('field_end_date', $enddate);
              $node->set('field_original_content_type', $original_content_type);
              $node->revision = TRUE;
              drupal_set_message("The END DATE is " . $enddate);
              //$node->set('moderation_state', "published");
              $time = strtotime($startdate);
              $now = strtotime('now');
              if($now < $time) {
                $node->set('moderation_state', 'future_revision');
              }else {
                $node->set('moderation_state', 'published');
              }

              if($startdate != '2030-12-31') {
                $node->save();
                $previous_nid_level3 = $node->id();
                drupal_set_message('second previous_nid is ' . $previous_nid_level2 . ' ' . $a_title2);
                \Drupal::database()->update('book')
                  ->fields(array('pid' => $previous_nid_level2, 'weight' => $thirdcounter))
                  ->condition('nid', $previous_nid_level3)
                  ->execute();
                $result = $connection->insert('complinetmigration')
                  ->fields([
                    'nid' => $node->id(),
                  ])
                  ->execute();
              }
            }
            // --begin new section --
            $thirdcounter = $thirdcounter + 1;
            $fourthcounter = 0;
            // grab the revisions
            for ($x = 0; $x <= $count; $x++) {
              $a_title2b = strip_tags((string) $a_section3->version[$x]->title);
              $a_title2b = html_entity_decode(strip_tags($a_title2b), ENT_QUOTES, 'UTF-8');
              $recordidb = $a_section3->version[$x]['recordId'];
              $startdateb = $a_section3->version[$x]['start'];
              $enddateb = $a_section3->version[$x]['end'];
              $a_rulenumber2b = $a_title . ", " . (string) $a_section3->version[$x]->number;
              $numberb = (string) $a_section3->version[$x]->number;
              $bodyb = $a_section3->version[$x]->content;
              $node->title = html_entity_decode(strip_tags($numberb), ENT_QUOTES, 'UTF-8') . " " . strip_tags($a_title2b);
              $node->field_recordid = $recordidb;
              $node->set('field_start_date', $startdateb);
              $node->set('field_end_date', $enddateb);
              $node->set('field_original_content_type', $original_content_type);
              $node->body = $bodyb;
              $node->field_complinet_record_number = $numberb;
              $node->revision = TRUE;
              $time = strtotime($startdateb);
              $now = strtotime('now');
              if($now < $time) {
                $node->set('moderation_state', 'future_revision');
              }else {
                $node->set('moderation_state', 'published');
              }
              if($startdateb != '2030-12-31') {
                $node->save();
              }
            }
            $nodeoriginal->save;
            foreach ($a_section3->section as $a_section4) {
              $count = count($a_section4->version) - 1;
              $a_title4 = strip_tags((string) $a_section4->version[$count]->title);
              $a_title4 = html_entity_decode(strip_tags($a_title4), ENT_QUOTES, 'UTF-8');
              $a_title4 = html_entity_decode(strip_tags($a_title4), ENT_QUOTES, 'UTF-8');
              \Drupal::logger('my_module')->notice('Title at level 4 is ' . $a_title4);
              $recordid = $a_section4->version[$count]['recordId'];
              if (in_array($recordid, $recordids)) {
                drupal_set_message('skipping recordid ' . $recordid);
                continue;
              }
              $elementid = $a_section4['elementId'];
              $startdate = $a_section4->version[$count]['start'];
              $enddate = $a_section4->version[$count]['end'];
              $a_rulenumber4 = $a_title . ", " . (string) $a_section4->version[$count]->number;
              $number = (string) $a_section4->version[$count]->number;
              drupal_set_message("four: " . $a_title4);
              drupal_set_message("four, rule number: " . $a_rulenumber4);

              $body = $a_section4->version[$count]->content;
              //$tid = $this->getTidByName($a_rulenumber4, 'complinet_rules');
              //$cid = $this->getTidByName($a_title4, 'complinet_titles');
              if($a_rulenumber4 != NULL) {
                $term = Term::create(array(
                  'parent' => $tid2,
                  'name' => $number . " " . strip_tags($a_title4),
                  'vid' => $TheVid,
                ));
                $TermResult3 = $term->save();
                $cid = $term->id();
                $tid3 = $term->id();
              }
              $node = Node::create([
                'type'        => 'article',
                'title'       => html_entity_decode(strip_tags($number), ENT_QUOTES, 'UTF-8') . " " . strip_tags($a_title4),
                'langcode' => 'en',
                'uid' => '1',
                'status' => 1,
                'body' => array(
                  'value' => $body,
                  'format' => 'full_html',
                ),
                //'field_complinet_rule' => $tid,
                'field_elementid' => $elementid,
                'field_recordid' => $recordid,
                'field_complinet_record_number' => $number,
                'field_complinet_title' => $cid,
              ]);
              drupal_set_message('fourth previous_nid is ' . $previous_nid_level3);
              if($a_title4 && strlen($previous_nid) > 0) {
                  $node->book["bid"] = $previous_nid1;
              }
              else {
                  $node->book = array(
                    "bid" => $previous_nid1
                  );
              }
              if($a_title4) {
                $node->set('field_start_date', $startdate);
                $node->set('field_end_date', $enddate);
                $node->set('field_original_content_type', $original_content_type);
                $node->revision = TRUE;
                $time = strtotime($startdate);
                $now = strtotime('now');
                if($now < $time) {
                  $node->set('moderation_state', 'future_revision');
                }else {
                  $node->set('moderation_state', 'published');
                }

                if($startdate != '2030-12-31') {
                  $node->save();
                  $previous_nid_level4 = $node->id();
                    drupal_set_message('third previous_nid is ' . $previous_nid_level3 . ' ' . $a_title3);
                  \Drupal::database()->update('book')
                    ->fields(array('pid' => $previous_nid_level3, 'weight' => $fourthcounter))
                    ->condition('nid', $previous_nid_level4)
                    ->execute();
                  $result = $connection->insert('complinetmigration')
                    ->fields([
                      'nid' => $node->id(),
                    ])
                    ->execute();
                }
                }
                $fourthcounter = $fourthcounter + 1;
                // grab the revisions
                for ($x = 0; $x <= $count; $x++) {
                  $a_title2b = strip_tags((string) $a_section4->version[$x]->title);
                  $a_title2b = html_entity_decode(strip_tags($a_title2b), ENT_QUOTES, 'UTF-8');
                  $recordidb = $a_section4->version[$x]['recordId'];
                  $startdateb = $a_section4->version[$x]['start'];
                  $enddateb = $a_section4->version[$x]['end'];
                  $a_rulenumber2b = $a_title . ", " . (string) $a_section4->version[$x]->number;
                  $numberb = (string) $a_section4->version[$x]->number;
                  $bodyb = $a_section4->version[$x]->content;
                  $node->title = html_entity_decode(strip_tags($numberb), ENT_QUOTES, 'UTF-8') . " " . strip_tags($a_title2b);
                  $node->field_recordid = $recordidb;
                  $node->set('field_start_date', $startdateb);
                  $node->set('field_end_date', $enddateb);
                  $node->set('field_original_content_type', $original_content_type);
                  $node->body = $bodyb;
                  $node->field_complinet_record_number = $numberb;
                  $node->revision = TRUE;
                  $time = strtotime($startdateb);
                  $now = strtotime('now');
                  if($now < $time) {
                    $node->set('moderation_state', 'future_revision');
                  }else {
                    $node->set('moderation_state', 'published');
                  }
                  if($startdateb != '2030-12-31') {
                    $node->save();
                  }
                }
                $nodeoriginal->save;
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
