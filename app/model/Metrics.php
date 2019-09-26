<?php

/**
 * Metrics handle operations on metrics, task_metrics, task_samples, translation_metrics tables
 */
class Metrics {

	private $db;

	private $enabledMetrics;

	public function __construct( Nette\Database\Context $db, EnabledMetricsList $enabledMetrics ) {
		$this->db = $db;
		$this->enabledMetrics = (array) $enabledMetrics;
	}

	public function getMetrics() {
		return $this->db
			->table( 'metrics' )
			->order( 'name' );
	}


	public function getMetricsId( $name ) {
		return  $this->db
			->table( 'metrics' )
			->where( 'name', $name )
			->fetch()->id;
	}


	public function getMetricDiffs( $metricId, $task1, $task2 ) {
		$scores = new ZipperIterator( array(
			$this->getScoresForTranslations( $metricId, $task1 ),
			$this->getScoresForTranslations( $metricId, $task2 )
		) );

		$diffScores = array();
		foreach( $scores as $score ) {
			$diffScores[] = $score[0][ 'score' ] - $score[1][ 'score' ];
		}

		sort( $diffScores );
		return $diffScores;
	}


	public function getScoresForTranslations( $metricId, $taskId ) {
		return $this->db
			->table( 'translations_metrics' )
			->select( 'score' )
			->where( 'metrics_id', $metricId )
			->where( 'tasks_id', $taskId )
			->order( 'translations.sentences_id' );
	}


	public function getMetricSamples( $metricId, $task ) {
		$samples = $this->getSamplesForTask( $metricId, $task );
		$samples = iterator_to_array( $samples );
		$samples = array_map( function( $sample ) {
			return $sample[ 'score' ];
		}, $samples );

		sort( $samples );

		return $samples;
	}


	public function getMetricSamplesDiff( $metricId, $task1, $task2 ) {
		$samples = new ZipperIterator( array(
			$this->getSamplesForTask( $metricId, $task1 ),
			$this->getSamplesForTask( $metricId, $task2 )
		) );

		$diffSamples = array();
		foreach( $samples as $sample ) {
			$diffSamples[] = $sample[0][ 'score' ] - $sample[1][ 'score' ];
		}

		sort( $diffSamples );
		return $diffSamples;
	}


	private function getSamplesForTask( $metricId, $taskId ) {
		return $this->db
			->table( 'tasks_metrics_samples' )
			->select( 'score' )
			->where( 'metrics_id', $metricId )
			->where( 'tasks_id', $taskId )
			->order( 'sample_position' );
	}


	public function getEnabledMetrics() {
		return array_keys( $this->enabledMetrics );
	}


	public function isMetricEnabled( $metric ) {
		return array_key_exists( $metric, $this->enabledMetrics );
	}
}
