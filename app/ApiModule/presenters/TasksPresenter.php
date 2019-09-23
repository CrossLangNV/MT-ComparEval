<?php

namespace ApiModule;

/**
 * TasksPresenter is used for serving list of task in test set from REST API
 */
class TasksPresenter extends BasePresenter {

	private $tasksModel;
	private $testSetsModel;
	private $enginesModel;
	private $sentencesModel;
	private $metricsModel;

	public function __construct( \Nette\Http\Request $httpRequest, \Tasks $tasksModel, \TestSets $testSetsModel, \Engines $enginesModel, \Sentences $sentencesModel, \Metrics $metricsModel ) {
		parent::__construct( $httpRequest );
		$this->tasksModel = $tasksModel;
		$this->testSetsModel = $testSetsModel;
		$this->enginesModel = $enginesModel;
		$this->sentencesModel = $sentencesModel;
		$this->metricsModel = $metricsModel;
	}

	public function renderDefault( $testSetId ) {
		$parameters = $this->context->getParameters();
		$show_administration = $parameters[ "show_administration" ];

		$response = array();
		$response[ 'tasks' ] = array();
		foreach( $this->tasksModel->getTasks( $testSetId ) as $task ) {
			$taskResponse[ 'id' ] = $task->id;
			$taskResponse[ 'url_key' ] = $task->url_key;
			$taskResponse[ 'description' ] = $task->description;
			$taskResponse[ 'engines_id' ] = $task->engines_id;
			$taskResponse[ 'engine_name' ] = $this->enginesModel->getEngineById($task->engines_id)[ 'name' ];
			if( $show_administration ) {
				$taskResponse[ 'edit_link' ] = $this->link( ':Tasks:edit', $task->id );
				$taskResponse[ 'delete_link' ] = $this->link( ':Tasks:delete', $task->id );
			}
			$taskResponse[ 'download_translation_link' ] = $this->link( ':Api:Tasks:downloadTranslation', $task->id );

			$response[ 'tasks' ][ $task->id ] = $taskResponse;
		}

		$response[ 'show_administration' ] = $show_administration;

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}

	public function renderUpload() {
		$test_set_id = $this->getPostParameter( 'test_set_id' );
		$engine_id = $this->getPostParameter( 'engine_id' );
		$url_key = \Nette\Utils\Strings::webalize( $test_set_id . "-" . $engine_id );
		$description = $this->getPostParameter( 'description' );
		$translation = $this->getPostFile( 'translation' );

		$data = array(
			'description' => $description,
			'url_key' => $url_key,
			'test_sets_id' => $test_set_id,
			'engines_id' => $engine_id
		);

		$testSet = $this->testSetsModel->getTestSetById( $test_set_id );
		$path = __DIR__ . '/../../../data/' . $testSet->url_key . '/' . $url_key . '/';
		$translation->move( $path . 'translation.txt' );
		file_put_contents( $path . 'config.neon', "description: $description\nurl_key: $url_key\ntest_sets_id: $test_set_id\nengines_id: $engine_id" );

		$response = array( 'task_id' => $this->tasksModel->saveTask( $data ) );

		if ( $this->getPostParameter( 'redirect', False ) ) {
			$this->flashMessage( "Task was successfully uploaded. It will appear in this list once it is imported.", "success" );
			$this->redirect( ":Tasks:list", $test_set_id );
		} else {
			$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
		}

	}

	public function renderDownloadBestOrWorstSentences() {
		$firstTaskId = $this->getPostParameter('first-task-id');
		$secondTaskId = $this->getPostParameter('second-task-id');
		$metricNr = $_POST['metric-for-download'];
		$nrOfSentences = $this->getPostParameter('nr');
		$highestOrLowest = $this->getPostParameter('highest-or-lowest');
		$compareTo = $this->getPostParameter('compare-to');
		$format = $this->getPostParameter('format');

		$enabledMetrics = $this->metricsModel->getEnabledMetrics();
		$metricName = $enabledMetrics[$metricNr];

		$order = 'desc';
		if ($highestOrLowest == 'lowest') {
			$order = 'asc';
		}

		// get the results
		$sentences;
		if ($compareTo == "first-task-and-reference") {
			$sentences = $this->sentencesModel->getTranslationsOfOneTask( $firstTaskId, 0, $nrOfSentences, $metricName, $order );
		}
		else if ($compareTo == "second-task-and-reference") {
			$sentences = $this->sentencesModel->getTranslationsOfOneTask( $secondTaskId, 0, $nrOfSentences, $metricName, $order );
		}
		else {
			$taskIds = array();
			array_push($taskIds, $firstTaskId);
			array_push($taskIds, $secondTaskId);
			$sentences = $this->sentencesModel->getFullSentencesSortedByDiffMetric($taskIds, 0, $nrOfSentences, $metricName, $order);
		}

		if ($format == 'xliff') {
			$this->writeSentencesToXliff($sentences, $compareTo);
		}
		else {
			$this->writeSentencesToCsv($sentences, $firstTaskId, $secondTaskId, $metricName, $compareTo);
		}
	}

