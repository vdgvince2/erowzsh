-- ✅ 0) (Optionnel) Vérifier en amont
-- SELECT name, COUNT(*) c FROM categories GROUP BY name HAVING c>1;
-- /Applications/MAMP/Library/bin/mysql IE -u IE -ptest < /Applications/MAMP/htdocs/SH/data/fixDuplicate.sql

START TRANSACTION;

-- 1) Survivant par name (homepage=1 prioritaire, sinon MIN(id))
CREATE TEMPORARY TABLE tmp_survivors AS
SELECT
  name,
  COALESCE(MAX(IF(homepage=1, id, NULL)), MIN(id)) AS keep_id
FROM categories
GROUP BY name;

-- 2) Mapping des doublons: tous les id ≠ keep_id
CREATE TEMPORARY TABLE tmp_mapping AS
SELECT
  c.id       AS old_id,
  s.keep_id  AS new_id,
  c.name
FROM categories c
JOIN tmp_survivors s ON s.name = c.name
WHERE c.id <> s.keep_id;

-- 3) Journalisation des changements Ancien => Nouveau
CREATE TABLE IF NOT EXISTS category_merge_log (
  old_id   INT NOT NULL,
  new_id   INT NOT NULL,
  name     VARCHAR(255) NOT NULL,
  merged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (old_id, new_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO category_merge_log (old_id, new_id, name)
SELECT old_id, new_id, name
FROM tmp_mapping;

-- 4) Mise à jour des références dans keywords
UPDATE keywords k
JOIN tmp_mapping m ON k.main_category = m.old_id
SET k.main_category = m.new_id;

-- 5) Suppression des doublons dans categories (on garde le survivant)
DELETE c
FROM categories c
JOIN tmp_mapping m ON c.id = m.old_id;

-- 6) Verrouille l’unicité pour éviter le retour des doublons
-- ⚠️ Si des doublons subsistent (transaction interrompue avant delete), cette ligne échouera.
ALTER TABLE categories
  ADD UNIQUE KEY uniq_categories_name (name);

COMMIT;

-- (Optionnel) Contrôle final
-- SELECT name, COUNT(*) c FROM categories GROUP BY name HAVING c>1;
