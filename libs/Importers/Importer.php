<?php

/**
 * Importer is base class of all importers.
 *
 * Importer defines default behaviour of all importers such as loading configuration, sentences etc.
 * It implements Template  method design pattern so specific behaviour can be defined in child classes.
 */
abstract class Importer {

	protected $logger;

	public function __construct() {
		$this->logger = new EmptyLogger();
	}

	public function setLogger( $logger ) {
		$this->logger = $logger;
	}

	public function setNormalizer( $normalizer ) {
		$this->normalizer = $normalizer;
	}

	public function importFromFolders( array $folders ) {
		$folders = array_values($folders);
		foreach ($folders as $i => $folder) {
			$isFirst = 0 === $i;
			$isLast = count($folders) - 1 === $i;
			var_dump("startin folder $i $isFirst $isLast");
			try {
				$config = array( 'url_key' => $folder->getName() );
				$metadata = array( 'test_set_id' => -1, 'task_id' => -1 );

				$config = $this->getConfig( $folder );

				$this->logImportStart( $config );
				$metadata = $this->processMetadata( $config );
				$sentences = $this->parseResources( $folder, $config );
				$this->processSentences( $config, $metadata, $sentences, $isFirst, $isLast);

				$this->logImportSuccess( $config );
				$this->showImported( $metadata );
				$folder->lock( 'imported' );
			} catch( \IteratorsLengthsMismatchException $exception ) {
				$this->handleNotMatchingNumberOfSentences( $config['url_key'] );
				$this->handleImportError( $folder, $metadata );
			} catch( \ImporterException $exception ) {
				$this->logImportAbortion( $config, $exception );
				$this->handleImportError( $folder, $metadata );
			} catch( Exception $exception ) {
				$this->logger->log( $exception->getMessage() );
				$this->handleImportError( $folder, $metadata );
			}
		}
	}

	protected abstract function logImportStart( $config );

	protected abstract function logImportSuccess( $config );

	protected function logImportAbortion( $config, ImporterException $exception ) {
		$this->logger->log( "{$exception->getMessage()}" );
		$this->logger->log( "Parsing of {$config['url_key']} aborted!" );
	}

	protected function handleImportError( $folder, $metadata ) {
		$folder->lock( 'notimported' );
		$this->deleteUnimported( $metadata );
	}

	protected abstract function processMetadata( $config );

	protected abstract function processSentences( $config, $metadata, $sentences );

	protected abstract function getResources();

	protected abstract function getDefaults( Folder $folder );

	protected abstract function showImported( $metadata );

	protected abstract function deleteUnimported( $metadata );

	protected function getSentences( \Folder $folder, $filename ) {
		$filepath = $folder->getChildrenPath( $filename );
		$normalizer = $this->normalizer;

		return new \MapperIterator( new \FileSentencesIterator( $filepath ), function( $sentence ) use ( $normalizer ) {
			return $normalizer->normalize( $sentence );
		} );
	}

	protected function parseResources( Folder $folder, $config ) {
		$sentences = array();
		foreach( $this->getResources() as $resource ) {
			$sentences[$resource] = $this->parseResource( $folder, $resource, $config );
		}

		return $sentences;
	}

	private function parseResource( $folder, $resource, $config ) {
		try {
			if ( !$folder->fileExists( $config[ $resource ] ) ) {
				throw new ImporterException( "{$config[$resource]} used as a $resource source doesn't exist" );
			}

			$this->logger->log( "{$config[$resource]} used as a $resource source." );

			$sentences =  $this->getSentences( $folder, $config[$resource] );
			$count = $sentences->count();

			$this->logger->log( "{$folder->getName()} has $count $resource sentences" );

			return $sentences;
		} catch( InvalidSentencesResourceException $exception ) {
			throw new ImporterException( "Missing {$resource} sentences in {$config['url_key']}" );
		}
	}

	protected function handleNotMatchingNumberOfSentences( $name ) {
		$this->logger->log( "$name has bad number of sentences" );
		$this->logger->log( "Parsing of $name aborted!" );
	}

	protected function getConfig( Folder $folder ) {
		$configPath = $folder->getChildrenPath( 'config.neon' );
		$defaults = $this->getDefaults( $folder );

		try {
			return new \ResourcesConfiguration( $configPath, $defaults );
		} catch ( Exception $exception ) {
			throw new ImporterException( "Error occured during parsing of the configuration file $configPath: {$exception->getMessage()}" );
		}
	}

}
