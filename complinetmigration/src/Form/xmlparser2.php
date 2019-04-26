<?php

/**
 * @file
 * Contains \Drupal\complinetmigration\Form\xmlparser2.
 */

namespace Drupal\complinetmigration\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class xmlparser2 extends FormBase {

  /**
   *  {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlparser2_form';
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
       this will create a hierarchical taxonomy of rules and titles
      for all but the Notices in the complinet xml.  It will then create
      nodes out of the xml &nbsp;&nbsp;"
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
    $connection = \Drupal::database();
    if($form_state->getValue('submissiontype') == 1) {
      // rollback data
      $connection = \Drupal::database();
      $query = $connection->query("SELECT nid FROM {complinetmigration}");
      $result = $query->fetchAll();
      foreach($result as $row) {
        $nid = $row->nid;
        \Drupal::logger('complinetmigration')->notice('Rolled back nid ' . $nid);
        //drupal_set_message($nid);
        $node = node_load($nid);
        if($node) {
          $node->delete();
        }
      }
      $tids = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'complinet_titles')
        ->execute();
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);

      $tids = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'complinet_rules2')
        ->execute();
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);

      $tids = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'complinet_rule_numbers')
        ->execute();
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);

      $tids = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'complinet_titles')
        ->execute();
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);

      $vocabularies = [
        'complinet_rules2',
        'complinet_rule_numbers',
        'complinet_titles'
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
    drupal_set_message('thanks for submitting the form!');
    if (file_exists(drupal_get_path('module', 'complinetmigration') . '/FINRAManual02-07-2019.xml')) {
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
      $vid = "complinet_rules2";
      $name = "Complinet Rules2";
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

      $vid = "complinet_rule_numbers";
      $name = "Complinet Rule Numbers";
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
      $xml = simplexml_load_file(drupal_get_path('module', 'complinetmigration') . '/FINRAManual08-08-18.xml');
      $xml2count = count((string) $xml->section->version) - 1;
      $xml2 = (string) $xml->section->version[$xml2count]->title;
      drupal_set_message('<pre>' . print_r($xml2, TRUE) . '</pre>');
      //$categories_vocabulary = 'complinet_titles'; // Vocabulary machine name
      //$categories_vocabulary2 = 'complinet_rules'; // Vocabulary machine name
      foreach ($xml->section->section as $a_section) {
        $count = count($a_section->version) - 1;
        $a_title = (string) $a_section->version[$count]->title;
        $number = (string) $a_section->version[$count]->number;
        $a_rulenumber = strip_tags($a_title . ' - ' . $number);
        $recordid = $a_section->version[$count]['recordId'];
        $elementid = $a_section['elementId'];
        $body = $a_section->version[$count]->content;
        if($a_title == "Notices") {
          continue;
        }
        if($a_title == 'NOTICES') {
          continue;
        }
        if(strlen($a_title) > 0) {
          $term = Term::create(array(
            'parent' => array(),
            'name' => strip_tags($a_title),
            'vid' => 'complinet_titles',
          ));
          $TermResult = $term->save();
          $ComplinetTitleTid1 = $term->id();

          if(strlen($a_title) > 0) {
            $TermNumber = Term::create(array(
              'parent' => array(),
              'name' => $a_rulenumber,
              'vid' => 'complinet_rules2',
            ));
            $TermResultNumber = $TermNumber->save();
            $ComplinetRuleTid1 = $TermNumber->id();
          }
          if(strlen($a_title) > 0) {
            $TermNumber = Term::create(array(
              'parent' => array(),
              'name' => $a_rulenumber,
              'vid' => 'complinet_rule_numbers',
            ));
            $TermResultNumber = $TermNumber->save();
            $ComplinetRuleNumberTid1 = $TermNumber->id();
          }
        }
        if(strlen($body) > 0) {
          $node = Node::create([
            'type'        => 'article',
            'title'       => $number . " " . strip_tags($a_title),
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
            'field_complinet_title' => $ComplinetTitleTid1,
          ]);
          $node->book = array(
            "bid" => 'new',
            "plid" => 0
          );
          $node->setPublished(TRUE);
          $node->set('moderation_state', "published");
          $node->save();
          $previous_nid = $node->id();
          $result = $connection->insert('complinetmigration')
            ->fields([
              'nid' => $node->id(),
            ])
            ->execute();
        }
        drupal_set_message($a_title);
        drupal_set_message($number);
        $thecount = 0;
        foreach ($a_section->section as $a_section2) {
          $thecount = $thecount + 1;
          $count = count($a_section2->version) - 1;
          $a_title2 = (string) $a_section2->version[$count]->title;
          $recordid = $a_section2->version[$count]['recordId'];
          $elementid = $a_section2['elementId'];
          $a_rulenumber2 = $a_title . " - " . (string) $a_section2->version[$count]->number;
          $number = (string) $a_section2->version[$count]->number;
          $body = $a_section2->version[$count]->content;
          if(strlen($a_title2) > 0 && strlen((String)$ComplinetTitleTid1) > 0) {
            $term2 = Term::create(array(
              'parent' => $ComplinetTitleTid1,
              'name' => strip_tags($number . ' ' . $a_title2),
              'vid' => 'complinet_titles',
            ));
            $TermResult2 = $term2->save();
            $ComplinetTitleTid2 = $term2->id();
          }
          if(strlen($number) > 0 && strlen((String)$ComplinetRuleTid1) > 0) {
            $TermNumber2 = Term::create(array(
              'parent' => $ComplinetRuleTid1,
              'name' => $a_rulenumber2,
              'vid' => 'complinet_rules2',
            ));
            $TermResultNumber2 = $TermNumber2->save();
            $ComplinetRuleTid2 = $TermNumber2->id();
          }
          drupal_set_message('GOT INSIDE HERE1, the number is ' . $number . ' ' . $thecount);
          drupal_set_message('tid parent is ' . $ComplinetRuleNumberTid1 . ' - ' . $thecount);
          if(strlen($number) > 0 && strlen((String)$ComplinetRuleNumberTid1) > 0) {
            drupal_set_message("in");
            $TermNumber = Term::create(array(
              'parent' => $ComplinetRuleNumberTid1,
              'name' => $number,
              'vid' => 'complinet_rule_numbers',
            ));
            $TermResultNumber = $TermNumber->save();
            $ComplinetRuleNumberTid2 = $TermNumber->id();
          }
          $node = Node::create([
            'type'        => 'article',
            'title'       => $number . " " . strip_tags($a_title2),
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
            'field_complinet_title' => $ComplinetTitleTid2,
          ]);
          if($a_title2) {
            $node->book = array(
              "bid" => $previous_nid,
              "plid" => 0
            );
            $node->setPublished(TRUE);
            $node->set('moderation_state', "published");
            $node->save();
            $result = $connection->insert('complinetmigration')
              ->fields([
                'nid' => $node->id(),
              ])
              ->execute();
          }
          drupal_set_message("TID is " . $TermNumber->id());
          drupal_set_message("two: " . $a_title2);
          drupal_set_message("two, rule number: " . $number);
          $thecount2 = 0;
          foreach ($a_section2->section as $a_section3) {
            break;
            $thecount2 = $thecount2 + 1;
            $count = count($a_section3->version) - 1;
            $a_title3 = (string) $a_section3->version[$count]->title;
            $recordid = $a_section3->version[$count]['recordId'];
            $elementid = $a_section3['elementId'];
            $a_rulenumber3 = $a_title . " - " . (string) $a_section3->version[$count]->number;
            $number = (string) $a_section3->version[$count]->number;
            $body = $a_section3->version[$count]->content;
            if(strlen($a_title3) > 0 && strlen((String)$ComplinetTitleTid2) > 0) {
              $term3 = Term::create(array(
                'parent' => $ComplinetTitleTid2,
                'name' => strip_tags($number . ' ' . $a_title3),
                'vid' => 'complinet_titles',
              ));
              $TermResult3 = $term3->save();
              $ComplinetTitleTid3 = $term3->id();
            }
            if(strlen($number) > 0 && strlen((String)$ComplinetRuleTid2) > 0) {
              $TermNumber3 = Term::create(array(
                'parent' => $ComplinetRuleTid2,
                'name' => $a_rulenumber3,
                'vid' => 'complinet_rules2',
              ));
              $TermResultNumber3 = $TermNumber3->save();
              $ComplinetRuleTid3 = $TermNumber3->id();
            }
            drupal_set_message('GOT INSIDE HERE2, the number is ' . $number . ' ' . $thecount2);
            drupal_set_message('tid parent is ' . $ComplinetRuleNumberTid2 . ' - ' . $thecount2);
            if(strlen($number) > 0 && strlen((String)$ComplinetRuleNumberTid2) > 0) {
              drupal_set_message("in");
              $TermNumber = Term::create(array(
                'parent' => $ComplinetRuleNumberTid2,
                'name' => $number,
                'vid' => 'complinet_rule_numbers',
              ));
              $TermResultNumber = $TermNumber->save();
              $ComplinetRuleNumberTid3 = $TermNumber->id();
            }
            if(strlen($body) > 0) {
              $node = Node::create([
                'type'        => 'article',
                'title'       => $number . " " . strip_tags($a_title3),
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
                'field_complinet_title' => $ComplinetTitleTid3,
              ]);

              $node->save();
              $result = $connection->insert('complinetmigration')
                ->fields([
                  'nid' => $node->id(),
                ])
                ->execute();
            }
            drupal_set_message("three: " . $a_title3);
            drupal_set_message("three, rule number: " . $number3);
            // --begin new section --
            $nothercounter = 0;
            foreach ($a_section3->section as $a_section4) {
              $nothercounter = $nothercounter + 1;
              $count = count($a_section4->version) - 1;
              $recordid = $a_section4->version[$count]['recordId'];
              $elementid = $a_section4['elementId'];
              $a_title4 = (string) $a_section4->version[$count]->title;
              $a_rulenumber4 = $a_title . " - " . (string) $a_section4->version[$count]->number;
              $body = $a_section3->version[$count]->content;
              $number = (string) $a_section4->version[$count]->number;
              drupal_set_message("four: " . $a_title4);
              drupal_set_message("four, rule number: " . $number4);
              if(strlen($a_title4) > 0 && strlen((String)$ComplinetTitleTid3) > 0) {
                $term4 = Term::create(array(
                  'parent' => $ComplinetTitleTid3,
                  'name' => strip_tags($number . ' ' . $a_title4),
                  'vid' => 'complinet_titles',
                ));
                $TermResult4 = $term4->save();
                $ComplinetTitleTid4 = $term4->id();
              }
              if(strlen($number) > 0 && strlen((String)$ComplinetRuleTid3) > 0) {
                $TermNumber4 = Term::create(array(
                  'parent' => $ComplinetRuleTid3,
                  'name' => $a_rulenumber4,
                  'vid' => 'complinet_rules2',
                ));
                $TermResultNumber4 = $TermNumber4->save();
                $ComplinetRuleTid4 = $TermNumber4->id();
              }
              drupal_set_message('GOT INSIDE HERE3' . $nothercounter);
              drupal_set_message('tid parent is ' . $ComplinetRuleNumberTid3 . ' - ' . $nothercounter);
              if(strlen($number) > 0 && strlen((String)$ComplinetRuleNumberTid3) > 0) {
                drupal_set_message("in");
                $TermNumber = Term::create(array(
                  'parent' => $ComplinetRuleNumberTid3,
                  'name' => $number,
                  'vid' => 'complinet_rule_numbers',
                ));
                $TermResultNumber = $TermNumber->save();
                $ComplinetRuleNumberTid4 = $TermNumber->id();
              }
              if(strlen($body) > 0) {
                $node = Node::create([
                  'type'        => 'article',
                  'title'       => $number . " " . strip_tags($a_title3),
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
                  'field_complinet_title' => $ComplinetTitleTid4,
                ]);

                $node->save();
                $result = $connection->insert('complinetmigration')
                  ->fields([
                    'nid' => $node->id(),
                  ])
                  ->execute();
              }
              // ---- end section ----
              // --begin new section --
              foreach ($a_section4->section as $a_section5) {
                break;
                $count = count($a_section5->version) - 1;
                $a_title5 = (string) $a_section5->version[$count]->title;
                $a_rulenumber5 = $a_title . " - " . (string) $a_section5->version[$count]->number;
                $number = (string) $a_section5->version[$count]->number;
                drupal_set_message("five: " . $a_title5);
                drupal_set_message("five, rule number: " . $a_rulenumber5);
                if(strlen($a_title5) > 0 && strlen((String)$ComplinetTitleTid4) > 0) {
                  $term5 = Term::create(array(
                    'parent' => $ComplinetTitleTid4,
                    'name' => strip_tags($number . ' ' . $a_title5),
                    'vid' => 'complinet_titles',
                  ));
                  $TermResult5 = $term5->save();
                  $ComplinetTitleTid5 = $term5->id();
                }

                if(strlen($number) > 0 && strlen((String)$ComplinetRuleTid4) > 0) {
                  $TermNumber5 = Term::create(array(
                    'parent' => $ComplinetRuleTid4,
                    'name' => $a_rulenumber5,
                    'vid' => 'complinet_rules2',
                  ));
                  $TermResultNumber5 = $TermNumber5->save();
                  $ComplinetRuleTid5 = $TermNumber5->id();
                }

                if(strlen($number) > 0 && strlen((String)$ComplinetRuleNumberTid4) > 0) {
                  $TermNumber = Term::create(array(
                    'parent' => $ComplinetRuleNumberTid4,
                    'name' => $number,
                    'vid' => 'complinet_rule_numbers',
                  ));
                  $TermResultNumber = $TermNumber->save();
                  $ComplinetRuleNumberTid5 = $TermNumber->id();
                }

              // ---- end section ----
              // --begin new section --
                foreach ($a_section5->section as $a_section6) {
                  break;
                  $count = count($a_section6->version) - 1;
                  $a_title6 = (string) $a_section6->version[$count]->title;
                  $a_rulenumber6 = $a_title . " - " . (string) $a_section6->version[$count]->number;
                  $number = (string) $a_section6->version[$count]->number;
                  drupal_set_message("six: " . $a_title6);
                  drupal_set_message("six, rule number: " . $a_rulenumber6);
                  if(strlen($a_title6) > 0 && strlen((String)$ComplinetTitleTid5) > 0) {
                    $term6 = Term::create(array(
                      'parent' => $ComplinetTitleTid5,
                      'name' => strip_tags($number . ' ' . $a_title6),
                      'vid' => 'complinet_titles',
                    ));
                    $TermResult6 = $term6->save();
                    $ComplinetTitleTid6 = $term6->id();
                  }

                  if(strlen($number) > 0 && strlen((String)$ComplinetRuleTid5) > 0) {
                    $term6 = Term::create(array(
                      'parent' => $ComplinetRuleTid5,
                      'name' => $a_rulenumber6,
                      'vid' => 'complinet_rules2',
                    ));
                    $TermNumber6 = $term6->save();
                    $ComplinetRuleTid6 = $term6->id();
                  }

                  if(strlen($number) > 0) {
                    $TermNumber = Term::create(array(
                      'parent' => $ComplinetRuleNumberTid5,
                      'name' => $number,
                      'vid' => 'complinet_rule_numbers',
                    ));
                    $TermNumberSave = $TermNumber->save();
                    $ComplinetRuleNumberTid6 = $TermNumber->id();
                  }


                // ---- end section ----
                // --begin new section --
                  foreach ($a_section6->section as $a_section7) {
                    break;
                    $count = count($a_section7->version) - 1;
                    $a_title7 = (string) $a_section7->version[$count]->title;
                    $a_rulenumber7 = $a_title . " - " . (string) $a_section7->version[$count]->number;
                    $number = (string) $a_section7->version[$count]->number;
                    drupal_set_message("seven: " . $a_title7);
                    drupal_set_message("seven, rule number: " . $a_rulenumber7);
                    if(strlen($a_title7) > 0 && strlen((String)$ComplinetTitleTid6) > 0) {
                      $term7 = Term::create(array(
                        'parent' => $ComplinetTitlesTid6,
                        'name' => strip_tags($a_title7),
                        'vid' => 'complinet_titles',
                      ));
                      $TermResult7 = $term7->save();
                      $ComplinetTitlesTid7 = $term7->id();
                    }

                    if($ComplinetRulesTid6 != NULL && $a_rulenumber7 != NULL && strlen((string) $a_section7->version[$count]->number) > 0) {
                      $TermNumber7 = Term::create(array(
                        'parent' => $ComplinetRulesTid6,
                        'name' => $a_rulenumber7,
                        'vid' => 'complinet_rules2',
                      ));
                      $TermResultNumber7 = $TermNumber7->save();
                      $ComplinetRulesTid7 = $TermNumber7->id();
                    }

                    if($number != NULL) {
                      $TermNumb = Term::create(array(
                        'parent' => $ComplinetRuleNumberTid6,
                        'name' => $number,
                        'vid' => 'complinet_rule_numbers',
                      ));
                      $TermNumber = $TermNumb->save();
                      $ComplinetRuleNumberTid7 = $TermNumb->id();
                    }

                  // ---- end section ----
                  // --begin new section --
                    foreach ($a_section7->section as $a_section8) {
                      break;
                      $count = count($a_section8->version) - 1;
                      $a_title8 = (string) $a_section8->version[$count]->title;
                      $a_rulenumber8 = $a_title . " - " . (string) $a_section8->version[$count]->number;
                      $number = (string) $a_section8->version[$count]->number;
                      drupal_set_message("eight: " . $a_title8);
                      drupal_set_message("eight, rule number: " . $a_rulenumber8);
                      if(strlen($a_title8) > 0 && strlen((String)$ComplinetTitleTid7) > 0) {
                        $term8 = Term::create(array(
                          'parent' => $ComplinetTitlesTid7,
                          'name' => strip_tags($a_title8),
                          'vid' => 'complinet_titles',
                        ));
                        $TermResult8 = $term8->save();
                        $ComplinetTitlesTid8 = $term8->id();
                      }

                      if($ComplinetRulesTid7 != NULL && $a_rulenumber8 != NULL && strlen((string) $a_section8->version[$count]->number) > 0) {
                        $TermNumber8 = Term::create(array(
                          'parent' => $tnid7,
                          'name' => $ComplinetRulesTid7,
                          'vid' => 'complinet_rules2',
                        ));
                        $TermResultNumber8 = $TermNumber8->save();
                        $ComplinetRulesTid8 = $TermNumber8->id();
                      }
                      if($number != NULL) {
                        $TermNumb = Term::create(array(
                          'parent' => $ComplinetRuleNumberTid7,
                          'name' => $number,
                          'vid' => 'complinet_rule_numbers',
                        ));
                        $TermNumber = $TermNumb->save();
                        $ComplinetRuleNumberTid8 = $TermNumb->id();
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
