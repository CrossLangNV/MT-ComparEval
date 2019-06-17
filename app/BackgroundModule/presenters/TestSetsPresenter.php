<?php

namespace BackgroundModule;

/**
 * Implementation of ImporterPresenter for running process for importing test sets
 *
 * Can be run by php -f www/index.php Background:TestSets:Import --folder=./data/testset
 */
class TestSetsPresenter extends ImporterPresenter {

	public function __construct( \TestSetsImporter $importer ) {
		$this->importer = $importer;
	}

}
