<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo "You are not logged in.";
    exit();
}

// Get the logged-in company's name from the session
$companyName = $_SESSION['companyName'];

// Create a connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the company ID based on the company name
$sql = "SELECT CompanyID FROM Companies WHERE CompanyName = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $companyName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $companyID = $row['CompanyID'];
} else {
    echo "Company not found.";
    exit();
}

$stmt->close();

// Fetch groups, members, and their projects information for the company
$sql = "
SELECT 
    g.GroupName, 
    m.MemberID, 
    m.FullName AS MemberFullName, 
    p.ProjectID, 
    p.VarietyOfSeedlings, 
    p.NumberOfSeedlingsOrdered, 
    p.AmountToBePaid, 
    p.DepositPaid, 
    p.Balance, 
    p.DateOfPayment, 
    p.DateToCompletePayment
FROM 
    `Groups` g
JOIN 
    Members m ON g.GroupID = m.GroupID
JOIN 
    Projects p ON m.MemberID = p.MemberID
WHERE 
    g.CompanyID = ?";

// Check if a group name is provided in the request
if (isset($_GET['groupName']) && !empty($_GET['groupName'])) {
    $groupName = $_GET['groupName'];
    $sql .= " AND g.GroupName = ?";
}

$stmt = $conn->prepare($sql);
if (isset($groupName)) {
    $stmt->bind_param("is", $companyID, $groupName);
} else {
    $stmt->bind_param("i", $companyID);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch and output the data
$projectsData = [];
while ($row = $result->fetch_assoc()) {
    $projectsData[] = $row;
}

$stmt->close();
$conn->close();

// Output the data as JSON
header('Content-Type: application/json');
echo json_encode($projectsData);
?>

