<?php

use \Nette\Application\UI\Form;
use \Nette\Forms\Controls;

class TestSetsPresenter extends BasePresenter {

	private $testSetsModel;
	private $tasksModel;
	private $languagePairsModel;
	private $enginesModel;

	public function __construct(Tasks $tasksModel, TestSets $testSetsModel, LanguagePairs $languagePairsModel, Engines $enginesModel) {
		$this->testSetsModel = $testSetsModel;
		$this->tasksModel = $tasksModel;
		$this->languagePairsModel = $languagePairsModel;
		$this->enginesModel = $enginesModel;
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

		$this->testSetsModel->updateTestSet( $id, $name, $description );

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
			->addRule( Form::FILLED, 'Please, fill in a name of the test set.' );
		$form->addTextArea( 'description', 'Description' )
			->addRule( Form::FILLED, 'Please, fill in a description of the test set.' );
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

		foreach ($languagePairs as $languagePairIndex => $languagePair) {
			$languagePairData = array();
			foreach ($testSets as $testSetIndex => $testSet) {
				$tasks = $this->tasksModel->getTasks($testSet['id']);
				foreach ($engines as $engineIndex => $engine) {
					if ($engine['language_pairs_id'] == $languagePair['id']) {
						foreach ($tasks as $taskIndex => $task) {
							if ($engine['id'] == $task['engines_id']) {
								$languagePairData[$testSet['id']][$engine['id']]['id'] = $task['id'];
								$languagePairData[$testSet['id']][$engine['id']]['description'] = $task['description'];
							}
						}
						if ($languagePairData[$testSet['id']][$engineIndex] == null) {
							$languagePairData[$testSet['id']][$engine['id']] = 0;
						}
					}
				}
			}
			$tableData[$languagePair['id']] = $languagePairData;
		}

		$this->template->engines = $this->enginesModel->getEngines();
		$this->template->testSets = $this->testSetsModel->getTestSets();
		$this->template->tableData = $tableData;
	}

	public function renderEngine( $languagePairId ) {
		$this->template->languagePair = $this->languagePairsModel->getLanguagePairById($languagePairId);
	}

	public function renderNew( $languagePairId ) {
		$this->template->languagePair = $this->languagePairsModel->getLanguagePairById($languagePairId);
	}

	public function renderGraphicalComparison($languagePairId) {
		$this->template->languagePair = $this->languagePairsModel->getLanguagePairById($languagePairId);
		$testSets = $this->testSetsModel->getTestSetsByLanguagePairId($languagePairId);
		$this->template->testSets = $testSets;
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
	}
}
