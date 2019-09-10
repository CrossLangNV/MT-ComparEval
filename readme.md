# MT-ComparEval
MT-ComparEval is a tool for comparison and evaluation of machine translations.
It allows users to compare translations according to several criteria, such as:
 - automatic metrics of machine translation quality computed either on whole documents or single sentences
 - quality comparison of single sentence translation by highlighting conﬁrmed, improving and worsening n-grams
 - summaries of the most improving and worsening n-grams for the whole document.

MT-ComparEval also plots a chart with absolute differences of metrics computed on single sentences
  and a chart with values obtained from paired bootstrap resampling.
MT-ComparEval is distributed under Apache 2.0 license with an exception of [Highcharts.js](http://www.highcharts.com/) library
  which is distributed under [Creative Commons Attribution-NonCommercial 3.0 License](http://creativecommons.org/licenses/by-nc/3.0/).

# Try it online before installing on your server
- http://wmt.ufal.cz: all systems from the [WMT](http://www.statmt.org/wmt18/) 2014–2018
- http://mt-compareval.ufal.cz: upload and analyze your translations

# Papers
When using MT-ComparEval please cite:
 - Ondřej Klejch, Eleftherios Avramidis, Aljoscha Burchardt, Martin Popel: [MT-ComparEval: Graphical evaluation interface for Machine Translation development](http://ufal.mff.cuni.cz/pbml/104/art-klejch-et-al.pdf). The Prague Bulletin of Mathematical Linguistics, No. 104, 2015, pp. 63–74.

For a user-focused show-case study explaining most of the features, see:
 - Roman Sudarikov, Martin Popel, Ondrej Bojar, Aljoscha Burchardt, Ondřej Klejch: [Using MT-ComparEval](http://www.cracking-the-language-barrier.eu/wp-content/uploads/Sudarikov-etal.pdf). LREC 2016 [MT-Eval Workshop](http://www.cracking-the-language-barrier.eu/mt-eval-workshop-2016/). See [slides](http://ufal.mff.cuni.cz/~popel/papers/2016_05_24_using_mt-compareval.pdf) and a [poster](http://ufal.mff.cuni.cz/~popel/papers/2016_lrec_tools-and-guidelines_poster.pdf).

# Installation

## Advanced Installation with additional Metrics

The default configuration shipped with the software includes only the basic BLEU-related metrics. If you are interested only for these metrics, proceed to the next chapter "Basic Installation".

There is the possibility to enable additional metrics. At the moment, the tool "Hjerson" is supported, but it is optional. Its entries are commented out in the file `config.neon` , in folder `app\config`, after downloading the software. If you are interested in enabling Hjerson, you should edit this file and uncomment (remove the leading `#` from) the lines that contain the name of the metric. Then proceed to the installation as following.

## Basic Installation

### Docker (recommended)

Prerequisites:
- [Docker](https://docs.docker.com/install/)
- [Docker Compose](https://docs.docker.com/compose/install/)

Clone the application:
```
git clone https://github.com/CrossLangNV/MT-ComparEval
```

Build:
```
docker-compose build
```

Docker Compose uses volumes to map the files on the host to the container, data is persisted on the host.

### Ubuntu/Debian Linux

MT-ComparEval has been designed and tested on Ubuntu and Debian systems. Such an operating system is therefore suggested.

In order to be able to run MT-ComparEval several dependencies have to be installed.
Namely, PHP version 5.4 and Sqlite 3.
On Ubuntu 14.04 these dependencies can be installed with the following commands:
```
sudo apt-get install sqlite3 php5-cli php5-sqlite curl
```
On Ubuntu 16.04 use:
```
sudo apt install sqlite3 php7.0-cli php7.0-sqlite3 curl php7.0-mbstring php7.0-xml
```

Download or clone the application and set your commandline within the new directory. Then the application can be installed with the following command:
```
bash bin/install.sh
```
During the installation you may be asked to enter GitHub OAuth token.
Just follow the instructions (open the url in your browser, generate the token and enter it).

### Windows

 - Download and install the following prerequisites: [GIT](https://git-scm.com/download/win), [Composer](https://getcomposer.org/download), [GNU CoreUtils Complete package, except sources](http://gnuwin32.sourceforge.net/packages/coreutils.htm) in the default locations.
 - Download [Python 2.7.x](https://www.python.org/downloads/release/python-2714) and install in the Program Files folder, in a subfolder named Python27 (The Program Files folder is normally `C:\Program Files\` but it may vary depending on the language and the installation of your system.
 - Open the Windows Explorer. Browse to the Program Files folder and create a new subfolder `sqlite`. Download [SQLite Precompiled Binaries for Windows](https://sqlite.org/download.html) (probably 64bit, this  depends on your version of Windows) and the [SQLite Tools Bundle](https://sqlite.org/download.html). Extract the contents directly in the subfolder `sqlite` that you created previously, withouth creating or including any other subfolder. A permissions question will appear, this is normal.
 - Using the Windows Explorer, go to `C:\` and create a directory `tools`. Download [MT-Compareval](https://github.com/choko/MT-ComparEval/archive/master.zip), open the zip file and copy the folder MT-ComparEval-master into `C:\tools` directory. Rename the folder from `MT-ComparEval-master` to `MT-ComparEval`.
 - Using the Windows Explorer, go to `C:\tools\MT-ComparEval\bin\win` and double click on `install` or `install.bat`. This will perform the installation. During the installation you may be asked to enter GitHub OAuth token. Just follow the instructions (open the url in your browser, generate the token and enter it).
 - If you have chosen different paths in the above steps, you have to edit install.bat and watcher.bat to reflect the accurate entries.


# Running MT-ComparEval

## Start the program

### Docker
Run as daemon:
```
docker-compose up -d
```

Application starts on [localhost:8080](http://localhost:8080).

#### Development

Recommended tools for development and debugging:
- [Visual Studio Code](https://code.visualstudio.com/docs/setup/setup-overview) with extensions:
  - Docker
  - Nette Latte + Neon
  - PHP Debug

#### Enable remote debugging with XDebug:

- First install PHP on your local machine (version 7.1 recommended)
- Installing the PHP Debug extension in vs code will normally create a file `.vscode/launch.json`, edit it to:
```
{
    "version": "0.2.0",
    "configurations": [

        {
            "name": "Listen for XDebug",
            "type": "php",
            "request": "launch",
            "port": 9001,
            "pathMappings": {
                "/app": "${workspaceFolder}/"
            },
        }
    ]
}
```

The `XDEBUG_CONFIG` environment variable is set in the Dockerfile
- `remote_port` should correspond with the port in `.vscode/launch.json`
- `remote_host` needs to be changed if host is Linux

Mark your breakpoints (click left of the line number) and click on `Start Debugging`.

### Linux
To start MT-ComparEval two processes have to be run:

 - `bin/server.sh` which starts the application server on the address [localhost:8080](http://localhost:8080)
  (you can can check/adapt `app/config/config.neon` first to set the main title, set of metrics etc. See the [default config](app/config/config.neon).)
 - `bin/watcher.sh` which monitors folder `data` for new test sets and tasks (the `data` folder must exist before you run `bin/watcher.sh`.)

### Windows
 - Open Windows Explorer and navigate to C:\tools\MT-ComparEval\bin\win.
 - Double-click on `server` or `server.bat` to start the web server. Leave the window open
 - Double-click on `watcher` or `watcher.bat` to start the watcher. Leave this window open too
 - Optionally: right click on these programs, create shortcuts and move the shortcuts on your desktop or in any convenient menu entry.
 - While these two programs are running, navigate your browser to the address [localhost:8080](http://localhost:8080)


## Structure of the `data` folder

Note: the original structure of the data folder has been changed in this version. The new structure is described here.

Folder `data` contains folders with *test sets* (e.g. `Eco-Fin-3`), which contains subfolders with *tasks* for each test set (e.g. `30-21`). For example:
```
data/
├─ Eco-Fin-3/
│  ├─ source.txt
│  ├─ reference.txt
│  ├─ 30-21/
│  │  └─ translation.txt
│  └─ 30-24/
│     └─ translation.txt
└─ Sub-Alt-5/
   ├─ source.txt
   ├─ reference.txt
   ├─ 45-12/
   │  └─ translation.txt
   └─ 45-18/
      └─ translation.txt
```

Each folder corresponds to one test set and it should contain the following files:
 - `source.txt` - a plain text file with sentences in source language (one sentence per line).
 - `reference.txt` - a plain text file with reference translations (in target language).
 - `config.neon` - (optionally) a configuration file with the following structure:
```
The name of the folder is the of the test set, followed by a dash and the ID of the corresponding language pair.

Inside config.neon:
name: Name of the test set
description: "Description of the test set\n can be multiline"
source: source.txt
reference: reference.txt
```
See http://ne-on.org/ for the syntax of neon files.
The `source` and the `reference` need to be defined only if you you choose non-default file names (not `source.txt` and `reference.txt`).

Individual machine translations called *tasks* are then stored in subfolders with the following files:
- `translation.txt` - a plain text file with translated sentences
- `config.neon` - (optionally) a configuration file with the following structure:
```

The name of the subfolder is the ID of the corresponding test set, followed by a dash and the ID of the corresponding engine.

Inside config.neon:
name: Name of the task
description: Description of the task
translation: translation.txt
precompute_ngrams: true
```

## API to create test sets, engines and tasks
A curl command to create a test set:
```bash
curl -X POST http://localhost:8080/api/testsets/upload -F "source=@source.txt" -F "test set name" -F "language-pairs-id=language pair ID" -F "description=test set description" -F "domain=test set domain" -F "reference=@reference.txt"
```

A curl command to create an engine:
```bash
curl -s -X POST http://localhost:8080/api/engine/new -F "name=engine name" -F "language-pairs-id=language pair id" -F "parent-id=parent engine ID"
```

A curl command to create a task:
```bash
curl -X POST http://localhost:8080/api/tasks/upload -F "description=task description" -F "test_set_id=test set id" -F "engine_id=engine id" -F "translation=@translation.txt"
```

For deleting test sets via API use `api/testsets/delete/<id>`.

## How to remove a task manually

* Retrieve the test set id from frontend. When you open a test set you can see the id in the URL.
* Stop watcher
* Remove task from folder `data/.../` (or if you want to reimport the task after watcher is restarted, delete the hidden files `.imported` and `.notimported`)
* Find out task id, e.g. `sqlite3 storage/database "SELECT id, name FROM tasks WHERE test_sets_id=XYZ"`;
* Delete task: `sqlite3 storage/database "sqlite3 storage/database "DELETE FROM tasks WHERE id=ABC";"`
* Restart watcher

# More about this updated version of MT-ComparEval

In order to accomodate the needs of researchers, MT-ComparEval has been adapted for a broader range of use cases.

## What has been added or changed?

### Experiment -> Test Set

The term experiment is no longer used in the new version of this tool. Instead, there are four core terms:
- language pair;
- test set;
- engine;
- task.

The meening of these terms and their usage can be clarified by exploring this example:
http://mtcompareval-ng.crosslang.com:8051/matrix

As one can see in the example, multiple language pairs can be added. Per language pair, one can add multiple test sets. Also, per language pair, one can add multiple engines. Test sets and engines of each language pair are presented in a table. Each cell of this table corresponds with a task. A task is the translation of the given test set by the given engine.

### The graphical comparison per language pair view

Below each language pair, a button has been added to grant access to the graphical comparison view. In this view, the engines that correspond to the chosen language pair are compared to each other, based upon the BLEU scores.

The following comparisons are provided. For each comparison, a graph and a table are visible.
- BLEU scores per engine per test set;
- BLEU score delta with a selected engine;
- BLEU scores per engine per test set, sorted per domain;
- BLEU score delta with a selected engine, sorted per domain.

### The engine parent-child hierarchy view

Another new view demonstrates the relationships between engines.

This view can be accessed by pressing the "View global engine hierarchy" button on the overview page. It can also be accessed by clicking an engine in one of the tables. In this case, only the engines related to the engine in question are displayed.

What one sees is a collection of nodes. Each node represents an engine. Engine name and BLEU score are displayed on the node.
If a parent-child relationship exists between two engines, they are connected by an arrow from the parent to the child. If the child's BLEU score is higher than the parent's, the arrow line is solid. In the other case, it is dashed.

### Possibility to download sentences that differ most/least between two engines or engine and reference

For each task, a possibility has been provided to download a selected number of sentences that differ most/least from the translation by another engine or from the reference.
The following parameters can be provided by the user:
- the number of sentences to download;
- whether one wants to download the most or the least differing sentences;
- the metric the comparison is based upon;
- to what the translation should be compared (reference or a translation by a different engine);
- the file format in which to download the sentences: CSV or XLIFF.

This view can be accessed by navigating to <application-address>/tasks/<first-task-id>-<second-task-id>/compare, and selecting the Download tab.

### Upload scripts

In order to facilitate uploading multiple test sets and tasks into MT-ComparEval at once, several bash scripts have been written:

1) upload-test-sets.sh

This script allows to upload multiple test sets at once. It requires two parameters. The first one is the URL of MT-ComparEval. The second one is the path to the CSV file containing the data to import. The expected CSV format is: "language-pairs-id,name,description,domain,source,reference". Each line of the CSV file corresponds to a test set to import.

2) upload-tasks.sh

This script allows to upload multiple tasks at once. It requires two parameters. The first one is the URL of MT-ComparEval. The second one is the path to the CSV file containing the data to import. The expected CSV format is: "test_set_id,engine_id,description,translation". Each line of the CSV file corresponds to a task to import.

3) upload-all.sh

This script allows to upload multiple language pairs, engines, test sets and tasks at once. It requires two parameters. The first one is the URL of MT-ComparEval. The second one is the path to the CSV file containing the data to import. The expected CSV format is: "source_language,target_language,test_set_name,test_set_description,test_set_domain,test_set_source,test_set_reference,engine_name,engine_parent_id,task_description,task_translation". Each line of the CSV file corresponds to a task to import. The script will try to create the language pair, the test set, the engine corresponding to the described task. If those entities exist already, the script will use the existing ones.

