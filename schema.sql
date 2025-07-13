CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    imdb_id VARCHAR(50),
    tmdb_id VARCHAR(50),
    rotten_tomatoes_id VARCHAR(50)
);

CREATE TABLE series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    imdb_id VARCHAR(50),
    tmdb_id VARCHAR(50),
    rotten_tomatoes_id VARCHAR(50)
);

CREATE TABLE seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    series_id INT,
    season_number INT NOT NULL,
    imdb_id VARCHAR(50),
    tmdb_id VARCHAR(50),
    rotten_tomatoes_id VARCHAR(50),
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
    UNIQUE (series_id, season_number)
);

CREATE TABLE episodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT,
    episode_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    imdb_id VARCHAR(50),
    tmdb_id VARCHAR(50),
    rotten_tomatoes_id VARCHAR(50),
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    UNIQUE (season_id, episode_number)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'user') NOT NULL
);

CREATE TABLE editor_permissions (
    user_id INT PRIMARY KEY,
    can_add BOOLEAN DEFAULT FALSE,
    can_edit BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE movie_languages (
    movie_id INT,
    language_id INT,
    PRIMARY KEY (movie_id, language_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE
);

CREATE TABLE movie_genres (
    movie_id INT,
    genre_id INT,
    PRIMARY KEY (movie_id, genre_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

CREATE TABLE series_languages (
    series_id INT,
    language_id INT,
    PRIMARY KEY (series_id, language_id),
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE
);

CREATE TABLE series_genres (
    series_id INT,
    genre_id INT,
    PRIMARY KEY (series_id, genre_id),
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

CREATE TABLE season_languages (
    season_id INT,
    language_id INT,
    PRIMARY KEY (season_id, language_id),
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE
);

CREATE TABLE season_genres (
    season_id INT,
    genre_id INT,
    PRIMARY KEY (season_id, genre_id),
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

CREATE TABLE episode_languages (
    episode_id INT,
    language_id INT,
    PRIMARY KEY (episode_id, language_id),
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE
);

CREATE TABLE episode_genres (
    episode_id INT,
    genre_id INT,
    PRIMARY KEY (episode_id, genre_id),
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

