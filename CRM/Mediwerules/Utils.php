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

  /**
   * Method to check if basis extension has been installed
   * @return bool
   */
  public static function mediweBasisInstalled() {
    try {
      $extStatus = civicrm_api3('Extension', 'getvalue', [
        'return' => "status",
        'full_name' => "be.mediwe.basis",
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

  /**
   * Method to add a civirule action
   * @param $data
   */
  public static function addAction ($data) {
    try {
      civicrm_api3('CiviRuleAction', 'create', $data);
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message(ts('Could not create CiviRule Action, message from API CiviRuleAction create: ' . $ex->getMessage()));
    }
  }

}
