<?php

use \Nette\Application\UI\Form;
use \Nette\Forms\Controls;

class TestSetsPresenter extends BasePresenter {

	private $testSetsModel;
	private $tasksModel;
	private $languagePairsModel;
	private $enginesModel;
	private $sentencesModel;

	public function __construct(Tasks $tasksModel, TestSets $testSetsModel, LanguagePairs $languagePairsModel, Engines $enginesModel, Sentences $sentencesModel) {
		$this->testSetsModel = $testSetsModel;
		$this->tasksModel = $tasksModel;
		$this->languagePairsModel = $languagePairsModel;
		$this->enginesModel = $enginesModel;
		$this->sentencesModel = $sentencesModel;
	}

	public function renderList() {
		$this->template->testSets = $this->testSetsModel->getTestSets();
	}

	public function renderDownload() {
		$output = fopen( "php://output", "w" ) or die( "Can't open php://output" );
		header( "Content-Type:application/csv" );
		header( "Content-Disposition:attachment;filename=statistics.csv" );

		$metricNames = array();
		foreach( $this->testSetsModel->getTestSets() as $testSet ) {
			foreach( $this->tasksModel->getTasks( $testSet->id ) as $task ) {
				$row = array();
				$row[] = $testSet->name;
				$row[] = $task->engines_id;
				$row[] = $task->description;

				$metrics = $this->tasksModel->getTaskMetrics( $task->id );
				if( !$metricNames ) {
					$metricNames = array_keys( $metrics );
				}

				foreach( $metricNames as $metricName ) {
					$row[] = $metrics[ $metricName ];
				}

				$data[] = $row;
			}
		}

		$header = array( "TestSet", "Task", "Description" );
		$header = array_merge( $header, $metricNames );
		fputcsv( $output, $header );

		foreach( $data as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output ) or die( "Can't close php://output" );
		$this->terminate();
	}

	public function actionEdit( $id ) {
		$data = $this->testSetsModel->getTestSetById( $id );
		$this->getComponent( 'editForm' )->setDefaults( $data );
	}

	public function saveEditForm( Form $form ) {
		$data = $form->getValues();
		$id = $data[ 'id' ];
		$name = $data[ 'name' ];
		$description = $data[ 'description' ];
		$domain = $data[ 'domain' ];

		$this->testSetsModel->updateTestSet( $id, $name, $description, $domain );

		$this->flashMessage( 'Test set was successfully updated.', 'alert-success' );
		$this->redirect( 'matrix' );
	}

	public function actionDelete( $id ) {
		$this->testSetsModel->deleteTestSet( $id );

		$this->redirect( 'matrix' );
	}

	public function actionDeleteLanguagePair( $languagePairId ) {
		$testSetsOfLanguagePair = $this->testSetsModel->getTestSetsByLanguagePairId($languagePairId);

		foreach($testSetsOfLanguagePair as $testSet) {
			$this->testSetsModel->deleteTestSet( $testSet['id'] );
		}

		$this->languagePairsModel->deleteLanguagePair( $languagePairId );

		$this->redirect( 'matrix' );
	}

	public function actionDeleteEngine( $engineId ) {
		$tasksOfEngine = $this->tasksModel->getTasksByEngineId($engineId);

		foreach($tasksOfEngine as $task) {
			$this->tasksModel->deleteTask( $task['id'] );
		}

		$this->enginesModel->deleteEngine( $engineId );

		$this->redirect( 'matrix' );
	}

	protected function createComponentEditForm() {
		$form = new Form( $this, 'editForm' );
		$form->addText( 'name', 'Name' )
			->addRule( Form::FILLED, 'Please, fill in the name of the test set.' );
		$form->addTextArea( 'description', 'Description' )
			->addRule( Form::FILLED, 'Please, fill in the description of the test set.' );
		$form->addText( 'domain', 'Domain' )
			->addRule( Form::FILLED, 'Please, fill in the domain of the test set.' );
		$form->addHidden( 'id' );
		$form->addSubmit('save', 'Save');
		$form->onSubmit[] = array( $this, 'saveEditForm' );

		$this->setupRenderer( $form );

		return $form;
	}

