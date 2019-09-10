<?php

/**
 * Hjerson external metric implementation
 */
class SacreBleu implements IMetric {

	private $referenceText = array();
	private $translationText = array();
	private $cache = null;
	private $externalCommand = "sacrebleu";

	public function __construct(Nette\Caching\Cache $cache) {
		$this->cache = $cache;
	}

	public function init() {
		$this->referenceText = array();
		$this->translationText = array();
	}

	public function addSentence( $reference, $translation, $meta ) {
		$this->referenceText []= $reference;
		$this->translationText  []= $translation;

		return 0;
	}

	public function getScore() {
		if (count($this->referenceText) == 0 || count($this->translationText) == 0) {
			return -1;
		}

		list($return, $output) = $this->cache->call(array($this, 'runExternalCommandOnSentences'), $this->referenceText, $this->translationText);
		if ($return != 0){
			return -1;
		}

		return $this->processOutput($output);
	}

	public function runExternalCommandOnSentences($referenceText, $translationText) {
		$reference = $this->saveSentencesToFile($referenceText, "temp/ref");
		$hypothesis  = $this->saveSentencesToFile($translationText, "temp/hyp");
		$cmd = sprintf("%s -i %s -b %s", $this->externalCommand, $hypothesis, $reference);
		exec($cmd, $output, $return);
		unlink($reference);
		unlink($hypothesis);
		return array($return, $output);
	}

	private function saveSentencesToFile($sentences, $path) {
		$path .= md5(time());
		file_put_contents($path, implode("\n", $sentences));

		return $path;
	}

	private function processOutput($output){
		return $output;
	}

}