	private function writeSentencesToCsv($sentences, $firstTaskId, $secondTaskId, $metricName, $compareTo) {
		$firstTask = $this->tasksModel->getTaskById($firstTaskId);
		$secondTask = $this->tasksModel->getTaskById($secondTaskId);

		$output = fopen( "php://output", "w" ) or die( "Can't open php://output" );
		header( "Content-Type:application/csv" );
		header( "Content-Disposition:attachment;filename=sentences.csv" );

		// make the header
		$header = array();
		if ($compareTo == "first-task-and-reference" || $compareTo == "second-task-and-reference") {
			array_push($header, "source,reference,translation," . $metricName);
		}
		else {
			$firstEngine = $this->enginesModel->getEngineById($firstTask['engines_id']);
			$firstEngineName = $firstEngine['name'];
			$secondEngine = $this->enginesModel->getEngineById($secondTask['engines_id']);
			$secondEngineName = $secondEngine['name'];
			array_push($header, "source,reference,translation-by-" . $firstEngineName . "," . $firstEngineName . "-" . $metricName . ",translation-by-" . $secondEngineName . "," . $secondEngineName . "-" . $metricName . "," . $metricName . "-diff");
		}

		// put results into the file
		$data = array();
		foreach( $sentences as $sentence ) {
			$row = array();
			$rowString = '"' . $sentence['source'] . '"';
			$rowString .= ',"' . $sentence['reference'] . '"';
			if ($compareTo == "the-two-tasks") {
				$rowString .= ',"' . $sentence['translations'][0]['text'] . '"';
				$rowString .= ',' . $sentence['translations'][0]['metrics'][$metricName];
				$rowString .= ',"' . $sentence['translations'][1]['text'] . '"';
				$rowString .= ',' . $sentence['translations'][1]['metrics'][$metricName];
				$diff = $sentence['translations'][0]['metrics'][$metricName] - $sentence['translations'][1]['metrics'][$metricName];
				$rowString .= ',' . $diff;
			}
			else {
				$rowString .= ',"' . $sentence['translation'] . '"';
				$rowString .= ',"' . $sentence['score'] . '"';
			}
			array_push($row, $rowString);
			$data[] = $row;
		}

		fputcsv( $output, $header );
		foreach( $data as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output ) or die( "Can't close php://output" );
		$this->terminate();
	}

	private function writeSentencesToXliff($sentences, $compareTo) {
		$output = fopen( "php://output", "w" ) or die( "Can't open php://output" );
		header('Content-Type: application/xml; charset=utf-8');
		header( "Content-Disposition:attachment;filename=sentences.xliff" );

		fputs($output, '<xliff version="1.2">');
		fputs($output, '<file>');
		fputs($output, '<header>');
		fputs($output, '</header>');
		fputs($output, '<body>');

		// put results into the file
		$data = array();
		foreach( $sentences as $key => $sentence ) {
			fputs( $output, '<trans-unit id="' . $key . '"">' );
			fputs( $output, '<source>' . $sentence['source'] . '</source>' );
			if ($compareTo == "the-two-tasks") {
				fputs($output, '<target>' . $sentence['translations'][0]['text'] . '</target>');
			}
			else {
				fputs($output, '<target>' . $sentence['translation'] . '</target>');
			}
			fputs($output, '</trans-unit>');
		}

		fputs($output, '</body>');
		fputs($output, '</file>');
		fputs($output, '</xliff>');

		fclose( $output ) or die( "Can't close php://output" );
		$this->terminate();
	}

	public function renderDownloadTranslation( $id ) {
		$task = $this->tasksModel->getTaskById($id);
		$testSet = $this->testSetsModel->getTestSetById($task['test_sets_id']);

		$filePath = '../data/' . $testSet['url_key'] . '/' . $task['url_key'] . '/translation.txt';
		if (!file_exists($filePath)) {
			$this->terminate();
		}

		header("Content-disposition: attachment; filename=translation.txt");
		header("Content-type: text/plain");
		readfile($filePath);

		$this->terminate();
	}

}
