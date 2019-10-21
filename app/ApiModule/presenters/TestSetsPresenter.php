<?php

namespace ApiModule;

class TestSetsPresenter extends BasePresenter {

	private $testSetsModel;
	private $languagePairsModel;
	private $enginesModel;
	private $tasksModel;

	public function __construct( \Nette\Http\Request $httpRequest, \TestSets $testSetsModel, \LanguagePairs $languagePairsModel , \Engines $enginesModel, \Tasks $tasksModel) {
		parent::__construct( $httpRequest );
		$this->testSetsModel = $testSetsModel;
		$this->languagePairsModel = $languagePairsModel;
		$this->enginesModel = $enginesModel;
		$this->tasksModel = $tasksModel;
	}

	public function renderUpload() {
		$name = $this->getPostParameter( 'name' );
		$languagePairsId = $this->getPostParameter( 'language-pairs-id' );
		$url_key = \Nette\Utils\Strings::webalize( $name . "-" . $languagePairsId);
		$description = $this->getPostParameter( 'description' );
		$domain = $this->getPostParameter( 'domain' );
		$source = $this->getPostFile( 'source' );
		$reference = $this->getPostFile( 'reference' );

		$data = array(
			'name' => $name,
			'description' => $description,
			'domain' => $domain,
			'url_key' => $url_key,
			'language_pairs_id' => $languagePairsId
		);

		$path = __DIR__ . '/../../../data/' . $url_key . '/';
		$source->move( $path . 'source.txt' );
		$reference->move( $path . 'reference.txt' );
		file_put_contents( $path . 'config.neon', "name: $name\ndescription: $description\ndomain: $domain\nurl_key: $url_key\nlanguage_pairs_id: $languagePairsId" );

		$response = array( 'test_set_id' => $this->testSetsModel->saveTestSet( $data ) );

		if ( $this->getPostParameter( 'redirect', False ) ) {
			$this->flashMessage( "Test set was successfully uploaded. It will appear in this overview once it is imported.", "success" );
			$this->redirect( ":TestSets:matrix" );
		} else {
			$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
		}
	}

	public function renderStatus( $id ) {
		$testSets = $this->testSetsModel->getTestSetById( $id );
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
		$response = array( 'status' => (bool) $this->testSetsModel->deleteTestSet( $id ) );

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}

	public function renderCreateLanguagePair() {
		$sourceLanguage = $this->getPostParameter( 'source-language' );
		$targetLanguage = $this->getPostParameter( 'target-language' );
		$url_key = \Nette\Utils\Strings::webalize( $sourceLanguage . "-" . $targetLanguage );

		$data = array(
			'source_language' => $sourceLanguage,
			'target_language' => $targetLanguage,
			'url_key' => $url_key,
			'visible' => 1
		);

		$response = array('language_pair_id' => $this->languagePairsModel->saveLanguagePair($data));

		if ( $this->getPostParameter( 'redirect', False ) ) {
			$this->flashMessage( "The language pair was created successfully.", "success" );
			$this->redirect( ":TestSets:matrix" );
		} else {
			$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
		}
	}

	public function renderDeleteLanguagePair( $id ) {
		$testSetsOfLanguagePair = $this->testSetsModel->getTestSetsByLanguagePairId($languagePairId);

		$ok = true;

		foreach($testSetsOfLanguagePair as $testSet) {
			$testSetDeleted = (bool) $this->testSetsModel->deleteTestSet( $testSet['id'] );
			$ok = (bool) ($ok * $testSetDeleted);
		}

		if ($ok) {
			$response = array( 'status' => (bool) $this->testSetsModel->deleteTestSet( $id ) );
		}
		else {
			$response = array( 'status' => false );
		}

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}

