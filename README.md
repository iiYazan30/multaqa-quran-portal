# Multaqa Quran Portal

## Project Overview

Multaqa Quran Portal is a Quran Halqa Management Portal designed to help manage the daily and weekly operations of Quran learning circles. The system provides tools for organizing students, supervisors, halqas, weekly recitations, exams, reports, and statistics through a dynamic web-based interface connected to a MySQL database.

The portal is intended to support structured follow-up between management, supervisors, and students while keeping academic and recitation records organized and accessible.

## Features

### Manager

- Manage student accounts and information.
- Manage supervisor accounts and assignments.
- Create, update, and organize halqas.
- View weekly recitation records.
- Manage exams and student evaluation data.
- Access reports and statistical summaries.
- Monitor overall portal activity and performance.

### Supervisor

- View assigned halqas.
- Manage students within supervised halqas.
- Record weekly Quran recitations.
- Follow up on student progress.
- Manage exam-related records for assigned students.
- View relevant reports and statistics.

### Student

- Access personal account information.
- View assigned halqa details.
- Review weekly recitation records.
- View exam records and progress information.
- Follow personal performance through available reports.

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP
- phpMyAdmin

## Installation / Setup Instructions

Follow these steps to run the project locally:

1. Install and open XAMPP.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Place the project folder inside the XAMPP `htdocs` directory.

   Example path:

   ```text
   C:\xampp\htdocs\multaqa-quran-portal
   ```

4. Open phpMyAdmin in your browser:

   ```text
   http://localhost/phpmyadmin
   ```

5. Create a new database if needed.
6. Import the database file:

   ```text
   multaqadb.sql
   ```

7. Open the project in your browser:

   ```text
   http://localhost/multaqa-quran-portal
   ```

## Database Import Instructions

The project database is included in the root directory as:

```text
multaqadb.sql
```

To import it:

1. Open phpMyAdmin.
2. Select or create the target database.
3. Go to the **Import** tab.
4. Choose `multaqadb.sql`.
5. Click **Import** and wait for the process to finish.

## Demo Login Credentials

### Manager Accounts

| Username | Password |
| --- | --- |
| `manager1` | `123456` |

### Supervisor Accounts

All supervisor accounts use the password `123456`.

| Username | Password |
| --- | --- |
| `supervisor1` | `123456` |
| `supervisor2` | `123456` |
| `supervisor3` | `123456` |
| `supervisor4` | `123456` |
| `supervisor5` | `123456` |
| `supervisor6` | `123456` |

### Student Accounts

All student accounts use the password `123456`.

| Username | Password |
| --- | --- |
| `student1` | `123456` |
| `student2` | `123456` |
| `student3` | `123456` |
| `student4` | `123456` |
| `student5` | `123456` |
| `student6` | `123456` |
| `student7` | `123456` |
| `student8` | `123456` |
| `student9` | `123456` |
| `student10` | `123456` |
| `student11` | `123456` |
| `student12` | `123456` |
| `student13` | `123456` |
| `student14` | `123456` |
| `student15` | `123456` |
| `student16` | `123456` |
| `student17` | `123456` |
| `student18` | `123456` |
| `student19` | `123456` |
| `student20` | `123456` |
| `student21` | `123456` |
| `student22` | `123456` |
| `student23` | `123456` |
| `student24` | `123456` |
| `student25` | `123456` |
| `student26` | `123456` |
| `student27` | `123456` |
| `student28` | `123456` |
| `student29` | `123456` |
| `student30` | `123456` |
| `student31` | `123456` |

## Notes

- This project uses demo data for testing and presentation purposes.
- The system is dynamic and connected to a MySQL database.
- The project can be extended in the future with additional roles, reports, permissions, and management features.