	private function setupRenderer( $form ) {
		$renderer = $form->getRenderer();
		$renderer->wrappers[ 'controls' ][ 'container' ] = NULL;
		$renderer->wrappers[ 'pair' ][ 'container' ] = 'div class=control-group';
		$renderer->wrappers[ 'pair' ][ '.error' ] = 'error';
		$renderer->wrappers[ 'control' ][ 'container' ] = 'div class=controls';
		$renderer->wrappers[ 'label' ][ 'container' ] = 'div class=control-label';
		$renderer->wrappers[ 'control' ][ 'description' ] = 'span class=help-inline';
		$renderer->wrappers[ 'control' ][ 'errorcontainer' ] = 'span class=help-inline';
		$form->getElementPrototype()->class( 'form-horizontal' );

		foreach ($form->getControls() as $control) {
			if ( $control instanceof Controls\Button ) {
				$control->getControlPrototype()->addClass( empty( $usedPrimary ) ? 'btn btn-primary' : 'btn' );
				$usedPrimary = TRUE;
			} else if ( $control instanceof Controls\TextInput || $control instanceof Controls\TextArea ) {
				$control->getControlPrototype()->addClass( 'input-block-level' );
			}
		}

	}

	public function renderMatrix() {
		$languagePairs = $this->languagePairsModel->getLanguagePairs();
		$this->template->languagePairs = $languagePairs;
		$testSets = $this->testSetsModel->getTestSets();
		$engines = $this->enginesModel->getEngines();

		$tableData = array();

		// Initialize with empty values
		foreach ($languagePairs as $languagePairIndex => $languagePair) {
			$languagePairData = array();
			foreach ($testSets as $testSetIndex => $testSet) {
				foreach ($engines as $engineIndex => $engine) {
					if ($engine['language_pairs_id'] == $languagePair['id']) {
						$languagePairData[$testSet['id']][$engine['id']] = 0;
					}
				}
				$tableData[$languagePair['id']] = $languagePairData;
			}
		}

		// Fill in data table
		$result = $this->tasksModel->getAllTasks();
		foreach ($result as $row) {
			$tableData[$row->languagepair_id][$row->testset_id][$row->engine_id] = array("id" => $row->task_id, "description" => $row->task_name);
		}

		$this->template->engines = $engines;
		$this->template->testSets = $testSets;
		$this->template->tableData = $tableData;
	}

	public function renderEngine( $languagePairId ) {
		$this->template->languagePair = $this->languagePairsModel->getLanguagePairById($languagePairId);
		$this->template->languagePairs = $this->languagePairsModel->getLanguagePairs();
		$this->template->engines = $this->enginesModel->getEngines();
	}

	public function renderNew( $languagePairId ) {
		$this->template->languagePair = $this->languagePairsModel->getLanguagePairById($languagePairId);
	}

	public function renderGraphicalComparison($languagePairId) {
		$this->template->languagePair = $this->languagePairsModel->getLanguagePairById($languagePairId);
		$testSets = $this->testSetsModel->getTestSetsByLanguagePairId($languagePairId);
		$this->template->testSets = $testSets;
		$testSetsOrderedByDomain = $this->testSetsModel->getTestSetsByLanguagePairIdOrderByDomain($languagePairId);
		$this->template->testSetsOrderedByDomain = $testSetsOrderedByDomain;
		$engines = $this->enginesModel->getEnginesByLanguagePairId($languagePairId);
		$this->template->engines = $engines;

		$metrics = array();

		foreach ($testSets as $testSetIndex => $testSet) {
			$tasks = $this->tasksModel->getTasks($testSet['id']);
			foreach ($engines as $engineIndex => $engine) {
				foreach ($tasks as $taskIndex => $task) {
					if ($engine['id'] == $task['engines_id']) {
						$metrics[$testSet['id']][$engine['id']]['id'] = $task['id'];
						$metrics[$testSet['id']][$engine['id']]['metrics'] = $this->tasksModel->getTaskMetrics($task['id']);
					}
				}
			}
		}

		$this->template->metrics = $metrics;

		$testSetNames = array();
		foreach ($testSets as $testSetIndex => $testSet) {
			array_push($testSetNames, $testSet['name']);
		}

		$this->template->testSetNames = $testSetNames;

		$chartSeries = array();
		foreach ($engines as $engineIndex => $engine) {
			$engineData = array();
			$engineData['name'] = $engine['name'];
			$engineBleuScores = array();
			foreach ($testSets as $testSetIndex => $testSet) {
				$task = $this->tasksModel->getTaskByTestSetIdAndEngineId($testSet['id'],$engine['id']);
				if ($task) {
					$metrics = $this->tasksModel->getTaskMetrics($task['id']);
					array_push($engineBleuScores, $metrics['BLEU']);
				}
				else {
					array_push($engineBleuScores, null);
				}
				$engineData['data'] = $engineBleuScores;
			}
			array_push($chartSeries, $engineData);
		}
		$this->template->chartSeries = $chartSeries;

		$chartSeriesSortedPerDomain = array();
		foreach ($engines as $engineIndex => $engine) {
			$engineData = array();
			$engineData['name'] = $engine['name'];
			$engineBleuScores = array();
			foreach ($testSetsOrderedByDomain as $testSetIndex => $testSet) {
				$task = $this->tasksModel->getTaskByTestSetIdAndEngineId($testSet['id'],$engine['id']);
				if ($task) {
					$metrics = $this->tasksModel->getTaskMetrics($task['id']);
					array_push($engineBleuScores, $metrics['BLEU']);
				}
				else {
					array_push($engineBleuScores, null);
				}
				$engineData['data'] = $engineBleuScores;
			}
			array_push($chartSeriesSortedPerDomain, $engineData);
		}
		$this->template->chartSeriesSortedPerDomain = $chartSeriesSortedPerDomain;

		$testSetNamesSortedPerDomain = array();
		foreach ($testSetsOrderedByDomain as $testSetIndex => $testSet) {
			array_push($testSetNamesSortedPerDomain, $testSet['name']);
		}

		$domains = array();
		$curDomain = array();
		$count = 0;
		foreach ($testSetsOrderedByDomain as $testSetIndex => $testSet) {
			if ($testSet['domain'] != $curDomain['name']) {
				if ($curDomain['name']) {
					$curDomain['end'] = $count;
					array_push($domains, $curDomain);
					$curDomain = array();
				}
				$curDomain['name'] = $testSet['domain'];
				$curDomain['begin'] = $count;
			}
			$count++;
		}
		if ($curDomain['name']) {
			$curDomain['end'] = $count;
			array_push($domains, $curDomain);
		}
		$this->template->domains = $domains;

		$this->template->testSetNamesSortedPerDomain = $testSetNamesSortedPerDomain;
	}

