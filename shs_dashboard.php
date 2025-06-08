<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];

// Database connection
$servername = 'localhost';
$dbusername = 'root';
$password = '';
$dbname = 'guidance_db';

$conn = new mysqli($servername, $dbusername, $password, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$where = "WHERE 1=1";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$gradeLevel = isset($_GET['gradeLevel']) ? trim($_GET['gradeLevel']) : '';
$strand = isset($_GET['strand']) ? trim($_GET['strand']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

if ($search) {
    $search = $conn->real_escape_string($search);
    $where .= " AND (student_id_no LIKE '%$search%' OR last_name LIKE '%$search%' OR first_name LIKE '%$search%' OR middle_name LIKE '%$search%')";
}
if ($gradeLevel) {
    $gradeLevel = $conn->real_escape_string($gradeLevel);
    $where .= " AND grade_level = '$gradeLevel'";
}
if ($strand) {
    $strand = urldecode($strand);
    $strand = $conn->real_escape_string($strand);
    $where .= " AND shs_program = '$strand'";
}
if ($status) {
    $status = $conn->real_escape_string($status);
    $where .= " AND status = '$status'";
}

$sql = "SELECT * FROM shs_students $where ORDER BY last_name LIMIT $start, $limit";
$result = $conn->query($sql);

$total_pages_sql = "SELECT COUNT(*) FROM shs_students $where";
$total_result = $conn->query($total_pages_sql);
$total_rows = $total_result->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHS Dashboard</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="icon" href="images/sti_logo.png" type="image/png">
    <style>
        .download-excel-button {
            padding: 10px 20px;
            background: #4caf50;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .download-excel-button:hover {
            background-color: #367c39;
        }

        .edit-button {
            background-color: #4287f5;
            color: white;
            padding: 10px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }

        .delete-button {
            background-color: #f44336;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .action-container {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            transition: background-color .3s;
            border: 1px solid #ddd;
            margin: 0 4px;
        }

        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }

        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Welcome to SHS Dashboard!</h2>
            <a href="dashboard.php" style="padding: 10px 20px; background: var(--primary-color); color: #1a1f36; border-radius: 6px; text-decoration: none; font-weight: bold;">Back to Main Dashboard</a>
        </div>
        <div class="search-container">
            <input type="text" id="search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search...">
            <select id="grade-filter">
                <option value="">Grade</option>
                <option value="11" <?php echo $gradeLevel == '11' ? 'selected' : ''; ?>>11</option>
                <option value="12" <?php echo $gradeLevel == '12' ? 'selected' : ''; ?>>12</option>
            </select>
            <select id="strand-filter">
                <option value="">Strand</option>
                <option value="Accountancy, Business, and Management" <?php echo $strand == 'Accountancy, Business, and Management' ? 'selected' : ''; ?>>Accountancy, Business, and Management</option>
                <option value="Science, Technology, Engineering, and Mathematics" <?php echo $strand == 'Science, Technology, Engineering, and Mathematics' ? 'selected' : ''; ?>>Science, Technology, Engineering, and Mathematics</option>
                <option value="Humanities and Social Sciences" <?php echo $strand == 'Humanities and Social Sciences' ? 'selected' : ''; ?>>Humanities and Social Sciences</option>
                <option value="General Academics" <?php echo $strand == 'General Academics' ? 'selected' : ''; ?>>General Academics</option>
                <option value="IT with Mobile App & Web Development" <?php echo $strand == 'IT with Mobile App & Web Development' ? 'selected' : ''; ?>>IT with Mobile App & Web Development</option>
                <option value="Digital Arts" <?php echo $strand == 'Digital Arts' ? 'selected' : ''; ?>>Digital Arts</option>
                <option value="Tourism Operations" <?php echo $strand == 'Tourism Operations' ? 'selected' : ''; ?>>Tourism Operations</option>
                <option value="Culinary Arts" <?php echo $strand == 'Culinary Arts' ? 'selected' : ''; ?>>Culinary Arts</option>
                <option value="Computer and Communications Technology" <?php echo $strand == 'Computer and Communications Technology' ? 'selected' : ''; ?>>Computer and Communications Technology</option>
            </select>
            <select id="status-filter">
                <option value="">Status</option>
                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="graduated" <?php echo $status == 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                <option value="dropped" <?php echo $status == 'dropped' ? 'selected' : ''; ?>>Dropped</option>
                <option value="transferred" <?php echo $status == 'transferred' ? 'selected' : ''; ?>>Transferred</option>
            </select>
            <button onclick="downloadExcel()" class="download-excel-button">Download Excel</button>
        </div>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
        <table id="students-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Number</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Grade Level</th>
                    <th style="white-space: normal;">SHS Program</th>
                    <th>Section</th>
                    <th>Status</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $id_counter = ($page - 1) * $limit + 1;

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($id_counter) . '</td>';
                        $raw_student_id_no = htmlspecialchars($row['student_id_no']);
                        $display_student_id_no = $raw_student_id_no;
                        if (strpos($display_student_id_no, '1000') === 0 || strpos($display_student_id_no, '2000') === 0) {
                            $display_student_id_no = '0' . $display_student_id_no;
                        }
                        echo "<td><a href='student_record.php?id=" . urlencode($display_student_id_no) . "&type=shs' target='_self' onclick=\"console.log('Student ID for record:', '" . urlencode($display_student_id_no) . "')\">" . $display_student_id_no . '</a></td>';
                        echo '<td>' . htmlspecialchars($row['last_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['first_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['middle_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['grade_level']) . '</td>';
                        echo "<td style='white-space: normal;'>" . htmlspecialchars($row['shs_program']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['section']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                        echo "<td style='text-align: center;'>";
                        echo "<div class='action-container'>";
                        echo "<a href='edit_student.php?id=" . $raw_student_id_no . "&type=shs'><button class='edit-button'>Edit</button></a>";
                        echo "<button class='delete-button' onclick=\"deleteStudent('" . $raw_student_id_no . "')\">Delete</button>";
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
                        $id_counter++;
                    }
                } else {
                    echo "<tr><td colspan='11'>No data available.</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="pagination">
            <?php
            $query_params = [];
            if ($search) $query_params['search'] = urlencode($search);
            if ($gradeLevel) $query_params['gradeLevel'] = urlencode($gradeLevel);
            if ($strand) $query_params['strand'] = urlencode($strand);
            if ($status) $query_params['status'] = urlencode($status);

            for ($i = 1; $i <= $total_pages; $i++) {
                $query_params['page'] = $i;
                $query_string = http_build_query($query_params);
                $active = ($i == $page) ? "active" : "";
                echo "<a class='$active' href='shs_dashboard.php?$query_string'>$i</a>";
            }
            ?>
        </div>
    </div>
    <script>
        function updateFilters() {
            const searchInput = document.getElementById('search-input').value;
            const gradeLevel = document.getElementById('grade-filter').value;
            const strand = document.getElementById('strand-filter').value;
            const status = document.getElementById('status-filter').value;

            const params = new URLSearchParams();
            if (searchInput) params.set('search', searchInput);
            if (gradeLevel) params.set('gradeLevel', gradeLevel);
            if (strand) params.set('strand', strand);
            if (status) params.set('status', status);
            params.set('page', 1); // Reset to page 1 on filter change

            window.location.href = 'shs_dashboard.php?' + params.toString();
        }

        function downloadExcel() {
            const gradeLevel = document.getElementById('grade-filter').value;
            const strand = document.getElementById('strand-filter').value;
            const status = document.getElementById('status-filter').value;
            const searchInput = document.getElementById('search-input').value;

            let url = `generate_shs_excel.php?type=shs`;
            if (gradeLevel) url += `&gradeLevel=${encodeURIComponent(gradeLevel)}`;
            if (strand) url += `&strand=${encodeURIComponent(strand)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;
            if (searchInput) url += `&search=${encodeURIComponent(searchInput)}`;

            window.location.href = url;
        }

        function deleteStudent(studentIdNo) {
            if (confirm('Are you sure you want to delete this student?')) {
                fetch('delete_student.php?student_id_no=' + studentIdNo + '&type=shs', {
                        method: 'GET',
                    })
                    .then(response => {
                        if (response.ok) {
                            alert('Student deleted successfully.');
                            location.reload();
                        } else {
                            alert('Failed to delete student.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the student.');
                    });
            }
        }

        // Event listeners
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                updateFilters(); // Search only on Enter
            }
        });
        document.getElementById('grade-filter').addEventListener('change', updateFilters);
        document.getElementById('strand-filter').addEventListener('change', updateFilters);
        document.getElementById('status-filter').addEventListener('change', updateFilters);
    </script>
</body>

</html>
<?php
$conn->close();
?>
