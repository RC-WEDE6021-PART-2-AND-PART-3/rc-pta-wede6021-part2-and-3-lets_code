# README — Pastimes (WEDE6021 POE, Part 2 & 3)

**Pastimes** is a PHP + MySQL e-commerce web application for buying and selling
second-hand branded clothing.

**Students:** Mpho Nethomboni (ST10448422) · Portia Mashaba (ST0357369)
**GitHub repo:** https://github.com/RC-WEDE6021-PART-2-AND-PART-3/rc-pta-wede6021-part2-and-3-lets_code

---

## 1. Software Required

| Software | Notes |
|---|---|
| **XAMPP** (or any Apache + MySQL/MariaDB + PHP stack) | Includes Apache, MySQL, and phpMyAdmin |
| **PHP 8.0 or higher** | The code uses `str_starts_with()`, which requires PHP 8.0+. Older PHP versions will throw a fatal error. |
| **MySQL 8.x / MariaDB** | Any modern version bundled with XAMPP is fine |
| A web browser | Chrome, Edge, or Firefox |

No Composer packages, no Node.js, and no `.env` file are needed — this is plain
PHP using the `mysqli` extension (enabled by default in XAMPP).

---

## 2. How to Open and Run the Project

1. **Copy the `Pastimes` folder** into your XAMPP `htdocs` directory, so the path looks like:
   ```
   C:\xampp\htdocs\Pastimes
   ```
   (On Mac/Linux: `htdocs/Pastimes`, or your local equivalent.)

2. **Start XAMPP** and switch on **Apache** and **MySQL** in the XAMPP Control Panel.

3. **Set up the database** — see Section 3 below. This step is required before
   the site will work, because the app needs the `ClothingStore` database and
   its seed data to exist.

4. **Open the site in your browser:**
   ```
   http://localhost/Pastimes/
   ```

---

## 3. Database Setup

### Where the database files are
All database-related files live inside the project, in:
```
Pastimes/database/
├── myClothingStore.sql     — Full SQL schema export (for reference / phpMyAdmin import)
├── userData.txt            — Seed data: users (plain-text passwords, hashed on load)
├── itemsData.txt           — Seed data: items
├── photosData.txt          — Seed data: item photos
├── ordersData.txt          — Seed data: orders
├── messagesData.txt        — Seed data: messages
└── addressesData.txt       — Seed data: addresses
```
A full phpMyAdmin export is also included at the project root for reference:
`DATABASE.docx`.

### ⚠️ Recommended setup method — run the PHP loader, not the raw .sql file
The **`.sql` file's seed data uses placeholder password hashes** (e.g.
`$2y$10$examplehashAAAAAAAAAAAA`), since real bcrypt hashes can't be hand-written
into a static SQL file. If you import `myClothingStore.sql` directly, **none of
the seeded test accounts will be able to log in.**

Instead, use the included PHP setup script, which creates the database **and**
correctly hashes every seeded password with PHP's `password_hash()`:

1. With Apache and MySQL running, open in your browser:
   ```
   http://localhost/Pastimes/loadClothingStore.php
   ```
2. This script will automatically:
   - Create the `ClothingStore` database (if it doesn't already exist)
   - Drop and recreate all 7 tables (`users`, `addresses`, `items`,
     `item_photos`, `cart`, `orders`, `messages`)
   - Load all seed data from the `.txt` files above
   - **Hash every user's plain-text password** from `userData.txt` before
     inserting it, so login works immediately afterwards
3. You should see a green "✅ Database initialisation complete!" message at
   the bottom of the page.

There is also a second, smaller script, `createTable.php`, which rebuilds just
the table set and reloads `userData.txt` (used to demonstrate the specific POE
requirement of dropping/recreating a table from a PHP script). Running
`loadClothingStore.php` is sufficient on its own and is the easiest way to get
a fully working copy of the site — there's no need to run both.

### Database connection details
No setup or config file editing is required. The connection settings are
hard-coded in `Pastimes/includes/DBConn.php` to match a default, unmodified
XAMPP installation:

| Setting | Value |
|---|---|
| Host | `localhost` |
| Username | `root` |
| Password | *(empty)* |
| Database name | `ClothingStore` |

If your MySQL `root` user has a password set, you will need to edit
`DB_PASS` in `includes/DBConn.php` accordingly.

---

## 4. Test Login Credentials

These accounts are created automatically by `loadClothingStore.php` (Section 3)
and are ready to use immediately — no need to register first.

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `Admin@Pastimes1` |
| Verified seller | `Nomvula_K` | `Pastimes2024!` |
| Verified seller | `Thabo_Style` | `Thrift@2024` |
| Buyer | `Sipho_M` | `Shop@2024!` |
| Buyer | `Thandi_P` | `Buyer2024!` |

Full list of all 10 seeded accounts is visible in `database/userData.txt`
(plain-text passwords, before hashing).

- Log in as **`admin`** to access the admin dashboard at `admin.php` — this is
  where pending seller and buyer accounts can be approved/rejected.
- Log in as a **verified seller** to access `sell.php` and list new items.
- Log in as a **buyer** to browse, add to cart, check out, and message sellers.

---

## 5. Important Notes for the Marker

- **Run `loadClothingStore.php` before testing anything** — without it, the
  database tables won't exist and every page will show a connection/query
  error. This is covered in Section 3.
- **New registrations require admin approval before they can log in.**
  Registering a new account via `register.php` sets `account_status = 'pending'`;
  `login.php` will block the login with a "pending admin approval" message
  until an admin approves it from the admin dashboard (`admin.php`). This is
  intentional and demonstrates the account-approval workflow — use one of the
  pre-seeded accounts above for immediate testing, or register a new account
  and then approve it as `admin` to test the full flow end-to-end.
- **Seller status vs. account status are two separate fields.** A user's
  `account_status` (pending/approved) controls whether they can log in at all.
  Their `seller_status` (none/pending/verified) separately controls whether
  they can list items for sale. Both are managed from the admin dashboard.
- The `/uploads` folder (for item photo uploads via `sell.php`) is created
  automatically by the script if it doesn't exist — no manual setup needed.
  Two sample uploaded images are already included for testing.
- `images/generate_placeholder.php` is a one-off utility script used during
  development to generate `images/placeholder.jpg` using the GD library. The
  placeholder image already exists in `/images`, so this script does **not**
  need to be run — it's left in the project only for reference.
- All passwords are stored using PHP's `password_hash()` (bcrypt) and verified
  with `password_verify()` — never stored in plain text. All database queries
  use prepared statements (MySQLi) to prevent SQL injection.
- On successful login, the system displays a flash message in the format
  *"User [First] [Last] is logged in"*, per the POE requirement.
- All forms (login, register, sell, etc.) are "sticky" — if a submission
  fails validation, previously entered values are pre-filled so the
  user/marker doesn't need to retype the whole form.

---

## 6. Project Folder Structure

```
Pastimes/
├── index.php, login.php, register.php, logout.php
├── browse.php, item.php, cart.php, checkout.php
├── sell.php, profile.php, admin.php
├── createTable.php          — Creates/reloads tables + userData.txt (POE Part 2 requirement)
├── loadClothingStore.php    — Full database setup script (recommended — see Section 3)
├── /css/style.css
├── /js/main.js
├── /images                  — Static images, logo, placeholders
├── /uploads                 — User-uploaded item photos (auto-created)
├── /database                — SQL export + seed data .txt files (see Section 3)
└── /includes
    ├── DBConn.php            — Database connection + helper functions
    ├── header.php / footer.php
    └── image_helper.php      — Resolves item images with brand-based fallback
```
