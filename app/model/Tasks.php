<?php

/**
 * Tasks handles operations on tasks table.
 */
class Tasks {

	private $db;

	public function __construct( Nette\Database\Context $db ) {
		$this->db = $db;
	}

	public function getTask( $taskId ) {
		return $this->db->table( 'tasks' )->wherePrimary( $taskId )->fetch();
	}

	public function getAllTasks( ) {
		return $this->db->query('SELECT t.id task_id, t.description task_name, e.id engine_id, e.name engine_name, ts.id testset_id, ts.name as testset_name, lp.id  languagepair_id 
		FROM tasks t
	 INNER JOIN engines e on t.engines_id = e.id
	 INNER JOIN test_sets ts on t.test_sets_id = ts.id
	 INNER JOIN language_pairs lp on ts.language_pairs_id = lp.id;');
	}

	public function getTaskMetrics( $taskId ) {
		$metrics = array();
		foreach( $this->getTask( $taskId )->related( 'tasks_metrics' ) as $metric ) {
			$metrics[ $metric->ref( 'metrics' )->name ] = $metric->score;
		}

		return $metrics;
	}

	public function getTasks( $testSetId ) {
		return $this->db->table( 'tasks' )
			->where( 'test_sets_id', $testSetId )
			->where( 'visible', 1 );
	}

	public function getTaskById( $taskId ) {
		return $this->db->table( 'tasks' )
			->wherePrimary( $taskId )
			->fetch();
	}

	public function getTaskByUrlKey( $taskUrlKey ) {
		return $this->db->table( 'tasks' )
			->where( 'url_key', $taskUrlKey )
			->fetch();
	}

	public function getTasksByEngineId( $engineId ) {
		return $this->db->table( 'tasks' )
			->where( 'engines_id', $engineId )
			->order( 'id' );
	}

	public function getTaskByTestSetIdAndEngineId( $testSetId, $engineId ) {
		return $this->db->table( 'tasks' )
			->where( 'test_sets_id', $testSetId )
			->where( 'engines_id', $engineId )
			->fetch();
	}

	public function saveTask( $data ) {
		if ( !$row = $this->getTaskByUrlKey( $data[ 'url_key' ] ) ) {
			$row = $this->db->table( 'tasks' )->insert( $data );
		}

		return $row->getPrimary( TRUE );
	}

	public function updateTask( $taskId, $description ) {
		$this->db->table( 'tasks' )
			->get( $taskId )
			->update( array('description' => $description ) );

	}

	public function setVisible( $taskId ) {
		$this->db->table( 'tasks' )
			->get( $taskId )
			->update( array( 'visible' => 1 ) );
	}

	public function addSentences( $taskId, $sentences, $metrics ) {
		$this->db->beginTransaction();

		foreach( $sentences as $key => $sentence ) {
			$data = array(
				'sentences_id' => $sentence['test_set']['id'],
				'tasks_id' => $taskId,
				'text' => $sentence['translation']
			);

			$translationId = $this->db->table( 'translations' )->insert( $data );

			$position = array();
			foreach( $sentence[ 'meta' ][ 'confirmed_ngrams' ] as $length => $confirmedNgrams ) {
				foreach( $confirmedNgrams as $confirmedNgram ) {
					if( !isset( $position[ $confirmedNgram ] ) ) {
						$position[ $confirmedNgram ] = 0;
					}

					$data = array(
						'translations_id' => $translationId,
						'position' => $position[ $confirmedNgram ]++,
						'length' => $length,
						'text' => $confirmedNgram
					);

					$this->db->table( 'confirmed_ngrams' )->insert( $data );
				}
			}

			$position = array();
			foreach( $sentence[ 'meta' ][ 'unconfirmed_ngrams' ] as $length => $unconfirmedNgrams ) {
				foreach( $unconfirmedNgrams as $unconfirmedNgram ) {
					if( !isset( $position[ $unconfirmedNgram ] ) ) {
						$position[ $unconfirmedNgram ] = 0;
					}

					$data = array(
						'translations_id' => $translationId,
						'position' => $position[ $unconfirmedNgram ]++,
						'length' => $length,
						'text' => $unconfirmedNgram
					);

					$this->db->table( 'unconfirmed_ngrams' )->insert( $data );
				}
			}


			foreach( $metrics as $metric => $values ) {
				$data = array(
					'translations_id' => $translationId,
					'metrics_id' => $this->db->table( 'metrics' )->where( 'name', $metric )->fetch()->id,
					'score' => $values[ $key ]
				);
				$this->db->table( 'translations_metrics' )->insert( $data );
			}
		}

		$this->db->commit();
	}

	public function addMetric( $taskId, $metric, $value ) {
		$data = array(
			'tasks_id' => $taskId,
			'metrics_id' => $this->db->table( 'metrics' )->where( 'name', $metric )->fetch()->id,
			'score' => $value
		);

		$this->db->table( 'tasks_metrics' )->insert( $data );
	}

	public function addSamples( $taskId, $metric, $samples ) {
		$this->db->beginTransaction();

		$metricId = $this->db->table( 'metrics' )->where( 'name', $metric )->fetch()->id;
		foreach( $samples as $position => $score ) {
			$data = array(
				'tasks_id' => $taskId,
				'metrics_id' => $metricId,
				'sample_position' => $position,
				'score' => $score
			);

			$this->db->table( 'tasks_metrics_samples' )->insert( $data );
		}

		$this->db->commit();
	}

	public function deleteTask( $taskId, $deleteFromFileSystem = TRUE ) {
		try {
			$task = $this->getTaskById( $taskId );
			$testSet = $task->test_set;

			if ( $task && $deleteFromFileSystem ) {
				\Nette\Utils\FileSystem::delete( __DIR__ . '/../../data/' . $testSet[ 'url_key' ] . '/' . $task[ 'url_key' ] );
			}

			return $this->db->table( 'tasks' )
				->wherePrimary( $taskId )
				->delete();
		} catch( \Exception $exception ) {
			return FALSE;
		}
	}

	public function deleteTaskByUrlKey( $urlKey ) {
		$this->db->table( 'tasks' )
			->where( 'url_key', $urlKey )
			->delete();
	}

}
