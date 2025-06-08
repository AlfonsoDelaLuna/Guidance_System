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
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

$where = "WHERE 1=1";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$yearLevel = isset($_GET['yearLevel']) ? trim($_GET['yearLevel']) : '';
$program = isset($_GET['program']) ? trim($_GET['program']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

if ($search) {
    $search = $conn->real_escape_string($search);
    $where .= " AND (student_id_no LIKE '%$search%' OR last_name LIKE '%$search%' OR first_name LIKE '%$search%' OR middle_name LIKE '%$search%')";
}
if ($yearLevel) {
    $yearLevel = $conn->real_escape_string($yearLevel);
    $where .= " AND year_level = '$yearLevel'";
}
if ($program) {
    $program = urldecode($program);
    $program = $conn->real_escape_string($program);
    $where .= " AND program = '$program'";
}
if ($status) {
    $status = $conn->real_escape_string($status);
    $where .= " AND status = '$status'";
}

$sql = "SELECT * FROM college_students $where ORDER BY last_name LIMIT $start, $limit";
$result = $conn->query($sql);

$total_pages_sql = "SELECT COUNT(*) FROM college_students $where";
$total_result = $conn->query($total_pages_sql);

$total_rows = $total_result->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Dashboard</title>
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
            <h2>Welcome to College Dashboard!</h2>
            <a href="dashboard.php"
                style="padding: 10px 20px; background: var(--primary-color); color: #1a1f36; border-radius: 6px; text-decoration: none; font-weight: bold;">Back
                to Main Dashboard</a>
        </div>
        <div class="search-container">
            <input type="text" id="search-input" value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search...">
            <select id="year-level-filter">
                <option value="">Year Level</option>
                <option value="1" <?php echo $yearLevel == '1' ? 'selected' : ''; ?>>1st Year</option>
                <option value="2" <?php echo $yearLevel == '2' ? 'selected' : ''; ?>>2nd Year</option>
                <option value="3" <?php echo $yearLevel == '3' ? 'selected' : ''; ?>>3rd Year</option>
                <option value="4" <?php echo $yearLevel == '4' ? 'selected' : ''; ?>>4th Year</option>
            </select>
            <select id="program-filter">
                <option value="">Program</option>
                // Old Section
                <option value="2-yr. Information Technology (IT)" <?php echo $program == '2-yr. Information Technology (IT)' ? 'selected' : ''; ?>>2-yr. Information Technology (IT)</option>
                <option value="2-yr. Hospitality and Restaurant Services (HRS)" <?php echo $program == '2-yr. Hospitality and Restaurant Services (HRS)' ? 'selected' : ''; ?>>2-yr. Hospitality and Restaurant Services (HRS)
                </option>
                <option value="2-yr. Tourism and Events Management (TEM)" <?php echo $program == '2-yr. Tourism and Events Management (TEM)' ? 'selected' : ''; ?>>2-yr. Tourism and Events Management (TEM)</option>
                // Current Section
                <option value="BS in Information Technology (BSIT)" <?php echo $program == 'BS in Information Technology (BSIT)' ? 'selected' : ''; ?>>BS in Information Technology (BSIT)</option>
                <option value="BS in Computer Science (BSCS)" <?php echo $program == 'BS in Computer Science (BSCS)' ? 'selected' : ''; ?>>BS in Computer Science (BSCS)</option>
                <option value="BS in Tourism Management (BSTM)" <?php echo $program == 'BS in Tourism Management (BSTM)' ? 'selected' : ''; ?>>BS in Tourism Management (BSTM)</option>
                <option value="BS in Accountancy (BSA)" <?php echo $program == 'BS in Accountancy (BSA)' ? 'selected' : ''; ?>>BS in Accountancy (BSA)</option>
                <option value="BS in Business Administration (BSBA)" <?php echo $program == 'BS in Business Administration (BSBA)' ? 'selected' : ''; ?>>BS in Business Administration (BSBA)</option>
                <option value="BS in Accounting Information System (BSAIS)" <?php echo $program == 'BS in Accounting Information System (BSAIS)' ? 'selected' : ''; ?>>BS in Accounting Information System (BSAIS)
                </option>
                <option value="BA in Communication (BACOMM)" <?php echo $program == 'BA in Communication (BACOMM)' ? 'selected' : ''; ?>>BA in Communication (BACOMM)</option>
                <option value="Bachelor of Multimedia Arts (BMMA)" <?php echo $program == 'Bachelor of Multimedia Arts (BMMA)' ? 'selected' : ''; ?>>Bachelor of Multimedia Arts (BMMA)</option>
                <option value="BS in Computer Engineering (BSCpE)" <?php echo $program == 'BS in Computer Engineering (BSCpE)' ? 'selected' : ''; ?>>BS in Computer Engineering (BSCpE)</option>
                <option value="BS in Hospitality Management (BSHM)" <?php echo $program == 'BS in Hospitality Management (BSHM)' ? 'selected' : ''; ?>>BS in Hospitality Management (BSHM)</option>
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
                    <th>Year Level</th>
                    <th style="white-space: normal;">College Program</th>
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
                        if (strpos($display_student_id_no, '2000') === 0) {
                            $display_student_id_no = '0' . $display_student_id_no;
                        }
                        echo "<td><a href='student_record.php?id=" . urlencode($display_student_id_no) . "&type=college' target='_self' onclick=\"console.log('Student ID for record:', '" . urlencode($display_student_id_no) . "')\">" . $display_student_id_no . '</a></td>';
                        echo '<td>' . htmlspecialchars($row['last_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['first_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['middle_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['year_level']) . '</td>';
                        echo "<td style='white-space: normal;'>" . htmlspecialchars($row['program']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['section']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                        echo "<td style='text-align: center;'>";
                        echo "<div class='action-container'>";
                        echo "<a href='edit_student.php?id=" . $raw_student_id_no . "&type=college'><button class='edit-button'>Edit</button></a>";
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
            if ($search)
                $query_params['search'] = urlencode($search);
            if ($yearLevel)
                $query_params['yearLevel'] = urlencode($yearLevel);
            if ($program)
                $query_params['program'] = urlencode($program);
            if ($status)
                $query_params['status'] = urlencode($status);

            for ($i = 1; $i <= $total_pages; $i++) {
                $query_params['page'] = $i;
                $query_string = http_build_query($query_params, '', '&');
                $active = ($i == $page) ? "active" : "";
                echo "<a class='$active' href='college_dashboard.php?$query_string'>$i</a>";
            }
            ?>
        </div>
    </div>
    <script>
        function updateFilters() {
            const searchInput = document.getElementById('search-input').value;
            const yearLevel = document.getElementById('year-level-filter').value;
            const program = document.getElementById('program-filter').value;
            const status = document.getElementById('status-filter').value;

            let url = 'college_dashboard.php?';
            if (searchInput) url += '&search=' + encodeURIComponent(searchInput);
            if (yearLevel) url += '&yearLevel=' + encodeURIComponent(yearLevel);
            if (program) url += '&program=' + encodeURIComponent(program);
            if (status) url += '&status=' + encodeURIComponent(status);
            url += '&page=1'; // Reset to page 1 on filter change
            url = url.replace('?&', '?');

            console.log('Generated URL:', url);
            window.location.href = url;
        }

        function downloadExcel() {
            const yearLevel = document.getElementById('year-level-filter').value;
            const program = document.getElementById('program-filter').value;
            const status = document.getElementById('status-filter').value;
            const searchInput = document.getElementById('search-input').value;

            let url = `generate_excel.php?type=college`;
            if (yearLevel) url += `&yearLevel=${encodeURIComponent(yearLevel)}`;
            if (program) url += `&program=${encodeURIComponent(program)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;
            if (searchInput) url += `&search=${encodeURIComponent(searchInput)}`;

            window.location.href = url;
        }

        function deleteStudent(studentIdNo) {
            if (confirm('Are you sure you want to delete this student?')) {
                fetch('delete_student.php?student_id_no=' + studentIdNo + '&type=college', {
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
        document.getElementById('year-level-filter').addEventListener('change', updateFilters);
        document.getElementById('program-filter').addEventListener('change', updateFilters);
        document.getElementById('status-filter').addEventListener('change', updateFilters);
    </script>
</body>

</html>
<?php
$conn->close();
?>