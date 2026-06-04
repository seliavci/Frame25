Frame 25 - Social Cinema Network & Archiving Platform
Frame 25 is a multi-layered, scalable social cinema platform designed to transform movie-watching experiences into a disciplined digital memory, specifically catering to enthusiasts of Auteur cinema.


Project Overview
In an era of rapid digital content consumption, tracking artistic works has become increasingly difficult. 
Frame 25 serves as a "niche" cultural memory space, allowing users to chronologically archive their viewing history and share artistic analyses through an aesthetic, user-centric interface.


Technical Architecture & EngineeringThe project is built upon a robust, multi-layered stack designed to meet modern web engineering standards:
Backend: PHP 8.x utilized for dynamic content management and business logic execution.
Database: A highly optimized MySQL architecture comprising 15 tables, normalized according to Third Normal Form (3NF) rules to ensure data integrity and query performance.
Frontend: Implements the "Glassmorphism" design language using HTML5 and CSS3 to create visual depth and a modern aesthetic.
Data Visualization: Integration of the Chart.js library to present user viewing statistics (e.g., genre distribution, watch rate) via dynamic graphics. 
API Integration: Asynchronous data processing via TMDB API for real-time access to global film databases.


Security & Data IntegrityThe system emphasizes security protocols throughout its development lifecycle:  
SQL Injection Protection: Implementation of Prepared Statements across all database queries.  
Data Privacy: Utilization of the password_hash algorithm to secure user credentials.  
Audit Mechanism: A unique logging protocol integrated to track critical system errors and operational logs.  
Referential Integrity: Enforced consistency through the use of Foreign Keys across the relational database model.


Administration & ManagementFrame 25 features a centralized Content Management System (CMS) enabling full control over the platform:  
Dynamic CMS: Manage slider content, navigation menus, and news feeds without code intervention.  
Role-Based Access Control (RBAC): Hierarchical permissions assigned to Super Admin, Editor, and Moderator roles.  
Reporting: Capability to export system records and user statistics in PDF/CSV formats.  


/Frame25
├── /admin          # CMS modules and management interfaces
├── /assets         # Glassmorphism-styled CSS, JS, and media assets
├── /config         # Secure PDO-based database connection classes
├── /includes       # Modular header, footer, and sidebar components
├── /api            # Asynchronous TMDB API data processing logic
└── index.php       # Main application entry point


Developed by: Selin Avcı 
















