<?php


/**
 * Language models handle operations on language models table
 */
class LanguagePairs {

	private $db;

	public function __construct(Nette\Database\Context $db) {
		$this->db = $db;
	}

	public function getLanguagePairs() {
		return $this->db->table( 'language_pairs' )
			->where( 'visible', 1 );
	}

  public function getLanguagePairById( $languagePairId ) {
    return $this->db->table( 'language_pairs' )
      ->wherePrimary( $languagePairId )
      ->fetch();
  }

  public function saveLanguagePair( $data ) {
    if ( !$row = $this->getLanguagePairByUrlKey( $data[ 'url_key' ] ) ) {
      $row = $this->db->table( 'language_pairs' )->insert( $data );
    }

    return $row->getPrimary( TRUE );
  }

  public function getLanguagePairByUrlKey( $urlKey ) {
    return $this->db->table( 'language_pairs' )
      ->where( 'url_key', $urlKey )
      ->fetch();
  }


  public function deleteLanguagePair( $languagePairId ) {
    try {
      $languagePair = $this->getLanguagePairById( $languagePairId );

      return $this->db->table( 'language_pairs' )
        ->wherePrimary( $languagePairId )
        ->delete();
    } catch( \Exception $exception ) {
      return FALSE;
    }
  }

  public function setVisible( $languagePairId ) {
    $this->db->table( 'language_pairs' )
      ->get( $languagePairId )
      ->update( array( 'visible' => 1 ) );
  }

}
