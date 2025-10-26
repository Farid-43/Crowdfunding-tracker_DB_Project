# CF Tracker - SQL Learning Platform

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)
![Status](https://img.shields.io/badge/status-complete-success.svg)
![Features](https://img.shields.io/badge/SQL_Features-75+-brightgreen.svg)

## 🎯 Project Overview

**CF Tracker** is an educational PHP/MySQL web application that systematically demonstrates **ALL 75+ SQL features** from an Oracle SQL curriculum. While it functions as a crowdfunding tracker platform, its primary purpose is to showcase advanced SQL capabilities with explicit visibility and educational focus on the queries themselves.

### 🌟 Key Features

- **🔍 Hover-to-Reveal SQL**: Every interactive element shows the exact SQL query it executes
- **📊 Live Query Tracking**: All queries logged in Query_Log table for review
- **🔎 SQL Query Search**: Search for any SQL keyword across executed queries and schema files
- **📚 Educational Focus**: Each operation includes explanations of SQL features
- **🎓 Comprehensive Coverage**: 75+ SQL features across DDL, DML, advanced queries, and database objects
- **📖 Complete Documentation**: 500+ lines of comprehensive guides included

## 🚀 Quick Start

### Prerequisites

- **XAMPP** (or LAMP/WAMP/MAMP)
  - PHP 8.0 or higher
  - MySQL 8.0 or higher
  - Apache Web Server

### Installation Steps

**⚡ Quick Setup (Recommended)**

1. **Start XAMPP**:

   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL**

2. **Run Automated Setup**:
   - Double-click `setup.bat` in the project folder
   - Press any key when prompted
   - Done! ✅

**📝 Manual Setup (Alternative)**

1. **Clone or Download** this repository to your XAMPP htdocs folder:

   ```bash
   cd F:\xampp\htdocs
   git clone <repository-url> Crowdfunding-tracker_DB_Project
   ```

2. **Start XAMPP Services**:

   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL**

3. **Import Database via phpMyAdmin**:

   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Click "Import" tab
   - Choose `database/schema_no_drop.sql` (⭐ Use this file!)
   - Click "Import"
   - Then import `database/sample_data.sql`
   - Click "Import"

4. **Access the Application**:
   - Open browser and navigate to:
   ```
   http://localhost/Crowdfunding-tracker_DB_Project/
   ```

> 📘 **Need detailed instructions?** See `COMPLETE_PROJECT_GUIDE.md` for comprehensive setup, troubleshooting, and feature documentation.

## 📂 Project Structure

```
Crowdfunding-tracker_DB_Project/
├── assets/
│   ├── css/
│   │   └── sql-styles.css          # SQL visualization styles
│   └── js/
│       └── sql-tooltip.js          # SQL tooltip system
├── config/
│   ├── database.php                # PDO connection & query logging
│   └── db_functions.php            # Reusable SQL operations
├── database/
│   ├── schema.sql                  # Complete database schema
│   ├── schema_no_drop.sql          # Schema without DROP statements
│   ├── sample_data.sql             # Test data
│   ├── rewards_sample_data.sql     # Rewards system test data
│   └── er_diagram.dot              # GraphvizOnline ER diagram code ⭐ NEW!
├── includes/
│   ├── header.php                  # Common header
│   └── footer.php                  # Common footer
├── index.php                       # Dashboard (Aggregations, JOINs)
├── campaigns.php                   # Campaign Management (CRUD, GROUP BY)
├── donations.php                   # Donations (Transactions, UNION)
├── rewards.php                     # Rewards System (M:N, Triggers) ⭐ NEW!
├── analytics.php                   # Analytics (CTEs, Views, Procedures)
├── users.php                       # User Management (Constraints, Triggers)
├── sql_features.php                # SQL Feature Catalog & Query Search
├── get_campaign_details.php        # AJAX endpoint for campaign details & comments ⭐ NEW!
├── setup.bat                       # Automated Database Setup
├── COMPLETE_PROJECT_GUIDE.md       # 📖 Full Documentation (500+ lines)
├── SCHEMA_UPDATES.md               # 📖 Schema Changes Documentation ⭐ NEW!
├── IMPLEMENTATION_SUMMARY.md       # 📖 Implementation Summary ⭐ NEW!
└── FILES_TO_KEEP_DELETE.md         # Quick Reference
```

## 🎓 SQL Features Demonstrated (75+ Total)

### DDL (Data Definition Language)

- ✅ CREATE TABLE with all constraint types
- ✅ ALTER TABLE (add/drop columns)
- ✅ DROP TABLE
- ✅ CREATE/ALTER/DROP VIEW
- ✅ CREATE INDEX (standard, composite, full-text)

### DML (Data Manipulation Language)

- ✅ INSERT (single/multiple rows)
- ✅ UPDATE (conditional, with JOINs)
- ✅ DELETE (with WHERE, cascade)
- ✅ SELECT with all clauses

### Constraints & Integrity

- ✅ PRIMARY KEY (simple, composite)
- ✅ FOREIGN KEY (with CASCADE, SET NULL)
- ✅ UNIQUE constraints
- ✅ CHECK constraints
- ✅ NOT NULL constraints
- ✅ DEFAULT values

### Query Operations

- ✅ SELECT with WHERE
- ✅ Complex WHERE (AND/OR/NOT)
- ✅ DISTINCT values
- ✅ ORDER BY (ASC/DESC)
- ✅ LIMIT/OFFSET pagination

### JOINs (All Types)

- ✅ INNER JOIN
- ✅ LEFT JOIN / RIGHT JOIN
- ✅ CROSS JOIN
- ✅ SELF JOIN
- ✅ Multiple table joins (3+ tables)

### Aggregation & Grouping

- ✅ COUNT(), SUM(), AVG(), MAX(), MIN()
- ✅ GROUP BY (single/multiple columns)
- ✅ HAVING clause
- ✅ GROUP BY ... WITH ROLLUP (subtotals)
- ✅ Conditional aggregation with CASE

### Window Functions

- ✅ ROW_NUMBER()
- ✅ SUM() OVER()
- ✅ AVG() OVER()
- ✅ RANK()
- ✅ PARTITION BY

### Set Operations

- ✅ UNION / UNION ALL
- ✅ INTERSECT (emulated)
- ✅ EXCEPT/MINUS (emulated)

### Subqueries

- ✅ Scalar subqueries
- ✅ Correlated subqueries
- ✅ EXISTS / NOT EXISTS
- ✅ IN / NOT IN subqueries

### Advanced Query Features

- ✅ CTEs (WITH clause) - Common Table Expressions
- ✅ Multiple CTEs in single query
- ✅ CASE statements (simple & searched)
- ✅ COALESCE, NULLIF functions
- ✅ CAST/CONVERT type conversion

### Database Objects

- ✅ **Views (4 total)** - Virtual tables
- ✅ **Stored Procedures (5 total)** - Reusable logic
- ✅ **Functions (4 total)** - Scalar return values
- ✅ **Triggers (9 total)** - BEFORE/AFTER automation ⭐ +4 NEW!
- ✅ **Indexes** - Performance optimization
- ✅ **Many-to-Many (4 total)** - Bridge tables with composite keys ⭐ UPDATED!

### Transaction Control

- ✅ START TRANSACTION / BEGIN
- ✅ COMMIT
- ✅ ROLLBACK
- ✅ SAVEPOINT

### String Functions

- ✅ CONCAT()
- ✅ UPPER() / LOWER()
- ✅ SUBSTRING()
- ✅ LIKE pattern matching

### Date/Time Functions

- ✅ NOW() / CURDATE()
- ✅ DATE()
- ✅ DATE_FORMAT()
- ✅ DATEDIFF()
- ✅ DATE_SUB() / DATE_ADD()

> 📖 **Complete Feature List**: Visit `/sql_features.php` for interactive catalog of all 75+ features with examples!

## 🗺️ Page-by-Page SQL Features

### 📊 Dashboard (`index.php`)

- Aggregation: COUNT(), SUM(), AVG(), MAX()
- INNER JOIN between tables
- Correlated subqueries
- LIMIT for recent items
- VIEW usage

## 🗺️ Page-by-Page SQL Features

### 📊 Dashboard (`index.php`)

- Aggregation: COUNT(), SUM(), AVG(), MAX()
- INNER JOIN between tables
- Correlated subqueries
- LIMIT for recent items
- VIEW usage

### 🎯 Campaigns (`campaigns.php`)

- INSERT, UPDATE, DELETE operations
- Complex multi-table JOINs
- GROUP BY with HAVING
- Transaction handling (BEGIN, COMMIT, ROLLBACK)
- CASE statements for status
- UNION for combined views
- Trigger demonstrations
- Window functions
- **Campaign Details Modal** - View campaign with all comments ⭐ NEW!
- **Self-Referencing Foreign Keys** - Hierarchical comments (parent/replies)
- **AJAX Loading** - Dynamic content fetching

### 💰 Donations (`donations.php`)

- Transaction handling (BEGIN, COMMIT, ROLLBACK)
- CASE statements for status
- UNION for combined views
- Trigger demonstrations
- Window functions

### 🎁 Rewards (`rewards.php`) ⭐ NEW!

- **Many-to-Many Relationships** - Donor_Rewards bridge table
- **4-Table JOINs** - Users, Rewards, Campaigns, Donations
- **Trigger Automation** - Auto-update backer counts
- **CASE Statements** - Conditional display logic
- **Aggregate Functions** - COUNT DISTINCT, SUM, AVG, MIN, MAX
- **Correlated Subqueries** - Validation logic
- **ENUM Fields** - Fulfillment status tracking
- **Percentage Calculations** - Capacity tracking with ROUND
- **Composite Primary Keys** - M:N relationship demonstration

### 📈 Analytics (`analytics.php`)

- VIEW usage
- CTEs with WITH clause
- Stored Procedures
- UNION operations
- ROLLUP grouping
- Multi-table JOINs with Categories

### 👥 Users (`users.php`)

- CHECK constraints
- UNIQUE constraints
- Custom functions
- Audit triggers
- Conditional updates

### 💰 Donations (`donations.php`)

- Transaction handling (BEGIN, COMMIT, ROLLBACK)
- CASE statements for status
- UNION for combined views
- Trigger demonstrations
- Window functions

### 📈 Analytics (`analytics.php`)

- VIEW usage
- CTEs with WITH clause
- Stored Procedures
- UNION operations
- ROLLUP grouping

### 👥 Users (`users.php`)

- CHECK constraints
- UNIQUE constraints
- Custom functions
- Audit triggers
- Conditional updates

### 🔍 SQL Features Catalog (`sql_features.php`)

- **Query Search** - Search for any SQL keyword (SELECT, JOIN, triggers, etc.)
- **Schema Search** - Find trigger/procedure/function definitions in schema files
- **Query History** - Last 50 executed queries with metrics
- **Feature Catalog** - All 80+ features organized by category
- **Query Statistics** - Execution time, row counts by query type
- **Highlighted Results** - Search terms highlighted in yellow
- **Scrollable Results** - Card-based layout for easy browsing

## 🎨 How to Use the SQL Learning Features

### 1. **Hover Over Elements**

Hover over any button, link, or interactive element to see:

- The exact SQL query
- Query type (SELECT, INSERT, etc.)
- Educational explanation

### 2. **SQL Feature Panels**

Each page has a dedicated panel showing:

- All SQL operations on that page
- Feature categories
- Example queries with syntax highlighting
- Collapsible sections

### 3. **Query Search** (NEW! ⭐)

On the SQL Features page:

**Search Executed Queries:**

- Type any SQL keyword: `SELECT`, `JOIN`, `WHERE`, etc.
- Search table names: `Campaigns`, `Donations`, etc.
- Find specific operations: `GROUP BY`, `SUM()`, etc.

**Search Schema Definitions:**

- Find triggers: `trigger`, `AFTER INSERT`
- Find procedures: `PROCEDURE`, `Process_Donation`
- Find functions: `FUNCTION`, `Get_Donor_Level`
- See line numbers and context

**Features:**

- ✅ Highlighted search terms (yellow)
- ✅ Scrollable results in cards
- ✅ Shows page where query was executed
- ✅ Execution time and row counts
- ✅ Quick search examples provided

### 4. **Query History**

View all executed queries in:

- Query_Log database table
- SQL Features page (last 50 queries)
- Grouped by query type with statistics

## 🔗 Entity-Relationship Diagram

### Visual ER Diagram

Want to see the complete database structure visually? We've got you covered!

**📊 Interactive ER Diagram:**

1. Open the file `database/er_diagram.dot`
2. Copy the DOT code
3. Visit [GraphvizOnline](https://dreampuf.github.io/GraphvizOnline/)
4. Paste the code and see your interactive diagram!

### Entity Relationships Explained

Our database implements a comprehensive relational model with **13 tables** organized into four categories:

#### 🎯 Core Entities (4 Tables)

**1. Users** - The foundation of the platform

- **Primary Key:** `user_id`
- **Attributes:** name, email, password, role
- **Role:** Central entity connecting to campaigns, donations, comments, and favorites
- **Constraints:** UNIQUE email, CHECK constraints on role

**2. Categories** - Campaign classification system

- **Primary Key:** `category_id`
- **Attributes:** category_name, description
- **Role:** Organizes campaigns into logical groups (Technology, Arts, etc.)

**3. Campaigns** - The core crowdfunding projects

- **Primary Key:** `campaign_id`
- **Foreign Key:** `user_id` → Users
- **Attributes:** title, description, goal_amount, start_date, end_date
- **Role:** Central hub for donations, rewards, updates, and comments

**4. Donations** - Financial transactions

- **Primary Key:** `donation_id`
- **Foreign Keys:** `user_id` → Users, `campaign_id` → Campaigns
- **Attributes:** amount, donation_date
- **Role:** Links donors to campaigns, triggers reward fulfillment

#### 🎁 Extended Entities (3 Tables)

**5. Rewards** - Campaign incentives for backers

- **Primary Key:** `reward_id`
- **Foreign Key:** `campaign_id` → Campaigns
- **Attributes:** description, min_amount
- **Role:** Defines reward tiers for different donation amounts

**6. Comments** - User feedback and discussions

- **Primary Key:** `comment_id`
- **Foreign Keys:** `user_id` → Users, `campaign_id` → Campaigns, `parent_comment_id` → Comments (SELF JOIN)
- **Attributes:** content, comment_date
- **Special:** Self-referencing foreign key for hierarchical comments (parent-child)
- **Location:** Displayed in Campaign Details modal (click View button on any campaign) ⭐
- **SQL Demo:** Demonstrates recursive relationships and threaded discussions

**7. Campaign_Updates** - Progress announcements

- **Primary Key:** `update_id`
- **Foreign Key:** `campaign_id` → Campaigns
- **Attributes:** title, content, posted_at
- **Role:** Campaign creators share progress with backers

#### 🔗 Bridge Tables - Many-to-Many Relationships (3 Tables)

**8. Campaign_Favorites** - User's saved campaigns

- **Composite Primary Key:** (`user_id`, `campaign_id`)
- **Foreign Keys:** `user_id` → Users, `campaign_id` → Campaigns
- **Relationship:** Users ↔️ Campaigns (M:N)
- **Purpose:** Users can favorite multiple campaigns

**9. Campaign_Category** - Multi-category assignments

- **Composite Primary Key:** (`campaign_id`, `category_id`)
- **Foreign Keys:** `campaign_id` → Campaigns, `category_id` → Categories
- **Relationship:** Campaigns ↔️ Categories (M:N)
- **Purpose:** Campaigns can belong to multiple categories

**10. Donor_Rewards** - Reward claim tracking

- **Composite Primary Key:** (`donor_id`, `reward_id`, `donation_id`)
- **Foreign Keys:** `donor_id` → Users, `reward_id` → Rewards, `donation_id` → Donations
- **Relationship:** Users ↔️ Rewards (M:N)
- **Purpose:** Tracks which donors claimed which rewards, with fulfillment status

#### 📋 Audit & Logging Tables (3 Tables)

**11. User_Audit_Log** - User change tracking

- **Primary Key:** `audit_id`
- **Attributes:** user_id, action_type, old_values, new_values, changed_at
- **Trigger:** Auto-populated by `trg_user_after_update`

**13. Donation_Audit_Log** - Donation event tracking

- **Primary Key:** `audit_id`
- **Attributes:** donation_id, action_type, amount, created_at
- **Trigger:** Auto-populated by `trg_donation_after_insert`

**12. Query_Log** - Educational query tracking

- **Primary Key:** `query_id`
- **Attributes:** query_text, query_type, execution_time, rows_affected
- **Purpose:** Logs all SQL queries for learning purposes

### Relationship Types

#### One-to-Many (1:N) Relationships

1. **Users → Campaigns** (user_id)

   - One user can create many campaigns
   - Each campaign has exactly one creator

2. **Categories → Campaigns** (via Campaign_Category bridge)

   - Categories link to campaigns through Campaign_Category table
   - Supports multi-category campaigns

3. **Campaigns → Donations**

   - One campaign can receive many donations
   - Each donation belongs to one campaign

4. **Users → Donations** (user_id)

   - One user can make many donations
   - Each donation is from one user

5. **Campaigns → Rewards**

   - One campaign can offer many rewards
   - Each reward belongs to one campaign

6. **Campaigns → Comments**

   - One campaign can have many comments
   - Each comment is about one campaign
   - **View Location:** Click "View" (👁️) button on any campaign to see all comments

7. **Users → Comments**

   - One user can write many comments
   - Each comment is by one user

8. **Comments → Comments** (SELF JOIN) ⭐

   - Comments can have replies to create threaded discussions
   - `parent_comment_id` references `comment_id` in the same table
   - Demonstrates **self-referencing foreign key** pattern
   - Parent comments have `parent_comment_id = NULL`
   - Reply comments reference their parent's `comment_id`

9. **Campaigns → Campaign_Updates**
   - One campaign can have many updates
   - Each update belongs to one campaign

#### Many-to-Many (M:N) Relationships

1. **Users ↔️ Campaigns** (Campaign_Favorites)

   - Users can favorite many campaigns
   - Campaigns can be favorited by many users
   - **Bridge Table:** Campaign_Favorites with composite key

2. **Campaigns ↔️ Categories** (Campaign_Category)

   - Campaigns can belong to many categories
   - Categories can contain many campaigns
   - **Bridge Table:** Campaign_Category with composite key

3. **Users ↔️ Rewards** (Donor_Rewards)
   - Donors can claim many rewards
   - Rewards can be claimed by many donors
   - **Bridge Table:** Donor_Rewards with 3-column composite key
   - **Special:** Includes donation_id to link specific donation to reward claim

### Key Design Patterns

✅ **Composite Keys** - All M:N bridge tables use composite primary keys  
✅ **Referential Integrity** - All foreign keys enforce CASCADE or SET NULL  
✅ **Self-Referencing** - Comments table for nested replies  
✅ **Audit Trail** - Trigger-populated audit tables for compliance  
✅ **Educational Logging** - Query_Log tracks all operations  
✅ **Normalized Design** - 3NF compliance, no redundant data  
✅ **CHECK Constraints** - Enforce business rules at database level  
✅ **Trigger Automation** - 9 triggers for data consistency  
✅ **Self-Referencing Keys** - Comments table demonstrates parent-child hierarchy ⭐

### Sample Data

The database includes realistic sample data:

- 15 users (admins, campaigners, donors)
- 10 categories
- 12 campaigns (active, completed, draft)
- 50+ donations across different campaigns
- 27 comments with hierarchical replies ⭐ NEW!
- Campaign favorites and category assignments
- Reward tiers with fulfillment tracking

### Cardinality Summary

| Relationship         | Type | Bridge Table       | SQL Demo                         |
| -------------------- | ---- | ------------------ | -------------------------------- |
| User → Campaigns     | 1:N  | -                  | INNER JOIN, GROUP BY             |
| Campaign → Donations | 1:N  | -                  | LEFT JOIN, Aggregation           |
| Campaign → Rewards   | 1:N  | -                  | Multi-table JOIN                 |
| User ↔ Campaign      | M:N  | Campaign_Favorites | Bridge table pattern             |
| Campaign ↔ Category  | M:N  | Campaign_Category  | Many-to-many JOIN                |
| User ↔ Reward        | M:N  | Donor_Rewards      | 3-column composite key           |
| Comment → Comment    | 1:N  | -                  | SELF JOIN (parent_comment_id) ⭐ |

> 💡 **Pro Tip:** Visit the Rewards page to see a real-world many-to-many relationship in action with 4-table JOINs across Users, Rewards, Campaigns, and Donations!

> 💬 **Comments Feature:** Click the "View" (👁️) button on any campaign to see all comments including hierarchical replies demonstrating self-referencing foreign keys!

## 🏗️ Database Schema Highlights

### Core Tables (12 Total)

**Main Tables:**

- **Users** - User accounts with role-based access, CHECK constraints
- **Categories** - Campaign categories
- **Campaigns** - Crowdfunding campaigns with FOREIGN KEY CASCADE
- **Donations** - Donation transactions with status tracking

**Relationship Tables:**

- **Comments** - Nested comments (SELF JOIN demo)
- **Campaign_Updates** - Campaign progress updates
- **Campaign_Favorites** - User favorites (many-to-many)
- **Rewards** - Campaign reward tiers

**Audit Tables:**

- **User_Audit_Log** - Tracks user changes (Trigger demo)
- **Donation_Audit_Log** - Tracks donation events
- **Query_Log** - Educational query tracking with execution metrics

### Views (4 Total)

- **Campaign_Progress** - Complex calculations with JOINs and aggregations
- **User_Statistics** - Multi-table aggregations across users, campaigns, donations
- **Top_Donors** - Donor ranking with ORDER BY
- **Active_Campaigns_Summary** - Filtered view with date calculations

### Stored Procedures (5 Total)

- **Process_Donation** - Transaction handling with BEGIN/COMMIT/ROLLBACK
- **Get_Campaign_Analytics** - Complex reporting with subqueries
- **Update_Campaign_Status** - Conditional updates with CASE
- **Get_User_Donation_History** - Parameterized queries with JOINs
- **Calculate_Platform_Statistics** - System-wide statistics

### Functions (4 Total)

- **Get_Donor_Level** - CASE-based donor categorization
- **Calculate_Campaign_Progress** - Mathematical percentage calculations
- **Get_Days_Until_End** - Date arithmetic with DATEDIFF
- **Is_Campaign_Fully_Funded** - Boolean logic return

### Triggers (5 Total)

- **trg_donation_after_insert** - AFTER INSERT trigger for audit logging
- **trg_user_after_update** - AFTER UPDATE trigger for audit trail
- **trg_user_before_delete** - BEFORE DELETE trigger for cascade tracking
- **trg_campaign_before_insert** - BEFORE INSERT trigger for validation
- **trg_campaign_after_delete** - AFTER DELETE trigger for cleanup

## 🔧 Configuration

### Database Configuration

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'CF_Tracker');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your password
```

### PHP Requirements

- PHP 8.0+
- PDO extension enabled
- MySQL extension enabled

## 📝 Sample Data

The `sample_data.sql` includes:

- 15 users (admins, campaigners, donors)
- 10 categories
- 12 campaigns (active, completed, draft)
- 50+ donations
- Comments and interactions
- User relationships

## 🐛 Troubleshooting

### Database Connection Error

```
Error: Database connection failed
```

**Solution**:

1. Ensure MySQL is running in XAMPP Control Panel
2. Verify database credentials in `config/database.php`
3. Check if `CF_Tracker` database exists in phpMyAdmin
4. Ensure PDO MySQL extension is enabled in php.ini

### Table Doesn't Exist Error

```
Error: Table 'cf_tracker.donations' doesn't exist
```

**Solution**:

1. Re-import `database/schema_no_drop.sql` (⭐ use this file, not schema.sql)
2. Then import `database/sample_data.sql`
3. Verify tables exist in phpMyAdmin

### Column Not Found Error

```
Error: Unknown column 'query_id'
```

**Solution**:

1. Use `schema_no_drop.sql` for import (it has the correct column names)
2. Table column names changed during development
3. Clear browser cache and refresh

### Queries Not Appearing in Query_Log

**Solution**:

1. Check Query_Log table exists: `SELECT * FROM Query_Log`
2. Verify `config/database.php` has `logQuery()` function
3. Ensure session is started (`session_start()` called)

### Search Feature Not Finding Triggers

**Solution**:

- Triggers are defined in schema files, not executed as queries
- Use "Found in Schema & SQL Files" section (purple box)
- They won't appear in "Executed Queries" section (blue box)

### XAMPP MySQL Won't Start

**Solution**:

1. Check if port 3306 is already in use
2. Stop other MySQL services in Windows Services
3. Check XAMPP error logs in `xampp\mysql\data\`
4. Try changing MySQL port in `my.ini`

> 📘 **Need more help?** See `COMPLETE_PROJECT_GUIDE.md` section "Troubleshooting" for detailed solutions to 6+ common issues.

## 🎯 Learning Path

### Beginner

1. Start with **Dashboard** - Basic SELECT queries
2. Explore **SQL Features** page - See all demonstrations
3. Hover over elements to see queries

### Intermediate

1. **Campaigns Page** - CRUD operations
2. **Analytics Page** - Aggregations and GROUP BY
3. Study VIEW and JOIN operations

### Advanced

1. **Donations Page** - Transactions and triggers
2. Examine stored procedures in phpMyAdmin
3. Study CTEs and window functions
4. Review audit trails and logging

## 📚 Additional Resources

### Project Documentation

- 📖 **COMPLETE_PROJECT_GUIDE.md** - Comprehensive 500+ line guide covering:

  - Complete setup instructions (3 methods)
  - All 80+ SQL features explained
  - Database schema details
  - Troubleshooting guide
  - Maintenance and backup
  - Educational use tips

- 📖 **ER_DIAGRAM_GUIDE.md** ⭐ NEW! - Entity-Relationship diagram documentation:

  - How to visualize with GraphvizOnline
  - Color coding and symbol explanations
  - All 13 relationships explained (9 1:N + 4 M:N)
  - Database statistics and design patterns
  - Query planning based on ER structure

- 📖 **SCHEMA_UPDATES.md** ⭐ NEW! - Detailed schema changes documentation:

  - Rewards system implementation
  - Campaign_Category M:N relationship fix
  - Trigger automation details
  - Testing queries and examples

- 📖 **IMPLEMENTATION_SUMMARY.md** ⭐ NEW! - Executive summary:

  - What was added/fixed
  - Database statistics
  - ER diagram verification
  - Quick reference guide

- 📄 **FILES_TO_KEEP_DELETE.md** - Quick reference for file management

### MySQL Documentation

- [MySQL 8.0 Reference Manual](https://dev.mysql.com/doc/refman/8.0/en/)
- [Stored Procedures](https://dev.mysql.com/doc/refman/8.0/en/stored-programs.html)
- [Triggers](https://dev.mysql.com/doc/refman/8.0/en/triggers.html)
- [Window Functions](https://dev.mysql.com/doc/refman/8.0/en/window-functions.html)

### PHP Documentation

- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)

## 🎯 Project Statistics

- **📊 13 Database Tables** with relationships (Core + Bridge + Audit)
- **🔗 3 Many-to-Many Relationships** (User-Campaign, Campaign-Category, Donor-Reward)
- **👁️ 4 Views** for data abstraction
- **⚙️ 5 Stored Procedures** for business logic
- **🔧 4 Custom Functions** for calculations
- **⚡ 9 Triggers** for automation (+4 reward triggers!)
- **📑 80+ SQL Features** demonstrated (updated!)
- **🔍 Query Search** with schema file parsing
- **📱 7 Interactive Pages** (Dashboard, Campaigns, Donations, Rewards, Analytics, Users, SQL Features)
- **💬 27 Sample Comments** demonstrating hierarchical relationships ⭐ NEW!
- **📄 AJAX Modals** for dynamic content loading ⭐ NEW!
- **📝 1000+ Lines** of comprehensive documentation

## 🤝 Contributing

This is an educational project. Feel free to:

- Add more SQL examples
- Improve documentation
- Enhance UI/UX
- Report bugs
- Suggest new features

## 📄 License

MIT License - Free to use for educational purposes

## 👨‍💻 Author

Created as an educational SQL demonstration platform for comprehensive Oracle SQL curriculum coverage

## 🙏 Acknowledgments

- Oracle SQL Curriculum
- MySQL 8.0 Documentation
- Tailwind CSS Framework
- Font Awesome Icons
- PHP PDO Library

---

## 🎓 Quick Tips

**For Students:**

- ✅ Use the Query Search feature to find examples of any SQL operation
- ✅ Hover over elements to see the exact SQL being executed
- ✅ Check Query_Log table to review your query history
- ✅ Experiment with different queries in phpMyAdmin

**For Teachers:**

- ✅ All 80+ features are documented with live examples
- ✅ Query logging shows student activity
- ✅ Use as a reference for SQL curriculum
- ✅ Demonstrate real-world database design
- ✅ Show many-to-many relationships with Rewards system

**Search Examples:**

```
SELECT          → Find all SELECT queries
JOIN            → Find all JOIN operations
trigger         → Find trigger definitions
PROCEDURE       → Find stored procedures
GROUP BY        → Find aggregation examples
Campaigns       → Find queries using specific table
Rewards         → Find reward-related queries
```

---

**🎓 Remember**: This platform is designed for **learning SQL**. Every interaction demonstrates real SQL queries. Use the Query Search feature to explore and learn!

**💡 Pro Tip**: Search for "trigger" or "PROCEDURE" in SQL Features page to see their definitions in the schema with line numbers and context!

**🎁 New Feature**: Check out the Rewards page to see many-to-many relationships, trigger automation, and 4-table JOINs in action!

---

**Project Status:** ✅ **Complete & Production Ready**  
**Last Updated:** October 26, 2025  
**Version:** 2.2
