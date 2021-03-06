<?php

namespace ApiModule;

/**
 * MetricsPresenter is used for serving metrics data from REST API
 *
 * This data is used on frontend for rendering various charts and tables
 */
class MetricsPresenter extends \Nette\Application\UI\Presenter {

	private $metricsModel;

	private $tasksModel;

	public function __construct( \Metrics $metricsModel, \Tasks $tasksModel ) {
		$this->metricsModel = $metricsModel;
		$this->tasksModel = $tasksModel;
	}


	public function renderDefault() {
		$response = array();
		$response[ 'metrics' ] = $this->metricsModel->getEnabledMetrics();

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}


	public function renderScores( $task1, $task2 ) {
		$response = array();
		$response[ $task1 ] = $this->tasksModel->getTaskMetrics( $task1 );
		$response[ $task2 ] = $this->tasksModel->getTaskMetrics( $task2 );

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}


	public function renderScoresInTestSet( $testSetId ) {
		$tasks = array();
		$metrics = array();

		foreach( $this->metricsModel->getEnabledMetrics() as $metric ) {
			$metrics[ $metric ] = array(
				'name' => $metric,
				'data' => array()
			);
		}

		foreach( $this->tasksModel->getTasks( $testSetId ) as $task ) {
			$tasks[ $task->id ] = $task->url_key;

			foreach( $this->tasksModel->getTaskMetrics( $task ) as $name => $score ) {
				if( !$this->metricsModel->isMetricEnabled( $name ) ) {
					continue;
				}

				$metrics[ $name ][ 'data' ][ $task->id ] = $score;
			}
		}

		$response = array(
			'tasks' => $tasks,
			'metrics' => array_values( $metrics )
		);

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}


	public function renderResults( $metric, $task1, $task2 ) {
		$metricId = $this->metricsModel->getMetricsId( $metric );

		$response = array();
		$response[ 'diffs' ] = array();
		$response[ 'diffs' ][ 'name' ] = $metric;
		$response[ 'diffs' ][ 'data' ] = $this->metricsModel->getMetricDiffs( $metricId, $task1, $task2 );

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}


	public function renderSamples( $metric, $task ) {
		$metricId = $this->metricsModel->getMetricsId( $metric );

		$response = array();
		$response[ 'samples' ] = array();
		$response[ 'samples' ][ 'name' ] = $metric;
		$response[ 'samples' ][ 'data' ] = $this->metricsModel->getMetricSamples( $metricId, $task );

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}


	public function renderSamplesDiff( $metric, $task1, $task2 ) {
		$metricId = $this->metricsModel->getMetricsId( $metric );

		$response = array();
		$response[ 'samples' ] = array();
		$response[ 'samples' ][ 'name' ] = $metric;
		$response[ 'samples' ][ 'data' ] = $this->metricsModel->getMetricSamplesDiff( $metricId, $task1, $task2 );

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}

}
