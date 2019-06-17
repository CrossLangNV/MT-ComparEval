<?php

namespace ApiModule;

/**
 * TasksPresenter is used for serving list of task in test set from REST API
 */
class TasksPresenter extends BasePresenter {

	private $tasksModel;
	private $testSetsModel;

	public function __construct( \Nette\Http\Request $httpRequest, \Tasks $tasksModel, \TestSets $testSetsModel ) {
		parent::__construct( $httpRequest );
		$this->tasksModel = $tasksModel;
		$this->testSetsModel = $testSetsModel;
	}

	public function renderDefault( $testSetId ) {
		$parameters = $this->context->getParameters();
		$show_administration = $parameters[ "show_administration" ];

		$response = array();
		$response[ 'tasks' ] = array();
		foreach( $this->tasksModel->getTasks( $testSetId ) as $task ) {
			$taskResponse[ 'id' ] = $task->id;
			$taskResponse[ 'name' ] = $task->name;
			$taskResponse[ 'description' ] = $task->description;
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
		$name = $this->getPostParameter( 'name' );
		$url_key = \Nette\Utils\Strings::webalize( $name );
		$description = $this->getPostParameter( 'description' );
		$test_set_id = $this->getPostParameter( 'test_set_id' );
		$translation = $this->getPostFile( 'translation' );
		$engines_id = $this->getPostParameter( 'engines_id' );

		$data = array(
			'name' => $name,
			'description' => $description,
			'url_key' => $url_key,
			'test_sets_id' => $test_set_id,
			'engines_id' => $engines_id
		);

		$testSet = $this->testSetsModel->getTestSetById( $test_set_id );
		$path = __DIR__ . '/../../../data/' . $testSet->url_key . '/' . $url_key . '/';
		$translation->move( $path . 'translation.txt' );
		file_put_contents( $path . 'config.neon', "name: $name\ndescription: $description\nurl_key: $url_key" );

		$response = array( 'task_id' => $this->tasksModel->saveTask( $data ) );

		if ( $this->getPostParameter( 'redirect', False ) ) {
			$this->flashMessage( "Task was successfully uploaded. It will appear in this list once it is imported.", "success" );
			$this->redirect( ":Tasks:list", $test_set_id );
		} else {
			$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
		}

	}

}