	public function renderEnginesTree($engineId) {
		$this->template->engineId = $engineId;
		$engine = $this->enginesModel->getEngineById($engineId);
		$engines = $this->enginesModel->getEngines()->fetchAssoc('id');

		$engines[$engineId]['show'] = true;
		$this->markChildren($engines, $engineId);
		$this->markAncestors($engines, $engineId);
		$this->template->engines = $engines;

		$hasFiles = false;
		$path = __DIR__ . '/../../engines-data/' . $engine['url_key'] . '/';
		if (!$this->dirIsEmpty($path)) {
			$hasFiles = true;
		}
		$this->template->hasFiles = $hasFiles;
	}

	private function dirIsEmpty($path) {
		if (!file_exists($path)) return true;
		if (!is_dir($path)) return true;
		foreach (scandir($path) as $file) {
			if (!in_array($file, array('.','..','.svn','.git'))) return false;
		}
		return true;
}

	public function renderEnginesTreeGlobal() {
		$engines = $this->enginesModel->getEngines()->fetchAssoc('id');
		$this->template->engines = $engines;
	}

	private function markChildren(&$engines, $engineId) {
		foreach ($engines as $key => $value) {
			if ($engines[$key]['parent_id']) {
				if ($engines[$key]['parent_id'] == $engineId) {
					$engines[$key]['show'] = true;
					$this->markChildren($engines, $key);
				}
			}
		}
	}

	private function markAncestors(&$engines, $engineId) {
		if ($engines[$engineId]['parent_id']) {
			$parentId = $engines[$engineId]['parent_id'];
			$engines[$parentId]['show'] = true;
			$this->markAncestors($engines, $parentId);
			$this->markChildren($engines, $parentId);
		}
	}

	public function renderPerSentenceComparison( $testSetId ) {
		$this->template->testSetId = $testSetId;
		$this->template->testSet = $this->testSetsModel->getTestSetById( $testSetId );

		$sentences = $this->sentencesModel->getSentencesByTestSet($testSetId);
		$perSentenceComparisonData = array();
		foreach($sentences as $id => $sentence) {
			$sentenceData = array();
			$translations = $this->sentencesModel->getTranslationsBySentenceId($id);
			$translationsData = array();
			foreach($translations as $translation) {
				$translationData = array();
				$task = $this->tasksModel->getTaskById($translation['tasks_id']);
				$translationData['engine_name'] = $this->enginesModel->getEngineById($task['engines_id'])['name'];
				$translationData['translation_text'] = $translation['text'];
				$taskMetrics = $this->tasksModel->getTaskMetrics($task['id']);
				$translationData['metrics'] = $taskMetrics;
				array_push($translationsData, $translationData);
			}
			$sentenceData['translations'] = $translationsData;
			$sentenceData['source'] = $sentence['source'];
			$sentenceData['reference'] = $sentence['reference'];

			$perSentenceComparisonData[$id] = $sentenceData;
		}
		$this->template->perSentenceComparisonData = $perSentenceComparisonData;
	}

}

