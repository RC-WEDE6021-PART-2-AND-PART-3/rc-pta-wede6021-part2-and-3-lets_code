<?php
/**
 * sell.php
 * Sell / Upload Item Page — Pastimes
 * WEDE6021 POE
 *
 * Only accessible by VERIFIED sellers.
 * Handles item upload with:
 * - Up to 6 photos (jpg/jpeg/png/webp, max 5MB each)
 * - Item details form
 * - Sticky form on error
 * - File validation
 */

session_start();
require_once 'includes/DBConn.php';

requireLogin();

$page_title = 'Sell Your Item';
$user_id    = $_SESSION['user_id'];

// ============================================================
// SELLER ACCESS CHECK
// ============================================================
$seller_status = $_SESSION['seller_status'] ?? 'none';
$can_sell      = ($seller_status === 'verified');
$is_pending    = ($seller_status === 'pending');

// Handle "Request Seller Status" form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_seller'])) {
    if ($seller_status === 'none') {
        $updStmt = $conn->prepare("UPDATE users SET seller_status='pending', role='seller' WHERE user_id=?");
        $updStmt->bind_param('i', $user_id);
        $updStmt->execute();
        $updStmt->close();
        $_SESSION['seller_status'] = 'pending';
        $_SESSION['role']          = 'seller';
        $_SESSION['flash_message'] = 'Seller request submitted! An admin will review your application.';
        $_SESSION['flash_type']    = 'info';
        redirect('sell.php');
    }
}

// ============================================================
// STICKY FORM VALUES
// ============================================================
$sticky = [
    'item_name'   => '',
    'brand'       => '',
    'category'    => '',
    'size'        => '',
    'colour'      => '',
    'condition'   => '',
    'description' => '',
    'price'       => '',
];
$errors  = [];
$success = false;

// ============================================================
// HANDLE ITEM UPLOAD (POST)
// ============================================================
if ($can_sell && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['list_item'])) {

    // Retrieve and sanitize inputs
    $sticky['item_name']   = sanitize($_POST['item_name']   ?? '');
    $sticky['brand']       = sanitize($_POST['brand']       ?? '');
    $sticky['category']    = sanitize($_POST['category']    ?? '');
    $sticky['size']        = sanitize($_POST['size']        ?? '');
    $sticky['colour']      = sanitize($_POST['colour']      ?? '');
    $sticky['condition']   = sanitize($_POST['condition']   ?? '');
    $sticky['description'] = sanitize($_POST['description'] ?? '');
    $sticky['price']       = sanitize($_POST['price']       ?? '');

    // Validation
    if (empty($sticky['item_name']))   $errors['item_name']   = 'Item name is required.';
    if (empty($sticky['brand']))       $errors['brand']       = 'Brand is required.';
    if (empty($sticky['category']))    $errors['category']    = 'Category is required.';
    if (empty($sticky['size']))        $errors['size']        = 'Size is required.';
    if (empty($sticky['condition']))   $errors['condition']   = 'Condition is required.';
    if (empty($sticky['description'])) $errors['description'] = 'Description is required.';
    if (empty($sticky['price']) || !is_numeric($sticky['price']) || (float)$sticky['price'] <= 0) {
        $errors['price'] = 'Please enter a valid price.';
    }

    // Valid condition values
    $valid_conditions = ['excellent', 'good', 'fair'];
    if (!in_array($sticky['condition'], $valid_conditions)) {
        $errors['condition'] = 'Invalid condition selected.';
    }

    // Handle photo uploads
    $uploaded_photos = [];
    $allowed_types   = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $max_file_size   = 5 * 1024 * 1024; // 5MB
    $upload_dir      = 'uploads/';

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $file_count = count($_FILES['photos']['name']);
        for ($i = 0; $i < min($file_count, 6); $i++) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['photos']['size'][$i] > $max_file_size) {
                $errors['photos'] = 'Each photo must be under 5MB.';
                break;
            }
            $mime = mime_content_type($_FILES['photos']['tmp_name'][$i]);
            if (!in_array($mime, $allowed_types)) {
                $errors['photos'] = 'Only JPG, JPEG, PNG, and WEBP photos are allowed.';
                break;
            }
            $ext      = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
            $filename = 'item_' . $user_id . '_' . time() . '_' . $i . '.' . strtolower($ext);
            $dest     = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $dest)) {
                $uploaded_photos[] = $dest;
            }
        }
    }

    // Insert item if no errors
    if (empty($errors)) {
        $price = (float)$sticky['price'];
        $insStmt2 = $conn->prepare(
            "INSERT INTO items (seller_id, item_name, brand, category, size, colour, `condition`, description, price, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')"
        );
        $insStmt2->bind_param('isssssssd',
            $user_id, $sticky['item_name'], $sticky['brand'],
            $sticky['category'], $sticky['size'], $sticky['colour'],
            $sticky['condition'], $sticky['description'], $price);

        if ($insStmt2->execute()) {
            $new_item_id = $conn->insert_id;
            $insStmt2->close();

            // Insert photos
            foreach ($uploaded_photos as $j => $photo_path) {
                $is_primary = ($j === 0) ? 1 : 0;
                $pStmt = $conn->prepare("INSERT INTO item_photos (item_id, file_path, is_primary) VALUES (?,?,?)");
                $pStmt->bind_param('isi', $new_item_id, $photo_path, $is_primary);
                $pStmt->execute();
                $pStmt->close();
            }

            $success = true;
            // Reset sticky
            $sticky = array_fill_keys(array_keys($sticky), '');
            $_SESSION['flash_message'] = 'Your item has been listed successfully!';
            $_SESSION['flash_type']    = 'success';
            redirect("item.php?id=$new_item_id");
        } else {
            $errors['general'] = 'Failed to list item. Please try again.';
            $insStmt2->close();
        }
    }
}

