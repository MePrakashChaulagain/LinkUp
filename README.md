# 💬 LinkUp - Real-Time Chat Application (Work in Progress)

[![PHP Version](https://img.shields.io/badge/PHP-8.0+-blue.svg)](https://php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://www.mysql.com/)
[![Project Status](https://img.shields.io/badge/status-active%20development-yellowgreen)](https://github.com/yourusername/linkup)

LinkUp is a full-stack web application developed as part of my journey into web development. While currently in active development and not yet hosted publicly, this evolving project demonstrates growing proficiency with core web technologies through its implemented features and planned enhancements.

![LinkUp Preview](https://via.placeholder.com/800x400.png?text=LinkUp+Preview+Coming+Soon)

## 🚀 Features

- ✅ **Secure User Authentication** with password hashing
- 💬 **Real-Time Messaging** with history preservation
- 🟢 **Active Status Tracking** (Online/Offline)
- 📷 **Profile Management** with avatar uploads
- 📜 **Message History** with automatic cleanup
- 📱 **Responsive Design** for all screen sizes

## 🛠 Tech Stack

**Frontend:**  
![HTML5](https://img.shields.io/badge/-HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/-CSS3-1572B6?logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/-JavaScript-F7DF1E?logo=javascript&logoColor=black)

**Backend:**  
![PHP](https://img.shields.io/badge/-PHP-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/-MySQL-4479A1?logo=mysql&logoColor=white)

**Security:**  
🔒 Password Hashing  
🛡 Input Sanitization

## 📌 Project Status

🔧 **Active Development** - Continuously adding features and refining functionality  
📅 **Roadmap**:
- Message notifications
- Group chats
- Image sharing
- Message reactions

## 🧠 Development Insights

This project has been instrumental in understanding:
- Session management and state persistence
- Database design and optimization
- Real-time update patterns without WebSockets
- Client-server communication dynamics

## 🗄 Database Schema

```sql
-- Create Database
CREATE DATABASE IF NOT EXISTS linkup;
USE linkup;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255),
    bio VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP NULL DEFAULT NULL,
    is_logged_in TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages Table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seen TINYINT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Message Limitation Trigger
DELIMITER //
CREATE TRIGGER IF NOT EXISTS limit_messages
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    DECLARE msg_count INT;
    SET msg_count = (SELECT COUNT(*) FROM messages WHERE room_id = NEW.room_id);
    IF msg_count > 100 THEN
        DELETE FROM messages
        WHERE room_id = NEW.room_id
        ORDER BY timestamp ASC
        LIMIT 1;
    END IF;
END //
DELIMITER ;
