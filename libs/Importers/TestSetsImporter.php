<?php

/**
 * Importer implementation for importing test sets into MT-ComparEval
 *
 * TestSetsImporter extracts source sentences and reference sentences from the given
 * folder and imports them into the database. It uses defaults values for names of files
 * containing source and reference.
 * TestSetsImporter choose default default name for test set same as name of the folder
 * that the test set is located in. The name can be overriden in configuration.
 *
 * Configuration of test set is read from configuration.neon file.
 */
class TestSetsImporter extends Importer {

	private $testSetsModel;

	public function __construct( TestSets $model ) {
		$this->testSetsModel = $model;
	}

	protected function logImportStart( $config ) {
		$this->logger->log( "New test set called {$config['url_key']} was found" );
	}

	protected function logImportSuccess( $config ) {
		$this->logger->log( "Test set {$config['url_key']} uploaded successfully." );
	}

	protected function processMetadata( $config ) {
		$data = array(
			'name' => $config['name'],
			'description' => $config['description'],
			'url_key' => $config['url_key'],
			'language_pairs_id' => $config['language_pairs_id'],
		);

		return array( 'test_set_id' => $this->testSetsModel->saveTestSet( $data ) );
	}

	protected function processSentences( $config, $metadata, $sentences ) {
		$testSetId = $metadata['test_set_id'];

		$this->testSetsModel->addSentences( $testSetId, new \ZipperIterator( $sentences, TRUE ) );
	}

	protected function getResources() {
		return array( 'source', 'reference' );
	}

	protected function getDefaults( Folder $testSetFolder ) {
		return array(
			'name' => $testSetFolder->getName(),
			'url_key' => $testSetFolder->getName(),
			'description' => '',
			'source' => 'source.txt',
			'reference' => 'reference.txt'
		);
	}

	protected function deleteUnimported( $metadata ) {
		$this->testSetsModel->deleteTestSet( $metadata[ 'test_set_id' ] );
	}

	protected function showImported( $metadata ) {
		$this->testSetsModel->setVisible( $metadata[ 'test_set_id' ] );
	}
}
