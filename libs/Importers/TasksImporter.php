<?php

/**
 * Importer implementation for importing tasks into MT-ComparEval
 *
 * TasksImporter loads translations from specified file (either by configuration or default value).
 * For all loaded sentences it computes all metrics in 'case-sensitive' and 'case-insensitive' mode.
 * It also computes metrics for whole documents. Then it computes significance intervals using
 * Bootstrap Resampling and then it searches for top improving and worsening n-grams.
 *
 * All these functionalities are provided to TasksImporter by DI via __construct.
 */
class TasksImporter extends Importer {

	private $testSetsModel;
	private $ngramsModel;
	private $tasksModel;
	private $sampler;
	private $preprocessor;
	private $metrics;
	private $engines;

	public function __construct( TestSets $testSetsModel, Tasks $tasksModel, NGrams $ngramsModel, BootstrapSampler $sampler, Preprocessor $preprocessor, $metrics, Engines $engines ) {
		$this->testSetsModel = $testSetsModel;
		$this->ngramsModel = $ngramsModel;
		$this->tasksModel = $tasksModel;
		$this->sampler = $sampler;
		$this->preprocessor = $preprocessor;
		$this->metrics = $metrics;
		$this->engines = $engines;
	}

	protected function logImportStart( $config ) {
		$this->logger->log( "Importing task: {$config['test_set']['url_key']}:{$config['url_key']}" );
	}

	protected function logImportSuccess( $config ) {
		$this->logger->log( "Task {$config['url_key']} uploaded successfully" );
	}

	protected function processMetadata( $config ) {
		$data = array(
			'description' => $config['description'],
			'test_sets_id' => $config['test_sets_id'],
			'engines_id' => $config['engines_id'],
			'url_key' => $config['test_sets_id'] . "-" . $config['engines_id']
		);

		return array( 'task_id' => $this->tasksModel->saveTask( $data ) );
	}

	protected function processSentences( $config, $metadata, $rawSentences, $storeTaskData = true, $storeEngineData = false) {
		$sentenceMetrics = array();

		foreach( array( FALSE, TRUE ) as $isCaseSensitive ) {
			$preprocessor = $this->preprocessor;
			$sentences = new MapperIterator(
				new \ZipperIterator( $rawSentences, TRUE ),
				function( $sentence ) use ( $preprocessor, $isCaseSensitive ) {
					$sentence[ 'is_case_sensitive' ] = $isCaseSensitive;

					return $preprocessor->preprocess( $sentence );
				}
			);

			$metrics = array();
			foreach( $this->metrics as $name => $metric ) {
				if( $metric[ 'case_sensitive' ] !== $isCaseSensitive ) {
					continue;
				}

				$metric = $metric[ 'class' ];

		  	if ($storeTaskData) {
			  	$metric->init();
			  }

				$metrics[ $name ] = $metric;

		  	if ($storeTaskData) {
				  $sentenceMetrics[ $name ] = array();
				}

			}

			foreach( $sentences as $sentence ) {
				foreach( $metrics as $name => $metric ) {
					$sentenceMetrics[ $name ][] = $metric->addSentence( $sentence['test_set']['reference'], $sentence['translation'], $sentence['meta'] );
				}
			}

			if ($storeEngineData) {
	    	foreach( $metrics as $name => $metric ) {
	    		if ($name == "BLEU") {
	    			$thisTask = $this->tasksModel->getTask($metadata['task_id']);
			    	$this->engines->updateBleu($thisTask['engines_id'], $metric->getScore());
	    		}
		    }
			}

			if ($storeTaskData) {
				foreach( $metrics as $name => $metric ) {
					var_dump("storing metric " . $metadata['task_id']);
					$this->tasksModel->addMetric( $metadata['task_id'], $name, $metric->getScore() );
				}

				foreach( $metrics as $name => $metric ) {
					if( $this->metrics[ $name ][ 'compute_bootstrap' ] !== TRUE ) {
						continue;
					}

					$this->logger->log( "Generating $name samples for {$config['url_key']}." );
					$samples = $this->sampler->generateSamples( $metric, iterator_to_array( $sentences ) );
					$this->tasksModel->addSamples( $metadata['task_id'], $name, $samples );
					$this->logger->log( "Samples generated." );
				}
			}
		}

		$this->tasksModel->addSentences( $metadata['task_id'], $sentences, $sentenceMetrics );

		if( $config[ 'precompute_ngrams' ] ) {
			$this->logger->log( "Precomputing n-grams for {$config['url_key']}." );
			$this->ngramsModel->precomputeNgrams( $config['test_set']['id'], $metadata['task_id'] );
			$this->logger->log( "N-grams precomputation done." );
		}
	}

	protected function parseResources( Folder $folder, $config ) {
		$sentences = parent::parseResources( $folder, $config );
		$sentences['test_set'] = $this->testSetsModel->getSentences( $config['test_set']['id'] );

		return $sentences;
	}

	protected function getResources() {
		return array( 'translation' );
	}

	protected function getDefaults( Folder $folder ) {
		return array(
			'url_key' => $folder->getName(),
			'test_set' => $this->testSetsModel->getTestSetByName( $folder->getParent()->getName() ),
			'description' => '',
			'translation' => 'translation.txt',
			'precompute_ngrams' => true
		);
	}

	protected function deleteUnimported( $metadata ) {
		$this->tasksModel->deleteTask( $metadata[ 'task_id' ], FALSE );
	}

	protected function showImported( $metadata ) {
		$this->tasksModel->setVisible( $metadata[ 'task_id' ] );
	}

}


