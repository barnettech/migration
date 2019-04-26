name: Complinet Migration XMLparser module
description: Creates a page with a form to process xml
  from the complinet site into nodes in Drupal 8.

/* You will need to create a private file directory as you see below, put the lines below in your settings.php file, then make sure the file FINRAManual08-08-18.xml is in the my_files directory, note I renamed the xml file to have no spaces, so make sure you rename your xml file to match exactly, with NO spaces FINRAManual08-08-18.xml */
/* also note installing the config devel module is really very useful so if you change the yml migrate file it will pick up the changes every time your Drupal page reloads, so you don't have to reinstall the custom migrate module every single time */
/**
 * Private file path:
 *
 * A local file system path where private files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * Note: Caches need to be cleared when this value is changed to make the
 * private:// stream wrapper available to the system.
 *
 * See https://www.drupal.org/documentation/modules/file for more information
 * about securing private files.
 */
$settings['file_private_path'] = '/Users/k28002/Sites/devdesktop/drupal8test/sites/default/files/my_files';

/* ignore what is in the src/Form directory for now, we were playing with a custom script to import, which is not the way we're going seems like */
