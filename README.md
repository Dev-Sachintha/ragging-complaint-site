Ragging Complaint Site Documentation
📹 Demo Video (Optional)

Table of Contents

Overview

Hosted Link

Features

Technologies Used

Usage

Installation

Overview
This project is a secure web-based system that allows students to anonymously report incidents of ragging or bullying. It includes file upload support for evidence, a chatbot assistant powered by OpenAI, and automated SMS notifications via Twilio for admin alerts. Built using PHP, HTML, CSS, JavaScript, and MySQL.

Hosted Link
🔗 https://your-deployment-link.com


Features
Anonymous Complaint Submission

Students can submit detailed complaints without disclosing identity.

File Upload Support

Upload images/videos as evidence of the incident.

AI Chat Assistant

Integrated chatbot using OpenAI API for guidance or FAQs.

Admin Notifications via SMS

Real-time alerts sent to predefined admin numbers using Twilio.

Secure Backend

Uses prepared statements (PDO) and environment variables for API security.

Technologies Used
Frontend: HTML, CSS, JavaScript

Backend: PHP

Database: MySQL

APIs:

OpenAI API

Twilio SMS API

Usage
Submit Complaint

Fill out the complaint form and upload supporting files.

Interact with Chat Assistant

Ask questions or get guidance using the integrated chatbot.

Admin Access

Receive complaint alerts via SMS and manage reports (if applicable).

Installation
1. Clone the Repository
bash
Copy
Edit
git clone https://github.com/Dev-Sachintha/ragging-complaint-site.git
cd ragging-complaint-site
2. Set Up .env File
Create a .env file in the root directory:

dotenv
Copy
Edit
OPENAI_API_KEY=your_openai_key_here
TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_PHONE_NUMBER=+1234567890
ADMIN_PHONE_1=+94775123456
3. Install PHP Dotenv
bash
Copy
Edit
composer require vlucas/phpdotenv
4. Start XAMPP Server
Place the project in htdocs folder.

Start Apache and MySQL in XAMPP.

Import database.sql (if available) into phpMyAdmin.

5. Access the App
Open browser and go to:
http://localhost/ragging-complaint-site
