<?php

class Tasks {

	private $db;

	public function __construct( Nette\Database\Connection $db ) {
		$this->db = $db;
	}

	public function getTask( $taskId ) {
		return $this->db->table( 'tasks' )->find( $taskId )->fetch();
	}

	public function getTasks( $experimentId ) {
		return $this->db->table( 'tasks' )
			->where( 'experiments_id', $experimentId );
	}

	public function saveTask( $data ) {
		$row = $this->db->table( 'tasks' )->insert( $data );

		return $row->getPrimary( TRUE );
	}

	public function addSentences( $taskId, $sentences, $metrics ) {
		$this->db->beginTransaction();

		foreach( $sentences as $key => $sentence ) {
			$data = array(
				'sentences_id' => $sentence['experiment']['id'],
				'tasks_id' => $taskId,
				'text' => $sentence['translation']
			);

			$translationId = $this->db->table( 'translations' )->insert( $data );
			
			foreach( $sentence[ 'meta' ][ 'confirmed_ngrams' ] as $length => $confirmedNgrams ) {
				foreach( $confirmedNgrams as $confirmedNgram ) {
					$data = array(
						'translations_id' => $translationId,
						'length' => $length,
						'text' => $confirmedNgram
					);
					
					$this->db->table( 'confirmed_ngrams' )->insert( $data );
				}
			}

			foreach( $sentence[ 'meta' ][ 'unconfirmed_ngrams' ] as $length => $unconfirmedNgrams ) {
				foreach( $unconfirmedNgrams as $unconfirmedNgram ) {
					$data = array(
						'translations_id' => $translationId,
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

	public function deleteTaskByName( $experimentId, $name ) {
		$this->db->table( 'tasks' )
			->where( 'experiments_id', $experimentId )
			->where( 'url_key', $name )
			->delete();
	}


}
