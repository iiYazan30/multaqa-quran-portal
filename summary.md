# 🧠📌 Quran Portal – Project Documentation

## 🎯 Project Overview

This project is a **web-based portal for a Quran Memorization Program (ملتقى القرآن)** at An-Najah National University.

The system is designed to:

* Track students’ memorization progress
* Manage supervisors and حلقات (study groups)
* Handle weekly recitation (تسميع) records
* Manage exams and results
* Generate statistics and performance insights

The system has a **hierarchical structure of roles** and focuses on **monitoring performance and engagement with Quran memorization**.

---

## 🧩 System Structure

### 📍 Colleges (Locations)

* كلية الهندسة
* كلية الطب
* الحرم القديم

Each college contains:

* Supervisors (مشرفين)
* حلقات (groups)
* Students

---

## 🏗️ Hierarchy

* 🔴 **General Manager (مسؤول عام)** → manages all colleges
* 🟡 **College Manager (مسؤول كلية)** → manages one college
* 🔵 **Supervisor (مشرف)** → manages حلقات
* 🟢 **Student (طالب)** → belongs to one حلقة
* 🟣 **Exam Manager (مسؤول الامتحانات)** → manages exams

---

## 👥 User Roles & Permissions

### 🟢 Student

* View personal progress
* View memorized pages
* View exams and results
* Request exams

---

### 🔵 Supervisor

* Manage students in their حلقات
* Add weekly recitation records
* Track performance

---

### 🟡 College Manager

* View all students and supervisors
* Monitor performance
* View statistics
* Identify top students and حلقات

---

### 🔴 General Manager

* View all colleges
* Compare performance
* Access global statistics

---

### 🟣 Exam Manager

* View exam requests
* Schedule exams
* Enter results

---

## 📊 Core Features

### 1. Weekly Recitation (التسميع)

Supervisors record:

* Type:

    * حفظ
    * مراجعة
* Page range

System calculates:

* Number of pages
* Points

### 🎯 Points System

* حفظ → 5 points per page
* مراجعة → 1 point per page
* Exam → 10 points

---

### 2. Exams System

* Student requests exam
* Exam manager reviews
* Result is recorded:

    * جزء number
    * Score
    * Date

---

### 3. Dashboard & Statistics

* Number of students
* Number of حلقات
* Pages memorized
* Total points
* Top students
* Best حلقات

---

### 4. Featured Sections

* Top students
* Best حلقات
* High exam scores (>98)

---

## 🎨 UI / Design Concept

### 🎨 Colors

* Olive Green (Primary)
* White (Background)
* Gold (Accent)

### ✨ Style

* Clean
* Minimal
* Elegant
* Islamic-inspired

---

## 🧱 Layout System

All pages (except login) use:

* Sidebar (right)
* Topbar
* Main content

Layout is reusable across all roles.

---

# 📄 Pages Description

## 1. Login Page

* Inputs:

    * User ID
    * Password
* Role detected automatically
* Includes:

    * Quran visual
    * Verse / quote

---

## 2. Dashboard (College Manager)

Contains:

* Statistics cards
* Best حلقات
* Top students
* Recent activity

---

## 3. Students Page

* List of students
* Columns:

    * Name
    * حلقة
    * Supervisor
    * Type
    * Points

---

## 4. Halqas Page

* List of حلقات
* Includes:

    * Supervisor
    * Student count
    * Performance

---

## 5. Weekly Recitation Page

* Form:

    * Student
    * Type
    * Pages
* Auto-calculates points

---

## 6. Exams Page

* Requests list
* Status:

    * Pending
    * Completed
* Enter results

---

## 7. Reports Page

* Charts
* Performance analysis

---

## 8. Student Profile Page

* Progress
* Pages
* Exams
* Points

---

## 9. Featured Section

* Top students
* Best حلقات
* High scorers

---

## 🧠 Development Notes

* Current stage: **Frontend only**
* Future: **PHP + Database**
* Data is currently **mock data**
* UI must be scalable

---

## 🎯 Key Philosophy

* One unified design
* Role-based content
* Clean structure
* Scalable system

---

## 🏆 Summary

This is a **complete management system for Quran memorization**, focused on:

* Tracking progress
* Monitoring performance
* Organizing learning groups
* Supporting continuous Quran engagement

---
