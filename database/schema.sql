-- Database Schema

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Chat messages table
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    sender_type ENUM('user', 'agent', 'bot') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_id VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_session_id (session_id)
);


-- Sample data for testing
INSERT INTO users (email, first_name, last_name, phone) VALUES
('john.doe@example.com', 'John', 'Doe', '+1234567890'),
('jane.smith@example.com', 'Jane', 'Smith', '+1234567891'),
('bob.wilson@example.com', 'Bob', 'Wilson', '+1234567892'),
('alice.johnson@example.com', 'Alice', 'Johnson', '+1234567893');

INSERT INTO chat_messages (user_id, message, sender_type, session_id) VALUES
(1, 'Hello, I need help with my account', 'user', 'session_001'),
(1, 'I can help you with that. What specific issue are you experiencing?', 'agent', 'session_001'),
(1, 'I cannot log in to my account', 'user', 'session_001'),
(2, 'Hi, I have a question about billing', 'user', 'session_002'),
(2, 'Sure, I can help you with billing questions. What would you like to know?', 'agent', 'session_002');
