<?php


/**
 * Engines handle operations on engines table
 */
class Engines {

	private $db;

	public function __construct(Nette\Database\Context $db) {
		$this->db = $db;
	}

	public function getEngines() {
		return $this->db->table( 'engines' )
			->where( 'visible', 1 );
	}

  public function getEnginesByLanguagePairId( $languagePairId ) {
    return $this->db->table( 'engines' )
      ->where( 'visible', 1 )
      ->where( 'language_pairs_id', $languagePairId);
  }

  public function getAvailableEngineIdsByTestSetId( $testSetId ) {
    $tasksOfTestSet = $this->db->table( 'tasks' )
      ->where( 'visible', 1 )
      ->where( 'test_sets_id', $testSetId );
    $takenEngineIds = array();
    foreach ($tasksOfTestSet as $task) {
      array_push($takenEngineIds, $task['engines_id']);
    }

    $testSet = $this->db->table( 'test_sets' )
      ->wherePrimary( $testSetId )
      ->fetch();
    $languagePairId = $testSet['language_pairs_id'];
    $enginesOfLanguagePair = $this->getEnginesByLanguagePairId( $languagePairId );
    $languagePairsEnginesIds = array();
    foreach ($enginesOfLanguagePair as $engine) {
      array_push($languagePairsEnginesIds, $engine['id']);
    }

    $availableEngineIds = array_diff($languagePairsEnginesIds, $takenEngineIds);
    return $availableEngineIds;
  }

  public function getAvailableEnginesByTestSetId( $testSetId ) {
    $availableEngineIds = $this->getAvailableEngineIdsByTestSetId($testSetId);
    $engines = array();
    foreach($availableEngineIds as $id) {
      $engine = $this->getEngineById($id);
      array_push($engines, $engine);
    }
    return $engines;
  }

  public function getEngineById( $engineId ) {
    return $this->db->table( 'engines' )
      ->wherePrimary( $engineId )
      ->fetch();
  }

  public function saveEngine( $data ) {
    if ( !$row = $this->getEngineByUrlKey( $data[ 'url_key' ] ) ) {
      $row = $this->db->table( 'engines' )->insert( $data );
    }

    return $row->getPrimary( TRUE );
  }

  public function getEngineByUrlKey( $urlKey ) {
    return $this->db->table( 'engines' )
      ->where( 'url_key', $urlKey )
      ->fetch();
  }


  public function deleteEngine( $engineId ) {
    try {
      $engine = $this->getEngineById( $engineId );

      return $this->db->table( 'engines' )
        ->wherePrimary( $engineId )
        ->delete();
    } catch( \Exception $exception ) {
      return FALSE;
    }
  }

  public function setVisible( $engineId ) {
    $this->db->table( 'engines' )
      ->get( $engineId )
      ->update( array( 'visible' => 1 ) );
  }

}
