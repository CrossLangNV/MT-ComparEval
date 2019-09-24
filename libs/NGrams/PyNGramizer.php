<?php

/**
 * PyNGramizer is used for generating all n-grams in sentence
 */
class PyNGramizer {

	private $tokenizer;
	private $externalCommand;
	private $externalScript;

	public function __construct( Tokenizer $tokenizer ) {
		$this->tokenizer = $tokenizer;
		$this->externalCommand = "python";
		$this->externalScript = "external/ngrams.py";
	}

	public function getNGrams( $sentence ) {
		$tokens = $this->tokenizer->tokenize( $sentence );
		$ngrams = json_decode($this->runExternalCommand($tokens), true);
		return $ngrams;
	}

	public function runExternalCommand( $tokenizedSentence ) {
		$tokensFmt = trim("%s %s --lista " . str_repeat("%s ", count($tokenizedSentence)));
		$args = array_merge(array($this->$externalCommand, $this->$externalScript), $tokenizedSentence);
		$cmd = vsprintf($tokensFmt, $args);
		exec($cmd, $output);
		return $this->processOutput($output);
	}

	public function processOutput( $output ) {
		// Removing the outer list brackets
		$data = substr($data,1,-1);

		$myArr = array();
		// Will get a 3 dimensional array, one dimension for each list
		$myArr = explode('],', $data);

		// Removing last list bracket for the last dimension
		if(count($myArr)>1)
		$myArr[count($myArr)-1] = substr($myArr[count($myArr)-1],0,-1);

		// Removing first last bracket for each dimenion and breaking it down further
		foreach ($myArr as $key => $value) {
			$value = substr($value,1);
			$myArr[$key] = array();
			$myArr[$key] = explode(',',$value);
		}
		return $myArr;
	}

}