	public function renderAddEngine() {
		$name = $this->getPostParameter( 'name' );
		$languagePairsId = $this->getPostParameter( 'language-pairs-id' );
		$url_key = \Nette\Utils\Strings::webalize( $name . "-" . $languagePairsId );
		$parentId = $this->getPostParameter( 'parent-id' );
		if ($parentId == "NULL") {
			$parentId == NULL;
		}

		$data = array(
			'name' => $name,
			'language_pairs_id' => $languagePairsId,
			'url_key' => $url_key,
			'parent_id' => $parentId,
			'visible' => 1
		);

		if ($_FILES['yaml']['error'] == 0) {
			$yaml = $this->getPostFile('yaml');
			$path = __DIR__ . '/../../../engines-data/' . $url_key . '/';
			$yaml->move( $path . 'config.yaml' );
		}

		if ($_FILES['model']) {
			$modelDirPath = __DIR__ . '/../../../engines-data/' . $url_key . '/model';
			mkdir($modelDirPath);
			foreach($_FILES['model']['name'] as $index => $name) {
				if ($_FILES['model']['error'][$index] == 0) {
					$tmpFilePath = $_FILES['model']['tmp_name'][$index];
					if ($tmpFilePath != "") {
						$newFilePath = $modelDirPath . '/' . $name;
						move_uploaded_file($tmpFilePath, $newFilePath);
					}
				}
			}
		}

		if ($_FILES['bpe']) {
			$bpeDirPath = __DIR__ . '/../../../engines-data/' . $url_key . '/bpe-model';
			mkdir($bpeDirPath);
			foreach($_FILES['bpe']['name'] as $index => $name) {
				if ($_FILES['bpe']['error'][$index] == 0) {
					$tmpFilePath = $_FILES['bpe']['tmp_name'][$index];
					if ($tmpFilePath != "") {
						$newFilePath = $bpeDirPath . '/' . $name;
						move_uploaded_file($tmpFilePath, $newFilePath);
					}
				}
			}
		}
			
		$response = array('engine_id' => $this->enginesModel->saveEngine($data));

		if ( $this->getPostParameter( 'redirect', False ) ) {
			$this->flashMessage( "The engine was added successfully.", "success" );
			$this->redirect( ":TestSets:matrix" );
		} else {
			$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
		}
	}

	public function renderDeleteEngine( $engineId ) {
		$tasksOfEngine = $this->tasksModel->getTasksByEngineId($engineId);

		$ok = true;

		foreach($tasksOfEngine as $task) {
			$taskDeleted = (bool) $this->tasksModel->deleteTask( $task['id'] );
			$ok = (bool) ($ok * $taskDeleted);
		}

		if ($ok) {
			$response = array( 'status' => (bool) $this->enginesModel->deleteEngine( $engineId ) );
		}
		else {
			$response = array( 'status' => false );
		}

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}

	public function renderDownloadSource( $id ) {
		$testSet = $this->testSetsModel->getTestSetById($id);
		$filePath = '../data/' . $testSet['url_key'] . '/source.txt';
		if (!file_exists($filePath)) {
			$this->terminate();
		}

		header("Content-disposition: attachment; filename=source.txt");
		header("Content-type: text/plain");
		readfile($filePath);

		$this->terminate();
	}

	public function renderDownloadReference( $id ) {
		$testSet = $this->testSetsModel->getTestSetById($id);
		$filePath = '../data/' . $testSet['url_key'] . '/reference.txt';
		if (!file_exists($filePath)) {
			$this->terminate();
		}

		header("Content-disposition: attachment; filename=reference.txt");
		header("Content-type: text/plain");
		readfile($filePath);

		$this->terminate();
	}

	public function renderDownloadEngineFiles( $id ) {
		$engine = $this->enginesModel->getEngineById($id);
		$path = __DIR__ . '/../../../engines-data/' . $engine['url_key'];

		$zipname = 'engine-training-files-' . $engine['name'] . '.zip';
		$zip = new \ZipArchive;
		$zip->open($zipname, \ZipArchive::CREATE);

		$configFile = $path . '/config.yaml';
		$download_file = file_get_contents($configFile);
		$zip->addFromString($engine['name'] . '/' . basename($configFile), $download_file);

		foreach (new \DirectoryIterator($path . '/model/') as $modelFile) {
			if($modelFile->isDot()) continue;
			$download_file = file_get_contents($modelFile->getPathname());
			$zip->addFromString($engine['name'] . '/model/' . basename($modelFile), $download_file);
		}

		foreach (new \DirectoryIterator($path . '/bpe-model/') as $modelFile) {
			if($modelFile->isDot()) continue;
			$download_file = file_get_contents($modelFile->getPathname());
			$zip->addFromString($engine['name'] . '/bpe-model/' . basename($modelFile), $download_file);
		}

		$zip->close();

		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename='.$zipname);
		header('Content-Length: ' . filesize($zipname));
		readfile($zipname);

		$this->terminate();
	}

}
