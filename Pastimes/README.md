# PASTIMES — Pre-loved Brands. New Stories.
## WEDE6021 POE — Web Application

---

## 📋 PROJECT OVERVIEW

Pastimes is a full-stack PHP + MySQL e-commerce web application for buying and selling second-hand branded clothing.

---

## 🛠 TECH STACK

| Technology | Usage |
|-----------|-------|
| PHP 8.x (OOP) | Server-side logic |
| MySQL 8.x | Database via MySQLi |
| HTML5 / CSS3 | Markup and styling |
| JavaScript | Client-side interactivity |
| XAMPP | Local development server |

---

## 📁 FOLDER STRUCTURE

```
/Pastimes
├── index.php           — Home page
├── login.php           — User login
├── register.php        — User registration
├── browse.php          — Browse clothing listings
├── item.php            — Item detail page
├── cart.php            — Shopping cart
├── checkout.php        — Checkout & order placement
├── sell.php            — Sell / upload item (verified sellers only)
├── profile.php         — User profile & order history
├── admin.php           — Admin dashboard
├── logout.php          — Session destroy
├── loadClothingStore.php — Full DB setup script
├── createTable.php     — Create/reload users table
│
├── /css
│   └── style.css       — Main stylesheet
│
├── /js
│   └── main.js         — Main JavaScript
│
├── /images
│   ├── logo.svg        — Pastimes logo
│   └── placeholder.jpg — Item image placeholder
│
├── /uploads            — Uploaded item photos
│
├── /database
│   ├── userData.txt        — User seed data
│   ├── itemsData.txt       — Items seed data
│   ├── photosData.txt      — Photos seed data
│   ├── ordersData.txt      — Orders seed data
│   ├── messagesData.txt    — Messages seed data
│   ├── addressesData.txt   — Addresses seed data
│   └── myClothingStore.sql — Full SQL export
│
└── /includes
    ├── DBConn.php      — Database connection + helper functions
    ├── header.php      — Site navigation header
    └── footer.php      — Site footer
```

---

## ⚡ QUICK SETUP (XAMPP)

### 1. Copy Project
```
Copy the /Pastimes folder to:
C:\xampp\htdocs\Pastimes
```

### 2. Start XAMPP
- Start **Apache** and **MySQL** in XAMPP Control Panel

### 3. Initialise Database
Open your browser and navigate to:
```
http://localhost/Pastimes/loadClothingStore.php
```
This will:
- Create the `ClothingStore` database
- Drop and recreate all tables
- Load all seed data from text files

### 4. Access the App
```
http://localhost/Pastimes/
```

---

## 🔑 DEFAULT LOGIN CREDENTIALS

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `Admin@Pastimes1` |
| Verified Seller | `Nomvula_K` | `Pastimes2024!` |
| Verified Seller | `Thabo_Style` | `Thrift@2024` |
| Buyer | `Sipho_M` | `Shop@2024!` |
| Buyer | `Thandi_P` | `Buyer2024!` |

---

## 🗄 DATABASE TABLES

| Table | Description |
|-------|-------------|
| `users` | All registered users (buyers, sellers, admins) |
| `addresses` | Delivery addresses per user |
| `items` | Clothing listings |
| `item_photos` | Photos per item |
| `cart` | Shopping cart items |
| `orders` | Completed orders |
| `messages` | Buyer-to-seller messages |

---

## 🔐 SECURITY FEATURES

- ✅ Passwords hashed with `password_hash()` (bcrypt)
- ✅ Login verified with `password_verify()`
- ✅ All DB queries use **prepared statements** (MySQLi)
- ✅ Input sanitised with `htmlspecialchars()` + `trim()`
- ✅ Session management with `session_regenerate_id()`
- ✅ Role-based access control (buyer / seller / admin)
- ✅ File upload validation (type, size, extension)
- ✅ SQL injection prevention via parameterised queries

---

## 📄 POE-SPECIFIC FILES

| File | Requirement |
|------|-------------|
| `includes/DBConn.php` | MySQLi connection with include |
| `createTable.php` | Drop/create users table + load userData.txt |
| `loadClothingStore.php` | Full DB initialisation script |
| `database/userData.txt` | 5+ user records (pipe-separated) |
| `database/itemsData.txt` | 5+ item records |
| `database/ordersData.txt` | 5+ order records |
| `database/messagesData.txt` | 5+ message records |
| `database/myClothingStore.sql` | Full DDL export |

---

## 👤 USER ROLES

| Role | Capabilities |
|------|-------------|
| **Buyer** | Browse, view items, add to cart, checkout, message sellers |
| **Seller** (pending) | All buyer + request seller status |
| **Seller** (verified) | All buyer + upload/manage listings |
| **Admin** | Full access + verify sellers + manage users/items |

---

## 📝 STICKY FORMS

All forms implement sticky behaviour — on validation error, previously entered values are pre-filled so users don't need to re-type.

---

## 🎨 DESIGN SYSTEM

| Token | Value |
|-------|-------|
| Primary Navy | `#082B59` |
| Primary Teal | `#0D8B8B` |
| Font | Poppins (Google Fonts) |
| Border Radius | 8px / 12px / 20px |
| Shadow | `0 2px 8px rgba(8,43,89,0.08)` |

---

## 📦 DEPLOYMENT CHECKLIST

- [ ] Copy project to htdocs
- [ ] Run `loadClothingStore.php`
- [ ] Verify admin login works
- [ ] Test buyer registration + login
- [ ] Test seller verification workflow
- [ ] Test cart + checkout flow
- [ ] Upload test item as verified seller
- [ ] Check admin dashboard stats
- [ ] Run `createTable.php` to verify POE requirement

---

*© 2024 Pastimes. WEDE6021 Portfolio of Evidence.*
