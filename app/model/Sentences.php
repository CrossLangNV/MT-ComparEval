<?php


/**
 * Sentences is used for manipulating sentences triples - (source, reference, [translation])
 */
class Sentences {

	private $db;

	private $metrics;

	public function __construct( Nette\Database\Context $db, Metrics $metrics ) {
		$this->db = $db;
		$this->metrics = $metrics;
	}

	public function getDb() {
		return $this->db;
	}

	public function getSentencesCount( $tasksIds ) {
		return $this->db->table( 'translations' )
			->where( 'tasks_id', $tasksIds )
			->count( 'DISTINCT sentences_id' );
	}

	public function getSentences( $taskIds, $offset, $limit, $orderBy, $order ) {
		$sentenceIds = $this->getSentenceIdsForRequest( $taskIds, $offset, $limit, $orderBy, $order );

		return $this->getSentencesWithIds( $sentenceIds, $taskIds );
	}

	public function getFullSentencesSortedByDiffMetric( $taskIds, $offset, $limit, $orderBy, $order ) {
		$sentenceIds = $this->getSentenceIdsForRequest( $taskIds, $offset, $limit, $orderBy, $order );
		$sentences = $this->getSentencesWithIds( $sentenceIds, $taskIds );

		// sort the sentences by the ids in $sentenceIds
		$sortedSentences = array_flip($sentenceIds);
		foreach($sentences as $sentence) {
			$id = $sentence['sentence_id'];
			$sentenceCopy = $sentence;
			$sortedSentences[$id] = $sentenceCopy;
		}

		return $sortedSentences;
	}

	public function getSentencesByIds( $sentenceIds, $taskIds, $offset, $limit ) {
		$sentenceIds = $this->sortSentencesIds( $sentenceIds );
		$sentenceIds = $this->sliceResult( $sentenceIds, $offset, $limit );

		return $this->getSentencesWithIds( $sentenceIds, $taskIds );
	}

	public function getTranslationsOfOneTask($taskId, $offset, $limit, $orderBy, $order) {
		$metricsId = $this->metrics->getMetricsId( $orderBy );

		$translationData = $this->db
			->table( 'translations_metrics' )
			->select( 'score, translations.sentences_id, translations.text' )
			->where( 'metrics_id', $metricsId )
			->where( 'translations.tasks_id', $taskId )
			->order( 'score ' . strtoupper( $order ) )
			->limit( $limit, $offset )
			->fetchPairs( 'sentences_id');

		$sourceData = $this->db->table('sentences')->where('id', array_keys($translationData))->fetchPairs('id');

		$results = array();
		foreach ($translationData as $sentenceId => $data) {
			$result = array();
			$result['score'] = $data['score'];
			$result['translation'] = $data['text'];
			$result['source'] = $sourceData[$sentenceId]['source'];
			$result['reference'] = $sourceData[$sentenceId]['reference'];
			array_push($results, $result);
		}
		return($results);
	}

	private function sortSentencesIds( $sentenceIds ) {
		$keys = array_unique( $sentenceIds );
		$occurences = array_combine( $keys, array_fill( 0, count( $keys ), 0 ) );
		foreach( $sentenceIds as $id ) {
			$occurences[ $id ]++;
		}

		return $this->sortResult( $occurences, 'desc' );
	}

	private function getSentencesWithIds( $sentenceIds, $taskIds ) {
		$rows = array();
		foreach( $this->db->table( 'sentences' )->where( 'id', $sentenceIds ) as $sentence ) {

			$row = array();
			$row[ 'sentence_id' ] = $sentence[ 'id' ];
			$row[ 'source' ] = $sentence[ 'source' ];
			$row[ 'reference' ] = $sentence[ 'reference' ];
			$row[ 'translations' ] = array();

			foreach( $sentence->related( 'translations.sentences_id' )->where( 'tasks_id', $taskIds ) as $translation ) {
				$rowTranslation = array();
				$rowTranslation[ 'task_id' ] = $translation[ 'tasks_id' ];
				$rowTranslation[ 'text' ] = $translation[ 'text' ];
				$rowTranslation[ 'metrics' ] = array();

				foreach( $translation->related( 'translations_metrics.translations_id' ) as $metric ) {
					$name = $this->db->table( 'metrics' )->wherePrimary( $metric['metrics_id'] )->fetch()->name;
					$rowTranslation[ 'metrics' ][ $name ] = $metric[ 'score' ];
				}

				$row[ 'translations' ][] = $rowTranslation;
			}

			if( count( $taskIds ) == 2 && $taskIds[0] > $taskIds[1] ) {
				$row[ 'translations' ] = array_reverse( $row[ 'translations' ] );
			}

			$rows[] = $row;
		}

		return $rows;
	}

