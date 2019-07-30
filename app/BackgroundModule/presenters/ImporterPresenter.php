<?php

namespace BackgroundModule;

/**
 * Template method implementation for creating processes for various importers
 *
 * Can be run by php -f www/index.php Background:Name_Of_Child:Import --folder=./some/path
 */
abstract class ImporterPresenter extends \Nette\Application\UI\Presenter {

    protected $importer;

  	public function renderImport( $folder ) {

      $folders = array(new \Folder( new \SplFileInfo( $folder ) ) );

      $engineId = end(explode('-', $folder));
      $dataDir = dirname($folder, 2);

      $taskDirs = array_filter(glob($dataDir . '/*/*-' . $engineId), 'is_dir');
      foreach($taskDirs as $taskDir) {
        if ($taskDir != $folder) {
          $taskFolder = new \Folder( new \SplFileInfo( $taskDir ) );
          array_push($folders, $taskFolder);
        }
      }

  		$this->importer->importFromFolders($folders);

  		$this->terminate();
  	}

  }
