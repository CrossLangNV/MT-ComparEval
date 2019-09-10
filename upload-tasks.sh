#!/bin/bash

paramsHelp="This script requires two parameters. The first one is the URL of MT-ComparEval. The second one is the path to the CSV file containing paths to the translations to import. The expected CSV format is: \"test_set_id,engine_id,description,translation\"."

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
      elif [ "$line" == "test_set_id,engine_id,description,translation" ]; then
        :
      else
        read -ra params <<< "$line"
        curl -X POST -F "description=${params[2]}" -F "test_set_id=${params[0]}" -F "engine_id=${params[1]}" -F "translation=@${params[3]}" $1/api/tasks/upload
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

