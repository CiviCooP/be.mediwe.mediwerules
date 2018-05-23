<?php
/**
 * Class voor de CiviRules action Controlearts toewijzen:
 * als eerste zoeken we alle beschikbare controle artsen waarbij de postcode van het huisbezoek adres in het werkgebied van de controle arts voorkomt,
 * gesorteerd op de afstand tot het huisbezoekadres
 *
 * De volgende lijst zijn redenen om een controle arts NIET op te nemen:
 * - de controle arts moet vooraf gebeld worden
 * - de controle arts staat niet op automatisch toewijzen (custom veld op controle arts)
 * - de controle arts is op vakantie op de controle datum
 * - de controle arts heeft vandaag als een niet-werk dag opgegeven (custom veld op controle-arts)
 * - de controle arts heeft al zijn of haar maximum opdrachten voor vandaag (custom veld op controle arts)
 * - de controle-arts is uitgesloten voor de klant of de klant medewerker (custom veld op klant en klant medewerker)
 * - de controle-arts vindt het genoeg voor vandaag (kenmerk "Genoeg voor vandaag")
 * - het tijdstip van toewijzing (systeem tijd) is voor de start tijd van de controle-arts of na de eind tijd van de controle-arts (custom veld op controle-arts)
 *
 * De overgebleven controle artsen (mits meer dan 1) moeten gesorteerd worden:
 * - we hebben een voorkeur voor artsen die de app gebruiken mits het verschil in resultaatpercentage niet groter is dan het percentage in de instellingen (moet toegevoegd worden). Dus bijvoorbeeld als de controle arts met de app een resultaatpercentage heeft dat 25% lager is dan de controle arts zonder de app dan geven we toch de voorkeur aan de arts zonder de app)
 * - er komt een stoplicht per controle arts (kenmerk). Bij een resultaat afwijking kleiner dan het in de instellingen op te geven afwijkingspercentage geef ik de voorkeur aan de arts met een groen stoplicht, of juist niet aan degene met het rode stoplicht
 *
 * Vanuit de nu verkregen sortering wordt de eerste controle arts gekozen.
 *
 * @author  Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date    16 April 2018
 * @license AGPL-3.0
 */
class CRM_Mediwerules_CivirulesActions_ArtsToewijzen extends CRM_Civirules_Action {

  private $_activityData = [];
  private $_toegewezenArtsId = NULL;
  private $_bezoekAdres = NULL;
  private $_bezoekPostcode = NULL;
  private $_bezoekGemeente = NULL;
  private $_controleArtsen = [];
  private $_medewerkerId = NULL;

  /**
   * Verplichte methode om aan te geven of er een form aan de action zit
   *
   * @param int $ruleActionId
   * @return bool|string|void
   */
  public function getExtraDataInputUrl($ruleActionId) {
    return FALSE;
  }

