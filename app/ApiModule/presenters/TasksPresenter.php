<?php

namespace ApiModule;

/**
 * TasksPresenter is used for serving list of task in test set from REST API
 */
class TasksPresenter extends BasePresenter {

	private $tasksModel;
	private $testSetsModel;
	private $enginesModel;

	public function __construct( \Nette\Http\Request $httpRequest, \Tasks $tasksModel, \TestSets $testSetsModel, \Engines $enginesModel ) {
		parent::__construct( $httpRequest );
		$this->tasksModel = $tasksModel;
		$this->testSetsModel = $testSetsModel;
		$this->enginesModel = $enginesModel;
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

}
