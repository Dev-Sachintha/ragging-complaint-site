# University Ragging Complaint & Reporting System

![University Anti-Ragging Portal](https://via.placeholder.com/1200x400.png?text=University+Anti-Ragging+Portal+Banner) <!-- Replace with an actual screenshot or banner image -->

This project is a secure, web-based system designed to empower students to report incidents of ragging or bullying within a university environment. It prioritizes user anonymity, facilitates evidence submission, provides immediate guidance through an AI-powered chatbot, and ensures timely admin awareness via SMS notifications.

---

## 📹 Demo Video (Optional)

<!--
(If you create a demo video, embed it or link it here)
Example:
[![Watch the Demo](https://img.youtube.com/vi/YOUR_VIDEO_ID/0.jpg)](https://www.youtube.com/watch?v=YOUR_VIDEO_ID)
-->
*A brief video showcasing the website's features and usage will be added here.*

---

## Table of Contents

*   [Overview](#overview)
*   [Hosted Link](#hosted-link)
*   [Key Features](#key-features)
*   [Technologies Used](#technologies-used)
*   [Usage](#usage)
    *   [Submitting a Complaint](#submitting-a-complaint)
    *   [Interacting with the Chat Assistant](#interacting-with-the-chat-assistant)
    *   [Admin Panel](#admin-panel)
*   [Installation & Setup](#installation--setup)
    *   [Prerequisites](#prerequisites)
    *   [Cloning the Repository](#cloning-the-repository)
    *   [Backend Dependencies (Composer)](#backend-dependencies-composer)
    *   [Environment Variables (.env)](#environment-variables-env)
    *   [Database Setup](#database-setup)
    *   [Web Server Configuration (XAMPP/LAMP/MAMP)](#web-server-configuration-xampplampmamp)
    *   [Running the Application](#running-the-application)
*   [Folder Structure](#folder-structure)
*   [Security Considerations](#security-considerations)
*   [Future Enhancements](#future-enhancements)
*   [Contributing](#contributing)
*   [License](#license)

---

## Overview

The University Ragging Complaint System aims to create a safer campus by providing a confidential and accessible platform for students to report ragging incidents. It incorporates modern web technologies and APIs to offer a comprehensive solution, including anonymous reporting, evidence upload, AI-driven assistance, and real-time admin alerts.

---

## Hosted Link

🔗 **Live Demo:** [https://your-deployment-link.com](https://your-deployment-link.com) <!-- Replace with your actual deployment link if available -->
*(Note: If not deployed, state: "This project is currently set up for local development.")*

---

## Key Features

*   ✅ **Anonymous Complaint Submission:** Students can submit detailed complaints without needing to disclose their identity, fostering a safer reporting environment.
*   📂 **File Upload Support:** Users can upload images, audio, or video files as evidence to support their complaints.
*   🤖 **AI Chat Assistant:** An integrated chatbot, powered by the OpenAI API, provides users with instant answers to frequently asked questions, guidance on anti-ragging policies, and information on how to use the portal.
*   📱 **Admin SMS Notifications (Twilio):** Predefined administrators receive real-time SMS alerts via Twilio when a new complaint is submitted, ensuring prompt awareness and action.
*   🔒 **Secure Admin Panel:** A password-protected dashboard for administrators to view, manage, and update the status of submitted complaints.
*   🛡️ **Backend Security:**
    *   Uses **Prepared Statements** (via mysqli) to prevent SQL injection vulnerabilities.
    *   Recommends **Environment Variables** for secure management of API keys and database credentials.
    *   Input sanitization and validation on both client and server-side.
*   📧 **Email Notifications:**
    *   Admins receive email notifications for new complaints.
    *   Complainants (if they provide contact info and are not anonymous) receive email updates on their complaint status.
    *   Admins can manually forward complaint details via email.
*   📋 **Complaint Management:** Admins can filter complaints, mark them as "Pending", "In Review", or "Resolved", and view full details.
*   📄 **Informative Pages:** Includes "About & Help" pages detailing anti-ragging laws, university policies, and FAQs.
*   🎨 **Responsive Design:** Built with Bootstrap for a user-friendly experience across various devices.

---

## Technologies Used

*   **Frontend:**
    *   HTML5
    *   CSS3 (with Bootstrap 5 for styling)
    *   JavaScript (for client-side interactions and AJAX for the chatbot)
*   **Backend:**
    *   PHP (Procedural with mysqli for database interaction)
*   **Database:**
    *   MySQL / MariaDB
*   **Libraries & APIs:**
    *   **PHPMailer:** For sending email notifications.
    *   **Twilio PHP SDK:** For sending SMS notifications.
    *   **OpenAI API (GPT-3.5 Turbo / GPT-4):** For the AI Chat Assistant.
    *   **Composer:** For PHP package management.
    *   **(Optional) DataTables.js:** For enhanced table functionality in the admin dashboard.
*   **Development Environment:**
    *   XAMPP (or similar LAMP/MAMP stack)

---

## Usage

### Submitting a Complaint

1.  Navigate to the "File Complaint" page.
2.  Fill in the required details about the incident: department, date, time, location, and a detailed description.
3.  Optionally, upload evidence files (images, audio, video).
4.  Choose to submit anonymously by checking the "Submit Anonymously" box. If unchecked, you can provide your name and contact information (email or phone number in E.164 format for SMS updates).
5.  Click "Submit Complaint".

### Interacting with the Chat Assistant

1.  Locate the chat widget (usually in the bottom-right corner).
2.  Click the chat header to open the chat window.
3.  Type your question related to ragging, university policies, or how to use the portal.
4.  Press Enter or click "Send". The AI assistant will provide a response.

### Admin Panel

*(Requires login credentials)*
1.  Navigate to the "Admin Login" page and enter credentials.
2.  **Dashboard:** View all submitted complaints. Filter by status, date, or department. View truncated descriptions and evidence links.
3.  **View Details:** Click "View Details" for a specific complaint to see all submitted information and evidence.
4.  **Update Status:** Change the status of a complaint (Pending, In Review, Resolved). If the complainant provided contact details, they will receive an email/SMS update.
5.  **Email Complaint:** Manually forward the full details of a complaint to a specified email address.

---

## Installation & Setup

### Prerequisites

*   A web server environment (XAMPP, WAMP, MAMP, or a LAMP/LEMP stack)
*   PHP (version 7.4 or higher recommended)
*   MySQL or MariaDB
*   Composer (PHP package manager)
*   A Twilio account (Account SID, Auth Token, Twilio Phone Number)
*   An OpenAI API Key

### Cloning the Repository

```bash
git clone https://github.com/Dev-Sachintha/ragging-complaint-site.git
cd ragging-complaint-site
