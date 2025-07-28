-- Ajout de la colonne introduction si elle n'existe pas
ALTER TABLE article
ADD COLUMN IF NOT EXISTS introduction TEXT AFTER slug;

-- Ajout de la colonne tags si elle n'existe pas
ALTER TABLE article
ADD COLUMN IF NOT EXISTS tags JSON AFTER introduction;

-- Mise à jour des noms de colonnes pour harmoniser id_article/id_article

-- 1. Sauvegarder les données existantes des tables liées
CREATE TEMPORARY TABLE IF NOT EXISTS temp_articles_categories AS 
SELECT * FROM articles_categories;

CREATE TEMPORARY TABLE IF NOT EXISTS temp_article_comment AS 
SELECT * FROM article_comment;

-- 2. Supprimer les contraintes de clé étrangère existantes
ALTER TABLE articles_categories 
    DROP FOREIGN KEY IF EXISTS articles_categories_ibfk_2;

ALTER TABLE article_comment 
    DROP FOREIGN KEY IF EXISTS article_comment_ibfk_1;

-- 3. Mise à jour des colonnes dans les tables liées
ALTER TABLE articles_categories 
    CHANGE COLUMN id_article id_article INT NOT NULL;

ALTER TABLE article_comment 
    CHANGE COLUMN id_article id_article INT NOT NULL;

-- 4. Réajout des contraintes de clé étrangère avec les nouveaux noms
ALTER TABLE articles_categories 
    ADD CONSTRAINT articles_categories_ibfk_2 
    FOREIGN KEY (id_article) REFERENCES article(id_article) 
    ON DELETE CASCADE;

ALTER TABLE article_comment 
    ADD CONSTRAINT article_comment_ibfk_1 
    FOREIGN KEY (id_article) REFERENCES article(id_article) 
    ON DELETE CASCADE;

-- 5. Vérification de l'intégrité des données
SELECT 'Vérification des articles_categories' as 'Info',
    COUNT(*) as 'Nombre total de relations',
    SUM(CASE WHEN ac.id_article IS NULL THEN 1 ELSE 0 END) as 'Relations invalides'
FROM articles_categories ac
LEFT JOIN article a ON ac.id_article = a.id_article;

SELECT 'Vérification des article_comment' as 'Info',
    COUNT(*) as 'Nombre total de commentaires',
    SUM(CASE WHEN ac.id_article IS NULL THEN 1 ELSE 0 END) as 'Commentaires invalides'
FROM article_comment ac
LEFT JOIN article a ON ac.id_article = a.id_article;
