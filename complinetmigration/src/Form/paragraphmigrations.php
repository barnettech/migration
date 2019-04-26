<?php

/**
 * @file
 * Contains \Drupal\complinetmigration\Form\paragraphmigrations.
 */

namespace Drupal\complinetmigration\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class paragraphmigrations extends FormBase {

  /**
   *  {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraphmigrations_form';
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
      "#markup" => "Clicking here will parse through field collections in drupal 7 and will parse them
      into drupal 8 paragraphs,"
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
      '#value' => t('Process Complinet XML Into Articles and taxnomize them into rules, titles, etc'),
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

     /**
      * {@inheritdoc}
      */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do something useful.
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
      drupal_set_message("Migration has been successfully rolled back");
    }
    else {
      // process data
      drupal_set_message('thanks for submitting the form!');
      \Drupal\Core\Database\Database::setActiveConnection('d7finra');
      /*$connection2 = \Drupal\Core\Database\Database::getConnection();*/
      // Get the connection
      $db = \Drupal\Core\Database\Database::getConnection();
      //$query = $db->select('field_data_field_link_list_url', 'fd');
      $query = $db->select('field_data_field_link_list_url', 'fd')
  ->condition('fd.entity_id', 211, '=')
  ->fields('fd', ['bundle'])
  ->range(0, 50);
      //$query->condition('fd.entity_id', 211, '=');
      /*$query = $connection2->query("SELECT * FROM {field_data_field_link_list_url}
        where etid = '211'");*/
      $result = $query->execute();
      foreach($result as $row) {
        drupal_set_message('<pre>' . print_r($row, TRUE) . '</pre>');
      }
      \Drupal\Core\Database\Database::setActiveConnection();

           # code...
         }
       }

}
