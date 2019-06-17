<?php

namespace BackgroundModule;

/**
 * Process implementation for watching given folder and running appropriate imports
 *
 * Can be run by php -f www/index.php Backgroung:Watcher:Watch --folder=./data
 */
class WatcherPresenter extends \Nette\Application\UI\Presenter {

	public function renderWatch( $folder, $sleep = 500000 ) {
		echo "Watcher is watching folder: $folder\n";

		while( TRUE ) {
			usleep( $sleep );

			foreach( $this->getUnimportedTestSets( $folder ) as $testSet ) {
				$this->runImportForTestSet( $testSet );
			}

			foreach( $this->getUnimportedTasks( $folder ) as $task ) {
				$this->runImportForTask( $task );
			}
		}

		$this->terminate();
	}

	private function getUnimportedTestSets( $folder ) {
		return \Nette\Utils\Finder::findDirectories( '*' )
			->in( $folder )
			->imported( FALSE )
			->aborted( FALSE );
	}

	private function getUnimportedTasks( $folder ) {
		$importedTestSets = \Nette\Utils\Finder::findDirectories( '*' )
			->in( $folder )
			->imported( TRUE )
			->toArray();

		if( count( $importedTestSets ) == 0 ) {
			return array();
		}

		return \Nette\Utils\Finder::findDirectories( '*' )
			->in( $importedTestSets )
			->imported( FALSE )
			->aborted( FALSE );
	}

	private function runImportForTestSet( $testSet ) {
		$action = "Background:TestSets:Import";

		$this->runCommand( $action, $testSet );
	}

	private function runImportForTask( $task ) {
		$action = "Background:Tasks:Import";

		$this->runCommand( $action, $task );
	}

	private function runCommand( $action, $folder ) {
		$scriptPath = __DIR__ . '/../../../www/index.php';
		$command = "php -f $scriptPath $action --folder=$folder | tee $folder/import.log";

		$return = 0;
		passthru( $command, $return );

		if ( $return != 0 ) {
			$folder = new \Folder( $folder );
			$folder->lock( 'unimported' );
		}
	}

}
