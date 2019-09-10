#!/bin/bash

paramsHelp="This script requires two parameters. The first one is the URL of MT-ComparEval. The second one is the path to the CSV file containing paths to the test sets to import. The expected CSV format is: \"language-pairs-id,name,description,domain,source,reference\"."

if [ "$#" -ne 2 ]; then
    echo "Illegal number of arguments."
    echo "$paramsHelp"
    echo "Stopping."
    exit
fi

IFS=','

FILE=$2
if test -f "$FILE"; then
  if [ ${FILE: -4} == ".csv" ]; then
    while read line; do
      if [ -z "$line" ]; then
        :
      elif [ "$line" == "language-pairs-id,name,description,domain,source,reference" ]; then
        :
      else
        read -ra params <<< "$line"
        curl -X POST $1/api/testsets/upload -F "source=@${params[4]}" -F "name=${params[1]}" -F "language-pairs-id=${params[0]}" -F "description=${params[2]}" -F "domain=${params[3]}" -F "reference=@${params[5]}"
      fi
    done <$FILE
    exit
  else
    echo "The provided file doesn't have the right extension (.csv). Stopping."
    exit
  fi
else
  echo "The file $FILE doesn't exist. Stopping."
  exit
fi

