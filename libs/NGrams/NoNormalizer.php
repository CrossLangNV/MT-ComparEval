<?php

/**
 * NoNormalizer does no processing on the sentences
 *
 */
class NoNormalizer implements INormalizer {

	public function normalize( $sentence ) {
		return $sentence;
	}

}
