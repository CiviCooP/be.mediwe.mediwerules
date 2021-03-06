<?php

require_once 'mediwerules.civix.php';
use CRM_Mediwerules_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function mediwerules_civicrm_config(&$config) {
  _mediwerules_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function mediwerules_civicrm_xmlMenu(&$files) {
  _mediwerules_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mediwerules_civicrm_install() {
  if (!class_exists('CRM_Basis_Utils')) {
    try {
      require_once 'CRM/Basis/Utils.php';
    }
    catch (Exception $ex) {
      throw new Exception(ts('The be.mediwe.basis extension is required but not installed on this environment. Please install be.mediwe.basis first and then try installing be.mediwe.mediwerules again'));
    }
  }
  if (!CRM_Mediwerules_Utils::civiRulesInstalled()) {
    throw new Exception(ts('The CiviRules extension is required but not installed on this environment. Please install CiviRules first and then try enabling be.mediwe.mediwerules again'));
  }
  if (!CRM_Basis_Utils::mediweBasisInstalled()) {
    throw new Exception(ts('The be.mediwe.basis extension is required but not installed on this environment. Please install be.mediwe.basis first and then try installing be.mediwe.mediwerules again'));
  }
  // add action arts toewijzen
  CRM_Mediwerules_Utils::addAction([
    'name' => "mediwe_arts_toewijzen",
    'label' => "Automatisch toewijzen controlearts",
    'class_name' => "CRM_Mediwerules_CivirulesActions_ArtsToewijzen",
    'is_active' => 1,
  ]);

  _mediwerules_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function mediwerules_civicrm_postInstall() {
  _mediwerules_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function mediwerules_civicrm_uninstall() {
  _mediwerules_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function mediwerules_civicrm_enable() {
  if (!class_exists('CRM_Basis_Utils')) {
    try {
      require_once 'CRM/Basis/Utils.php';
    }
    catch (Exception $ex) {
      throw new Exception(ts('The be.mediwe.basis extension is required but not installed on this environment. Please install be.mediwe.basis first and then try installing be.mediwe.mediwerules again'));
    }
  }
  if (!CRM_Mediwerules_Utils::civiRulesInstalled()) {
    throw new Exception(ts('The CiviRules extension is required but not installed on this environment. Please install CiviRules first and then try enabling be.mediwe.mediwerules again'));
  }
  if (!CRM_Basis_Utils::mediweBasisInstalled()) {
    throw new Exception(ts('The be.mediwe.basis extension is required but not installed on this environment. Please install be.mediwe.basis first and then try installing be.mediwe.mediwerules again'));
  }
  _mediwerules_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function mediwerules_civicrm_disable() {
  _mediwerules_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function mediwerules_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mediwerules_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function mediwerules_civicrm_managed(&$entities) {
  _mediwerules_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function mediwerules_civicrm_caseTypes(&$caseTypes) {
  _mediwerules_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function mediwerules_civicrm_angularModules(&$angularModules) {
  _mediwerules_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function mediwerules_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _mediwerules_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function mediwerules_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function mediwerules_civicrm_navigationMenu(&$menu) {
  _mediwerules_civix_insert_navigation_menu($menu, NULL, array(
    'label' => E::ts('The Page'),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _mediwerules_civix_navigationMenu($menu);
} // */
