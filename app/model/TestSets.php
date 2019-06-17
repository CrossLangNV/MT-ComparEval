<?php


/**
 * Test sets handle operations on test set table
 */
class TestSets {

	private $db;

	public function __construct( Nette\Database\Context $db ) {
		$this->db = $db;
	}

	public function getTestSets() {
		return $this->db->table( 'test_sets' )
			->where( 'visible', 1 );
	}

	public function getTestSetById( $testSetId ) {
		return $this->db->table( 'test_sets' )
			->wherePrimary( $testSetId )
			->fetch();
	}

	public function getTestSetByName( $name ) {
		return $this->db->table( 'test_sets' )
			->where( 'url_key', $name )
			->fetch();
	}

	public function saveTestSet( $data ) {
		if ( !$row = $this->getTestSetByName( $data[ 'url_key' ] ) ) {
			$row = $this->db->table( 'test_sets' )->insert( $data );
		}

		return $row->getPrimary( TRUE );
	}

	public function updateTestSet( $testSetId, $name, $description ) {
		$this->db->table( 'test_sets' )
			->get( $testSetId )
			->update( array( 'name' => $name, 'description' => $description ) );

	}

	public function setVisible( $testSetId ) {
		$this->db->table( 'test_sets' )
			->get( $testSetId )
			->update( array( 'visible' => 1 ) );
	}

	public function getSentences( $testSetId ) {
		return $this->db->table( 'sentences' )
			->where( 'test_sets_id', $testSetId )
			->order( 'id' );
	}

	public function addSentences( $testSetId, $sentences ) {
		$this->db->beginTransaction();

		foreach( $sentences as $sentence ) {
			$this->db->table( 'sentences' )->insert( array(
				'test_sets_id' => $testSetId,
				'source' => $sentence['source'],
				'reference' => $sentence['reference']
			) );
		}

		$this->db->commit();
	}

	public function deleteTestSet( $testSetId ) {
		try {
			$testSet = $this->getTestSetById( $testSetId );

			if ( $testSet ) {
				\Nette\Utils\FileSystem::delete( __DIR__ . '/../../data/' . $testSet[ 'url_key' ] );
			}

			return $this->db->table( 'test_sets' )
				->wherePrimary( $testSetId )
				->delete();
		} catch( \Exception $exception ) {
			return FALSE;
		}
	}

	public function deleteTestSetByName( $name ) {
		return $this->db->table( 'test_sets' )
			->where( 'url_key', $name )
			->delete();
	}

}
