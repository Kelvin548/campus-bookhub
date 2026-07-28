<?php
// student/books.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_student();

$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ? OR course_code LIKE ? OR lecturer LIKE ? ORDER BY book_id DESC");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $books = $stmt->get_result();
} else {
    $books = $conn->query("SELECT * FROM books ORDER BY book_id DESC");
}

$page_title = "Browse Books - Campus BookHub";
require_once '../includes/header.php';
?>

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

    /* Page Content */
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

    /* Helpers */
    .text-wine { color: var(--wine-main) !important; }
    .bg-wine { background-color: var(--wine-main) !important; color: #ffffff !important; }
    .bg-wine-subtle { background-color: rgba(107, 29, 47, 0.08) !important; color: var(--wine-main) !important; }
    .border-wine-subtle { border-color: rgba(107, 29, 47, 0.2) !important; }

    .btn-wine {
        background: var(--wine-gradient);
        color: #ffffff !important;
        border: none;
        transition: all 0.3s ease;
    }

    .btn-wine:hover, .btn-wine:focus {
        background: var(--wine-gradient-card);
        color: #ffffff !important;
        box-shadow: 0 6px 18px rgba(107, 29, 47, 0.35);
    }

    /* Catalog Cards Hover Effect */
    .book-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(107, 29, 47, 0.12) !important;
    }

    /* Mobile Overlay */
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
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
        }
    }
</style>

<div id="wrapper">
    <!-- Mobile Drawer Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Student Sidebar -->
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand-box px-3 py-3 text-center">
            <a href="dashboard.php" class="d-inline-block">
                <img src="../assets/images/logo.jpeg" alt="Campus BookHub Logo" class="sidebar-logo-img img-fluid">
            </a>
        </div>

        <div class="p-3">
            <small class="text-uppercase fw-bold px-2 mb-2 d-block fs-8" style="letter-spacing: 0.8px; color: rgba(255,255,255,0.4) !important;">
                Student Portal
            </small>

            <a href="dashboard.php" class="sidebar-link mb-1">
                <i class="bi bi-grid-1x2-fill me-2.5"></i> Dashboard
            </a>
            <a href="books.php" class="sidebar-link active mb-1">
                <i class="bi bi-journal-bookmark-fill me-2.5"></i> Browse Manuals
            </a>
            <a href="my_orders.php" class="sidebar-link mb-1">
                <i class="bi bi-bag-check-fill me-2.5"></i> My Orders
            </a>
            <a href="profile.php" class="sidebar-link mb-1">
                <i class="bi bi-person-gear me-2.5"></i> Profile Settings
            </a>

            <hr style="border-color: rgba(255,255,255,0.1); margin: 1.25rem 0;">

            <a href="../logout.php" class="sidebar-link text-danger mb-1" style="color: #ff6b81 !important;">
                <i class="bi bi-box-arrow-right me-2.5"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg top-navbar px-4">
            <div class="container-fluid p-0 d-flex align-items-center">
                <button class="btn btn-light border me-3 d-lg-none rounded-3 p-1 px-2" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-4 text-wine"></i>
                </button>

                <span class="navbar-brand fw-semibold text-muted fs-6 mb-0">Available Course Textbooks</span>

                <div class="d-flex align-items-center gap-2 ms-auto">
                    <div class="bg-wine-subtle border border-wine-subtle px-3 py-1.5 rounded-pill d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle text-wine fs-6"></i>
                        <span class="fw-semibold text-dark fs-7"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    </div>
                </div>
            </div>
        </nav>

        <main class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Course Manual Catalog</h4>
                    <p class="text-muted fs-7 mb-0">Select a course manual to view details and submit an order.</p>
                </div>
            </div>

            <!-- Search Form Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-3 p-md-4">
                    <form action="books.php" method="GET" class="row g-2">
                        <div class="col-md-10">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control form-control-lg border-start-0 fs-6 rounded-end-3" placeholder="Search by title, course code (e.g. ICT243), or lecturer..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-wine btn-lg w-100 rounded-3 fs-6 fw-semibold">
                                Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Catalog Grid -->
            <div class="row g-4">
                <?php if ($books && $books->num_rows > 0): ?>
                    <?php while ($book = $books->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white book-card">
                                <?php if (!empty($book['image']) && file_exists('../' . $book['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($book['image']); ?>" class="card-img-top" style="height: 210px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-wine-subtle card-img-top d-flex align-items-center justify-content-center" style="height: 210px;">
                                        <i class="bi bi-book display-4 text-wine"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-wine-subtle text-wine border border-wine-subtle rounded-pill px-3 py-1 fw-semibold fs-8">
                                            <?php echo htmlspecialchars($book['course_code']); ?>
                                        </span>
                                        <small class="text-muted fs-8 text-truncate ms-2" style="max-width: 120px;" title="<?php echo htmlspecialchars($book['lecturer']); ?>">
                                            <i class="bi bi-person me-1 text-wine"></i><?php echo htmlspecialchars($book['lecturer']); ?>
                                        </small>
                                    </div>

                                    <h6 class="card-title fw-bold text-dark lh-base mb-3"><?php echo htmlspecialchars($book['title']); ?></h6>
                                    
                                    <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted fs-8 d-block">Price</span>
                                            <span class="fw-bold text-wine fs-5">GH₵ <?php echo number_format($book['price'], 2); ?></span>
                                        </div>
                                        <a href="book_details.php?id=<?php echo $book['book_id']; ?>" class="btn btn-wine btn-sm px-3 py-2 rounded-3 fw-semibold">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-5 bg-white">
                            <div class="bg-wine-subtle rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-journal-x display-5 text-wine"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">No Course Manuals Found</h5>
                            <p class="text-muted fs-7 mb-0">Try adjusting your search filters or check back later.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
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