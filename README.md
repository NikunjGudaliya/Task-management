# 📋 Acorn Task Manager

A simple, readable Task Management web application built with **Laravel 11**, **MySQL**, and the **Acorn Admin Theme** style layout. It supports task creation, editing, deletion, project-based filtering, and real-time drag-and-drop priority sorting.

---

## Features

1. **Task CRUD**: Create, read, edit, and delete tasks.
2. **Project Filtering**: Group tasks by project and filter the dashboard to view only tasks associated with a selected project.
3. **Drag-and-Drop Reordering**: Uses `SortableJS` in the frontend to let you drag tasks around. Dropping automatically updates task priority sequence via AJAX in the MySQL database.
4. **Acorn Styling**: Loaded with the exact stylesheet assets and layout styling from the parent ERP project.

---

## Installation & Setup

Follow these steps to run the application locally:

### 1. Database Creation
Create a new MySQL database named `task_manager_db` in your MySQL server (e.g. phpMyAdmin or Command Line):
```sql
CREATE DATABASE task_manager_db;
```

### 2. Configure Environment Variables
Open the `.env` file in the root of the project and make sure your MySQL database credentials are set correctly:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Generate Application Key & Run Migrations
Run composer post-install setup commands to prepare the database structure:
```bash
php artisan key:generate
php artisan migrate
```

### 4. Running the Application
Start the local Laravel development server:
```bash
php artisan serve
```
Open your browser and navigate to `http://127.0.0.1:8000`.

---

## Running Tests

To verify that the CRUD operations and reordering functionality works as intended:
```bash
php artisan test
```
