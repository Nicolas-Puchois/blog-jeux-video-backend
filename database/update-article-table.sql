-- Ajout de la colonne introduction si elle n'existe pas
ALTER TABLE article
ADD COLUMN IF NOT EXISTS introduction TEXT AFTER slug;

-- Ajout de la colonne tags si elle n'existe pas
ALTER TABLE article
ADD COLUMN IF NOT EXISTS tags JSON AFTER introduction;
