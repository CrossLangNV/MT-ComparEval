<?php

/**
 * Importer implementation for importing language pairs into MT-ComparEval
 *
 * Configuration is read from configuration.neon file.
 */
class LanguagePairsImporter extends Importer {

	private $languagePairsModel;

	public function __construct( LanguagePairs $model ) {
		$this->languagePairsModel = $model;
	}

	protected function logImportStart( $config ) {
		$this->logger->log( "New language pair called {$config['url_key']} was found" );
	}

	protected function logImportSuccess( $config ) {
		$this->logger->log( "Language pair {$config['url_key']} uploaded successfully." );
	}

	protected function processMetadata( $config ) {
		$data = array(
			'source_language' => $config['source_language'],
			'target_language' => $config['target_language'],
			'url_key' => $config['url_key']
		);

		return array( 'language_pair_id' => $this->languagePairsModel->saveLanguagePair( $data ) );
	}

	protected function deleteUnimported( $metadata ) {
		$this->languagePairsModel->deleteLanguagePair( $metadata[ 'language_pair_id' ] );
	}

	protected function showImported( $metadata ) {
		$this->languagePairsModel->setVisible( $metadata[ 'language_pair_id' ] );
	}

	protected function processSentences( $config, $metadata, $sentences ) {
		$languagePairId = $metadata['language_pair_id'];

		// $this->languagePairsModel->addSentences( $testSetId, new \ZipperIterator( $sentences, TRUE ) );
	}

	protected function getResources() {
		return array( 'source_language', 'target_language' );
	}

	protected function getDefaults( Folder $languagePairFolder ) {
		return array(
			'name' => $languagePairFolder->getName(),
			'url_key' => $languagePairFolder->getName(),
			'description' => '',
			'source' => 'source.txt',
			'reference' => 'reference.txt'
		);
	}
}
