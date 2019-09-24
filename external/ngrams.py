# using https://stackoverflow.com/questions/32441605/generating-ngrams-unigrams-bigrams-etc-from-a-large-corpus-of-txt-files-and-t

from itertools import chain
import argparse
import json

def n_grams(seq, n=1):
    """Returns an iterator over the n-grams given a list_tokens"""
    shift_token = lambda i: (el for j,el in enumerate(seq) if j>=i)
    shifted_tokens = (shift_token(i) for i in range(n))
    tuple_ngrams = zip(*shifted_tokens)
    return tuple_ngrams # if join in generator : (" ".join(i) for i in tuple_ngrams)

def range_ngrams(list_tokens, ngram_range=(1,2)):
    """Returns an itirator over all n-grams for n in range(ngram_range) given a list_tokens."""
    return chain(*(n_grams(list_tokens, i) for i in range(*ngram_range)))

cli=argparse.ArgumentParser()
cli.add_argument(
  "--lista",  
  nargs="*",
  type=str
)

args = cli.parse_args()
tokens = args.lista

out = [list(item) for item in range_ngrams(tokens, (1, 5))]
print(out)
# json.dumps(dict(out))