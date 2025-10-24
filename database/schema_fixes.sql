-- =====================================================
-- Schema Fixes: Add Rewards Table & Fix Campaign_Category
-- Date: October 25, 2025
-- =====================================================

USE CF_Tracker;

-- =====================================================
-- FIX 1: Add Rewards Table (Missing from original schema)
-- =====================================================

CREATE TABLE IF NOT EXISTS Rewards (
    reward_id INT AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    min_amount DECIMAL(10, 2) NOT NULL,
    max_backers INT NULL,  -- NULL means unlimited
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

-- Create index for performance
CREATE INDEX idx_rewards_campaign ON Rewards(campaign_id);
CREATE INDEX idx_rewards_available ON Rewards(is_available);

-- =====================================================
-- FIX 2: Add Donor_Rewards junction table (Many-to-Many)
-- Demonstrates: Which donors claimed which rewards
-- =====================================================

CREATE TABLE IF NOT EXISTS Donor_Rewards (
    donor_id INT NOT NULL,
    reward_id INT NOT NULL,
    donation_id INT NOT NULL,  -- Links to the donation that claimed this reward
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

-- Create indexes
CREATE INDEX idx_donor_rewards_donor ON Donor_Rewards(donor_id);
CREATE INDEX idx_donor_rewards_reward ON Donor_Rewards(reward_id);
CREATE INDEX idx_donor_rewards_status ON Donor_Rewards(fulfillment_status);

-- =====================================================
-- FIX 3: Fix Campaign_Category table (Add missing FKs)
-- =====================================================

-- Check if foreign keys already exist, if not add them
-- First, let's check if the table has any data that might violate FK constraints

-- Add foreign key to Categories if not exists
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = 'CF_Tracker' 
    AND TABLE_NAME = 'Campaign_Category' 
    AND CONSTRAINT_NAME = 'fk_campaign_category_category'
);

-- Only add if it doesn't exist
-- Note: We need to ensure the table structure is correct first

-- Drop and recreate Campaign_Category with proper structure
DROP TABLE IF EXISTS Campaign_Category;

CREATE TABLE Campaign_Category (
    campaign_id INT NOT NULL,
    category_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Composite PRIMARY KEY (Many-to-Many relationship)
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

-- Create indexes for better performance
CREATE INDEX idx_campaign_category_campaign ON Campaign_Category(campaign_id);
CREATE INDEX idx_campaign_category_category ON Campaign_Category(category_id);

-- =====================================================
-- FIX 4: Remove redundant 'category' VARCHAR column from Campaigns
-- Keep only category_id FK (proper normalized design)
-- =====================================================

-- Check if the column exists before trying to drop it
SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'CF_Tracker' 
    AND TABLE_NAME = 'Campaigns' 
    AND COLUMN_NAME = 'category'
);

-- Drop the redundant column if it exists
SET @query = IF(@column_exists > 0,
    'ALTER TABLE Campaigns DROP COLUMN category',
    'SELECT "Column category does not exist" AS Info'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- FIX 5: Add Triggers for Rewards table
-- =====================================================

DELIMITER //

-- Trigger: After claiming a reward, update backer count
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

-- Trigger: Validate reward before insert/update
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
-- FIX 6: Update Views to include Rewards information
-- =====================================================

-- Drop and recreate Campaign_Progress view with rewards info
DROP VIEW IF EXISTS Campaign_Progress;

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

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Show all tables
SELECT 'Database Tables:' AS Info;
SHOW TABLES;

-- Show Rewards table structure
SELECT 'Rewards Table Structure:' AS Info;
DESCRIBE Rewards;

-- Show Donor_Rewards table structure
SELECT 'Donor_Rewards Table Structure:' AS Info;
DESCRIBE Donor_Rewards;

-- Show Campaign_Category table structure
SELECT 'Campaign_Category Table Structure (Fixed):' AS Info;
DESCRIBE Campaign_Category;

-- Show Campaigns table structure (category column should be removed)
SELECT 'Campaigns Table Structure (category column removed):' AS Info;
DESCRIBE Campaigns;

-- Show foreign key constraints
SELECT 'Foreign Key Constraints:' AS Info;
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'CF_Tracker'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

SELECT 'Schema fixes applied successfully!' AS Status;
