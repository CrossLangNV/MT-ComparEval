#!/bin/bash

paramsHelp="This script requires two parameters. The first one is the URL of MT-ComparEval. The second one is the path to the CSV file containing the data to import. The expected CSV format is: \"source_language,target_language,test_set_name,test_set_description,test_set_domain,test_set_source,test_set_reference,engine_name,engine_parent_id,task_description,task_translation\"."

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
      elif [ "$line" == "source_language,target_language,test_set_name,test_set_description,test_set_domain,test_set_source,test_set_reference,engine_name,engine_parent_id,task_description,task_translation" ]; then
        :
      else
        read -ra params <<< "$line"
        LP_ID=$(curl -s -X POST $1/api/language-pair/new -F "source-language=${params[0]}" -F "target-language=${params[1]}" | jq '.language_pair_id')
        TEST_SET_ID=$(curl -s -X POST $1/api/testsets/upload -F "source=@${params[5]}" -F "name=${params[2]}" -F "language-pairs-id=$LP_ID" -F "description=${params[3]}" -F "domain=${params[4]}" -F "reference=@${params[6]}" | jq '.test_set_id')
        if [ -z ${params[8]} ]; then
          params[8]="NULL"
        fi
        ENGINE_ID=$(curl -s -X POST $1/api/engine/new -F "name=${params[7]}" -F "language-pairs-id=$LP_ID" -F "parent-id=${params[8]}" | jq '.engine_id')
        curl -X POST -F "description=${params[9]}" -F "test_set_id=$TEST_SET_ID" -F "engine_id=$ENGINE_ID" -F "translation=@${params[10]}" $1/api/tasks/upload
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