  /**
   * Method om de actie uit te voeren
   *
   * @param CRM_Civirules_TriggerData_TriggerData $triggerData
   */
  public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData) {
    $this->_activityData = $triggerData->getEntityData('activity');
    $this->getMedewerkerFromActivity();
    if ($this->getAdresUitHuisbezoek() != FALSE && $this->checkHeeftControleArts() == FALSE) {
      // vind alle relevante controleartsen
      $this->findControleArts();
    }
  }

  /**
   * Method om te controleren of de activiteit huisbezoek al toegewezen is aan een controle arts
   *
   * @return bool
   */
  private function checkHeeftControleArts() {
    if (isset($this->_activityData['activity_id']) && !empty($this->_activityData['activity_id'])) {
      $query = "SELECT COUNT(*) FROM civicrm_activity_contact WHERE activity_id = %1 AND record_type_id = %2";
      $count = CRM_Core_DAO::singleValueQuery($query, [
        1 => [$this->_activityData['activity_id'], 'Integer'],
        2 => [CRM_Basis_Config::singleton()->getAssigneeRecordTypeId(), 'Integer'],
      ]);
      if ($count > 0) {
        return TRUE;
      }
    } else {
      Civi::log()->error(ts('Geen activity_id in property _activityData, toewijzen arts mislukt in ' . __METHOD__));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method om bezoekadres van huisbezoek op te halen
   *
   * @return bool
   */
  private function getAdresUitHuisbezoek() {
    if (empty($this->_activityData['activity_id'])) {
      Civi::log()->error(ts('Toewijzen controlearts mislukt - geen activity_id in property _activityData in ' . __METHOD__));
      return FALSE;
    }
    // haal bezoek adres op
    $query = "SELECT * FROM " . CRM_Basis_Config::singleton()->getMedischeControleHuisbezoekCustomGroup('table_name')
      . " WHERE entity_id = %1";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$this->_activityData['activity_id'], 'Integer']]);
    if ($dao->fetch()) {
      $columnProperties = [
        '_bezoekAdres' => CRM_Basis_Config::singleton()->getHuisbezoekAdresCustomField('column_name'),
        '_bezoekPostcode' => CRM_Basis_Config::singleton()->getHuisbezoekPostcodeCustomField('column_name'),
        '_bezoekGemeente' => CRM_Basis_Config::singleton()->getHuisbezoekGemeenteCustomField('column_name'),
      ];
      foreach ($columnProperties as $property => $columnName) {
        if (isset($dao->$columnName)) {
          $this->$property = $dao->$columnName;
        } else {
          $this->$property = '';
        }
      }
      return TRUE;
    }
    return FALSE;
  }


  /**
   * Method om medewerker te vinden gebaseerd op de huisbezoek case activiteit (target)
   *
   * @return array|bool
   */
  private function getMedewerkerFromActivity() {
    if (isset($this->_activityData['activity_id'])) {
      try {
        $this->_medewerkerId = civicrm_api3('ActivityContact', 'getvalue', [
          'activity_id' => $this->_activityData['activity_id'],
          'record_type_id' => CRM_Basis_Config::singleton()->getTargetRecordTypeId(),
          'return' => 'contact_id',
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method om de beschikbare relevante controleartsen te vinden
   */
  private function findControleArts() {
    $this->_controleArtsen = [];
    try {
      $findParams = [
        'mw_postcode' => $this->_bezoekPostcode,
        'mw_gemeente' => $this->_bezoekGemeente,
        'mcc_automatisch_toewijzen' => 1,
        'options' => ['limit' => 0],
        ];
      $voorafBelMoment = CRM_Basis_Config::singleton()->getVoorafBelMomentValue();
      if (!empty($voorafBelMoment)) {
        $findParams['mcc_arts_bel_moment'] = ['!=' => $voorafBelMoment];
      }
      $this->_controleArtsen = civicrm_api3('ControleArts', 'get', $findParams)['values'];
      // als ik artsen heb
      if (!empty($this->_controleArtsen)) {
        // check of de eerste arts binnen de afstand in de instellingen zit. Zo niet, dan niet automatisch toewijzen
        if ($this->checkBinnenAfstandSetting() == TRUE) {
          // nu de uit te sluiten artsen verwijderen
          foreach ($this->_controleArtsen as $key => $controleArts) {
            if ($this->checkValideArts($controleArts) == FALSE) {
              unset($this->_controleArtsen[$key]);
            }
          }
          // selecteren arts
          $this->_toegewezenArtsId = $this->selectControleArts();
          // wijs huisbezoek toe aan eerste controlearts
          $this->assignControleArts();
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method om te controleren of er resultaten zijn binnen de ingestelde maximale afstand
   * (hoeft alleen de eerste te controleren)
   *
   * @return bool
   */
  private function checkBinnenAfstandSetting() {
    if (!empty($this->_controleArtsen)) {
      $maxAfstand = Civi::settings()->get('mediwe_max_afstand_huisbezoek');
      if (!empty($this->_controleArtsen[0]['street_address']) && !empty($this->_controleArtsen[0]['postal_code']) && !empty($this->_controleArtsen[0]['city'])) {
        try {
          $google = civicrm_api3('Google', 'afstand', [
            'adres' => $this->_bezoekAdres,
            'postcode' => $this->_bezoekPostcode,
            'gemeente' => $this->_bezoekGemeente,
            'adres_arts' => $this->_controleArtsen[0]['street_address'],
            'postcode_arts' => $this->_controleArtsen[0]['postal_code'],
            'gemeente_arts' => $this->_controleArtsen[0]['city'],
          ]);
          if (isset($google['values']['km'])) {
            if ($google['values']['km'] > $maxAfstand) {
              Civi::log()->info('Afstand te groot');
              return FALSE;
            }
          }
        } catch (CiviCRM_API3_Exception $ex) {
        }
      }
    }
    return TRUE;
  }

  /**
   * Verwijder de controle artsen die niet relevant zijn
   *
   * @param array $artsData
   * @return bool
   */
  private function checkValideArts($controleArts) {
    // arts mag niet op vakantie zijn
    if (CRM_Basis_ControleArts::isOpVakantie($this->_activityData['activity_date_time'], $controleArts['id']) == TRUE) {
      Civi::log()->info('arts ' . $controleArts['id'] . ' is op vakantie (activiteit ' . $this->_activityData['activity_id'] . ')');
      return FALSE;
    }
    // arts mag niet controledag als vrije dag hebben en moet op deze tijd werken
    $werktNietOp =  CRM_Basis_Config::singleton()->getArtsWerktNietOpCustomField('column_name');
    if (!isset($controleArts[$werktNietOp])) {
      $controleArts[$werktNietOp] = [];
    }
    if (CRM_Basis_ControleArts::checkArtsWerkt($this->_activityData['activity_date_time'],
        $controleArts['id'], $controleArts[$werktNietOp]) == FALSE) {
      Civi::log()->info('arts ' . $controleArts['id'] . ' werkt niet op ' . $this->_activityData['activity_date_time'] . ' (activiteit ' . $this->_activityData['activity_id'] . ')');
      return FALSE;
    }
    // arts mag niet al zijn maximale aantal opdrachten hebben
    $max = CRM_Basis_Config::singleton()->getArtsMaxOpdrachtenCustomField('column_name');
    if (!isset($controleArts[$max])) {
      $controleArts[$max] = 999;
    }
    if (CRM_Basis_ControleArts::checkArtsHeeftMaxBereikt($this->_activityData['activity_date_time'], $controleArts['id'], $controleArts[$max]) == TRUE) {
      Civi::log()->info('arts ' . $controleArts['id'] . ' heeft max (activiteit ' . $this->_activityData['activity_id'] . ')');
      return FALSE;
    }
    // arts mag niet uitgesloten zijn voor klant of klant medewerker
    if (CRM_Basis_ControleArts::checkArtsUitgesloten($controleArts['id'], $this->_medewerkerId) == TRUE) {
      CCivi::log()->info('arts ' . $controleArts['id'] . ' uitgesloten (activiteit ' . $this->_activityData['activity_id'] . ')');
      return FALSE;
    }
    // arts mag niet genoeg voor vandaag aangegeven hebben
    if (CRM_Basis_ControleArts::checkArtsGenoegVandaag($controleArts['id']) == TRUE) {
      Civi::log()->info('arts ' . $controleArts['id'] . ' genoeg voor vandaag (activiteit ' . $this->_activityData['activity_id'] . ')');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Method om de toe te wijzen arts te selecteren uit de overgebleven artsen
   *
   * @return int
   */
  private function selectControleArts() {
    // als er maar eentje is hoef ik verder niks te doen
    $count = count($this->_controleArtsen);
    if ($count == 1) {
      return $this->_controleArtsen[0]['contact_id'];
    }
    $maxAfwijkingApp = Civi::settings()->get('mediwe_afwijking_resultaat_app');
    $appColumnName = CRM_Basis_Config::singleton()->getArtsGebruiktAppCustomField('name');
    $pctColumnName = CRM_Basis_Config::singleton()->getArtsPercentageAkkoordCustomField('name');
    // eerst index en goedkeuringspercentage in array en dan sorteren van laag naar hoog
    $resultArray = [];
    foreach ($this->_controleArtsen as $artsKey => $arts) {
      if (!isset($arts[$pctColumnName])) {
        $arts[$pctColumnName] = 0;
      }
      if (!isset($arts[$appColumnName])) {
        $arts[$appColumnName] = 0;
      }
      $resultArray[] = [
        $pctColumnName => $arts[$pctColumnName],
        $appColumnName => $arts[$appColumnName],
        'contact_id' => $arts['contact_id'],
      ];
    }
    sort($resultArray);
    // als de eerste een app gebruiker is, gebruik die
    if ($resultArray[0][$appColumnName] == 1) {
      return $resultArray[0]['contact_id'];
    } else {
      $bestId = $resultArray[0]['contact_id'];
      $bestPct = $resultArray[0][$pctColumnName];
      // zo niet, loop door array en pak eerste app gebruiker binnen afwijking (of
      // blijf eerste gebruiker als niks gevonden)
      for ($i = 1; $i < $count; $i++) {
        if ($resultArray[$i][$appColumnName] == 1) {
          $afwijking = $bestPct - $resultArray[$i][$pctColumnName];
          if ($afwijking <= $maxAfwijkingApp) {
            $bestId = $resultArray[$i]['contact_id'];
          }

        }
      }
    }
    return $bestId;
  }

  /**
   * Method om de toegewezen arts vast te leggen
   */
  private function assignControleArts() {
    if (!empty($this->_toegewezenArtsId)) {
      if (isset($this->_activityData['activity_id'])) {
        try {
          civicrm_api3('Activity', 'create', [
            'id' => $this->_activityData['activity_id'],
            'assignee_id' => $this->_toegewezenArtsId,
          ]);
        } catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->warning(ts('Could not assign huisbezoek in ' . __METHOD__
            . ', message from API Activity create: ' . $ex->getMessage()));
        }
      }
    }
  }

}
