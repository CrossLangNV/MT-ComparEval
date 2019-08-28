# Future work

## Export

Some export possibilities have already been implemented.
Further possibilities:
- export post editing information along with the sentences (the discussion about storing post-editing information in the xliff format, and the format specifications for that, is currently ongoing)
- export tables and figures to csv, LaTeX, pdf...

## Possibility to import mulitple test sets (!), tasks, engines, language pairs

This could be done by importing a csv.

## Multiple test set comparison

This is already implemented; possible improvements:
- measure how well an engine does for a domain
- add a metric for how significat a certain score is
- currently, the comparisons are based upon BLEU scores. Make this configurable so that other metrics can be used for comparison as well.
- make comparison of a subset of test sets possible (currently: all test sets / per domain)

## Improvement of global engine hierarchy

UX can be improved.

## Sample data

E.g.: with sacreBLEU you can download WMT test sets. Provide a similar possibility in the application.

## Multi-user environment

Implement a signup/login system. Each user has their own data.

