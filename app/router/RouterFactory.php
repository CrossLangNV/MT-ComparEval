<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\CliRouter,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	private $consoleMode = false;

	public function __construct( $consoleMode ) {
		$this->consoleMode = $consoleMode;
	}

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();
		if( $this->consoleMode ) {
			$router[] = new CliRouter();
		} else {
			$router[] = new Route('index.php', 'TestSets:matrix', Route::ONE_WAY);
			$router[] = new Route('matrix', 'TestSets:matrix');
			$router[] = new Route('list', 'TestSets:list');
			$router[] = new Route('api/sentences', 'Api:Sentences:default');
			$router[] = new Route('api/sentences/by-id', 'Api:Sentences:byId');
			$router[] = new Route('api/tasks', 'Api:Tasks:default');
			$router[] = new Route('api/tasks/upload', 'Api:Tasks:upload');
			$router[] = new Route('api/testsets/upload', 'Api:TestSets:upload');
			$router[] = new Route('api/testsets/status/<id>', 'Api:TestSets:status');
			$router[] = new Route('api/testsets/delete/<id>', 'Api:TestSets:delete');
			$router[] = new Route('api/language-pair/new', 'Api:TestSets:createLanguagePair');
			$router[] = new Route('api/metrics', 'Api:Metrics:default');
			$router[] = new Route('api/metrics/scores', 'Api:Metrics:scores');
			$router[] = new Route('api/metrics/scores-in-test-set', 'Api:Metrics:scoresInTestSet');
			$router[] = new Route('api/metrics/results', 'Api:Metrics:results');
			$router[] = new Route('api/metrics/samples', 'Api:Metrics:samples');
			$router[] = new Route('api/metrics/samples-diff', 'Api:Metrics:samplesDiff');
			$router[] = new Route('api/ngrams/confirmed', 'Api:NGrams:confirmed');
			$router[] = new Route('api/ngrams/unconfirmed', 'Api:NGrams:unconfirmed');
			$router[] = new Route('tasks/<id1>-<id2>/compare', 'Tasks:compare');
			$router[] = new Route('tasks/<id>/detail', 'Tasks:detail');
			$router[] = new Route('<presenter>/<action>[/<id>]', 'TestSets:matrix');
			// $router[] = new Route('', 'TestSets:dummy');
		}

		return $router;
	}

}
