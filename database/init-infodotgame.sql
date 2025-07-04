DROP DATABASE IF EXISTS infodotgame;

CREATE DATABASE IF NOT EXISTS infodotgame;

use infodotgame;

CREATE TABLE `user` (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    email_token VARCHAR(255),
    verified_at TIMESTAMP NULL,
    is_verified BOOLEAN,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user'
);

CREATE TABLE game (
    id_game INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(100),
    platform VARCHAR(100),
    developer VARCHAR(100),
    release_date DATE,
    publisher VARCHAR(100),
    cover_image VARCHAR(255)
);

CREATE TABLE genre (
    id_genre INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE game_genre (
    id_game_genre INT AUTO_INCREMENT PRIMARY KEY,
    id_game INT NOT NULL,
    id_genre INT NOT NULL,
    FOREIGN KEY (id_game) REFERENCES game(id_game) ON DELETE CASCADE,
    FOREIGN KEY (id_genre) REFERENCES genre(id_genre) ON DELETE CASCADE
);

CREATE TABLE review (
    id_review INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    rating INT CHECK (rating >= 0 AND rating <= 10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_user INT NOT NULL,
    id_game INT NOT NULL,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_game) REFERENCES game(id_game) ON DELETE CASCADE
);

CREATE TABLE comments (
    id_comments INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_user INT NOT NULL,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE
);

CREATE TABLE review_comment (
    id_review_comment INT AUTO_INCREMENT PRIMARY KEY,
    id_review INT NOT NULL,
    id_comments INT NOT NULL,
    FOREIGN KEY (id_review) REFERENCES review(id_review) ON DELETE CASCADE,
    FOREIGN KEY (id_comments) REFERENCES comments(id_comments) ON DELETE CASCADE
);

CREATE TABLE article (
    id_articles INT AUTO_INCREMENT PRIMARY KEY,
    cover_image VARCHAR(255),
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    id_user INT,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE SET NULL
);

CREATE TABLE categories (
    id_categories INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE articles_categories (
    id_articles_categories INT AUTO_INCREMENT PRIMARY KEY,
    id_categories INT NOT NULL,
    id_articles INT NOT NULL,
    FOREIGN KEY (id_categories) REFERENCES categories(id_categories) ON DELETE CASCADE,
    FOREIGN KEY (id_articles) REFERENCES article(id_articles) ON DELETE CASCADE
);

CREATE TABLE article_comment (
    id_article_comment INT AUTO_INCREMENT PRIMARY KEY,
    id_articles INT NOT NULL,
    id_comments INT NOT NULL,
    FOREIGN KEY (id_articles) REFERENCES article(id_articles) ON DELETE CASCADE,
    FOREIGN KEY (id_comments) REFERENCES comments(id_comments) ON DELETE CASCADE
);
