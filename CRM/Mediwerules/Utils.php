<?php
/**
 * Class with extension specific util functions
 *
 * @author  Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date    16 April 2018
 * @license AGPL-3.0
 */

class CRM_Mediwerules_Utils {

  /**
   * Method to check if CiviRules extension has been installed
   * @return bool
   */
  public static function civiRulesInstalled() {
    try {
      $extStatus = civicrm_api3('Extension', 'getvalue', [
        'return' => "status",
        'full_name' => "org.civicoop.civirules",
      ]);
      if ($extStatus == 'installed') {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }
}
