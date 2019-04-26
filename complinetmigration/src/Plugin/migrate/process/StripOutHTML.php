<?php

/**
 * @file
 * Contains \Drupal\complinetmigration\Plugin\migrate\process\StripOutHTML.
 */

namespace Drupal\complinetmigration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "stripouthtml"
 * )
 */
 class StripOutHTML extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // $value: cut off .<nnn>Z and replace by Z
    if( is_null($value) )
    {
      return $value;
    }

    // $old = $value;

    $value= strip_tags($value);
    // drush_print_r( $old . ' -> ' . $value );

    return $value;
  }

}
