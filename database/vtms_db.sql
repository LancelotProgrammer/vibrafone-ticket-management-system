DROP DATABASE vtms_db;
CREATE DATABASE IF NOT EXISTS vtms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vtms_db;
CREATE TABLE IF NOT EXISTS countries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_name VARCHAR(64) UNIQUE NOT NULL,
    ar_country_name VARCHAR(64) NOT NULL,
    en_country_name VARCHAR(64) NOT NULL,
    country_code VARCHAR(8) UNIQUE NOT NULL,
    dial_code VARCHAR(8) NOT NULL
);
CREATE TABLE IF NOT EXISTS levels (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(64) NOT NULL,
    code VARCHAR(64) NOT NULL,
    description VARCHAR(512)
);
CREATE TABLE IF NOT EXISTS types (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(64) NOT NULL,
    description VARCHAR(512)
);
CREATE TABLE IF NOT EXISTS priorities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(64) NOT NULL,
    description VARCHAR(512),
    type_id BIGINT UNSIGNED NOT NULL,
    CONSTRAINT FK_Priorities_Types FOREIGN KEY (type_id) REFERENCES types (id) ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS departments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(64) NOT NULL,
    code VARCHAR(64) NOT NULL,
    description VARCHAR(512)
);
CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(64) NOT NULL,
    description VARCHAR(512)
);
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(64) NULL DEFAULT NULL,
    email VARCHAR(128) UNIQUE NOT NULL,
    password TEXT NULL,
    company TEXT NULL,
    email_verified_at DATETIME NULL DEFAULT NULL,
    remember_token VARCHAR(256) NULL DEFAULT NULL,
    blocked_at DATETIME NULL DEFAULT NULL,
    country_id INT NULL DEFAULT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    level_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT NOW(),
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE NOW(),
    CONSTRAINT FK_Users_Countries FOREIGN KEY (country_id) REFERENCES countries (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Users_Levels FOREIGN KEY (level_id) REFERENCES levels (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Users_Departments FOREIGN KEY (department_id) REFERENCES departments (id) ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS tickets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ticket_identifier VARCHAR(64) NOT NULL,
    type_id BIGINT UNSIGNED NOT NULL,
    level_id BIGINT UNSIGNED NOT NULL,
    priority_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(64) NOT NULL,
    description VARCHAR(512) NOT NULL,
    company TEXT NULL,
    ne_product VARCHAR(64) NOT NULL,
    sw_version VARCHAR(64) NOT NULL,
    work_order VARCHAR(64) NULL DEFAULT NULL,
    sub_work_order VARCHAR(64) NULL DEFAULT NULL,
    status VARCHAR(64) NULL DEFAULT NULL,
    handler VARCHAR(64) NULL DEFAULT NULL,
    start_at DATETIME NULL DEFAULT NULL,
    end_at DATETIME NULL DEFAULT NULL,
    attachments json NULL,
    created_at DATETIME DEFAULT NOW(),
    deleted_at DATETIME NULL DEFAULT NULL,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE NOW(),
    CONSTRAINT FK_Tickets_Types FOREIGN KEY (type_id) REFERENCES types (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Tickets_Level FOREIGN KEY (level_id) REFERENCES levels (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Tickets_Priorities FOREIGN KEY (priority_id) REFERENCES priorities (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Tickets_Departments FOREIGN KEY (department_id) REFERENCES departments (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Tickets_Categories FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE CASCADE ON DELETE RESTRICT
);
-- CREATE TABLE IF NOT EXISTS ticket_chat (
--     ticket_id BIGINT UNSIGNED NOT NULL,
--     user_id BIGINT UNSIGNED NOT NULL,
--     message VARCHAR(512),
--     created_at DATETIME DEFAULT NOW(),
--     updated_at DATETIME NULL DEFAULT NULL ON UPDATE NOW(),
--     CONSTRAINT FK_TicketChat_Tickets FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE CASCADE ON DELETE CASCADE,
--     CONSTRAINT FK_TicketChat_Users FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
--     CONSTRAINT UQ_TicketChat_TicketId_UserId UNIQUE (ticket_id, user_id)
-- );
CREATE TABLE IF NOT EXISTS ticket_customer (
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    CONSTRAINT FK_TicketCustomer_Tickets FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT FK_TicketCustomer_Users FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT UQ_TicketTechnicalSupport_TicketId_UserId UNIQUE (ticket_id, user_id)
);
CREATE TABLE IF NOT EXISTS ticket_technical_support (
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    CONSTRAINT FK_TicketTechnicalSupport_Tickets FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT FK_TicketTechnicalSupport_Users FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT UQ_TicketTechnicalSupport_TicketId_UserId UNIQUE (ticket_id, user_id)
);
CREATE TABLE IF NOT EXISTS ticket_high_technical_support (
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    CONSTRAINT FK_TicketHighTechnicalSupport_Tickets FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT FK_TicketHighTechnicalSupport_Users FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT UQ_TicketTechnicalSupport_TicketId_UserId UNIQUE (ticket_id, user_id)
);
CREATE TABLE IF NOT EXISTS ticket_histories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    owner VARCHAR(64) NOT NULL,
    title VARCHAR(512) NOT NULL,
    body VARCHAR(512) NULL DEFAULT NULL,
    work_order VARCHAR(64) NULL DEFAULT NULL,
    sub_work_order VARCHAR(64) NULL DEFAULT NULL,
    status VARCHAR(64) NULL DEFAULT NULL,
    handler VARCHAR(64) NULL DEFAULT NULL,
    attachments json NULL,
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE NOW(),
    CONSTRAINT FK_TicketHistory_Tickets FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE CASCADE ON DELETE CASCADE
);
-- CREATE TABLE IF NOT EXISTS blogs (
--     id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--     title VARCHAR(128) NOT NULL,
--     content TEXT NOT NULL,
--     published_at DATETIME NULL DEFAULT NULL,
--     created_at DATETIME DEFAULT NOW(),
--     updated_at DATETIME NULL DEFAULT NULL ON UPDATE NOW(),
--     INDEX idx_published_at (published_at)
-- );
-- CREATE TABLE IF NOT EXISTS knowledges (
--     id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--     title VARCHAR(128) NOT NULL,
--     content TEXT NOT NULL,
--     published_at DATETIME NULL DEFAULT NULL,
--     created_at DATETIME DEFAULT NOW(),
--     updated_at DATETIME NULL DEFAULT NULL ON UPDATE NOW(),
--     INDEX idx_published_at (published_at)
-- );
CREATE TABLE IF NOT EXISTS contacts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(64) NOT NULL,
    email VARCHAR(64) NOT NULL,
    subject VARCHAR(128) NOT NULL,
    description VARCHAR(512) NOT NULL,
    read_at DATETIME NULL DEFAULT NULL,
    is_important BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE NOW(),
    INDEX idx_read_at (read_at)
);
-- CREATE TABLE IF NOT EXISTS frequently_asked_question_groups (
--     id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--     title VARCHAR(64) NOT NULL,
--     description TEXT NULL DEFAULT NULL,
--     created_at DATETIME DEFAULT NOW(),
--     updated_at DATETIME NULL DEFAULT NULL ON UPDATE NOW()
-- );
-- CREATE TABLE IF NOT EXISTS frequently_asked_questions (
--     id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
--     question TEXT NOT NULL,
--     answer TEXT NOT NULL,
--     group_id BIGINT UNSIGNED NOT NULL,
--     created_at DATETIME DEFAULT NOW(),
--     updated_at DATETIME NULL DEFAULT NULL ON UPDATE NOW(),
--     CONSTRAINT FK_FrequentlyAskedQuestions_FrequentlyAskedQuestionGroups FOREIGN KEY (group_id) REFERENCES frequently_asked_question_groups (id) ON UPDATE CASCADE ON DELETE RESTRICT
-- );
