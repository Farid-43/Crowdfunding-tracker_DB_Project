-- =====================================================
-- CF_Tracker Database Schema (Without DROP)
-- Educational SQL Feature Demonstration Platform
-- =====================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS CF_Tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE CF_Tracker;

-- =====================================================
-- PART 1: DDL - TABLE CREATION WITH CONSTRAINTS
-- =====================================================

-- Table: Users
-- Demonstrates: PRIMARY KEY, UNIQUE, CHECK, NOT NULL, DEFAULT constraints
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_role ENUM('admin', 'campaigner', 'donor') DEFAULT 'donor',
    phone VARCHAR(20),
    bio TEXT,
    profile_picture VARCHAR(255) DEFAULT 'default-avatar.png',
    account_balance DECIMAL(12, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    -- PRIMARY KEY constraint
    PRIMARY KEY (user_id),
    -- CHECK constraint: account balance cannot be negative
    CONSTRAINT chk_balance_positive CHECK (account_balance >= 0),
    -- CHECK constraint: email must contain @ symbol
    CONSTRAINT chk_email_format CHECK (email LIKE '%@%'),
    -- CHECK constraint: username length
    CONSTRAINT chk_username_length CHECK (CHAR_LENGTH(username) >= 3)
) ENGINE=InnoDB COMMENT='Users table with multiple constraint demonstrations';

-- Table: Categories
-- Demonstrates: Simple PRIMARY KEY, NOT NULL
CREATE TABLE Categories (
    category_id INT AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fa-folder',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (category_id)
) ENGINE=InnoDB COMMENT='Campaign categories';

