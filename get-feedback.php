<?php


require_once "../includes/config.php"; // Include config 

header("Content-Type: application/json"); // Return JSON format  JSON 



if (!isset($_GET["id"])) {

    echo json_encode([
        "success" => false,
        "error" => "No ID provided"
    ]);

    exit();
}



$id = (int)$_GET["id"];



$sql = "SELECT * FROM contacts WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);



$feedback = mysqli_fetch_assoc($result);



if ($feedback) {

    
    echo json_encode([

        "success" => true,

        "feedback" => $feedback
    ]);

} else {

    
    echo json_encode([

        "success" => false,

        "error" => "Feedback not found"
    ]);
}
?>