	private function getSentenceIdsForRequest( $taskIds, $offset, $limit, $orderBy, $order ) {
		if( $orderBy === 'id' ) {
			return $this->getSentenceIdsSortedById( $taskIds, $orderBy, $order, $offset, $limit );
		} else if( count( $taskIds ) == 1 ) {
			return $this->getSentencesIdsSortedByMetric( $taskIds, $orderBy, $order, $offset, $limit );
		} else {
			return $this->getSentencesSortedByDiffMetric( $taskIds, $orderBy, $order, $offset, $limit );
		}
	}

	private function getSentenceIdsSortedById( $taskIds, $orderBy, $order, $offset, $limit ) {
		return array_keys( $this->db
			->table( 'translations' )
			->select( 'DISTINCT sentences_id' )
			->where( 'tasks_id', $taskIds )
			->order( 'sentences_id ' . strtoupper( $order ) )
			->limit( $limit, $offset )
			->fetchPairs( 'sentences_id' ) );
	}

	private function getSentencesIdsSortedByMetric( $taskIds, $orderBy, $order, $offset, $limit ) {
		$metricsId = $this->metrics->getMetricsId( $orderBy );

		return array_keys( $this->db
			->table( 'translations' )
			->where( 'tasks_id', $taskIds )
			->where( 'translations_metrics:metrics_id', $metricsId )
			->order( 'translations_metrics:score ' . strtoupper( $order ) )
			->limit( $limit, $offset )
			->fetchPairs( 'sentences_id' ) );
	}

	private function getSentencesSortedByDiffMetric( $taskIds, $orderBy, $order, $offset, $limit ) {
		$metricsId = $this->metrics->getMetricsId( $orderBy );
		$resultsA = $this->getTranslationsMetricsForTask( $taskIds[0], $metricsId );
		$resultsB = $this->getTranslationsMetricsForTask( $taskIds[1], $metricsId );
		$rawResult = $this->joinResults( $resultsA, $resultsB );
		$sortedResult = $this->sortResult( $rawResult, $order );

		return $this->sliceResult( $sortedResult, $offset, $limit );
	}

	public function getTranslationsMetricsForTask( $task, $metric ) {
		return $this->db
			->table( 'translations_metrics' )
			->select( 'score, translations.sentences_id' )
			->where( 'metrics_id', $metric )
			->where( 'translations.tasks_id', $task )
			->fetchPairs( 'sentences_id', 'score' );
	}

	public function getSentencesByTestSet( $testSetId ) {
		return $this->db->table( 'sentences' )
			->where( 'test_sets_id', $testSetId )
			->fetchAll();
	}

	public function getTranslationsBySentenceId($sentenceId) {
		return $this->db->table( 'translations' )
			->where( 'sentences_id', $sentenceId )
			->fetchAll();
	}

	private function joinResults( $resultsA, $resultsB ) {
		$result = array();
		foreach( $resultsA as $sentenceId => $score ) {
			$result[ $sentenceId ] = $score - $resultsB[ $sentenceId ];
		}

		return $result;
	}

	private function sortResult( $result, $order ) {
		asort( $result, SORT_NUMERIC );
		if( $order == 'desc' ) {
			$result = array_reverse( $result, TRUE );
		}

		return $result;
	}

	private function sliceResult( $result, $offset, $limit ) {
		return array_slice( array_keys( $result ), $offset, $limit );
	}

}
