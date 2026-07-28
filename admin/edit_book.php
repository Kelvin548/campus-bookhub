<?php
// admin/edit_book.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$book_id = intval($_GET['id'] ?? 0);
if ($book_id <= 0) {
    header("Location: books.php");
    exit();
}

$errors = [];
$success = '';

// Fetch Book Data
$stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: books.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid session token.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $course_code = trim($_POST['course_code'] ?? '');
        $lecturer = trim($_POST['lecturer'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (empty($title) || empty($course_code) || empty($lecturer) || $price <= 0) {
            $errors[] = "Title, Course Code, Lecturer, and Price are required.";
        }

        $image_path = $book['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($file_ext, $allowed)) {
                $errors[] = "Only JPG, JPEG, PNG, and WEBP formats allowed.";
            } else {
                $new_filename = 'book_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $upload_dir = '../uploads/books/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $destination = $upload_dir . $new_filename;
                if (move_uploaded_file($file_tmp, $destination)) {
                    // Remove old image if present
                    if (!empty($book['image']) && file_exists('../' . $book['image'])) {
                        unlink('../' . $book['image']);
                    }
                    $image_path = 'uploads/books/' . $new_filename;
                } else {
                    $errors[] = "Failed to upload image.";
                }
            }
        }

        if (empty($errors)) {
            $update_stmt = $conn->prepare("UPDATE books SET title = ?, course_code = ?, lecturer = ?, price = ?, stock = ?, image = ?, description = ? WHERE book_id = ?");
            $update_stmt->bind_param("sssdissi", $title, $course_code, $lecturer, $price, $stock, $image_path, $description, $book_id);

            if ($update_stmt->execute()) {
                $success = "Book updated successfully!";
                // Refresh local book variable
                $book['title'] = $title;
                $book['course_code'] = $course_code;
                $book['lecturer'] = $lecturer;
                $book['price'] = $price;
                $book['stock'] = $stock;
                $book['image'] = $image_path;
                $book['description'] = $description;
            } else {
                $errors[] = "Failed to update record.";
            }
            $update_stmt->close();
        }
    }
}

$page_title = "Edit Book - Campus BookHub";
require_once '../includes/header.php';
?>

<!-- Google Fonts: Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --wine-main: #6b1d2f;
        --wine-dark: #2e0811;
        --wine-light: #8e2b43;
        --wine-gradient: linear-gradient(135deg, #6b1d2f 0%, #2e0811 100%);
        --wine-gradient-card: linear-gradient(145deg, #7c2237 0%, #420b17 100%);
        --sidebar-bg: #1f050b;
        --font-family: 'Plus Jakarta Sans', sans-serif;
    }

    body {
        font-family: var(--font-family);
        background-color: #f6f3f4;
        color: #2b2b2b;
        overflow-x: hidden;
    }

    #wrapper {
        display: flex;
        width: 100%;
        min-height: 100vh;
        position: relative;
    }

    /* Sidebar Styling */
    #sidebar-wrapper {
        width: 260px;
        min-width: 260px;
        background: var(--sidebar-bg) !important;
        min-height: 100vh;
        border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1050;
    }

    .sidebar-brand-box {
        background: rgba(255, 255, 255, 0.03);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sidebar-logo-img {
        max-height: 48px;
        object-fit: contain;
        background: #ffffff;
        padding: 4px 8px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }

    .sidebar-link {
        color: rgba(255, 255, 255, 0.7) !important;
        padding: 0.75rem 1rem;
        font-weight: 500;
        font-size: 0.88rem;
        border-radius: 10px !important;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        text-decoration: none;
    }

    .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
        transform: translateX(3px);
    }

    .sidebar-link.active {
        background: var(--wine-gradient-card) !important;
        color: #ffffff !important;
        box-shadow: 0 6px 16px rgba(107, 29, 47, 0.4);
        font-weight: 600;
    }

    /* Page Content Wrapper */
    #page-content-wrapper {
        flex: 1;
        width: 100%;
        min-width: 0;
    }

    /* Top Navbar */
    .top-navbar {
        background-color: #ffffff;
        border-bottom: 1px solid #eae2e4;
        height: 70px;
    }

    .text-wine { color: var(--wine-main); }

    /* Buttons & Form Controls */
    .btn-wine {
        background: var(--wine-gradient);
        color: #ffffff;
        border: none;
        transition: all 0.25s ease;
    }

    .btn-wine:hover {
        background: linear-gradient(135deg, #7c2237 0%, #3d0a17 100%);
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(107, 29, 47, 0.35);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--wine-main);
        box-shadow: 0 0 0 0.25rem rgba(107, 29, 47, 0.15);
    }

    .book-preview-thumb {
        width: 80px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #eae2e4;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    /* Mobile Drawer Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(2px);
        z-index: 1040;
    }

    /* Responsive Breakpoints */
    @media (max-width: 991.98px) {
        #sidebar-wrapper {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            margin-left: -260px;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.3);
        }

        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }

        #wrapper.toggled .sidebar-overlay {
            display: block;
        }

        .top-navbar {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        main {
            padding: 1.25rem !important;
        }
    }

    @media (min-width: 1200px) {
        main {
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }
    }
</style>

