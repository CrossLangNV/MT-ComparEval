<?php

namespace ApiModule;

class TestSetsPresenter extends BasePresenter {

	private $model;

	public function __construct( \Nette\Http\Request $httpRequest, \TestSets $model ) {
		parent::__construct( $httpRequest );
		$this->model = $model;
	}

	public function renderUpload() {
		$name = $this->getPostParameter( 'name' );
		$url_key = \Nette\Utils\Strings::webalize( $name );
		$description = $this->getPostParameter( 'description' );
		$source = $this->getPostFile( 'source' );
		$reference = $this->getPostFile( 'reference' );

		$data = array(
			'name' => $name,
			'description' => $description,
			'url_key' => $url_key
		);

		$path = __DIR__ . '/../../../data/' . $url_key . '/';
		$source->move( $path . 'source.txt' );
		$reference->move( $path . 'reference.txt' );
		file_put_contents( $path . 'config.neon', "name: $name\ndescription: $description\nurl_key: $url_key" );

		$response = array( 'test_set_id' => $this->model->saveTestSet( $data ) );

		if ( $this->getPostParameter( 'redirect', False ) ) {
			$this->flashMessage( "Test set was successfully uploaded. It will appear in this list once it is imported.", "success" );
			$this->redirect( ":TestSets:list" );
		} else {
			$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
		}
	}

	public function renderStatus( $id ) {
		$testSets = $this->model->getTestSetById( $id );
		$tasks = $testSets->related( 'tasks' );
		$allTasksImported = array_reduce( $tasks->fetchAll(), function( $acc, $cur ) { return $acc && $cur->visible == 1; }, TRUE );

		$response = array(
			'test_set_imported' => @$testSet->visible == 1,
			'all_tasks_imported' => $allTasksImported,
			'url' => $this->link( '//:Tasks:list', $id ),
		);

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}

	public function renderDelete( $id ) {
		$response = array( 'status' => (bool) $this->model->deleteTestSet( $id ) );

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}

	public function renderCreateLanguagePair() {
		$sourceLanguage = $this->getPostParameter( 'sourceLanguage' );
		$targetLanguage = $this->getPostParameter( 'targetLanguage' );
		$url_key = \Nette\Utils\Strings::webalize( $sourceLanguage . "-" . $targetLanguage );

		$data = array(
			'sourceLanguage' => $sourceLanguage,
			'targetLanguage' => $targetLanguage,
			'url_key' => $url_key
		);

		exit();

		// call the model create method here

		if ( $this->getPostParameter( 'redirect', False ) ) {
			$this->flashMessage( "The language pair was created successfully. It will appear in this list once it is imported.", "success" );
			$this->redirect( ":TestSets:matrix" );
		} else {
			$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
		}
	}

}