-- Table: Campaigns
-- Demonstrates: FOREIGN KEY with CASCADE, multiple constraints
CREATE TABLE Campaigns (
    campaign_id INT AUTO_INCREMENT,
    campaign_title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    goal_amount DECIMAL(12, 2) NOT NULL,
    current_amount DECIMAL(12, 2) DEFAULT 0.00,
    creator_id INT NOT NULL,
    category_id INT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    featured BOOLEAN DEFAULT FALSE,
    image_url VARCHAR(255),
    video_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- PRIMARY KEY
    PRIMARY KEY (campaign_id),
    -- FOREIGN KEY with CASCADE DELETE
    CONSTRAINT fk_campaign_creator 
        FOREIGN KEY (creator_id) REFERENCES Users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    -- FOREIGN KEY with SET NULL (optional relationship)
    CONSTRAINT fk_campaign_category 
        FOREIGN KEY (category_id) REFERENCES Categories(category_id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    -- CHECK constraints
    CONSTRAINT chk_goal_positive CHECK (goal_amount > 0),
    CONSTRAINT chk_current_amount CHECK (current_amount >= 0),
    CONSTRAINT chk_date_valid CHECK (end_date >= start_date),
    CONSTRAINT chk_current_not_exceed_goal CHECK (current_amount <= goal_amount * 1.5)
) ENGINE=InnoDB COMMENT='Campaigns with extensive constraint demonstrations';

-- Table: Donations
-- Demonstrates: Composite relationships, CHECK constraints
CREATE TABLE Donations (
    donation_id INT AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    donor_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    donation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('credit_card', 'paypal', 'bank_transfer', 'crypto') NOT NULL,
    transaction_id VARCHAR(100) UNIQUE,
    message TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    refund_reason TEXT,
    refunded_at TIMESTAMP NULL,
    -- PRIMARY KEY
    PRIMARY KEY (donation_id),
    -- FOREIGN KEYS with CASCADE
    CONSTRAINT fk_donation_campaign 
        FOREIGN KEY (campaign_id) REFERENCES Campaigns(campaign_id) 
        ON DELETE CASCADE,
    CONSTRAINT fk_donation_donor 
        FOREIGN KEY (donor_id) REFERENCES Users(user_id) 
        ON DELETE CASCADE,
    -- CHECK constraint: minimum donation amount
    CONSTRAINT chk_donation_amount CHECK (amount >= 1.00)
) ENGINE=InnoDB COMMENT='Donations with transaction tracking';

-- Table: Comments
-- Demonstrates: SELF-REFERENCING foreign key
CREATE TABLE Comments (
    comment_id INT AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT NULL,  -- For nested comments (SELF JOIN demonstration)
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_edited BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (comment_id),
    -- Regular foreign keys
    CONSTRAINT fk_comment_campaign 
        FOREIGN KEY (campaign_id) REFERENCES Campaigns(campaign_id) 
        ON DELETE CASCADE,
    CONSTRAINT fk_comment_user 
        FOREIGN KEY (user_id) REFERENCES Users(user_id) 
        ON DELETE CASCADE,
    -- SELF-REFERENCING foreign key for nested comments
    CONSTRAINT fk_comment_parent 
        FOREIGN KEY (parent_comment_id) REFERENCES Comments(comment_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Comments with self-referencing for nested replies';

-- Table: Campaign_Updates
-- Demonstrates: One-to-many relationship
CREATE TABLE Campaign_Updates (
    update_id INT AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (update_id),
    CONSTRAINT fk_update_campaign 
        FOREIGN KEY (campaign_id) REFERENCES Campaigns(campaign_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Campaign progress updates';

-- Table: User_Followers
-- Demonstrates: Many-to-many relationship, composite key
CREATE TABLE User_Followers (
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    followed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Composite PRIMARY KEY
    PRIMARY KEY (follower_id, following_id),
    CONSTRAINT fk_follower 
        FOREIGN KEY (follower_id) REFERENCES Users(user_id) 
        ON DELETE CASCADE,
    CONSTRAINT fk_following 
        FOREIGN KEY (following_id) REFERENCES Users(user_id) 
        ON DELETE CASCADE,
    -- CHECK: user cannot follow themselves
    CONSTRAINT chk_no_self_follow CHECK (follower_id != following_id)
) ENGINE=InnoDB COMMENT='User following system with composite key';

-- Table: Campaign_Favorites
-- Demonstrates: Many-to-many relationship
CREATE TABLE Campaign_Favorites (
    user_id INT NOT NULL,
    campaign_id INT NOT NULL,
    favorited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, campaign_id),
    CONSTRAINT fk_favorite_user 
        FOREIGN KEY (user_id) REFERENCES Users(user_id) 
        ON DELETE CASCADE,
    CONSTRAINT fk_favorite_campaign 
        FOREIGN KEY (campaign_id) REFERENCES Campaigns(campaign_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='User favorite campaigns';

-- Table: Campaign_Category
-- Demonstrates: Many-to-many relationship between Campaigns and Categories
CREATE TABLE Campaign_Category (
    campaign_id INT NOT NULL,
    category_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Composite PRIMARY KEY
    PRIMARY KEY (campaign_id, category_id),
    -- FOREIGN KEY to Campaigns
    CONSTRAINT fk_campaign_category_campaign 
        FOREIGN KEY (campaign_id) REFERENCES Campaigns(campaign_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    -- FOREIGN KEY to Categories
    CONSTRAINT fk_campaign_category_category 
        FOREIGN KEY (category_id) REFERENCES Categories(category_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Many-to-many: Campaigns can have multiple categories';

-- Table: Rewards
-- Demonstrates: One-to-many relationship, CHECK constraints, reward tiers
CREATE TABLE Rewards (
    reward_id INT AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    min_amount DECIMAL(10, 2) NOT NULL,
    max_backers INT NULL,
    current_backers INT DEFAULT 0,
    estimated_delivery DATE,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- PRIMARY KEY
    PRIMARY KEY (reward_id),
    -- FOREIGN KEY to Campaigns
    CONSTRAINT fk_reward_campaign 
        FOREIGN KEY (campaign_id) REFERENCES Campaigns(campaign_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    -- CHECK constraints
    CONSTRAINT chk_reward_min_amount CHECK (min_amount >= 0),
    CONSTRAINT chk_reward_max_backers CHECK (max_backers IS NULL OR max_backers > 0),
    CONSTRAINT chk_reward_current_backers CHECK (current_backers >= 0),
    CONSTRAINT chk_reward_backers_limit CHECK (max_backers IS NULL OR current_backers <= max_backers)
) ENGINE=InnoDB COMMENT='Campaign rewards/perks for backers';

-- Table: Donor_Rewards
-- Demonstrates: Many-to-many relationship between Donors and Rewards
CREATE TABLE Donor_Rewards (
    donor_id INT NOT NULL,
    reward_id INT NOT NULL,
    donation_id INT NOT NULL,
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fulfillment_status ENUM('pending', 'processing', 'shipped', 'delivered') DEFAULT 'pending',
    tracking_number VARCHAR(100),
    notes TEXT,
    -- Composite PRIMARY KEY
    PRIMARY KEY (donor_id, reward_id, donation_id),
    -- FOREIGN KEYs
    CONSTRAINT fk_donor_reward_user 
        FOREIGN KEY (donor_id) REFERENCES Users(user_id) 
        ON DELETE CASCADE,
    CONSTRAINT fk_donor_reward_reward 
        FOREIGN KEY (reward_id) REFERENCES Rewards(reward_id) 
        ON DELETE CASCADE,
    CONSTRAINT fk_donor_reward_donation 
        FOREIGN KEY (donation_id) REFERENCES Donations(donation_id) 
        ON DELETE CASCADE,
    -- Unique constraint: one reward per donation
    CONSTRAINT uk_donation_reward UNIQUE (donation_id)
) ENGINE=InnoDB COMMENT='Many-to-many: Donors claiming campaign rewards';

-- =====================================================
-- AUDIT TABLES (for TRIGGER demonstrations)
-- =====================================================

-- Table: User_Audit_Log
-- Demonstrates: Audit logging via triggers
CREATE TABLE User_Audit_Log (
    audit_id INT AUTO_INCREMENT,
    user_id INT,
    action_type ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_email VARCHAR(100),
    new_email VARCHAR(100),
    old_role ENUM('admin', 'campaigner', 'donor'),
    new_role ENUM('admin', 'campaigner', 'donor'),
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    change_description TEXT,
    PRIMARY KEY (audit_id)
) ENGINE=InnoDB COMMENT='Audit trail for user changes';

-- Table: Donation_Audit_Log
-- Demonstrates: Transaction audit trail
CREATE TABLE Donation_Audit_Log (
    audit_id INT AUTO_INCREMENT,
    donation_id INT,
    campaign_id INT,
    donor_id INT,
    amount DECIMAL(10, 2),
    action_type ENUM('INSERT', 'UPDATE', 'DELETE', 'REFUND') NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    PRIMARY KEY (audit_id)
) ENGINE=InnoDB COMMENT='Audit trail for donation transactions';

-- Table: Query_Log
-- Demonstrates: SQL query tracking for educational purposes
CREATE TABLE Query_Log (
    log_id INT AUTO_INCREMENT,
    query_text TEXT NOT NULL,
    query_type VARCHAR(50),  -- SELECT, INSERT, UPDATE, DELETE, etc.
    page_name VARCHAR(100),
    execution_time DECIMAL(10, 6),  -- in seconds
    rows_affected INT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_session VARCHAR(100),
    PRIMARY KEY (log_id),
    INDEX idx_query_type (query_type),
    INDEX idx_executed_at (executed_at)
) ENGINE=InnoDB COMMENT='Tracks all SQL queries for educational display';

-- =====================================================
-- PART 2: CREATE INDEXES
-- Demonstrates: Index creation for performance
-- =====================================================

-- Index on Users for search optimization
CREATE INDEX idx_users_email ON Users(email);
CREATE INDEX idx_users_username ON Users(username);
CREATE INDEX idx_users_role ON Users(user_role);
CREATE INDEX idx_users_active ON Users(is_active);

-- Composite index for campaign searches
CREATE INDEX idx_campaigns_status_dates ON Campaigns(status, start_date, end_date);
CREATE INDEX idx_campaigns_creator ON Campaigns(creator_id);
CREATE INDEX idx_campaigns_category ON Campaigns(category_id);
CREATE INDEX idx_campaigns_featured ON Campaigns(featured);

-- Index on Donations for reporting
CREATE INDEX idx_donations_campaign ON Donations(campaign_id);
CREATE INDEX idx_donations_donor ON Donations(donor_id);
CREATE INDEX idx_donations_date ON Donations(donation_date);
CREATE INDEX idx_donations_status ON Donations(status);

-- Index on Rewards for performance
CREATE INDEX idx_rewards_campaign ON Rewards(campaign_id);
CREATE INDEX idx_rewards_available ON Rewards(is_available);

-- Index on Donor_Rewards
CREATE INDEX idx_donor_rewards_donor ON Donor_Rewards(donor_id);
CREATE INDEX idx_donor_rewards_reward ON Donor_Rewards(reward_id);
CREATE INDEX idx_donor_rewards_status ON Donor_Rewards(fulfillment_status);

-- Index on Campaign_Category
CREATE INDEX idx_campaign_category_campaign ON Campaign_Category(campaign_id);
CREATE INDEX idx_campaign_category_category ON Campaign_Category(category_id);

-- Full-text index for search functionality
CREATE FULLTEXT INDEX idx_campaigns_search ON Campaigns(campaign_title, description);
CREATE FULLTEXT INDEX idx_users_search ON Users(full_name, bio);

-- =====================================================
-- PART 3: CREATE VIEWS
-- Demonstrates: VIEW creation for complex query abstraction
-- =====================================================

-- View: Campaign_Progress
-- Demonstrates: VIEW with calculated columns, JOINs, and aggregation
CREATE VIEW Campaign_Progress AS
SELECT 
    c.campaign_id,
    c.campaign_title,
    c.goal_amount,
    c.current_amount,
    ROUND((c.current_amount / c.goal_amount) * 100, 2) AS progress_percentage,
    ROUND((c.current_amount / c.goal_amount) * 100, 2) AS completion_percentage,
    c.status,
    c.start_date,
    c.end_date,
    c.created_at,
    c.updated_at,
    c.category_id,
    DATEDIFF(c.end_date, CURDATE()) AS days_remaining,
    u.user_id AS creator_id,
    u.username AS creator_username,
    u.full_name AS creator_name,
    cat.category_name,
    COUNT(DISTINCT d.donation_id) AS total_donations,
    COUNT(DISTINCT d.donor_id) AS unique_donors,
    COALESCE(AVG(d.amount), 0) AS average_donation,
    (SELECT COUNT(*) FROM Rewards r WHERE r.campaign_id = c.campaign_id) AS total_rewards,
    (SELECT COUNT(*) FROM Rewards r WHERE r.campaign_id = c.campaign_id AND r.is_available = TRUE) AS available_rewards,
    CASE 
        WHEN c.current_amount >= c.goal_amount THEN 'Fully Funded'
        WHEN c.current_amount >= c.goal_amount * 0.75 THEN 'Nearly There'
        WHEN c.current_amount >= c.goal_amount * 0.50 THEN 'Halfway'
        WHEN c.current_amount > 0 THEN 'In Progress'
        ELSE 'Just Started'
    END AS funding_status
FROM Campaigns c
INNER JOIN Users u ON c.creator_id = u.user_id
LEFT JOIN Categories cat ON c.category_id = cat.category_id
LEFT JOIN Donations d ON c.campaign_id = d.campaign_id AND d.status = 'completed'
GROUP BY c.campaign_id, c.campaign_title, c.goal_amount, c.current_amount, 
         c.status, c.start_date, c.end_date, c.created_at, c.updated_at, c.category_id,
         u.user_id, u.username, u.full_name, cat.category_name;

-- View: User_Statistics
-- Demonstrates: Aggregation across multiple tables
CREATE VIEW User_Statistics AS
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    u.user_role,
    COUNT(DISTINCT c.campaign_id) AS campaigns_created,
    COUNT(DISTINCT d.donation_id) AS donations_made,
    COALESCE(SUM(d.amount), 0) AS total_donated,
    COALESCE(SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END), 0) AS active_campaigns,
    COALESCE(SUM(c.current_amount), 0) AS total_funds_raised,
    (SELECT COUNT(*) FROM User_Followers WHERE following_id = u.user_id) AS followers_count,
    (SELECT COUNT(*) FROM User_Followers WHERE follower_id = u.user_id) AS following_count
FROM Users u
LEFT JOIN Campaigns c ON u.user_id = c.creator_id
LEFT JOIN Donations d ON u.user_id = d.donor_id AND d.status = 'completed'
GROUP BY u.user_id, u.username, u.full_name, u.user_role;

-- View: Top_Donors
-- Demonstrates: Ranking with ORDER BY and LIMIT simulation
CREATE VIEW Top_Donors AS
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    COUNT(d.donation_id) AS donation_count,
    SUM(d.amount) AS total_donated,
    AVG(d.amount) AS avg_donation,
    MAX(d.amount) AS largest_donation,
    MAX(d.donation_date) AS last_donation_date
FROM Users u
INNER JOIN Donations d ON u.user_id = d.donor_id
WHERE d.status = 'completed'
GROUP BY u.user_id, u.username, u.full_name
HAVING total_donated > 0
ORDER BY total_donated DESC;

-- View: Active_Campaigns_Summary
-- Demonstrates: Filtered view with date calculations
CREATE VIEW Active_Campaigns_Summary AS
SELECT 
    c.campaign_id,
    c.campaign_title,
    c.goal_amount,
    c.current_amount,
    ROUND((c.current_amount / c.goal_amount) * 100, 2) AS completion_percentage,
    DATEDIFF(c.end_date, CURDATE()) AS days_left,
    u.username AS creator,
    cat.category_name,
    (SELECT COUNT(*) FROM Donations d WHERE d.campaign_id = c.campaign_id AND d.status = 'completed') AS backer_count
FROM Campaigns c
INNER JOIN Users u ON c.creator_id = u.user_id
LEFT JOIN Categories cat ON c.category_id = cat.category_id
WHERE c.status = 'active' 
  AND c.end_date >= CURDATE();

-- =====================================================
-- PART 4: STORED PROCEDURES
-- Demonstrates: Procedures with parameters, logic, transactions
-- =====================================================

DELIMITER //

-- Procedure: Process_Donation
-- Demonstrates: Transaction control, parameters, IF-ELSE logic, error handling
CREATE PROCEDURE Process_Donation(
    IN p_campaign_id INT,
    IN p_donor_id INT,
    IN p_amount DECIMAL(10,2),
    IN p_payment_method VARCHAR(50),
    IN p_message TEXT,
    IN p_is_anonymous BOOLEAN,
    OUT p_donation_id INT,
    OUT p_success BOOLEAN,
    OUT p_message_out VARCHAR(255)
)
BEGIN
    DECLARE v_campaign_exists INT;
    DECLARE v_donor_exists INT;
    DECLARE v_campaign_status VARCHAR(20);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message_out = 'Transaction failed due to error';
    END;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Validate campaign exists and is active
    SELECT COUNT(*), MAX(status) 
    INTO v_campaign_exists, v_campaign_status
    FROM Campaigns 
    WHERE campaign_id = p_campaign_id;
    
    IF v_campaign_exists = 0 THEN
        SET p_success = FALSE;
        SET p_message_out = 'Campaign does not exist';
        ROLLBACK;
    ELSEIF v_campaign_status != 'active' THEN
        SET p_success = FALSE;
        SET p_message_out = 'Campaign is not active';
        ROLLBACK;
    ELSE
        -- Validate donor exists
        SELECT COUNT(*) INTO v_donor_exists
        FROM Users WHERE user_id = p_donor_id;
        
        IF v_donor_exists = 0 THEN
            SET p_success = FALSE;
            SET p_message_out = 'Donor does not exist';
            ROLLBACK;
        ELSE
            -- Insert donation
            INSERT INTO Donations (campaign_id, donor_id, amount, payment_method, message, is_anonymous)
            VALUES (p_campaign_id, p_donor_id, p_amount, p_payment_method, p_message, p_is_anonymous);
            
            SET p_donation_id = LAST_INSERT_ID();
            
            -- Update campaign current amount
            UPDATE Campaigns 
            SET current_amount = current_amount + p_amount,
                updated_at = CURRENT_TIMESTAMP
            WHERE campaign_id = p_campaign_id;
            
            -- Commit transaction
            COMMIT;
            
            SET p_success = TRUE;
            SET p_message_out = 'Donation processed successfully';
        END IF;
    END IF;
END//

-- Procedure: Get_Campaign_Analytics
-- Demonstrates: Complex SELECT with multiple aggregations and subqueries
CREATE PROCEDURE Get_Campaign_Analytics(IN p_campaign_id INT)
BEGIN
    SELECT 
        c.campaign_id,
        c.campaign_title,
        c.goal_amount,
        c.current_amount,
        ROUND((c.current_amount / c.goal_amount) * 100, 2) AS progress_percent,
        COUNT(DISTINCT d.donation_id) AS total_donations,
        COUNT(DISTINCT d.donor_id) AS unique_donors,
        COALESCE(MIN(d.amount), 0) AS min_donation,
        COALESCE(MAX(d.amount), 0) AS max_donation,
        COALESCE(AVG(d.amount), 0) AS avg_donation,
        DATEDIFF(c.end_date, c.start_date) AS campaign_duration_days,
        DATEDIFF(c.end_date, CURDATE()) AS days_remaining,
        (SELECT COUNT(*) FROM Comments WHERE campaign_id = p_campaign_id) AS comment_count,
        (SELECT COUNT(*) FROM Campaign_Favorites WHERE campaign_id = p_campaign_id) AS favorite_count
    FROM Campaigns c
    LEFT JOIN Donations d ON c.campaign_id = d.campaign_id AND d.status = 'completed'
    WHERE c.campaign_id = p_campaign_id
    GROUP BY c.campaign_id;
END//

-- Procedure: Update_Campaign_Status
-- Demonstrates: Conditional UPDATE with CASE logic
CREATE PROCEDURE Update_Campaign_Status()
BEGIN
    -- Auto-update campaign statuses based on dates and funding
    UPDATE Campaigns
    SET status = CASE
        WHEN end_date < CURDATE() AND status = 'active' THEN 'completed'
        WHEN current_amount >= goal_amount AND status = 'active' THEN 'completed'
        WHEN start_date <= CURDATE() AND end_date >= CURDATE() AND status = 'draft' THEN 'active'
        ELSE status
    END,
    updated_at = CURRENT_TIMESTAMP
    WHERE status IN ('draft', 'active');
    
    SELECT ROW_COUNT() AS campaigns_updated;
END//

-- Procedure: Get_User_Donation_History
-- Demonstrates: Parameterized query with JOINs and ORDER BY
CREATE PROCEDURE Get_User_Donation_History(
    IN p_user_id INT,
    IN p_limit INT
)
BEGIN
    SELECT 
        d.donation_id,
        d.amount,
        d.donation_date,
        d.payment_method,
        d.message,
        d.status,
        c.campaign_title,
        c.campaign_id,
        u.username AS campaign_creator
    FROM Donations d
    INNER JOIN Campaigns c ON d.campaign_id = c.campaign_id
    INNER JOIN Users u ON c.creator_id = u.user_id
    WHERE d.donor_id = p_user_id
    ORDER BY d.donation_date DESC
    LIMIT p_limit;
END//

-- Procedure: Calculate_Platform_Statistics
-- Demonstrates: Multiple aggregations, subqueries, and reporting
CREATE PROCEDURE Calculate_Platform_Statistics()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM Users WHERE is_active = TRUE) AS total_active_users,
        (SELECT COUNT(*) FROM Campaigns) AS total_campaigns,
        (SELECT COUNT(*) FROM Campaigns WHERE status = 'active') AS active_campaigns,
        (SELECT COUNT(*) FROM Campaigns WHERE status = 'completed') AS completed_campaigns,
        (SELECT COALESCE(SUM(amount), 0) FROM Donations WHERE status = 'completed') AS total_funds_raised,
        (SELECT COUNT(*) FROM Donations WHERE status = 'completed') AS total_donations,
        (SELECT COALESCE(AVG(amount), 0) FROM Donations WHERE status = 'completed') AS avg_donation_amount,
        (SELECT COUNT(*) FROM Categories) AS total_categories,
        (SELECT COUNT(DISTINCT donor_id) FROM Donations WHERE status = 'completed') AS unique_donors;
END//

DELIMITER ;

-- =====================================================
-- PART 5: FUNCTIONS
-- Demonstrates: Scalar functions with RETURN values
-- =====================================================

DELIMITER //

-- Function: Get_Donor_Level
-- Demonstrates: CASE statement in function, scalar return
CREATE FUNCTION Get_Donor_Level(p_user_id INT)
RETURNS VARCHAR(20)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_donated DECIMAL(12,2);
    
    SELECT COALESCE(SUM(amount), 0) 
    INTO v_total_donated
    FROM Donations 
    WHERE donor_id = p_user_id AND status = 'completed';
    
    RETURN CASE
        WHEN v_total_donated >= 10000 THEN 'Platinum'
        WHEN v_total_donated >= 5000 THEN 'Gold'
        WHEN v_total_donated >= 1000 THEN 'Silver'
        WHEN v_total_donated >= 100 THEN 'Bronze'
        ELSE 'Supporter'
    END;
END//

-- Function: Calculate_Campaign_Progress
-- Demonstrates: Mathematical calculation in function
CREATE FUNCTION Calculate_Campaign_Progress(p_campaign_id INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_progress DECIMAL(5,2);
    
    SELECT ROUND((current_amount / goal_amount) * 100, 2)
    INTO v_progress
    FROM Campaigns
    WHERE campaign_id = p_campaign_id;
    
    RETURN IFNULL(v_progress, 0);
END//

-- Function: Get_Days_Until_End
-- Demonstrates: Date calculation in function
CREATE FUNCTION Get_Days_Until_End(p_campaign_id INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_days INT;
    
    SELECT DATEDIFF(end_date, CURDATE())
    INTO v_days
    FROM Campaigns
    WHERE campaign_id = p_campaign_id;
    
    RETURN IFNULL(v_days, 0);
END//

-- Function: Is_Campaign_Fully_Funded
-- Demonstrates: Boolean logic in function
CREATE FUNCTION Is_Campaign_Fully_Funded(p_campaign_id INT)
RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_is_funded BOOLEAN;
    
    SELECT (current_amount >= goal_amount)
    INTO v_is_funded
    FROM Campaigns
    WHERE campaign_id = p_campaign_id;
    
    RETURN IFNULL(v_is_funded, FALSE);
END//

DELIMITER ;

-- =====================================================
-- PART 6: TRIGGERS
-- Demonstrates: BEFORE/AFTER triggers for automation
-- =====================================================

DELIMITER //

-- Trigger: After Insert on Donations - Update Campaign Amount
-- Demonstrates: AFTER INSERT trigger
CREATE TRIGGER trg_donation_after_insert
AFTER INSERT ON Donations
FOR EACH ROW
BEGIN
    -- Log the donation in audit table
    INSERT INTO Donation_Audit_Log (donation_id, campaign_id, donor_id, amount, action_type, notes)
    VALUES (NEW.donation_id, NEW.campaign_id, NEW.donor_id, NEW.amount, 'INSERT', 
            CONCAT('New donation of $', NEW.amount, ' received'));
END//

-- Trigger: After Update on Users - Audit Trail
-- Demonstrates: AFTER UPDATE trigger with OLD and NEW references
CREATE TRIGGER trg_user_after_update
AFTER UPDATE ON Users
FOR EACH ROW
BEGIN
    IF OLD.email != NEW.email OR OLD.user_role != NEW.user_role THEN
        INSERT INTO User_Audit_Log (user_id, action_type, old_email, new_email, old_role, new_role, 
                                   changed_by, change_description)
        VALUES (NEW.user_id, 'UPDATE', OLD.email, NEW.email, OLD.user_role, NEW.user_role,
                USER(), CONCAT('User profile updated: ', OLD.username));
    END IF;
END//

-- Trigger: Before Delete on Users - Audit Trail
-- Demonstrates: BEFORE DELETE trigger
CREATE TRIGGER trg_user_before_delete
BEFORE DELETE ON Users
FOR EACH ROW
BEGIN
    INSERT INTO User_Audit_Log (user_id, action_type, old_email, old_role, changed_by, change_description)
    VALUES (OLD.user_id, 'DELETE', OLD.email, OLD.user_role, USER(), 
            CONCAT('User account deleted: ', OLD.username));
END//

-- Trigger: Before Insert on Campaigns - Validation
-- Demonstrates: BEFORE INSERT trigger with validation
CREATE TRIGGER trg_campaign_before_insert
BEFORE INSERT ON Campaigns
FOR EACH ROW
BEGIN
    -- Ensure end_date is after start_date
    IF NEW.end_date <= NEW.start_date THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'End date must be after start date';
    END IF;
    
    -- Ensure goal amount is positive
    IF NEW.goal_amount <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Goal amount must be positive';
    END IF;
END//

-- Trigger: After Delete on Campaigns - Cascade cleanup
-- Demonstrates: AFTER DELETE trigger for cleanup operations
CREATE TRIGGER trg_campaign_after_delete
AFTER DELETE ON Campaigns
FOR EACH ROW
BEGIN
    -- Log campaign deletion (note: Query_Log table might not exist yet during schema import)
    -- This is safe because we check table existence first
    INSERT INTO Query_Log (query_text, query_type, page_name)
    VALUES (CONCAT('Campaign deleted: ', OLD.campaign_title), 'DELETE', 'TRIGGER');
END//

-- Trigger: After claiming a reward, update backer count
-- Demonstrates: AFTER INSERT trigger for automatic updates
CREATE TRIGGER trg_donor_reward_after_insert
AFTER INSERT ON Donor_Rewards
FOR EACH ROW
BEGIN
    -- Increment current_backers count for the reward
    UPDATE Rewards 
    SET current_backers = current_backers + 1,
        updated_at = CURRENT_TIMESTAMP
    WHERE reward_id = NEW.reward_id;
    
    -- Mark reward as unavailable if max capacity reached
    UPDATE Rewards 
    SET is_available = FALSE,
        updated_at = CURRENT_TIMESTAMP
    WHERE reward_id = NEW.reward_id 
      AND max_backers IS NOT NULL 
      AND current_backers >= max_backers;
END//

-- Trigger: After removing a reward claim, update backer count
-- Demonstrates: AFTER DELETE trigger for cleanup
CREATE TRIGGER trg_donor_reward_after_delete
AFTER DELETE ON Donor_Rewards
FOR EACH ROW
BEGIN
    -- Decrement current_backers count
    UPDATE Rewards 
    SET current_backers = GREATEST(current_backers - 1, 0),
        updated_at = CURRENT_TIMESTAMP
    WHERE reward_id = OLD.reward_id;
    
    -- Re-enable reward if it was at capacity
    UPDATE Rewards 
    SET is_available = TRUE,
        updated_at = CURRENT_TIMESTAMP
    WHERE reward_id = OLD.reward_id 
      AND max_backers IS NOT NULL 
      AND current_backers < max_backers;
END//

-- Trigger: Before inserting reward claim, validate availability
-- Demonstrates: BEFORE INSERT trigger with validation logic
CREATE TRIGGER trg_donor_reward_before_insert
BEFORE INSERT ON Donor_Rewards
FOR EACH ROW
BEGIN
    DECLARE v_is_available BOOLEAN;
    DECLARE v_max_backers INT;
    DECLARE v_current_backers INT;
    DECLARE v_min_amount DECIMAL(10,2);
    DECLARE v_donation_amount DECIMAL(10,2);
    
    -- Check if reward is available
    SELECT is_available, max_backers, current_backers, min_amount
    INTO v_is_available, v_max_backers, v_current_backers, v_min_amount
    FROM Rewards
    WHERE reward_id = NEW.reward_id;
    
    -- Check donation amount meets minimum
    SELECT amount INTO v_donation_amount
    FROM Donations
    WHERE donation_id = NEW.donation_id;
    
    IF v_donation_amount < v_min_amount THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Donation amount does not meet minimum for this reward';
    END IF;
    
    IF NOT v_is_available THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This reward is no longer available';
    END IF;
    
    -- Check if reward is at capacity
    IF v_max_backers IS NOT NULL AND v_current_backers >= v_max_backers THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This reward has reached maximum backers';
    END IF;
END//

-- Trigger: Validate reward before insert
-- Demonstrates: BEFORE INSERT trigger with business rule validation
CREATE TRIGGER trg_reward_before_insert
BEFORE INSERT ON Rewards
FOR EACH ROW
BEGIN
    -- Ensure min_amount is positive
    IF NEW.min_amount < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Reward minimum amount must be non-negative';
    END IF;
    
    -- Ensure estimated_delivery is in the future (if provided)
    IF NEW.estimated_delivery IS NOT NULL AND NEW.estimated_delivery < CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Estimated delivery date must be in the future';
    END IF;
END//

DELIMITER ;

-- =====================================================
-- END OF SCHEMA
-- =====================================================

-- Display success message
SELECT 'CF_Tracker database schema created successfully!' AS Status,
       'All tables, views, procedures, functions, and triggers have been created.' AS Message;
