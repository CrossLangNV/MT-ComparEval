<?php

/**
 * Importer implementation for importing engines into MT-ComparEval
 *
 * Configuration is read from configuration.neon file.
 */
class EnginesImporter extends Importer {

	private $enginesModel;

	public function __construct( Engines $model ) {
		$this->enginesModel = $model;
	}

	protected function logImportStart( $config ) {
		$this->logger->log( "New engine called {$config['url_key']} was found" );
	}

	protected function logImportSuccess( $config ) {
		$this->logger->log( "Engine {$config['url_key']} uploaded successfully." );
	}

	protected function processMetadata( $config ) {
		$data = array(
			'language_pairs_id' => $config['language_pairs_id'],
			'name' => $config['name'],
			'url_key' => $config['url_key']
		);

		return array( 'engine_id' => $this->enginesModel->saveEngine( $data ) );
	}

	protected function deleteUnimported( $metadata ) {
		$this->enginesModel->deleteEngine( $metadata[ 'engine_id' ] );
	}

	protected function showImported( $metadata ) {
		$this->enginesModel->setVisible( $metadata[ 'engine_id' ] );
	}

	protected function processSentences( $config, $metadata, $sentences ) {
		$engineId = $metadata['engine_id'];

		// $this->languagePairsModel->addSentences( $testSetId, new \ZipperIterator( $sentences, TRUE ) );
	}

	protected function getResources() {
		return array('name');
	}

	protected function getDefaults( Folder $engineFolder ) {
		return array(
			// 'name' => $languagePairFolder->getName(),
			// 'url_key' => $languagePairFolder->getName(),
			// 'description' => '',
			// 'source' => 'source.txt',
			// 'reference' => 'reference.txt'
		);
	}
}
