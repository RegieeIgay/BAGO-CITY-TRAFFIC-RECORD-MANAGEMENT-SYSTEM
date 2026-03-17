BAGO CITY TRAFFIC RECORD MANAGEMENT SYSTEM (BCTRMS)
1. DATABASE SETUP (XAMPP)
Start Services: Open your XAMPP Control Panel and ensure Apache and MySQL are both running (green).

Access Tool: Open your browser and go to: http://localhost/phpmyadmin.

Create DB: On the left sidebar, click New.

Database Name: Under Database name, type exactly: bago_traffic_db.

Collation: Keep the collation as utf8mb4_general_ci (default) and click Create.

2. IMPORTING THE SQL DATA
Select DB: Click on your newly created bago_traffic_db in the left sidebar.

Navigate: Click the Import tab at the top of the screen.

Select File: Click the Choose File button and navigate to: C:\xampp\htdocs\BCTRMS\database\.

Upload: Select the SQL file, click Open, and then scroll to the bottom and click Import (or Go).

Success: You should see a green message: "Import has been successfully finished."

3. SYSTEM COMPONENTS & USER ROLES
The system logic is divided by user roles (Admin vs. Traffic Enforcer) to ensure data security.

A. Driver Management (drivers.php)
Admin: Authorized to Add, Edit, and Delete all driver profiles, license information, and photos.

Traffic Enforcer: Granted View & Search access only to verify driver identities in the field; cannot modify records.

B. Vehicle Management (vehicles.php)
Admin: Full management of the vehicle database, including Adding, Editing, and Deleting vehicle records.

Traffic Enforcer: View & Search access only to verify plate numbers and ownership details.

C. Violation Types (violation_types.php)
Admin: Manages the list of legal offenses and their specific fine amounts.

Traffic Enforcer: View Only access to ensure the correct offense is chosen when recording a citation.

D. Operational & Reporting Modules
Violation Records: Both roles can view and manage citations and identify Repeat Offenders.

Accident Records: Comprehensive database for reporting and viewing road traffic incidents.

Reports Section: Admin-level access to Main Reports and Individual Reports for data analysis and printing.

4. SYSTEM ACCESS
Login URL: http://localhost/BCTRMS/login.php.

File Path: Ensure the project is located at C:\xampp\htdocs\BCTRMS\.