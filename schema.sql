CREATE TABLE "language_pairs" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "source_language" text NOT NULL,
  "target_language" text NOT NULL,
  "url_key" text NOT NULL UNIQUE,
  "visible" integer(0) NULL,
  UNIQUE("source_language","target_language")
);


CREATE TABLE "test_sets" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "language_pairs_id" integer NOT NULL,
  "name" text NOT NULL,
  "url_key" text NOT NULL UNIQUE,
  "description" text NOT NULL,
  "domain" text NOT NULL,
  "visible" integer(0) NULL,
  UNIQUE("name","language_pairs_id"),
  FOREIGN KEY ("language_pairs_id") REFERENCES "language_pairs" ("id") ON DELETE CASCADE
);


CREATE TABLE "sentences" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "test_sets_id" integer NOT NULL,
  "source" text NOT NULL,
  "reference" text NOT NULL,
  FOREIGN KEY ("test_sets_id") REFERENCES "test_sets" ("id") ON DELETE CASCADE
);


CREATE TABLE "engines" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "language_pairs_id" integer NOT NULL,
  "parent_id" integer DEFAULT NULL,
  "name" text NOT NULL,
  "url_key" text NOT NULL UNIQUE,
  "bleu" float NOT NULL DEFAULT 0,
  "visible" integer(0) NULL,
  UNIQUE("name","language_pairs_id"),
  FOREIGN KEY ("language_pairs_id") REFERENCES "language_pairs" ("id") ON DELETE CASCADE,
  FOREIGN KEY ("parent_id") REFERENCES "engines" ("id") ON DELETE SET NULL
);


CREATE TABLE "tasks" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "test_sets_id" integer NOT NULL,
  "engines_id" integer NOT NULL,
  "url_key" text NOT NULL UNIQUE,
  "description" text NULL,
  "visible" integer(0) NULL,
  UNIQUE("test_sets_id","engines_id"),
  FOREIGN KEY ("test_sets_id") REFERENCES "test_sets" ("id") ON DELETE CASCADE,
  FOREIGN KEY ("engines_id") REFERENCES "engines" ("id") ON DELETE CASCADE
);


CREATE TABLE "translations" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "tasks_id" integer NOT NULL,
  "sentences_id" integer NOT NULL,
  "text" text NOT NULL,
  UNIQUE("tasks_id","sentences_id") ON CONFLICT IGNORE,
  FOREIGN KEY ("tasks_id") REFERENCES "tasks" ("id") ON DELETE CASCADE,
  FOREIGN KEY ("sentences_id") REFERENCES "sentences" ("id") ON DELETE CASCADE
);


CREATE TABLE "metrics" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" text NOT NULL
);


INSERT INTO `metrics` (`id`, `name`) VALUES (0, 'BREVITY-PENALTY');
INSERT INTO `metrics` (`id`, `name`) VALUES (1, 'BLEU-cased');
INSERT INTO `metrics` (`id`, `name`) VALUES (2, 'BLEU');
INSERT INTO `metrics` (`id`, `name`) VALUES (3, 'PRECISION-cased');
INSERT INTO `metrics` (`id`, `name`) VALUES (4, 'PRECISION');
INSERT INTO `metrics` (`id`, `name`) VALUES (5, 'RECALL-cased');
INSERT INTO `metrics` (`id`, `name`) VALUES (6, 'RECALL');
INSERT INTO `metrics` (`id`, `name`) VALUES (7, 'F-MEASURE-cased');
INSERT INTO `metrics` (`id`, `name`) VALUES (8, 'F-MEASURE');
INSERT INTO `metrics` (`id`, `name`) VALUES (9, 'H-WORDORDER-cased');
INSERT INTO `metrics` (`id`, `name`) VALUES (10, 'H-WORDORDER');
INSERT INTO `metrics` (`id`, `name`) VALUES (11, 'H-ADDITION-cased');
INSERT INTO `metrics` (`id`, `name`) VALUES (12, 'H-ADDITION');
INSERT INTO `metrics` (`id`, `name`) VALUES (13, 'H-MISTRANSLATION-cased');
INSERT INTO `metrics` (`id`, `name`) VALUES (14, 'H-MISTRANSLATION');
INSERT INTO `metrics` (`id`, `name`) VALUES (15, 'H-OMISSION-cased');
INSERT INTO `metrics` (`id`, `name`) VALUES (16, 'H-OMISSION');
INSERT INTO `metrics` (`id`, `name`) VALUES (17, 'H-FORM-cased');
INSERT INTO `metrics` (`id`, `name`) VALUES (18, 'H-FORM');
INSERT INTO `metrics` (`id`, `name`) VALUES (19, 'TER');


CREATE TABLE "translations_metrics" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "translations_id" integer NOT NULL,
  "metrics_id" integer NOT NULL,
  "score" real NOT NULL,
  UNIQUE("translations_id","metrics_id"),
  FOREIGN KEY ("translations_id") REFERENCES "translations" ("id") ON DELETE CASCADE,
  FOREIGN KEY ("metrics_id") REFERENCES "metrics" ("id") ON DELETE CASCADE
);


CREATE TABLE "tasks_metrics" (
  "tasks_id" integer NOT NULL,
  "metrics_id" integer NOT NULL,
  "score" real NOT NULL,
  UNIQUE("tasks_id","metrics_id"),
  FOREIGN KEY ("tasks_id") REFERENCES "tasks" ("id") ON DELETE CASCADE,
  FOREIGN KEY ("metrics_id") REFERENCES "metrics" ("id") ON DELETE CASCADE
);


CREATE TABLE "tasks_metrics_samples" (
  "tasks_id" integer NOT NULL,
  "metrics_id" integer NOT NULL,
  "sample_position" integer NOT NULL,
  "score" real NOT NULL,
  FOREIGN KEY ("tasks_id") REFERENCES "tasks" ("id") ON DELETE CASCADE,
  FOREIGN KEY ("metrics_id") REFERENCES "metrics" ("id") ON DELETE CASCADE
);


CREATE TABLE "confirmed_ngrams" (
  "translations_id" integer NOT NULL,
  "text" text NOT NULL,
  "length" integer NOT NULL,
  "position" integer NOT NULL,
  FOREIGN KEY ("translations_id") REFERENCES "translations" ("id") ON DELETE CASCADE
);


CREATE TABLE "unconfirmed_ngrams" (
  "translations_id" integer NOT NULL,
  "text" text NOT NULL,
  "length" integer NOT NULL,
  "position" integer NOT NULL,
  FOREIGN KEY ("translations_id") REFERENCES "translations" ("id") ON DELETE CASCADE
);