<div id="wrapper">
    <!-- Mobile Drawer Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Admin Sidebar -->
    <aside id="sidebar-wrapper">
        <!-- Logo Branding Header -->
        <div class="sidebar-brand-box px-3 py-3 text-center">
            <a href="dashboard.php" class="d-inline-block">
                <img src="../assets/images/logo.jpeg" alt="Campus BookHub Logo" class="sidebar-logo-img img-fluid">
            </a>
        </div>

        <div class="p-3">
            <small class="text-uppercase fw-bold text-muted px-2 mb-2 d-block fs-8" style="letter-spacing: 0.8px; color: rgba(255,255,255,0.4) !important;">
                Admin Control
            </small>

            <a href="dashboard.php" class="sidebar-link mb-1">
                <i class="bi bi-grid-1x2-fill me-2.5"></i> Dashboard
            </a>
            <a href="books.php" class="sidebar-link active mb-1">
                <i class="bi bi-journal-bookmark-fill me-2.5"></i> Manage Books
            </a>
            <a href="orders.php" class="sidebar-link mb-1">
                <i class="bi bi-cart-check-fill me-2.5"></i> Orders
            </a>
            <a href="payments.php" class="sidebar-link mb-1">
                <i class="bi bi-receipt-cutoff me-2.5"></i> Payments
            </a>
            <a href="reports.php" class="sidebar-link mb-1">
                <i class="bi bi-bar-chart-line-fill me-2.5"></i> Reports
            </a>

            <hr style="border-color: rgba(255,255,255,0.1); margin: 1.25rem 0;">

            <a href="../logout.php" class="sidebar-link text-danger mb-1" style="color: #ff6b81 !important;">
                <i class="bi bi-box-arrow-right me-2.5"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <div class="container-fluid p-0 d-flex align-items-center">
                <!-- Mobile Sidebar Toggle -->
                <button class="btn btn-light border me-3 d-lg-none rounded-3 p-1 px-2" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4 text-wine"></i>
                </button>

                <span class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square text-wine d-none d-sm-inline"></i>
                    <span>Edit Book Details</span>
                </span>

                <div class="d-flex align-items-center gap-3 ms-auto">
                    <div class="badge bg-white text-dark border px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2 fs-7">
                        <i class="bi bi-person-circle text-wine fs-6"></i>
                        <span class="fw-semibold d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    </div>
                </div>
            </div>
        </nav>

        <main class="p-4">
            <!-- Title Header & Navigation Button -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1 fs-4 fs-sm-3">Update Book Entry</h3>
                    <p class="text-muted fs-7 mb-0">Modify details, price, inventory stock, or manual cover image.</p>
                </div>
                <div>
                    <a href="books.php" class="btn btn-outline-secondary rounded-3 px-3 py-2 fw-semibold fs-7 d-inline-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-arrow-left fs-6"></i>
                        <span>Back to Catalog</span>
                    </a>
                </div>
            </div>

            <!-- Error Notifications -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-3 p-3 mb-4">
                    <div class="d-flex align-items-center gap-2 text-danger fw-bold mb-1">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <span>Please correct the following:</span>
                    </div>
                    <ul class="mb-0 ps-4 fs-7 text-danger">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Success Notification -->
            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-center gap-2 text-success fw-semibold fs-7">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Edit Form Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text text-wine fs-5"></i>
                    <h6 class="fw-bold mb-0 text-dark">Book Details Form</h6>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="edit_book.php?id=<?php echo $book_id; ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <!-- Title -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark fs-7">Book Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3 py-2.5" value="<?php echo htmlspecialchars($book['title']); ?>" required placeholder="e.g. Introduction to Database Systems">
                        </div>

                        <!-- Course Code & Lecturer -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark fs-7">Course Code <span class="text-danger">*</span></label>
                                <input type="text" name="course_code" class="form-control rounded-3 py-2.5" value="<?php echo htmlspecialchars($book['course_code']); ?>" required placeholder="e.g. INFT 204">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark fs-7">Lecturer / Author <span class="text-danger">*</span></label>
                                <input type="text" name="lecturer" class="form-control rounded-3 py-2.5" value="<?php echo htmlspecialchars($book['lecturer']); ?>" required placeholder="e.g. Dr. K. Mensah">
                            </div>
                        </div>

                        <!-- Price & Stock -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark fs-7">Price (GH₵) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0 fw-semibold">GH₵</span>
                                    <input type="number" step="0.01" name="price" class="form-control rounded-end-3 py-2.5 border-start-0" value="<?php echo htmlspecialchars($book['price']); ?>" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark fs-7">Stock Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="stock" class="form-control rounded-3 py-2.5" value="<?php echo htmlspecialchars($book['stock']); ?>" required placeholder="0">
                            </div>
                        </div>

                        <!-- Cover Image Upload -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark fs-7">Cover Image</label>
                            <?php if (!empty($book['image']) && file_exists('../' . $book['image'])): ?>
                                <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded-3 border w-fit-content">
                                    <img src="../<?php echo htmlspecialchars($book['image']); ?>" alt="Current Cover" class="book-preview-thumb">
                                    <div>
                                        <span class="badge bg-secondary-subtle text-secondary mb-1">Current Image</span>
                                        <small class="d-block text-muted fs-8">Upload a new file below to replace this image.</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control rounded-3 py-2" accept="image/*">
                            <small class="text-muted fs-8 mt-1 d-block">Allowed formats: JPG, JPEG, PNG, WEBP</small>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark fs-7">Description</label>
                            <textarea name="description" class="form-control rounded-3 p-3" rows="4" placeholder="Brief summary of manual contents, topics covered, or course requirements..."><?php echo htmlspecialchars($book['description']); ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" class="btn btn-wine px-4 py-2.5 rounded-3 fw-semibold fs-7 d-inline-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Update Changes</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Mobile Drawer Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const wrapper = document.getElementById('wrapper');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function (e) {
                e.preventDefault();
                wrapper.classList.toggle('toggled');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function () {
                wrapper.classList.remove('toggled');
            });
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>