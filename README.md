# NASCON Event Management System

## 📘 Subject: Software Engineering  
**National University of Computer and Emerging Sciences**

### 👥 By:
- Muhammad Mubeen (21I-0794)  
- Maaz Masood (21I-2551)  
- Syed Ali (22I-XXXX)

### 👨‍🏫 Submitted to:
Mr. Naveed Khursheed

---

## 📌 Introduction

### Purpose
This document describes the initial development phase of the **NASCON Event Management System**, focusing on the **User Registration and Login Module**.

### Document Conventions
- **Bold** for section titles and emphasis.
- `Monospace` for code/commands.
- Requirements are labeled by priority:
  - **High** – Critical
  - **Medium** – Useful
  - **Low** – Optional

### Intended Audience
- Developers
- Project Managers
- Testers
- University Administration / Event Organizers

### Product Scope
Centralized event management for:
- Hackathons
- Concerts
- Food Stalls
- Sports Competitions

### References
- Assignment 1 Document  
- IEEE SRS Template  
- GitHub Repo: *(Add Link)*  
- Trello Board: *(Add Link)*  

---

## 💡 Overall Description

### Product Perspective
A self-contained platform replacing manual event handling.

### Product Functions
- 👤 User Registration/Login
- 📅 Event Registration
- 🧑‍💼 User Profile Management

### User Roles
- **Students** – Register & view events  
- **Outsiders** – Register for limited events  
- **Organizers** – Manage events  
- **Admins** – Full access  

### Operating Environment
- Web-based (Chrome-compatible)
- OS: Windows
- Backend: MySQL
- Frontend: JavaScript / Express.js

### Constraints
- Must comply with university data policies
- Encryption for user authentication

---

## 📄 User Documentation
- User Manual
- Online Help / FAQs
- Admin Training Sessions

---

## 🔗 External Interface Requirements

### User Interfaces
- **Login Screen**
- **Registration Form**
- **Profile Management**
- **Error Handling**

### Hardware Interfaces
- Keyboard, mouse, monitor
- Internet connection

### Software Interfaces
- **Database**: MySQL
- **Email**: SMTP (Outlook integration)
- **Web**: Chrome v95+
- **Framework**: Express.js / ASP.NET Core
- **API**: JSON-based (Future: REST)
- **Security**: HTTPS, AES-256 encryption

### Communication Interfaces
- HTTPS with TLS 1.2+
- MIME for emails
- JSON for browser-server communication

---

## ✨ System Features

### 1. User Registration & Login
- **REQ-1**: Validate user input
- **REQ-2**: Authenticate login
- **REQ-3**: Send confirmation email

### 2. Event Registration
- **REQ-4**: Display events
- **REQ-5**: Validate eligibility
- **REQ-6**: Confirm registration (email + message)

### 3. Profile Management
- **REQ-7**: View profile
- **REQ-8**: Edit details
- **REQ-9**: Confirm updates

---

## 🧰 Non-Functional Requirements

### Performance
- <2s response time
- Support 100 concurrent users

### Safety
- Backup & recovery
- Data integrity protocols

### Security
- AES-256 encrypted passwords
- MFA for admin
- HTTPS/TLS for data
- Role-based access

### Quality Attributes
- **Reliability**: 99.9% uptime  
- **Maintainability**: Modular code  
- **Scalability**: Expandable system  
- **Usability**: Intuitive UI  

### Business Rules
- Only registered users can register for events
- Organizers manage only their events
- Admins can manage all content
- Registration closes 24 hours before event

---

## 🏃 Sprint 1 Backlog (2 Weeks)

### Modules: User Registration, Login & Profile

| User Story | Goal | Sub-Tasks |
|------------|------|-----------|
| Student Registration | Allow student sign-up | Form UI, Validate, DB store, Email |
| Outsider Registration | Allow outsider sign-up | Role field, Email verification |
| User Login | Login functionality | Form UI, Encrypt, Authenticate |
| Invalid Login Feedback | Show errors on failure | Display message, Log attempt |
| Dashboard Access | View user data | Layout, Fetch user data |
| View Profile | See user info | Profile page, DB fetch |
| Edit Profile | Update info | Editable fields, Validate, Save |
| Password Recovery | Reset forgotten password | Forgot UI, Email, Secure link |
| Input Validation | Prevent invalid input | HTML5 checks, Regex |
| Confirmation Email | Notify on registration | SMTP, Template, Trigger email |

---

## 🧪 Software Testing

### Equivalence Class Partitioning

| Field | Valid | Invalid | Remarks |
|-------|-------|---------|---------|
| Email | Valid format | Missing '@', domain | Regex |
| Password | 8–16 alphanumeric | <8 chars, no num/letter | Encrypted |
| Username | Alphabetic, ≥3 chars | <3 chars, numeric only | Displayed |
| Phone # | 11-digit numeric | <11 or >11 digits | Optional |
| Role | Student, Outsider, Admin | Empty, invalid | Dropdown enforced |

### Weak & Strong Equivalence

- **WECT**: 20 test cases
- **SECT**: 32–40 test cases

### Boundary Value Analysis

| Field | Valid | Invalid | Note |
|-------|-------|---------|------|
| Email | Valid format | Empty, near-invalid | Structural rules |
| Password | 8–16 chars | 7, 17 chars | Fixed range |
| Username | 3+ chars | <3 chars | Min = 3 |
| Phone # | Exactly 11 digits | <11 or >11 digits | Strict length |

---
