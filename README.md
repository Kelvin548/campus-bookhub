# Campus BookHub 📚

**Campus BookHub** is a web-based management system designed to streamline the ordering, tracking, and pickup verification of academic course manuals and books for university students and course representatives.

---

## 🛠️ Tech Stack & Prerequisites

- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5, Bootstrap Icons
- **Backend:** PHP (Native / Procedural with Prepared Statements)
- **Database:** MySQL / MariaDB
- **Server Environment:** XAMPP / WAMP / MAMP (PHP 8.x recommended)

---

## 📁 Project Structure

```text
campus_bookhub/
├── includes/            # Database connection, headers, footers, & auth functions
│   ├── auth.php
│   ├── db.php
│   ├── footer.php
│   └── header.php
├── student/             # Student portal pages
│   ├── book_details.php
│   ├── books.php
│   ├── dashboard.php
│   ├── my_orders.php
│   ├── process_momo_payment.php
│   ├── profile.php
│   └── verify_paystack.php
├── uploads/             # Directory for payment proofs/images
├── fix_admin.php        # Utility script
├── index.php            # Homepage / Redirects
├── login.php            # User authentication
├── logout.php           # Session termination
├── register.php         # Student registration
└── README.md            # Project documentation