$categories = ['T-Shirts','Shirts','Hoodies & Sweatshirts','Jackets','Jeans','Dresses','Skirts','Accessories','Shoes','Other'];
$sizes      = ['XS','S','M','L','XL','XXL','XXXL','6','8','10','12','14','16','One Size','28','30','32','34','36'];
$brands_list= ['Nike','Adidas',"Levi's",'Zara','H&M','Puma','Gucci','Louis Vuitton','Chanel','Woolworths','Edgars','Superdry','Tommy Hilfiger','Ralph Lauren','Calvin Klein','Under Armour','Other'];

include 'includes/header.php';
?>

<!-- Page Hero -->
<div class="page-hero" style="min-height:160px;">
    <div style="position:absolute;right:0;top:0;bottom:0;width:100%;background:linear-gradient(135deg,#0d3a6e,#082B59);opacity:0.9;">
        <img src="images/Browse & sell hero picture.png" alt="Sell Hero Image" style="width:100%;height:100%;object-fit:cover;opacity:.9;">
    </div>
    <div class="container">
        <h1>Sell Your Item</h1>
        <p>Give your pre-loved items a new home and earn extra cash.</p>
    </div>
</div>

<div style="background:var(--off-white);padding:40px 0 60px;">
    <div class="container">

        <?php if (!isLoggedIn()): ?>
        <div class="alert alert-warning" style="max-width:600px;margin:0 auto;">
            Please <a href="login.php">login</a> to list items for sale.
        </div>

        <?php elseif ($seller_status === 'none'): ?>
        <!-- Not a seller yet -->
        <div style="max-width:600px;margin:0 auto;text-align:center;background:var(--white);border-radius:var(--radius-lg);padding:50px;border:1px solid var(--mid-gray);">
            <div style="font-size:3rem;margin-bottom:16px;">🏷️</div>
            <h2 style="color:var(--navy);margin-bottom:10px;">Become a Seller</h2>
            <p style="color:var(--text-mid);margin-bottom:28px;">Request seller status to start listing your pre-loved clothing. An admin will review and approve your application.</p>
            <form method="POST">
                <button type="submit" name="request_seller" class="btn btn-primary btn-lg">Request Seller Status</button>
            </form>
        </div>

        <?php elseif ($is_pending): ?>
        <!-- Pending approval -->
        <div style="max-width:600px;margin:0 auto;text-align:center;background:var(--white);border-radius:var(--radius-lg);padding:50px;border:1px solid var(--mid-gray);">
            <div style="font-size:3rem;margin-bottom:16px;">⏳</div>
            <h2 style="color:var(--navy);margin-bottom:10px;">Application Pending</h2>
            <p style="color:var(--text-mid);margin-bottom:10px;">Your seller application is currently being reviewed by an administrator.</p>
            <p style="color:var(--text-light);font-size:.85rem;">You'll be notified once your account is approved. This usually takes 24–48 hours.</p>
        </div>

        <?php else: ?>
        <!-- Verified seller — show listing form -->
        <div class="sell-layout">

            <!-- Left Sidebar -->
            <div class="sell-sidebar">
                <div class="sell-sidebar-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M6.5 1a.5.5 0 0 0-.5.5v.5H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H9.5V1.5a.5.5 0 0 0-.5-.5h-2z"/></svg>
                </div>
                <h3>Start Selling</h3>
                <p>Join our community of sellers and help promote sustainable fashion.</p>

                <div class="sell-feature">
                    <div class="sell-feature-icon">🌿</div>
                    <div>
                        <h4>Sustainable Choice</h4>
                        <p>Reduce fashion waste by giving clothes a second life.</p>
                    </div>
                </div>
                <div class="sell-feature">
                    <div class="sell-feature-icon">✅</div>
                    <div>
                        <h4>Trusted Community</h4>
                        <p>All sellers are verified to ensure a safe experience.</p>
                    </div>
                </div>
                <div class="sell-feature">
                    <div class="sell-feature-icon">💰</div>
                    <div>
                        <h4>Earn Extra Cash</h4>
                        <p>Turn your pre-loved items into extra income.</p>
                    </div>
                </div>
            </div>

            <!-- Main Form -->
            <div class="sell-form-card">
                <h3>Item Details</h3>
                <p>Fill in the details below to list your item.</p>

                <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" novalidate>

                    <!-- Photos Upload -->
                    <div class="form-group">
                        <label>Photos <span style="color:var(--text-light);font-weight:400;">(Add up to 6 photos — first photo will be the main image)</span></label>
                        <div class="photo-upload-zone" id="photo-upload-zone">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                            <h4>Click to upload photos</h4>
                            <p>or drag and drop<br>PNG, JPG or WEBP (Max 5MB each)</p>
                        </div>
                        <input type="file" id="photo-input" name="photos[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none;">
                        <div class="photo-slots">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="photo-slot">+</div>
                            <?php endfor; ?>
                        </div>
                        <?php if (isset($errors['photos'])): ?>
                        <span class="invalid-feedback" style="display:block;"><?php echo htmlspecialchars($errors['photos']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Item Name -->
                    <div class="form-group">
                        <label for="item_name">Item Name <span class="required">*</span></label>
                        <input type="text" id="item_name" name="item_name"
                               class="form-control <?php echo isset($errors['item_name']) ? 'is-invalid' : ''; ?>"
                               placeholder="e.g. Nike Crewneck Sweatshirt"
                               value="<?php echo htmlspecialchars($sticky['item_name']); ?>" required>
                        <?php if (isset($errors['item_name'])): ?><span class="invalid-feedback"><?php echo htmlspecialchars($errors['item_name']); ?></span><?php endif; ?>
                    </div>

                    <!-- Brand -->
                    <div class="form-group">
                        <label for="brand">Brand <span class="required">*</span></label>
                        <select id="brand" name="brand" class="form-control <?php echo isset($errors['brand']) ? 'is-invalid' : ''; ?>" required>
                            <option value="">e.g. Nike</option>
                            <?php foreach ($brands_list as $b): ?>
                            <option value="<?php echo htmlspecialchars($b); ?>" <?php echo $sticky['brand'] === $b ? 'selected' : ''; ?>><?php echo htmlspecialchars($b); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['brand'])): ?><span class="invalid-feedback"><?php echo htmlspecialchars($errors['brand']); ?></span><?php endif; ?>
                    </div>

                    <!-- Category + Size -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category <span class="required">*</span></label>
                            <select id="category" name="category" class="form-control <?php echo isset($errors['category']) ? 'is-invalid' : ''; ?>" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $sticky['category'] === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category'])): ?><span class="invalid-feedback"><?php echo htmlspecialchars($errors['category']); ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="size">Size <span class="required">*</span></label>
                            <select id="size" name="size" class="form-control <?php echo isset($errors['size']) ? 'is-invalid' : ''; ?>" required>
                                <option value="">Select size</option>
                                <?php foreach ($sizes as $sz): ?>
                                <option value="<?php echo htmlspecialchars($sz); ?>" <?php echo $sticky['size'] === $sz ? 'selected' : ''; ?>><?php echo htmlspecialchars($sz); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['size'])): ?><span class="invalid-feedback"><?php echo htmlspecialchars($errors['size']); ?></span><?php endif; ?>
                        </div>
                    </div>

                    <!-- Colour + Condition -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="colour">Colour</label>
                            <select id="colour" name="colour" class="form-control">
                                <option value="">Select colour</option>
                                <?php foreach (['Black','White','Navy Blue','Grey','Red','Blue','Green','Yellow','Pink','Brown','Beige','Multi-colour','Other'] as $col): ?>
                                <option value="<?php echo $col; ?>" <?php echo $sticky['colour'] === $col ? 'selected' : ''; ?>><?php echo $col; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="condition">Condition <span class="required">*</span></label>
                            <select id="condition" name="condition" class="form-control <?php echo isset($errors['condition']) ? 'is-invalid' : ''; ?>" required>
                                <option value="">Select condition</option>
                                <option value="excellent" <?php echo $sticky['condition']==='excellent'?'selected':''; ?>>Excellent</option>
                                <option value="good"      <?php echo $sticky['condition']==='good'?'selected':''; ?>>Good</option>
                                <option value="fair"      <?php echo $sticky['condition']==='fair'?'selected':''; ?>>Fair</option>
                            </select>
                            <?php if (isset($errors['condition'])): ?><span class="invalid-feedback"><?php echo htmlspecialchars($errors['condition']); ?></span><?php endif; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>"
                                  rows="4" required maxlength="500"
                                  data-maxlength="500" data-counter="desc-count"
                                  placeholder="e.g. Worn only a few times, still in excellent condition. No marks or stains."><?php echo htmlspecialchars($sticky['description']); ?></textarea>
                        <div class="char-count"><span id="desc-count">0/500</span></div>
                        <?php if (isset($errors['description'])): ?><span class="invalid-feedback"><?php echo htmlspecialchars($errors['description']); ?></span><?php endif; ?>
                    </div>

                    <!-- Price -->
                    <div class="form-group">
                        <label for="price">Price (R) <span class="required">*</span></label>
                        <input type="number" id="price" name="price"
                               class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>"
                               placeholder="e.g. 350" min="1" step="0.01"
                               value="<?php echo htmlspecialchars($sticky['price']); ?>" required>
                        <small style="color:var(--text-light);font-size:.78rem;">Set a fair price for your item.</small>
                        <?php if (isset($errors['price'])): ?><span class="invalid-feedback"><?php echo htmlspecialchars($errors['price']); ?></span><?php endif; ?>
                    </div>

                    <!-- Tips Banner -->
                    <div class="sell-tips">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
                        <strong>Our Tips:</strong> Clear photos, accurate details and fair pricing help your item sell faster.
                    </div>

                    <div class="sell-form-actions">
                        <a href="browse.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" name="list_item" class="btn btn-primary btn-lg">Preview Listing</button>
                    </div>
                </form>
            </div>

            <!-- Right Guidelines Sidebar -->
            <div class="guidelines-card">
                <h3>Selling Guidelines</h3>
                <div class="guideline-item">
                    <div class="guideline-icon">📸</div>
                    <div><h4>Take Clear Photos</h4><p>Use good lighting and multiple angles.</p></div>
                </div>
                <div class="guideline-item">
                    <div class="guideline-icon">✅</div>
                    <div><h4>Be Honest</h4><p>Describe the condition accurately.</p></div>
                </div>
                <div class="guideline-item">
                    <div class="guideline-icon">💲</div>
                    <div><h4>Price It Right</h4><p>Check similar listings to set a competitive price.</p></div>
                </div>
                <div class="guideline-item">
                    <div class="guideline-icon">📦</div>
                    <div><h4>Ship Promptly</h4><p>Post your item within 2–3 working days.</p></div>
                </div>
                <div class="need-help-box">
                    <strong>Need Help?</strong><br>
                    Visit our <a href="#">Seller Guidelines</a> or contact support for assistance.
                    <div style="margin-top:10px;">
                        <a href="#" class="btn btn-outline btn-sm btn-block">View Seller Guidelines</a>
                    </div>
                </div>
            </div>

        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom Trust Bar -->
<section class="page-trust-bar">
    <div class="container">
        <div class="trust-feature"><div class="trust-feature-icon">🌿</div><div><h4>Eco-Friendly Choice</h4><p>Help reduce fashion waste by selling pre-loved items.</p></div></div>
        <div class="trust-feature"><div class="trust-feature-icon">✅</div><div><h4>Verified Platform</h4><p>We verify all sellers to ensure a safe community.</p></div></div>
        <div class="trust-feature"><div class="trust-feature-icon">🔒</div><div><h4>Secure Transactions</h4><p>Your payments and personal information are protected.</p></div></div>
        <div class="trust-feature"><div class="trust-feature-icon">💸</div><div><h4>Fast Payouts</h4><p>Get paid quickly and securely.</p></div></div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
